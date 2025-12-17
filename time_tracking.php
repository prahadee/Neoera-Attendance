<?php
// time_tracking.php - Secured and displays dynamic time log history
require_once "auth_check.php";

if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$time_history = [];

// Stats Variables
$total_days_worked = 0;
$total_hours_seconds = 0;

// --- Fetch User's Time Log History (Last 30 days) ---
$sql_history = "
    SELECT check_in_time, check_out_time
    FROM time_logs
    WHERE user_id = ?
    AND check_in_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY check_in_time DESC
";

if ($stmt = $conn->prepare($sql_history)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $time_history[] = $row;
            
            // Calculate stats
            $total_days_worked++;
            if ($row['check_out_time']) {
                $start = new DateTime($row['check_in_time']);
                $end = new DateTime($row['check_out_time']);
                $total_hours_seconds += ($end->getTimestamp() - $start->getTimestamp());
            }
        }
    }
    $stmt->close();
}

// Convert total seconds to hours
$total_hours_worked = round($total_hours_seconds / 3600, 1);

// Function to calculate duration
function calculate_duration_log($in, $out) {
    if (!$out) return "Active";
    $start = new DateTime($in);
    $end = new DateTime($out);
    $diff = $start->diff($end);
    return $diff->h . "h " . $diff->i . "m";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time History | NeoEra Infotech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME VARIABLES (MATCHING DASHBOARD) --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #d19931ff 0%, #c38728ff 100%);
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
            background: var(--primary-gradient);
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
            color: #d19931ff;
            border-left: 3px solid #d19931ff;
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

        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
        }

        /* --- STATS SUMMARY --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-info h3 { font-size: 1.5rem; margin-bottom: 0; color: var(--text-color); }
        .stat-info p { margin-bottom: 0; color: var(--text-muted); font-size: 0.9rem; }

        .bg-purple { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .bg-blue { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .bg-orange { background: linear-gradient(120deg, #f6d365 0%, #fda085 100%); }

        /* --- TABLE SECTION --- */
        .log-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
        }

        .log-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label { font-weight: 500; color: var(--text-muted); }

        .date-input {
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.8);
            color: var(--text-color);
            outline: none;
        }

        .download-btn {
            padding: 10px 20px;
            background: white;
            border: 1px solid #d19931ff;
            color: #d19931ff;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
        }
        
        .download-btn:hover { background: #d19931ff; color: white; }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px; /* Forces scroll on small screens */
        }

        .styled-table thead tr {
            background-color: rgba(102, 126, 234, 0.1);
            text-align: left;
        }

        .styled-table th, .styled-table td {
            padding: 15px 20px;
        }

        .styled-table th {
            color: #d19931ff;
            font-weight: 600;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }

        .styled-table td {
            color: var(--text-color);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .styled-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-completed { background: rgba(0, 184, 148, 0.15); color: #00b894; }
        .status-active { background: rgba(9, 132, 227, 0.15); color: #0984e3; }
        .status-missing { background: rgba(255, 118, 117, 0.15); color: #d63031; }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .log-controls { flex-direction: column; align-items: flex-start; }
            .download-btn { width: 100%; }
            .stat-card { gap: 12px; padding: 16px; }
            .stat-icon { width: 50px; height: 50px; }
        }

        /* Mobile: Convert table to stacked cards for better readability */
        @media (max-width: 600px) {
            .styled-table { min-width: 0; }
            .styled-table thead { display: none; }
            .styled-table, .styled-table tbody, .styled-table tr, .styled-table td { display: block; width: 100%; }
            .styled-table tr { margin-bottom: 14px; background: var(--glass-bg); padding: 16px; border-radius: 12px; box-shadow: var(--glass-shadow); }
            .styled-table td { padding: 8px 0; border-bottom: none; position: relative; }
            .styled-table td::before {
                content: attr(data-label);
                display: block;
                font-weight: 700;
                color: var(--text-muted);
                margin-bottom: 6px;
            }
            .styled-table td .status-badge { margin-top: 6px; font-size: 0.9rem; }

            /* Make controls and buttons easier to tap */
            .mobile-toggle { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(0,0,0,0.03); }
            .download-btn { width: 100%; padding: 12px 16px; font-size: 0.95rem; }
            .table-responsive { padding: 6px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation" aria-hidden="false">
        <div class="logo-area">
            <div class="logo-icon">NE</div>
            <div class="logo-text">NeoEra Portal</div>
        </div>
        
        <nav class="nav-links">
            <ul>
                <li><a href="employee_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="employee_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li class="active"><a href="time_tracking.php"><i class="fa-solid fa-clock"></i> History</a></li>
                <li><a href="apply_leave.php"><i class="fa-solid fa-calendar-days"></i> Apply Leave</a></li>
                <li><a href="my_tasks.php"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li><a href="employee_announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
                <li><a href="logout.php" style="color: var(--danger-color);"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="user-profile-mini">
            <div class="avatar"><?php echo $first_two_letters; ?></div>
            <div class="user-info-mini">
                <h4 style="font-size: 0.9rem; color: var(--text-color);"><?php echo htmlspecialchars($full_name); ?></h4>
                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($job_title); ?></span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="page-title">
                <h1>Attendance History</h1>
            </div>
            <button id="mobileToggle" class="mobile-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button> 
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_days_worked; ?></h3>
                    <p>Days Present (30d)</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_hours_worked; ?>h</h3>
                    <p>Total Hours</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_days_worked > 0 ? round($total_hours_worked / $total_days_worked, 1) : 0; ?>h</h3>
                    <p>Avg. Hours / Day</p>
                </div>
            </div>
        </div>

        <div class="log-section">
            <div class="log-controls">
                <div class="filter-group">
                    <label for="dateFilter"><i class="fa-solid fa-filter"></i> Filter Date:</label>
                    <input type="date" id="dateFilter" class="date-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button class="download-btn" onclick="alert('Download feature coming soon!')">
                    <i class="fa-solid fa-download"></i> Export Report
                </button>
            </div>

            <div class="table-responsive">
                <table class="styled-table" id="logTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($time_history)): ?>
                            <?php foreach ($time_history as $log): 
                                $in_time = new DateTime($log['check_in_time']);
                                $out_time = $log['check_out_time'] ? new DateTime($log['check_out_time']) : null;
                                $duration = calculate_duration_log($log['check_in_time'], $log['check_out_time']);
                                
                                if (!$out_time) {
                                    $status_text = "Active Now";
                                    $status_class = "status-active";
                                } else {
                                    $status_text = "Completed";
                                    $status_class = "status-completed";
                                }
                            ?>
                                <tr class="log-row">
                                    <td data-label="Date"><?php echo $in_time->format('M d, Y'); ?></td>
                                    <td data-label="Check In"><i class="fa-regular fa-clock" style="color: #d19931ff; margin-right:5px;"></i> <?php echo $in_time->format('h:i A'); ?></td>
                                    <td data-label="Check Out"><?php echo $out_time ? $out_time->format('h:i A') : '-'; ?></td>
                                    <td data-label="Total Duration" style="font-weight: 500;"><?php echo $duration; ?></td>
                                    <td data-label="Status"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 30px; color: var(--text-muted);">No records found for the last 30 days.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Simple Client-Side Date Filter (Visual only)
        document.getElementById('dateFilter').addEventListener('change', function() {
            const filterDate = new Date(this.value).toDateString();
            const rows = document.querySelectorAll('.log-row');
            
            // Note: This logic assumes the "Date" column is formatted in a way JS can parse, 
            // or we strictly check strings. For 'M d, Y' (e.g. Dec 11, 2025), simple string comparison needs parsing.
            // For simplicity in this demo, we'll reload the page with a GET param in a real app, 
            // but here is a simple string highlighter.
            
            const selectedStr = new Date(this.value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

            rows.forEach(row => {
                const rowDate = row.cells[0].innerText; // "Dec 11, 2025"
                if(rowDate === selectedStr) {
                    row.style.background = "rgba(102, 126, 234, 0.2)";
                } else {
                    row.style.background = "transparent";
                }
            });
        });

        // Close sidebar when a nav link is clicked on mobile
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });

        // Ensure sidebar is in correct state after resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });
    </script>

    <script>
    (function(){
      const sidebar = document.getElementById('sidebar');
      const mobileToggle = document.getElementById('mobileToggle');
      let lastFocused = null;

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