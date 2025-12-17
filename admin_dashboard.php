<?php
// admin_dashboard.php - Secured and dynamic content page
require_once "auth_check.php";

// Check for correct role access
if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));

// ---------------- KPI QUERIES ---------------- //

// Total employees (non-admin users who have employee_details)
$total_employees = 0;
$sql = "SELECT COUNT(*) AS total 
        FROM users u 
        JOIN employee_details ed ON u.id = ed.user_id
        WHERE u.role = 'employee'";
if ($res = $conn->query($sql)) {
    $row = $res->fetch_assoc();
    $total_employees = (int)$row['total'];
}

// Pending leave requests
$pending_leave = 0;
$sql = "SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Pending'";
if ($res = $conn->query($sql)) {
    $row = $res->fetch_assoc();
    $pending_leave = (int)$row['total'];
}

// New users this month (based on joining_date)
$new_users = 0;
$sql = "SELECT COUNT(*) AS total 
        FROM employee_details 
        WHERE joining_date IS NOT NULL 
          AND MONTH(joining_date) = MONTH(CURDATE())
          AND YEAR(joining_date) = YEAR(CURDATE())";
if ($res = $conn->query($sql)) {
    $row = $res->fetch_assoc();
    $new_users = (int)$row['total'];
}

// Active check-ins today (checked in today, not checked out yet)
$active_checkins = 0;
$sql = "SELECT COUNT(*) AS total 
        FROM time_logs 
        WHERE DATE(check_in_time) = CURDATE() 
          AND check_out_time IS NULL";
if ($res = $conn->query($sql)) {
    $row = $res->fetch_assoc();
    $active_checkins = (int)$row['total'];
}

// Recent leave requests (latest 5)
$recent_leaves = [];
$sql = "SELECT ed.full_name, lr.leave_type, lr.total_days
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        JOIN employee_details ed ON ed.user_id = u.id
        ORDER BY lr.requested_on DESC
        LIMIT 5";
if ($res = $conn->query($sql)) {
    if ($res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $recent_leaves[] = $r;
        }
    }
}

// You can close after all queries
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Panel | NeoEra Infotech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME VARIABLES (MATCHING DASHBOARD) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --text-color: #2d3748;
            --text-muted: #718096;
            --sidebar-width: 260px;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #ff7675;
            --info-color: #0984e3;
            --input-bg: rgba(255, 255, 255, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
        }

        /* --- SIDEBAR --- */
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
            background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%); /* Different color for Admin */
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
        }

        .logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--text-color);
        }

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

        /* --- MAIN CONTENT --- */
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

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-color);
        }
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

        /* --- ADMIN WIDGETS --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .stat-content h3 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; color: var(--text-color); }
        .stat-content p { color: var(--text-muted); font-size: 0.9rem; margin: 0; }
        
        .stat-icon-bg {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .bg-blue { color: #0984e3; }
        .bg-red { color: #d63031; }
        .bg-green { color: #00b894; }
        .bg-orange { color: #e17055; }

        /* --- MANAGEMENT SECTIONS --- */
        .management-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            height: 100%;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h3 { font-size: 1.1rem; color: var(--text-color); margin: 0; }
        
        .view-all-btn {
            font-size: 0.85rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        /* Table */
        .styled-table {
            width: 100%;
            border-collapse: collapse;
        }
        .styled-table th { text-align: left; padding: 10px; color: #667eea; font-weight: 600; font-size: 0.9rem; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .styled-table td { padding: 12px 10px; color: var(--text-color); border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 0.9rem; }
        .styled-table tr:last-child td { border-bottom: none; }

        .action-btn {
            padding: 5px 10px;
            border: 1px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .action-btn:hover { background: #667eea; color: white; }

        /* System Status List */
        .status-list { list-style: none; padding: 0; }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.9rem;
        }
        .status-item:last-child { border-bottom: none; }
        
        .status-indicator { font-weight: 600; }
        .status-good { color: var(--success-color); }
        .status-warn { color: var(--warning-color); }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
            .management-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        /* Small screen: table -> stacked cards */
        @media (max-width: 600px) {
            .styled-table,
            .styled-table thead,
            .styled-table tbody,
            .styled-table th,
            .styled-table td,
            .styled-table tr { display: block; width: 100%; }
            .styled-table thead { display: none; }
            .styled-table tr { margin-bottom: 16px; background: var(--glass-bg); border-radius: 12px; padding: 12px; box-shadow: var(--glass-shadow); }
            .styled-table td { padding: 8px 12px; border-bottom: none; position: relative; }
            .styled-table td::before { content: attr(data-label); display: block; font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; }
            .action-btn { min-width: 44px; padding: 10px; }
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
                <li class="active"><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li><a href="admin_manage_users.php"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
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
                <h1>Admin Control Center</h1>
                <p>Welcome back, Admin.</p>
            </div>
            <button id="mobileToggle" class="mobile-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $total_employees; ?></h3>
                    <p>Total Employees</p>
                </div>
                <i class="fa-solid fa-users stat-icon-bg bg-blue"></i>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3 style="color: var(--danger-color);"><?php echo $pending_leave; ?></h3>
                    <p>Pending Leaves</p>
                </div>
                <i class="fa-solid fa-file-circle-exclamation stat-icon-bg bg-red"></i>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $new_users; ?></h3>
                    <p>New Users (Month)</p>
                </div>
                <i class="fa-solid fa-user-plus stat-icon-bg bg-green"></i>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h3><?php echo $active_checkins; ?></h3>
                    <p>Active Today</p>
                </div>
                <i class="fa-solid fa-building-user stat-icon-bg bg-orange"></i>
            </div>
        </div>

        <div class="management-grid">
            
            <div class="glass-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-bell"></i> Recent Leave Requests</h3>
                    <a href="admin_leave_requests.php" class="view-all-btn">View All &rarr;</a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_leaves)): ?>
                                <?php foreach ($recent_leaves as $leave): 
                                    $initials = strtoupper(substr($leave['full_name'], 0, 2));
                                ?>
                                    <tr>
                                        <td data-label="Employee">
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:30px; height:30px; background:#667eea; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">
                                                    <?php echo htmlspecialchars($initials); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($leave['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Type"><?php echo htmlspecialchars(ucfirst($leave['leave_type'])); ?></td>
                                        <td data-label="Duration"><?php echo htmlspecialchars($leave['total_days']) . ' days'; ?></td>
                                        <td data-label="Action"><button class="action-btn" onclick="window.location.href='admin_leave_requests.php'">Review</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">
                                        No leave requests found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-server"></i> System Health</h3>
                </div>
                <ul class="status-list">
                    <li class="status-item">
                        <span>Database Connection</span>
                        <span class="status-indicator status-good"><i class="fa-solid fa-check"></i> Online</span>
                    </li>
                    <li class="status-item">
                        <span>Server Load</span>
                        <span class="status-indicator status-warn"><i class="fa-solid fa-triangle-exclamation"></i> 45% (High)</span>
                    </li>
                    <li class="status-item">
                        <span>Auth Service</span>
                        <span class="status-indicator status-good"><i class="fa-solid fa-check"></i> Operational</span>
                    </li>
                    <li class="status-item">
                        <span>Last Backup</span>
                        <span style="color: var(--text-muted);">1 hour ago</span>
                    </li>
                </ul>
                <button class="btn-primary" onclick="window.location.href='admin_manage_users.php'">
                    <i class="fa-solid fa-user-plus"></i> Add / Manage Users
                </button>
            </div>
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
