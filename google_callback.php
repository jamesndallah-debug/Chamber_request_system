<?php
// Google OAuth callback handler for popup-based authentication
require_once __DIR__ . '/function.php';

if (!isset($_GET['code'])) {
    echo '<script>window.close();</script>';
    exit;
}

$code = $_GET['code'];

// Exchange authorization code for access token
$client_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
$client_secret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '';
$redirect_uri = rtrim(defined('BASE_URL') ? BASE_URL : ('http://' . $_SERVER['HTTP_HOST'] . '/chamber_request_system'), '/') . '/google_callback.php';

$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirect_uri
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$token_response = curl_exec($ch);
$token_info = json_decode($token_response, true);
curl_close($ch);

if (!isset($token_info['access_token'])) {
    echo '<script>alert("Authentication failed"); window.close();</script>';
    exit;
}

// Get user info from Google
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_info['access_token'];
$user_response = file_get_contents($user_info_url);
$user_info = json_decode($user_response, true);

if (!$user_info || !isset($user_info['email'])) {
    echo '<script>alert("Failed to get user information"); window.close();</script>';
    exit;
}

try {
    $email = $user_info['email'];
    $name = $user_info['name'] ?? '';
    $google_id = $user_info['id'] ?? '';
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR google_id = ?');
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update Google ID if not set
        if (empty($user['google_id'])) {
            $updateStmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE user_id = ?');
            $updateStmt->execute([$google_id, $user['user_id']]);
        }
        $_SESSION['user'] = $user;
    } else {
        // Create new user
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, fullname, department, role_id, google_id) VALUES (?, ?, ?, ?, ?, ?)');
        
        if ($stmt->execute([$email, $password, $name, 'General', 1, $google_id])) {
            $newId = (int)$pdo->lastInsertId();
            $fetch = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
            $fetch->execute([$newId]);
            $_SESSION['user'] = $fetch->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    echo '<script>window.close();</script>';
    
} catch (Exception $e) {
    echo '<script>alert("Authentication error: ' . addslashes($e->getMessage()) . '"); window.close();</script>';
}
?>
