<?php
// FILE: Voucher.php
// Model for handling all database operations related to vouchers.

// Include voucher functions if not already included
if (!function_exists('get_ed_user_id')) {
    require_once __DIR__ . '/voucher_functions.php';
}

class VoucherModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new voucher
     */
    public function create_voucher($data) {
        $sql = "INSERT INTO vouchers (request_id, voucher_type, pv_no, date, activity, 
                payee_name, budget_code, particulars, amount, total, amount_words, prepared_by, 
                finance_status, ed_status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['request_id'],
            $data['voucher_type'],
            $data['pv_no'],
            $data['date'],
            $data['activity'],
            $data['payee_name'],
            $data['budget_code'],
            $data['particulars'],
            $data['amount'],
            $data['total'],
            $data['amount_words'] ?? '',
            $data['prepared_by'],
            'pending', // Finance creates voucher, needs FM approval first
            'pending', // Default ED status
        ]);
        
        if ($result) {
            $voucher_id = $this->pdo->lastInsertId();
            
            // Note: Finance creates vouchers with 'pending' status
            // Finance can approve their own vouchers immediately after creation
            // Once approved by Finance, it goes to ED for final approval
            
            return $voucher_id;
        }
        
        return false;
    }

    /**
     * Get all vouchers
     */
    public function get_all_vouchers() {
        $sql = "SELECT v.*, r.title as request_title, u.fullname as prepared_by_name 
                FROM vouchers v 
                LEFT JOIN requests r ON v.request_id = r.request_id 
                JOIN users u ON v.prepared_by = u.user_id 
                ORDER BY v.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get vouchers for a specific user role
     */
    public function get_vouchers_for_role($role_id, $user_id) {
        $sql = "SELECT v.voucher_id, v.*, 
                COALESCE(r.title, 'Standalone Voucher') as request_title, 
                r.request_type, u.fullname as prepared_by_name, 
                r.user_id as requester_id, 
                (SELECT COUNT(*) FROM voucher_messages WHERE voucher_id = v.voucher_id AND recipient_id = ? AND is_read = 0) as unread_messages
                FROM vouchers v 
                LEFT JOIN requests r ON v.request_id = r.request_id 
                JOIN users u ON v.prepared_by = u.user_id 
                WHERE 1=1 ";
        $params = [$user_id]; // For unread messages count
        
        switch ($role_id) {
            case 5: // Finance
                $sql .= "AND v.prepared_by = ?";
                $params[] = $user_id;
                break;
            case 4: // ED
                // ED sees all vouchers, prioritizing pending ones
                $sql .= " ORDER BY v.ed_status = 'pending' DESC, v.finance_status = 'approved' DESC, v.created_at DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            case 7: // Admin
                // Admin can see all vouchers
                break;
            default:
                // Other roles don't see vouchers
                return [];
        }
        
        $sql .= " ORDER BY v.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    /**
     * Get vouchers for a specific request
     */
    public function get_vouchers_by_request_id($request_id) {
        $sql = "SELECT v.*, u.fullname as prepared_by_name 
                FROM vouchers v 
                JOIN users u ON v.prepared_by = u.user_id 
                WHERE v.request_id = ? 
                ORDER BY v.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$request_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a specific voucher by ID
     */
    public function get_voucher_by_id($voucher_id) {
        $sql = "SELECT v.*, r.title as request_title, r.request_type, u.fullname as prepared_by_name 
                FROM vouchers v 
                LEFT JOIN requests r ON v.request_id = r.request_id 
                JOIN users u ON v.prepared_by = u.user_id 
                WHERE v.voucher_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucher_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update voucher status by ED
     * 
     * ED can approve or reject vouchers created by Finance
     * If ED rejects, they can add a remark explaining why
     */
    public function update_ed_status($voucher_id, $status, $remark) {
        $sql = "UPDATE vouchers SET 
                ed_status = ?, 
                ed_remark = ?, 
                ed_approved_at = CURRENT_TIMESTAMP 
                WHERE voucher_id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$status, $remark, $voucher_id]);
            
            // If ED rejects, create a notification for Finance
            if ($result && $status == 'rejected' && !empty($remark)) {
                // Get the voucher to find the Finance user who prepared it
                $voucher = $this->get_voucher_by_id($voucher_id);
                if ($voucher) {
                    // Create a message from ED to Finance about the rejection
                    $ed_user_id = get_ed_user_id($this->pdo);
                    $this->add_voucher_message($voucher_id, $ed_user_id, $voucher['prepared_by'], "Voucher rejected: " . $remark);
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating ED status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update voucher status by Finance
     * 
     * Note: Finance creates vouchers after approving financial requests,
     * so vouchers are created with finance_status = 'approved'
     * This method is used if finance needs to update the status later
     */
    public function update_finance_status($voucher_id, $status, $remark) {
        $sql = "UPDATE vouchers SET 
                finance_status = ?, 
                finance_remark = ?, 
                finance_approved_at = CURRENT_TIMESTAMP 
                WHERE voucher_id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$status, $remark, $voucher_id]);
            
            // If FM approves voucher, notify ED for final approval
            if ($result && $status == 'approved') {
                // Get voucher details
                $voucher = $this->get_voucher_by_id($voucher_id);
                if ($voucher) {
                    // Notify ED about the voucher that needs approval
                    $ed_user_id = get_ed_user_id($this->pdo);
                    if ($ed_user_id) {
                        $title = ($voucher['voucher_type'] == 'petty_cash' ? 'Petty Cash' : 'Payment') . ' Voucher Ready for Final Approval';
                        $message = 'Voucher (PV No: ' . $voucher['pv_no'] . ') has been approved by Finance and is now ready for your final approval.';
                        create_voucher_notification($this->pdo, $ed_user_id, $title, $message, $voucher_id);
                        
                        // Add a message from Finance to ED
                        $this->add_voucher_message(
                            $voucher_id,
                            $voucher['prepared_by'], // Use the original creator (Finance user)
                            $ed_user_id,
                            'This voucher has been reviewed and approved by Finance. It is now ready for your final approval.'
                        );
                    }
                }
            }
            
            // If finance rejects a voucher, automatically set ED status to pending
            if ($result && $status == 'rejected') {
                $this->update_ed_status($voucher_id, 'pending', null);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating Finance status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a message between Finance and ED regarding a voucher
     */
    public function add_voucher_message($voucher_id, $sender_id, $recipient_id, $message) {
        $sql = "INSERT INTO voucher_messages (voucher_id, sender_id, recipient_id, message) 
                VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$voucher_id, $sender_id, $recipient_id, $message]);
        } catch (PDOException $e) {
            error_log("Error adding voucher message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a user can communicate about vouchers (ED and Finance only)
     */
    public function can_communicate_about_voucher($user_role_id, $voucher_id) {
        // Only ED (role_id = 4) and Finance (role_id = 5) can communicate about vouchers
        return in_array($user_role_id, [4, 5]);
    }
    
    /**
     * Get valid recipient for voucher communication based on sender role
     */
    public function get_voucher_communication_recipient($sender_role_id) {
        if ($sender_role_id == 4) { // ED
            return get_finance_manager_id($this->pdo); // Send to Finance Manager
        } elseif ($sender_role_id == 5) { // Finance
            return get_ed_user_id($this->pdo); // Send to ED
        }
        return null;
    }

    /**
     * Get messages for a specific voucher
     */
    public function get_voucher_messages($voucher_id) {
        $sql = "SELECT vm.*, 
                sender.fullname as sender_name, 
                recipient.fullname as recipient_name 
                FROM voucher_messages vm 
                JOIN users sender ON vm.sender_id = sender.user_id 
                JOIN users recipient ON vm.recipient_id = recipient.user_id 
                WHERE vm.voucher_id = ? 
                ORDER BY vm.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucher_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark voucher messages as read
     */
    public function mark_messages_as_read($voucher_id, $user_id) {
        $sql = "UPDATE voucher_messages 
                SET is_read = 1 
                WHERE voucher_id = ? AND recipient_id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$voucher_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error marking messages as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread message count for a user
     */
    public function get_unread_message_count($user_id) {
        $sql = "SELECT COUNT(*) FROM voucher_messages 
                WHERE recipient_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Check if a request has any vouchers
     */
    public function has_vouchers($request_id) {
        $sql = "SELECT COUNT(*) FROM vouchers WHERE request_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$request_id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a request is financial (eligible for vouchers)
     */
    public function is_financial_request($request_type) {
        // Define which request types are considered financial
        $financial_types = ['Imprest request', 'Reimbursement request', 'Salary advance', 'TCCIA retirement request'];
        
        return in_array($request_type, $financial_types);
    }
    
    /**
     * Check if a voucher exists for a specific request
     */
    public function voucher_exists_for_request($request_id) {
        return $this->has_vouchers($request_id);
    }
    
    /**
     * Check if a voucher has unread messages for a user
     */
    public function has_unread_messages($voucher_id, $user_id) {
        $sql = "SELECT COUNT(*) FROM voucher_messages 
                WHERE voucher_id = ? AND recipient_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voucher_id, $user_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Count unread messages for a user
     */
    public function count_unread_messages($user_id) {
        $sql = "SELECT COUNT(*) FROM voucher_messages 
                WHERE recipient_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
    
    
    /**
     * Check if a voucher exists for a specific request
     */
    public function has_voucher_for_request($request_id) {
        return $this->has_vouchers($request_id);
    }
    
    /**
     * Get a voucher for a specific request
     */
    public function get_voucher_by_request_id($request_id) {
        $sql = "SELECT v.*, u.fullname as prepared_by_name 
                FROM vouchers v 
                JOIN users u ON v.prepared_by = u.user_id 
                WHERE v.request_id = ? 
                ORDER BY v.created_at DESC 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$request_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}