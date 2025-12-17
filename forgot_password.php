<?php
// forgot_password.php - Handles the submission of a password reset request.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "db_config.php";

$message = "";
$error_type = "";
$username_or_email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username_or_email']);

    if (empty($username_or_email)) {
        $message = "Please enter your Username or Work Email.";
        $error_type = "error";
    } else {
        // Search the employee_details table using the provided input (username or email)
        $sql = "SELECT u.id, ed.work_email, u.username 
                FROM users u
                JOIN employee_details ed ON u.id = ed.user_id
                WHERE u.username = ? OR ed.work_email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $work_email = $user['work_email'];
                
                // --- GENERATE TOKEN ---
                // Generate a unique, secure token (base64_encode(random_bytes(32)))
                $token = bin2hex(random_bytes(32));
                $expiry_time = date('Y-m-d H:i:s', time() + (3600 * 1)); // 1 hour expiry
                
                // --- SAVE TOKEN TO DB ---
                $sql_update = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("ssi", $token, $expiry_time, $user_id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

                // --- SIMULATED EMAIL ---
                // In a live application, you would use a mail library (PHPMailer) here.
                $reset_link = "http://localhost/Neoera_Portal/reset_password.php?token=" . $token;
                
                // IMPORTANT: We only tell the user that the process started, NOT if the email exists.
                $message = "If an account exists for this email/username, a password reset link has been sent to your work email ($work_email). Check your inbox.";
                $error_type = "success";
                
                // LOGGING (Good for debugging)
                // file_put_contents('reset_log.txt', "Reset link generated for User ID $user_id: $reset_link\n", FILE_APPEND);


            } else {
                // IMPORTANT SECURITY PRACTICE: Give a generic message even if the user wasn't found.
                $message = "If an account exists for this email/username, a password reset link has been sent to your work email. Check your inbox.";
                $error_type = "success";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NeoEra Infotech</title>
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
            <h1>Forgot Password</h1>
            <p>Enter your username or work email to receive a reset link.</p>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="system-message <?php echo $error_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="input-group">
                <label for="username_or_email">Username or Work Email</label>
                <input type="text" id="username_or_email" name="username_or_email" required 
                       value="<?php echo htmlspecialchars($username_or_email); ?>">
            </div>
            
            <button type="submit" class="login-btn">
                Send Reset Link
            </button>
            
            <a href="index.php" class="back-to-login">← Back to Login</a>
        </form>
    </div>
</body>
</html>