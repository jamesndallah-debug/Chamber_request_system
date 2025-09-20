<?php
// error.php - Simple standalone error page

// No dependencies or session handling

// Get error message if available
$error_message = isset($_GET['message']) ? $_GET['message'] : 'An unexpected error occurred.';
$error_code = isset($_GET['code']) ? $_GET['code'] : '500';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Chamber Request System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo htmlspecialchars($error_code); ?></div>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <a href="index.php?action=login" class="back-button">Return to Login</a>
    </div>
</body>
</html>