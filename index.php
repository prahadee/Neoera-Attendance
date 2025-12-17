<?php
// index.php - Login form combined with PHP error handling
require_once "login.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Portal Login - NeoEra Infotech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./asset/css/login.css">

</head>
<body>

    <div class="login-wrapper">
        <header>
            <div class="logo-area">
                <span>NE</span>
            </div>
            <h1>NeoEra Portal</h1>
            <p>Enter your credentials to access</p>
        </header>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm">
            
            <div class="input-group">
                <label for="role">Select Role</label>
                <div class="input-wrapper select-wrapper">
                    <select id="role" name="role" required>
                        <option value="" disabled selected>Choose Access Level</option>
                        <option value="employee" <?php if(isset($role) && $role == 'employee') echo 'selected'; ?>>Employee</option>
                        <option value="admin" <?php if(isset($role) && $role == 'admin') echo 'selected'; ?>>Administrator</option>
                    </select>
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
            
            <div class="input-group">
                <label for="username">Username / ID</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="e.g. user@neoera.com" required 
                           value="<?php echo htmlspecialchars($username); ?>">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <span>Secure Login</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>

            <?php 
            if (!empty($login_err)) {
                echo '<div class="error-message" id="formError">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>' . $login_err . '</span>
                      </div>';
            }
            ?>
        </form>

        <footer>
            <a href="forgot_password.php">Forgot your password?</a>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>