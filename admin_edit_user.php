<?php
// admin_edit_user.php
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

$errors = [];
$success = '';

// Load existing data
$sql = "SELECT u.username, u.role, ed.full_name, ed.employee_id, ed.department, ed.job_title, ed.work_email
        FROM users u
        JOIN employee_details ed ON u.id = ed.user_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$current = $result->fetch_assoc();
$stmt->close();

if (!$current) {
    header("location: admin_manage_users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $role        = $_POST['role'] ?? 'employee';
    $full_name_f = trim($_POST['full_name'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $job_title   = trim($_POST['job_title'] ?? '');
    $work_email  = trim($_POST['work_email'] ?? '');

    if ($username === '' || $full_name_f === '' || $employee_id === '' || $department === '' || $job_title === '' || $work_email === '') {
        $errors[] = "All fields marked * are required.";
    }
    if (!in_array($role, ['admin','employee'], true)) {
        $errors[] = "Invalid role selected.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $stmt1->bind_param("ssi", $username, $role, $id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("UPDATE employee_details 
                                     SET full_name = ?, employee_id = ?, department = ?, job_title = ?, work_email = ?
                                     WHERE user_id = ?");
            $stmt2->bind_param("sssssi", $full_name_f, $employee_id, $department, $job_title, $work_email, $id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            $success = "User updated successfully.";
            // Refresh current data for display
            $current['username']    = $username;
            $current['role']        = $role;
            $current['full_name']   = $full_name_f;
            $current['employee_id'] = $employee_id;
            $current['department']  = $department;
            $current['job_title']   = $job_title;
            $current['work_email']  = $work_email;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error updating user. Please try again.";
        }
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
    <title>Edit User | NeoEra Admin</title>
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
            padding: 8px;
            min-width: 44px;
            min-height: 44px;
            border-radius: 8px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px 30px;
            margin-top: 20px;
        }
        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: var(--input-bg);
            outline: none;
            color: var(--text-color);
            transition: 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            background: #ffffff;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
        }

        .btn-row {
            margin-top: 25px;
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
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar" aria-hidden="false" role="navigation">
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
                <h1>Edit User</h1>
                <p>Update login and profile details.</p>
            </div>
            <button id="mobileToggle" class="mobile-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
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

            <form method="post" action="">
                <div class="form-section-title"><i class="fa-solid fa-lock"></i> Login Credentials</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required
                               value="<?php echo htmlspecialchars($current['username']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="employee" <?php echo ($current['role'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="admin" <?php echo ($current['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:25px;"><i class="fa-solid fa-id-badge"></i> Employee Details</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required
                               value="<?php echo htmlspecialchars($current['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Employee ID *</label>
                        <input type="text" name="employee_id" required
                               value="<?php echo htmlspecialchars($current['employee_id']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <input type="text" name="department" required
                               value="<?php echo htmlspecialchars($current['department']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Job Title *</label>
                        <input type="text" name="job_title" required
                               value="<?php echo htmlspecialchars($current['job_title']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Work Email *</label>
                        <input type="email" name="work_email" required
                               value="<?php echo htmlspecialchars($current['work_email']); ?>">
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='admin_manage_users.php'">
                        <i class="fa-solid fa-arrow-left"></i> Back to Users
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
    (function(){
      const sidebar = document.getElementById('sidebar');
      const mobileToggle = document.getElementById('mobileToggle');
      let lastFocused = null;

      // initialize aria state based on viewport
      if (sidebar) {
        const hidden = window.innerWidth > 768 ? 'false' : 'true';
        sidebar.setAttribute('aria-hidden', hidden);
      }
      if (mobileToggle) {
        mobileToggle.setAttribute('aria-expanded', window.innerWidth > 768 ? 'true' : 'false');
      }

      function openSidebar(){
        if(!sidebar) return;
        sidebar.classList.add('active');
        sidebar.setAttribute('aria-hidden','false');
        if(mobileToggle) mobileToggle.setAttribute('aria-expanded','true');
        lastFocused = document.activeElement;
        const first = sidebar.querySelector('.nav-links a, button, [href]');
        if(first) first.focus();
        document.addEventListener('keydown', onKeyDown);
      }

      function closeSidebar(){
        if(!sidebar) return;
        sidebar.classList.remove('active');
        sidebar.setAttribute('aria-hidden','true');
        if(mobileToggle) mobileToggle.setAttribute('aria-expanded','false');
        if(lastFocused && lastFocused.focus) lastFocused.focus();
        document.removeEventListener('keydown', onKeyDown);
      }

      window.toggleSidebar = function(){ if(sidebar && sidebar.classList.contains('active')) closeSidebar(); else openSidebar(); };

      if(mobileToggle){
        mobileToggle.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); window.toggleSidebar(); } });
        mobileToggle.addEventListener('click', function(){ window.toggleSidebar(); });
      }

      function onKeyDown(e){ if(e.key==='Escape'){ closeSidebar(); } }

      document.querySelectorAll('.nav-links a').forEach(a => a.addEventListener('click', ()=>{ if(window.innerWidth<=768) closeSidebar(); }));

      window.addEventListener('resize', ()=>{
        if(!sidebar) return;
        if(window.innerWidth>768){ sidebar.setAttribute('aria-hidden','false'); if(mobileToggle) mobileToggle.setAttribute('aria-expanded','true'); sidebar.classList.remove('active'); }
        else { sidebar.setAttribute('aria-hidden', sidebar.classList.contains('active') ? 'false' : 'true'); if(mobileToggle) mobileToggle.setAttribute('aria-expanded', sidebar.classList.contains('active') ? 'true' : 'false'); }
      });
    })();
    </script>
</body>
</html>
