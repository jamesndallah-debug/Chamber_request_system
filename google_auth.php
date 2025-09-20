<?php
require_once __DIR__ . '/function.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Handle demo mode for testing
if (isset($input['demo']) && $input['demo'] === true) {
    $email = $input['email'] ?? 'demo@chamber.co.tz';
    $name = $input['name'] ?? 'Demo User';
    $action = $input['action'] ?? 'login';
    $google_id = 'demo_' . time();
    
    // Check if demo user exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user'] = $user;
        echo json_encode(['success' => true, 'message' => 'Login successful', 'user_role' => (int)$user['role_id']]);
    } else {
        // Create new user for registration
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, fullname, department, role_id, google_id) VALUES (?, ?, ?, ?, ?, ?)');
        
        if ($stmt->execute([$email, $password, $name, 'General', 1, $google_id])) {
            $newId = (int)$pdo->lastInsertId();
            $fetch = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
            $fetch->execute([$newId]);
            $user_data = $fetch->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user'] = $user_data;
            
            echo json_encode(['success' => true, 'message' => 'Account created successfully!', 'user_role' => (int)$user_data['role_id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create account']);
        }
    }
    exit;
}

if (!isset($input['credential'])) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit;
}

$action = $input['action'] ?? 'login'; // Default to login, but can be 'register'

try {
    // Decode the JWT token from Google
    $credential = $input['credential'];
    $parts = explode('.', $credential);
    
    if (count($parts) !== 3) {
        throw new Exception('Invalid JWT format');
    }
    
    // Decode the payload (second part)
    $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    
    if (!$payload) {
        throw new Exception('Invalid JWT payload');
    }
    
    // Extract user information
    $email = $payload['email'] ?? '';
    $name = $payload['name'] ?? '';
    $google_id = $payload['sub'] ?? '';
    
    if (empty($email) || empty($google_id)) {
        throw new Exception('Missing required user information');
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR google_id = ?');
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update Google ID if not set
        if (empty($user['google_id'])) {
            $updateStmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE user_id = ?');
            $updateStmt->execute([$google_id, $user['user_id']]);
            $user['google_id'] = $google_id;
        }
        
        // Login existing user
        $_SESSION['user'] = $user;
        
        if ($action === 'register') {
            echo json_encode(['success' => true, 'message' => 'Account already exists. Logged in successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        }
    } else {
        // Create new user (works for both login and register actions)
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT); // Random password
        
        // Default department based on action
        $defaultDept = ($action === 'register') ? 'General' : 'General';
        
        $stmt = $pdo->prepare('INSERT INTO users (username, password, fullname, department, role_id, google_id) VALUES (?, ?, ?, ?, ?, ?)');
        
        if ($stmt->execute([$email, $password, $name, $defaultDept, 1, $google_id])) {
            $newId = (int)$pdo->lastInsertId();
            $fetch = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
            $fetch->execute([$newId]);
            $_SESSION['user'] = $fetch->fetch(PDO::FETCH_ASSOC);
            
            if ($action === 'register') {
                echo json_encode(['success' => true, 'message' => 'Account created successfully! Welcome to Chamber Request System.']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Account created and login successful']);
            }
        } else {
            throw new Exception('Failed to create user account');
        }
    }
    
} catch (Exception $e) {
    error_log('Google Auth Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()]);
}
?>
