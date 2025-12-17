<?php
// admin_announcements.php - Secured page for Admin role, handles announcement submission
require_once "auth_check.php";

// 1. Ensure only ADMINS access this page
if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$submission_message = "";
$error_type = ""; 

// Fetch Recent Announcements (Placeholder Query - Adapt to your DB)
$announcement_history = [];
$sql_history = "
    SELECT title, target, priority, published_on, expiry_date 
    FROM announcements 
    ORDER BY published_on DESC 
    LIMIT 5
";

$result = $conn->query($sql_history);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcement_history[] = $row;
    }
}

// -------------------------------------------------------------------
// ANNOUNCEMENT SUBMISSION LOGIC
// -------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Get and Sanitize Input
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $target = trim($_POST['target']);
    $priority = trim($_POST['priority']);
    $expiry_date = empty($_POST['expiryDate']) ? NULL : trim($_POST['expiryDate']);
    
    // Simple Validation
    if (empty($title) || empty($body)) {
        $submission_message = "Title and content cannot be empty.";
        $error_type = "error";
    } else {
        // 3. Prepare SQL INSERT Statement
        $sql = "INSERT INTO announcements (user_id, title, body, target, priority, expiry_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $bind_expiry = ($expiry_date === NULL) ? NULL : $expiry_date;
            $stmt->bind_param("isssss", $user_id, $title, $body, $target, $priority, $bind_expiry);
            
            if ($stmt->execute()) {
                $submission_message = "Announcement published successfully!";
                $error_type = "success";
            } else {
                $submission_message = "Error publishing: " . $stmt->error;
                $error_type = "error";
            }
            $stmt->close();
        }
    }
    // Note: In a real app, redirect after POST to prevent resubmission
}

$conn->close(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcements | NeoEra Admin</title>
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
            background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
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
        }

        /* --- LAYOUT GRID --- */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            height: 100%;
        }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: var(--input-bg);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 0.95rem;
            transition: 0.3s;
            outline: none;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-col { flex: 1; }

        .btn-publish {
            padding: 12px 30px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            float: right;
        }
        .btn-publish:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* History List */
        .history-list { list-style: none; padding: 0; }
        .history-item {
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .history-item:last-child { border-bottom: none; }
        
        .history-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 5px; display: block; }
        .history-meta { font-size: 0.8rem; color: var(--text-muted); display: flex; justify-content: space-between; }
        
        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-high { background: rgba(255, 118, 117, 0.15); color: #d63031; }
        .badge-normal { background: rgba(9, 132, 227, 0.15); color: #0984e3; }

        /* Messages */
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: rgba(0, 184, 148, 0.15); color: #00b894; border: 1px solid rgba(0, 184, 148, 0.3); }
        .alert-error { background: rgba(255, 118, 117, 0.15); color: #d63031; border: 1px solid rgba(255, 118, 117, 0.3); }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .form-row { flex-direction: column; gap: 0; }
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
                <li><a href="admin_manage_users.php"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="admin_time_logs.php"><i class="fa-solid fa-clock-rotate-left"></i> Time Logs</a></li>
                <li><a href="admin_leave_requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li class="active"><a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
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
                <h1>Announcements</h1>
                <p>Broadcast messages to your team.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <?php if (!empty($submission_message)): ?>
            <div class="alert-box <?php echo ($error_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <i class="fa-solid <?php echo ($error_type == 'success') ? 'fa-check-circle' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $submission_message; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            
            <div class="glass-card">
                <h3 style="margin-bottom: 25px; color: var(--text-color);">Create New Post</h3>
                
                <form action="admin_announcements.php" method="POST">
                    <div class="form-group">
                        <label for="title">Subject / Title</label>
                        <input type="text" id="title" name="title" placeholder="e.g. System Maintenance Alert" required>
                    </div>

                    <div class="form-group">
                        <label for="target">Audience</label>
                        <select id="target" name="target" required>
                            <option value="all">All Employees</option>
                            <option value="dept_it">IT Department</option>
                            <option value="dept_hr">HR Department</option>
                            <option value="admins">Admins Only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="body">Message Body</label>
                        <textarea id="body" name="body" rows="6" placeholder="Type your announcement here..." required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-col form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High Importance</option>
                            </select>
                        </div>
                        <div class="form-col form-group">
                            <label for="expiryDate">Expiry (Optional)</label>
                            <input type="date" id="expiryDate" name="expiryDate">
                        </div>
                    </div>

                    <button type="submit" class="btn-publish">
                        <i class="fa-solid fa-paper-plane"></i> Publish Now
                    </button>
                </form>
            </div>

            <div class="glass-card">
                <h3 style="margin-bottom: 20px; color: var(--text-color);">Recent History</h3>
                
                <?php if (empty($announcement_history)): ?>
                    <p style="color: var(--text-muted); text-align: center; margin-top: 40px;">No announcements posted yet.</p>
                <?php else: ?>
                    <ul class="history-list">
                        <?php foreach ($announcement_history as $item): 
                            $badge_class = ($item['priority'] === 'high') ? 'badge-high' : 'badge-normal';
                            $date_display = date('M d, Y', strtotime($item['published_on']));
                        ?>
                            <li class="history-item">
                                <span class="history-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                <div class="history-meta">
                                    <span><?php echo $date_display; ?></span>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($item['priority']); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <button onclick="alert('View Archive')" style="width:100%; padding:10px; margin-top:20px; background:transparent; border:1px solid #667eea; color:#667eea; border-radius:8px; cursor:pointer;">
                    View All Archives
                </button>
            </div>

        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>