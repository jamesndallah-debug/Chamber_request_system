<?php
// FILE: function.php
// Central file for shared functions and database connection logic.

require_once __DIR__ . '/config.php';

// Connect to the database using PDO.
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set PDO to throw exceptions on errors.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Use emulated prepared statements to support older versions of MySQL, but it's not ideal for security.
    // For better security, ensure your MySQL driver supports real prepared statements and set this to false.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure requests.details_json column exists to store dynamic form data
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM requests LIKE 'details_json'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        // Use TEXT for broad MySQL compatibility
        $pdo->exec("ALTER TABLE requests ADD COLUMN details_json TEXT NULL AFTER attachment_path");
    }
} catch (Throwable $e) {
    // Soft-fail; feature will be disabled if column missing
}

// Ensure requests.title column exists (app expects it)
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM requests LIKE 'title'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE requests ADD COLUMN title VARCHAR(150) NULL AFTER request_type");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure users.active column exists to support activation/deactivation
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure users.last_login column exists
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER active");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure users.deleted_at column exists for soft delete
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL AFTER last_login");
    }
} catch (Throwable $e) { /* ignore */ }

// Helper: update last_login timestamp
function set_last_login(PDO $pdo, int $user_id): void {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } catch (Throwable $e) {
        error_log('set_last_login failed: ' . $e->getMessage());
    }
}

// Ensure users.profile_image column exists for avatars
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER department");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure users.google_id column exists for Google authentication
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    if ($colCheck && $colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER profile_image");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure roles table exists with proper data
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        role_id INT NOT NULL AUTO_INCREMENT,
        role_name VARCHAR(50) NOT NULL,
        PRIMARY KEY (role_id),
        UNIQUE KEY role_name (role_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insert default roles if they don't exist
    $roleCheck = $pdo->query("SELECT COUNT(*) FROM roles");
    if ($roleCheck && $roleCheck->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO roles (role_id, role_name) VALUES
            (1, 'Employee'),
            (2, 'HRM'),
            (3, 'HOD'),
            (4, 'ED'),
            (5, 'Finance'),
            (6, 'Internal Auditor'),
            (7, 'Admin')");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure request_statuses table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_statuses (
        status_id INT NOT NULL AUTO_INCREMENT,
        status_name VARCHAR(50) NOT NULL,
        PRIMARY KEY (status_id),
        UNIQUE KEY status_name (status_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insert default statuses if they don't exist
    $statusCheck = $pdo->query("SELECT COUNT(*) FROM request_statuses");
    if ($statusCheck && $statusCheck->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO request_statuses (status_id, status_name) VALUES
            (1, 'Pending'),
            (2, 'Approved'),
            (3, 'Rejected')");
    }
} catch (Throwable $e) { /* ignore */ }

// Ensure requests table exists with all required columns
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS requests (
        request_id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        request_type VARCHAR(100) NOT NULL,
        title VARCHAR(150) NULL,
        description TEXT NOT NULL,
        amount DECIMAL(10,2) NULL,
        attachment_path VARCHAR(255) NULL,
        details_json TEXT NULL,
        status_id INT NOT NULL DEFAULT 1,
        hod_status VARCHAR(20) NULL,
        hod_remark TEXT NULL,
        hod_approved_at TIMESTAMP NULL,
        hrm_status VARCHAR(20) NULL,
        hrm_remark TEXT NULL,
        hrm_approved_at TIMESTAMP NULL,
        auditor_status VARCHAR(20) NULL,
        auditor_remark TEXT NULL,
        auditor_approved_at TIMESTAMP NULL,
        finance_status VARCHAR(20) NULL,
        finance_remark TEXT NULL,
        finance_approved_at TIMESTAMP NULL,
        ed_status VARCHAR(20) NULL,
        ed_remark TEXT NULL,
        ed_approved_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (request_id),
        KEY user_id (user_id),
        KEY status_id (status_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) { /* ignore */ }

// Ensure leave_balance_logs table exists for audit trail
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_balance_logs (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        leave_type VARCHAR(64) NOT NULL,
        year INT NOT NULL,
        change_days INT NOT NULL,
        balance_after INT DEFAULT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        request_id INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) { /* ignore */ }

// Ensure notifications table exists for user alerts
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        request_id INT DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) { /* ignore */ }

// Function to get the current logged-in user from the session.
function current_user() {
    return $_SESSION['user'] ?? null;
}

// Escapes output for HTML to prevent XSS.
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Simple email validation
function is_valid_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Send an email using PHP's mail() function. Falls back gracefully if not configured.
function send_email(string $to, string $subject, string $message, array $headersExtra = []): bool {
    if (!is_valid_email($to)) {
        return false;
    }
    $fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'no-reply@localhost';
    $fromName  = getenv('MAIL_FROM_NAME') ?: 'Chamber Request System';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $fromEmail);
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    foreach ($headersExtra as $h) {
        $headers[] = $h;
    }

    // Use native mail(). On some local dev setups (e.g., Windows), this may need SMTP configured in php.ini.
    // We fail soft here to avoid blocking registration.
    try {
        return @mail($to, $subject, $message, implode("\r\n", $headers));
    } catch (Throwable $e) {
        error_log('send_email failed: ' . $e->getMessage());
        return false;
    }
}

// Check if a user can approve a request.
function can_approve($user_role_id, $request) {
    // Admin can always approve.
    if ((int)$user_role_id === 7) {
        return true;
    }

    $type = (string)($request['request_type'] ?? '');
    $hod = strtolower((string)($request['hod_status'] ?? ''));
    $hrm = strtolower((string)($request['hrm_status'] ?? ''));
    $aud = strtolower((string)($request['auditor_status'] ?? ''));
    $fin = strtolower((string)($request['finance_status'] ?? ''));
    $ed  = strtolower((string)($request['ed_status'] ?? ''));

    // Special routing: Salary advance => HRM -> Finance -> ED only
    if ($type === 'Salary advance') {
        switch ((int)$user_role_id) {
            case 3: // HOD never involved
            case 6: // Auditor never involved
                return false;
            case 2: // HRM first
                return $hrm === 'pending';
            case 5: // Finance after HRM
                return $fin === 'pending' && $hrm === 'approved';
            case 4: // ED after Finance
                return $ed === 'pending' && $fin === 'approved';
            default:
                return false;
        }
    }

    // Special routing: TCCIA retirement request => Finance -> ED only
    if ($type === 'TCCIA retirement request') {
        switch ((int)$user_role_id) {
            case 5: // Finance first
                return $fin === 'pending';
            case 4: // ED after Finance
                return $ed === 'pending' && $fin === 'approved';
            default:
                return false;
        }
    }

    // Default workflow: HOD -> HRM -> Internal Auditor -> Finance -> ED
    switch ((int)$user_role_id) {
        case 3: // HOD
            return $hod === 'pending';
        case 2: // HRM
            return $hrm === 'pending' && $hod === 'approved';
        case 6: // Internal Auditor
            return $aud === 'pending' && $hrm === 'approved';
        case 5: // Finance
            return $fin === 'pending' && $aud === 'approved';
        case 4: // ED
            return $ed === 'pending' && $fin === 'approved';
        default:
            return false;
    }
}

// Ensure yearly leave caps exist for a given user
function ensure_leave_caps(PDO $pdo, int $user_id, int $year): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS leave_balances (
            user_id INT NOT NULL,
            leave_type VARCHAR(64) NOT NULL,
            year INT NOT NULL,
            balance_days INT NOT NULL,
            last_reset_date DATE DEFAULT NULL,
            PRIMARY KEY (user_id, leave_type, year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Check if we need to reset annual leave balance after a year
        check_annual_leave_reset($pdo, $user_id, $year);
        
        $caps = [
            'Annual leave' => 28,
            'Compassionate leave' => 7,
            'Paternity leave' => 3,
            'Maternity leave' => 84,
            'Sick leave' => 0,
        ];
        foreach ($caps as $type => $cap) {
            $stmt = $pdo->prepare("SELECT balance_days FROM leave_balances WHERE user_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$user_id, $type, $year]);
            if ($stmt->fetchColumn() === false) {
                $ins = $pdo->prepare("INSERT INTO leave_balances (user_id, leave_type, year, balance_days, last_reset_date) VALUES (?, ?, ?, ?, CURDATE())");
                $ins->execute([$user_id, $type, $year, $cap]);
            }
        }
    } catch (Throwable $e) {
        error_log('ensure_leave_caps failed: ' . $e->getMessage());
    }
}

// Check and reset all leave balances when they exceed limits or after time periods
function check_annual_leave_reset(PDO $pdo, int $user_id, int $year): void {
    try {
        // Check all leave types and their reset conditions
        check_all_leave_balances_reset($pdo, $user_id, $year);
    } catch (Throwable $e) {
        error_log('check_annual_leave_reset failed: ' . $e->getMessage());
    }
}

// Comprehensive function to check and reset all leave types
function check_all_leave_balances_reset(PDO $pdo, int $user_id, int $year): void {
    try {
        // Define leave types with their maximum allowed balances and reset conditions
        $leave_types = [
            'Annual leave' => ['max_days' => 28, 'reset_period_days' => 365],
            'Compassionate leave' => ['max_days' => 7, 'reset_period_days' => 365],
            'Paternity leave' => ['max_days' => 3, 'reset_period_days' => 365],
            'Maternity leave' => ['max_days' => 84, 'reset_period_days' => 365],
            'Sick leave' => ['max_days' => 0, 'reset_period_days' => null] // Sick leave doesn't reset
        ];
        
        foreach ($leave_types as $leave_type => $config) {
            // Skip sick leave as it doesn't have a reset mechanism
            if ($leave_type === 'Sick leave') {
                continue;
            }
            
            $stmt = $pdo->prepare("SELECT balance_days, last_reset_date FROM leave_balances WHERE user_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$user_id, $leave_type, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $current_balance = $result['balance_days'];
                $last_reset = $result['last_reset_date'];
                $current_date = date('Y-m-d');
                $max_days = $config['max_days'];
                $reset_period = $config['reset_period_days'];
                
                $should_reset = false;
                $reset_reason = '';
                
                // Check if balance exceeds maximum allowed (reset to max)
                if ($current_balance > $max_days) {
                    $should_reset = true;
                    $reset_reason = "Balance exceeded maximum allowed ({$max_days} days)";
                }
                // Check if reset period has passed
                elseif ($last_reset && $reset_period && (strtotime($current_date) - strtotime($last_reset)) >= ($reset_period * 24 * 60 * 60)) {
                    $should_reset = true;
                    $reset_reason = "Automatic reset after {$reset_period} days";
                }
                
                if ($should_reset) {
                    // Reset balance to maximum allowed
                    $update_stmt = $pdo->prepare("UPDATE leave_balances SET balance_days = ?, last_reset_date = CURDATE() WHERE user_id = ? AND leave_type = ? AND year = ?");
                    $update_stmt->execute([$max_days, $user_id, $leave_type, $year]);
                    
                    // Log the reset
                    try {
                        $log_stmt = $pdo->prepare("INSERT INTO leave_balance_logs (user_id, leave_type, year, change_days, balance_after, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $reset_change = $max_days - $current_balance;
                        $log_stmt->execute([$user_id, $leave_type, $year, $reset_change, $max_days, $reset_reason]);
                    } catch (PDOException $e) {
                        error_log("Failed to log {$leave_type} reset: " . $e->getMessage());
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log('check_all_leave_balances_reset failed: ' . $e->getMessage());
    }
}

// Reset any leave type balance for a specific user (can be called manually by admin)
function reset_user_leave_balance(PDO $pdo, int $user_id, string $leave_type, int $year = null): bool {
    try {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        // Define maximum days for each leave type
        $leave_caps = [
            'Annual leave' => 28,
            'Compassionate leave' => 7,
            'Paternity leave' => 3,
            'Maternity leave' => 84,
            'Sick leave' => 0
        ];
        
        if (!isset($leave_caps[$leave_type])) {
            error_log("Invalid leave type: $leave_type");
            return false;
        }
        
        $max_days = $leave_caps[$leave_type];
        
        // Update the balance to maximum allowed and set reset date
        $stmt = $pdo->prepare("UPDATE leave_balances SET balance_days = ?, last_reset_date = CURDATE() WHERE user_id = ? AND leave_type = ? AND year = ?");
        $result = $stmt->execute([$max_days, $user_id, $leave_type, $year]);
        
        if ($stmt->rowCount() > 0) {
            // Log the manual reset
            try {
                $log_stmt = $pdo->prepare("INSERT INTO leave_balance_logs (user_id, leave_type, year, change_days, balance_after, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $log_stmt->execute([$user_id, $leave_type, $year, $max_days, $max_days, "Manual {$leave_type} reset by admin"]);
            } catch (PDOException $e) {
                error_log("Failed to log manual {$leave_type} reset: " . $e->getMessage());
            }
            return true;
        }
        
        return false;
    } catch (Throwable $e) {
        error_log("reset_user_leave_balance failed: " . $e->getMessage());
        return false;
    }
}

// Legacy function for backward compatibility
function reset_user_annual_leave(PDO $pdo, int $user_id, int $year = null): bool {
    return reset_user_leave_balance($pdo, $user_id, 'Annual leave', $year);
}

// Format currency with proper formatting and currency symbol
function format_currency($amount, $currency = 'TShs') {
    if (!is_numeric($amount)) {
        return $currency . ' 0.00';
    }
    return $currency . ' ' . number_format($amount, 2);
}

// Get user name by user ID
function get_user_name($user_id) {
    global $pdo;
    
    if (!$user_id) {
        return 'Unknown';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['full_name'] : 'Unknown';
    } catch (PDOException $e) {
        error_log('get_user_name failed: ' . $e->getMessage());
        return 'Unknown';
    }
}