<?php
// admin_leave_requests.php - Secured page for Admin role, fetches leave requests
require_once "auth_check.php";

if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$leave_requests = [];

// --- Database Query to Fetch Pending and Approved Leave Requests ---
// Note: Ensure your DB columns match these exactly
$sql_requests = "
    SELECT 
        lr.id AS request_id, 
        lr.leave_type,
        lr.start_date,
        lr.end_date,
        lr.total_days,
        lr.reason,
        lr.status,
        ed.full_name
    FROM leave_requests lr
    JOIN employee_details ed ON lr.user_id = ed.user_id
    ORDER BY lr.status DESC, lr.start_date ASC
";

$result = $conn->query($sql_requests);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $leave_requests[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests | NeoEra Admin</title>
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

        /* --- MANAGEMENT CARD --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            height: 100%;
        }

        /* Controls */
        .controls-bar {
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
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.8);
            outline: none;
            color: var(--text-color);
            cursor: pointer;
        }

        .btn-report {
            padding: 10px 20px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-report:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .styled-table thead tr {
            background-color: rgba(102, 126, 234, 0.1);
            text-align: left;
        }

        .styled-table th {
            padding: 15px 20px;
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .styled-table td {
            padding: 12px 20px;
            color: var(--text-color);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        .styled-table tr:hover { background-color: rgba(255,255,255,0.4); }

        /* Status Badge */
        .status-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-completed { background: rgba(0, 184, 148, 0.1); color: #00b894; } /* Approved */
        .status-absent { background: rgba(255, 118, 117, 0.1); color: #d63031; } /* Rejected */
        .status-pending { background: rgba(253, 203, 110, 0.1); color: #e17055; } /* Pending */

        /* Actions */
        .action-cell { display: flex; gap: 8px; }
        .icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        
        .btn-approve { background: rgba(0, 184, 148, 0.1); color: #00b894; }
        .btn-approve:hover { background: #00b894; color: white; }

        .btn-reject { background: rgba(255, 118, 117, 0.1); color: #d63031; }
        .btn-reject:hover { background: #d63031; color: white; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; width: 100%; }
            .filter-select { width: 100%; }
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
                <li class="active"><a href="admin_leave_requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
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
                <h1>Leave Requests</h1>
                <p>Approve or reject employee time off.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="glass-card">
            
            <div class="controls-bar">
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select" onchange="filterRequests()">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    
                    <select id="typeFilter" class="filter-select" onchange="filterRequests()">
                        <option value="all">All Leave Types</option>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                <button class="btn-report" onclick="alert('Exporting Leave History...')">
                    <i class="fa-solid fa-file-export"></i> Export Data
                </button>
            </div>

            <div class="table-responsive">
                <table class="styled-table" id="leaveTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leave_requests)): ?>
                            <?php foreach ($leave_requests as $req): 
                                $status_class = match(strtolower($req['status'])) {
                                    'approved' => 'status-completed',
                                    'rejected' => 'status-absent',
                                    default => 'status-pending',
                                };
                                $is_pending = (strtolower($req['status']) === 'pending');
                            ?>
                                <tr class="request-row" 
                                    data-status="<?php echo strtolower($req['status']); ?>" 
                                    data-type="<?php echo strtolower($req['leave_type']); ?>">
                                    
                                    <td>#<?php echo $req['request_id']; ?></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($req['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['leave_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($req['start_date'])); ?></td>
                                    <td><?php echo $req['total_days']; ?></td>
                                    <td title="<?php echo htmlspecialchars($req['reason']); ?>">
                                        <?php echo substr(htmlspecialchars($req['reason']), 0, 25) . '...'; ?>
                                    </td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                    
                                    <td>
                                        <?php if ($is_pending): ?>
                                            <div class="action-cell">
                                                <button class="icon-btn btn-approve" onclick="alert('Approved Request #<?php echo $req['request_id']; ?>')" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button class="icon-btn btn-reject" onclick="alert('Rejected Request #<?php echo $req['request_id']; ?>')" title="Reject">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size:0.8rem; color:var(--text-muted);">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding: 30px;">No leave requests found.</td></tr>
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

        // Client-side Filtering
        function filterRequests() {
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const rows = document.querySelectorAll('.request-row');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const type = row.getAttribute('data-type');
                
                // Flexible type matching (e.g., 'annual' matches 'Annual Leave')
                const matchStatus = (statusFilter === 'all') || (status === statusFilter);
                const matchType = (typeFilter === 'all') || type.includes(typeFilter);

                if (matchStatus && matchType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>