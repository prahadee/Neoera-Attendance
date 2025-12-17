<?php
// admin_reset_password.php
require_once "auth_check.php";

if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("location: admin_manage_users.php");
    exit;
}

// Get basic user info for display
$stmt = $conn->prepare("SELECT u.username, ed.full_name FROM users u JOIN employee_details ed ON u.id = ed.user_id WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res   = $stmt->get_result();
$user  = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    header("location: admin_manage_users.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($password === '' || $password2 === '') {
        $errors[] = "Both password fields are required.";
    } elseif ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);
        $stmt->execute();
        $stmt->close();
        $success = "Password reset successfully.";
    }
}

// Avatar initials of logged-in admin
$first_two_letters = strtoupper(substr($full_name ?? 'AD', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | NeoEra Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --text-color: #2d3748;
            --text-muted: #718096;
            --sidebar-width: 260px;
            --success-color: #00b894;
            --danger-color: #ff7675;
            --input-bg: rgba(255, 255, 255, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border-right: var(--glass-border);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: 0.3s ease;
            box-shadow: var(--glass-shadow);
        }
        .logo-area {
            padding: 30px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
        }
        .logo-text { font-size: 1.2rem; font-weight: 600; letter-spacing: 0.5px; color: var(--text-color); }
        .nav-links { padding: 20px 0; flex: 1; }
        .nav-links ul { list-style: none; }
        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: var(--text-muted);
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        .nav-links li a:hover, .nav-links li.active a {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border-left: 3px solid #667eea;
        }
        .nav-links i { width: 20px; text-align: center; }

        .user-profile-mini {
            padding: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.5);
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: 0.3s ease;
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title h1 { font-size: 1.8rem; font-weight: 600; color: var(--text-color); }
        .page-title p { color: var(--text-muted); }
        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            max-width: 600px;
        }

        .user-summary {
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: var(--text-muted);
        }
        .user-summary span {
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        .form-group input {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: var(--input-bg);
            outline: none;
            color: var(--text-color);
            transition: 0.3s;
        }
        .form-group input:focus {
            background: #ffffff;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
        }

        .btn-row {
            margin-top: 10px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-primary {
            padding: 12px 20px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .btn-secondary {
            padding: 12px 20px;
            background: rgba(226, 232, 240, 0.9);
            color: var(--text-color);
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-secondary:hover { background: #e2e8f0; }

        .alert-success {
            background: rgba(0, 184, 148, 0.1);
            border-left: 4px solid var(--success-color);
            padding: 10px 15px;
            border-radius: 10px;
            color: var(--success-color);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .alert-error {
            background: rgba(255, 118, 117, 0.08);
            border-left: 4px solid var(--danger-color);
            padding: 10px 15px;
            border-radius: 10px;
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .glass-card { max-width: 100%; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-icon">AD</div>
            <div class="logo-text">Admin Panel</div>
        </div>

        <nav class="nav-links">
            <ul>
                <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li class="active"><a href="admin_manage_users.php"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="admin_time_logs.php"><i class="fa-solid fa-clock-rotate-left"></i> Time Logs</a></li>
                <li><a href="admin_leave_requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li><a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
                <li><a href="logout.php" style="color: var(--danger-color);"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="user-profile-mini">
            <div class="avatar"><?php echo $first_two_letters; ?></div>
            <div class="user-info-mini">
                <h4 style="font-size: 0.9rem; color: var(--text-color);"><?php echo htmlspecialchars($full_name); ?></h4>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Administrator</span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="page-title">
                <h1>Reset Password</h1>
                <p>Set a new password for this user.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="glass-card">
            <?php foreach ($errors as $err): ?>
                <div class="alert-error"><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="user-summary">
                Resetting password for
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                (username: <span><?php echo htmlspecialchars($user['username']); ?></span>).
            </div>

            <form method="post" action="">
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="password_confirm" required>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-key"></i> Reset Password
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='admin_manage_users.php'">
                        <i class="fa-solid fa-arrow-left"></i> Back to Users
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
