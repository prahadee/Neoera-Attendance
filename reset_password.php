<?php
// reset_password.php - Allows the user to set a new password using a valid token.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "db_config.php";

$message = "";
$error_type = "";
$token_valid = false;
$token = "";
$user_id = null;

// 1. Check for Token in URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Check if token is valid and not expired
    $sql = "SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $token_valid = true;
        } else {
            $message = "Invalid or expired password reset token.";
            $error_type = "error";
        }
        $stmt->close();
    }
} else {
    $message = "Access denied. No reset token provided.";
    $error_type = "error";
}

// 2. Handle New Password Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $posted_user_id = (int)$_POST['user_id']; // Hidden field security check

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please enter both fields.";
        $error_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $error_type = "error";
    } elseif ($posted_user_id !== $user_id) {
        $message = "Security check failed. Please retry the process.";
        $error_type = "error";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear token/expiry fields
        $sql_update = "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?";
        
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt_update->execute()) {
                $message = "Your password has been reset successfully. You can now log in.";
                $error_type = "success";
                $token_valid = false; // Disable the form after success
            } else {
                $message = "Error updating password.";
                $error_type = "error";
            }
            $stmt_update->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NeoEra Infotech</title>
    <link rel="stylesheet" href="css/dashboard_style.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .system-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .system-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .system-message { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .back-to-login { display: block; margin-top: 15px; text-align: center; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <header>
            <h1>Reset Password</h1>
            <p>Set a new password for your account.</p>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="system-message <?php echo $error_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="login-btn">
                    Set New Password
                </button>
            </form>
        <?php endif; ?>
        
        <a href="index.php" class="back-to-login">← Back to Login</a>
    </div>
</body>
</html>