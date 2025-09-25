<?php
// FILE: Request.php
// Model for handling all database operations related to requests.

class RequestModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create_request($data) {
        $sql = "INSERT INTO requests (user_id, request_type, title, description, amount, attachment_path, details_json) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['user_id'],
                $data['request_type'],
                $data['title'],
                $data['description'],
                $data['amount'],
                $data['attachment_path'],
                $data['details_json'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error creating request: " . $e->getMessage());
            return false;
        }
    }

    public function get_requests_by_user_id($user_id) {
        $sql = "SELECT r.*, rs.status_name
                FROM requests r
                JOIN request_statuses rs ON r.status_id = rs.status_id
                WHERE r.user_id = ? ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function get_requests_for_role($role_id, $user_data) {
        $sql = "SELECT r.*, rs.status_name, u.fullname AS employee_fullname, u.department
                FROM requests r
                JOIN request_statuses rs ON r.status_id = rs.status_id
                JOIN users u ON r.user_id = u.user_id
                WHERE 1=1 ";
        $params = [];

        // Helper snippets for special-type visibility
        $not_salary_or_retirement = " AND r.request_type NOT IN ('Salary advance','TCCIA retirement request')";
        $not_retirement_only = " AND r.request_type <> 'TCCIA retirement request'";

        switch ($role_id) {
            case 1: // Employee - only own requests
                $sql .= "AND r.user_id = ?";
                $params[] = $user_data['user_id'];
                break;

            case 3: // HOD - must NOT see Salary advance or TCCIA retirement; show items pending HOD in their department
                $sql .= "AND u.department = ? AND r.hod_status = 'pending'" . $not_salary_or_retirement;
                $params[] = $user_data['department'];
                break;

            case 2: // HRM - must NOT see TCCIA retirement; show items pending HRM with prior HOD approved (where applicable)
                $sql .= "AND r.hrm_status = 'pending'" . $not_retirement_only . " AND (
                            r.hod_status = 'approved' OR r.hod_status IS NULL OR r.hod_status = ''
                        )";
                break;

            case 6: // Internal Auditor - must NOT see Salary advance or TCCIA retirement; show items pending Auditor with HRM approved
                $sql .= "AND r.auditor_status = 'pending' AND r.hrm_status = 'approved'" . $not_salary_or_retirement;
                break;

            case 5: // Finance - show items pending Finance. For Salary advance and TCCIA retirement, they are allowed regardless of Auditor.
                $sql .= "AND r.finance_status = 'pending' AND (
                            r.request_type IN ('Salary advance','TCCIA retirement request')
                            OR r.auditor_status = 'approved'
                        )";
                break;

            case 4: // ED - show items pending ED with Finance approved
                $sql .= "AND r.ed_status = 'pending' AND r.finance_status = 'approved'";
                break;

            case 7: // Admin - can see everything
                // no extra filters
                break;

            default:
                // No requests for this role.
                return [];
        }

        $sql .= " ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_my_requests($user_id) {
        $sql = "SELECT r.*, rs.status_name, u.fullname AS employee_fullname, u.department
                FROM requests r
                JOIN request_statuses rs ON r.status_id = rs.status_id
                JOIN users u ON r.user_id = u.user_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_request_by_id($request_id) {
        $sql = "SELECT r.*, u.fullname, u.department, rs.status_name
                FROM requests r
                JOIN users u ON r.user_id = u.user_id
                JOIN request_statuses rs ON r.status_id = rs.status_id
                WHERE r.request_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$request_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update_status($request_id, $user_role_id, $status, $remark) {
        $status_field = '';
        $remark_field = '';
        $date_field = '';
        $next_status_id = 1; // Default to Pending

        switch ($status) {
            case 'approved':
                $next_status_id = 2;
                break;
            case 'rejected':
                $next_status_id = 3;
                break;
        }

        switch ($user_role_id) {
            case 3:
                $status_field = 'hod_status';
                $remark_field = 'hod_remark';
                $date_field = 'hod_approved_at';
                break;
            case 2:
                $status_field = 'hrm_status';
                $remark_field = 'hrm_remark';
                $date_field = 'hrm_approved_at';
                break;
            case 6:
                $status_field = 'auditor_status';
                $remark_field = 'auditor_remark';
                $date_field = 'auditor_approved_at';
                break;
            case 5:
                $status_field = 'finance_status';
                $remark_field = 'finance_remark';
                $date_field = 'finance_approved_at';
                break;
            case 4:
                $status_field = 'ed_status';
                $remark_field = 'ed_remark';
                $date_field = 'ed_approved_at';
                break;
            default:
                return false;
        }

        $this->pdo->beginTransaction();
        
        try {
            // Update the specific role's status and remark.
            $sql = "UPDATE requests SET {$status_field} = ?, {$remark_field} = ?, {$date_field} = NOW() WHERE request_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status, $remark, $request_id]);

            // If the request is rejected at any stage, update the main status to Rejected.
            if ($status === 'rejected') {
                $sql_main_status = "UPDATE requests SET status_id = ? WHERE request_id = ?";
                $stmt_main_status = $this->pdo->prepare($sql_main_status);
                $stmt_main_status->execute([3, $request_id]);
            }
            // If the last approver (ED) approves, update status and deduct Annual leave if applicable.
            elseif ($user_role_id == 4 && $status === 'approved') {
                $sql_main_status = "UPDATE requests SET status_id = ? WHERE request_id = ?";
                $stmt_main_status = $this->pdo->prepare($sql_main_status);
                $stmt_main_status->execute([2, $request_id]);

                // Deduct Annual leave balance if this is an Annual leave request
                try {
                    $stmtReq = $this->pdo->prepare("SELECT user_id, request_type, details_json FROM requests WHERE request_id = ?");
                    $stmtReq->execute([$request_id]);
                    $req = $stmtReq->fetch(PDO::FETCH_ASSOC);
                    if ($req && isset($req['request_type']) && $req['request_type'] === 'Annual leave') {
                        $uid = (int)$req['user_id'];
                        $year = (int)date('Y');
                        $daysApplied = 0;
                        if (!empty($req['details_json'])) {
                            $json = json_decode($req['details_json'], true);
                            if (is_array($json)) {
                                $daysApplied = (int)($json['days_applied'] ?? 0);
                            }
                        }
                        if ($daysApplied > 0) {
                            // Ensure row exists, then deduct with floor at zero
                            $this->pdo->exec("CREATE TABLE IF NOT EXISTS leave_balances (
                                user_id INT NOT NULL,
                                leave_type VARCHAR(64) NOT NULL,
                                year INT NOT NULL,
                                balance_days INT NOT NULL,
                                PRIMARY KEY (user_id, leave_type, year)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                            // Initialize if missing
                            $init = $this->pdo->prepare("INSERT IGNORE INTO leave_balances (user_id, leave_type, year, balance_days) VALUES (?, 'Annual leave', ?, 28)");
                            $init->execute([$uid, $year]);
                            // Deduct
                            $deduct = $this->pdo->prepare("UPDATE leave_balances SET balance_days = GREATEST(balance_days - ?, 0) WHERE user_id = ? AND leave_type = 'Annual leave' AND year = ?");
                            $deduct->execute([$daysApplied, $uid, $year]);
                            // Log the deduction with resulting balance
                            try {
                                $stmtBal = $this->pdo->prepare("SELECT balance_days FROM leave_balances WHERE user_id = ? AND leave_type = 'Annual leave' AND year = ?");
                                $stmtBal->execute([$uid, $year]);
                                $after = (int)$stmtBal->fetchColumn();
                                $log = $this->pdo->prepare("INSERT INTO leave_balance_logs (user_id, leave_type, year, change_days, balance_after, reason, request_id) VALUES (?, 'Annual leave', ?, ?, ?, 'ED approval deduction', ?)");
                                $log->execute([$uid, $year, -$daysApplied, $after, $request_id]);
                            } catch (PDOException $e) {
                                error_log('Failed to log leave deduction: ' . $e->getMessage());
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log('Failed to deduct annual leave: ' . $e->getMessage());
                }

                // Create notification for status change
                $this->create_status_notification($request_id, $user_role_id, $status, $remark);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating request status: " . $e->getMessage());
            return false;
        }
    }
    
    private function create_status_notification($request_id, $user_role_id, $status, $remark) {
        try {
            $stmtReq = $this->pdo->prepare("SELECT user_id, request_type, title FROM requests WHERE request_id = ?");
            $stmtReq->execute([$request_id]);
            $req = $stmtReq->fetch(PDO::FETCH_ASSOC);
            
            if (!$req) return;
            
            $empId = (int)$req['user_id'];
            $roleNames = [
                3 => 'HOD',
                2 => 'HRM',
                6 => 'Internal Auditor',
                5 => 'Finance',
                4 => 'ED'
            ];
            
            $roleName = $roleNames[$user_role_id] ?? 'Manager';
            $ntTitle = $status === 'approved' ? 'Request Approved' : ($status === 'rejected' ? 'Request Rejected' : 'Request Updated');
            $ntMsg = 'Your request (' . ($req['title'] ?? $req['request_type']) . ') has been ' . $status . ' by ' . $roleName . '.';
            
            if (!empty($remark)) {
                $ntMsg .= ' Remark: ' . $remark;
            }
            
            $nt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, request_id) VALUES (?, ?, ?, ?)");
            $nt->execute([$empId, $ntTitle, $ntMsg, $request_id]);
        } catch (PDOException $e) {
            error_log('Failed to create notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Get filtered requests with pagination
     */
    public function get_requests_filtered($filters) {
        $sql = "SELECT r.*, rs.status_name, u.fullname AS employee_fullname, u.department
                FROM requests r
                JOIN request_statuses rs ON r.status_id = rs.status_id
                JOIN users u ON r.user_id = u.user_id
                WHERE 1=1 ";
        $params = [];
        
        if (!empty($filters['q'])) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        
        if (!empty($filters['request_type'])) {
            $sql .= " AND r.request_type = ?";
            $params[] = $filters['request_type'];
        }
        
        if (!empty($filters['department'])) {
            $sql .= " AND u.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['from'])) {
            $sql .= " AND DATE(r.created_at) >= ?";
            $params[] = $filters['from'];
        }
        
        if (!empty($filters['to'])) {
            $sql .= " AND DATE(r.created_at) <= ?";
            $params[] = $filters['to'];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)$filters['offset'];
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting filtered requests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get count of filtered requests
     */
    public function get_requests_filtered_count($filters) {
        $sql = "SELECT COUNT(*) 
                FROM requests r
                JOIN request_statuses rs ON r.status_id = rs.status_id
                JOIN users u ON r.user_id = u.user_id
                WHERE 1=1 ";
        $params = [];
        
        if (!empty($filters['q'])) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        
        if (!empty($filters['request_type'])) {
            $sql .= " AND r.request_type = ?";
            $params[] = $filters['request_type'];
        }
        
        if (!empty($filters['department'])) {
            $sql .= " AND u.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['from'])) {
            $sql .= " AND DATE(r.created_at) >= ?";
            $params[] = $filters['from'];
        }
        
        if (!empty($filters['to'])) {
            $sql .= " AND DATE(r.created_at) <= ?";
            $params[] = $filters['to'];
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting filtered requests count: " . $e->getMessage());
            return 0;
        }
    }
}
