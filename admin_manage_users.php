<?php
// admin_manage_users.php - Secured page for Admin role, fetches all user data
require_once "auth_check.php";

// Ensure only ADMINS access this page
if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$user_list = [];

// --- Database Query (Reverted to your EXACT original working query) ---
$sql_users = "
    SELECT 
        u.id AS user_pk,
        u.role,
        ed.employee_id,
        ed.full_name,
        ed.job_title,
        ed.department
    FROM users u
    JOIN employee_details ed ON u.id = ed.user_id
    ORDER BY u.role DESC, ed.employee_id ASC
";

$result = $conn->query($sql_users);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_list[] = $row;
    }
} else {
    // Optional: Uncomment to debug SQL errors if users still don't show
    // echo "Error: " . $conn->error;
}

// Close connection after fetching data
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | NeoEra Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME VARIABLES --- */
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

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

        .search-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .search-input {
            width: 100%;
            max-width: 300px;
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.8);
            outline: none;
            color: var(--text-color);
            transition: 0.3s;
        }
        .search-input:focus {
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn-add {
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
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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

        /* Badges & Avatars */
        .user-cell { display: flex; align-items: center; gap: 12px; }
        .table-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #667eea;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .role-admin { background: rgba(214, 48, 49, 0.1); color: #d63031; }
        .role-employee { background: rgba(9, 132, 227, 0.1); color: #0984e3; }

        .status-dot {
            height: 8px;
            width: 8px;
            background-color: #00b894;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        /* Action Buttons */
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
        .btn-edit { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .btn-edit:hover { background: #667eea; color: white; }
        .btn-pwd { background: rgba(253, 203, 110, 0.1); color: #e17055; }
        .btn-pwd:hover { background: #e17055; color: white; }
        .btn-del { background: rgba(255, 118, 117, 0.1); color: #d63031; }
        .btn-del:hover { background: #d63031; color: white; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .search-input { max-width: 100%; }
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
                <h1>User Management</h1>
                <p>Add, edit, or remove system access.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="glass-card">
            
            <div class="controls-bar">
                <div class="search-group">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search users by name, ID or role..." onkeyup="filterUsers()">
                    <select id="roleFilter" class="filter-select" onchange="filterUsers()">
                        <option value="all">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
<button class="btn-add" onclick="window.location.href='admin_add_user.php'">
    <i class="fa-solid fa-user-plus"></i> Add New User
</button>

            </div>

            <div class="table-responsive">
                <table class="styled-table" id="userTable">
                    <thead>
                        <tr>
                            <th>User Profile</th>
                            <th>Emp ID</th>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($user_list)): ?>
                            <?php foreach ($user_list as $user): 
                                $initials = strtoupper(substr($user['full_name'], 0, 2));
                                $role_class = ($user['role'] === 'admin') ? 'role-admin' : 'role-employee';
                            ?>
                                <tr class="user-row" data-role="<?php echo $user['role']; ?>">
                                    <td>
                                        <div class="user-cell">
                                            <div class="table-avatar"><?php echo $initials; ?></div>
                                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $role_class; ?>"><?php echo ucfirst($user['role']); ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: var(--success-color);"><span class="status-dot"></span>Active</span>
                                    </td>
                                    <td>
<td>
    <div class="action-cell">
        <!-- Edit User -->
        <button class="icon-btn btn-edit" 
                title="Edit User"
                onclick="window.location.href='admin_edit_user.php?id=<?php echo (int)$user['user_pk']; ?>'">
            <i class="fa-solid fa-pen"></i>
        </button>

        <!-- Reset Password -->
        <button class="icon-btn btn-pwd" 
                title="Reset Password"
                onclick="window.location.href='admin_reset_password.php?id=<?php echo (int)$user['user_pk']; ?>'">
            <i class="fa-solid fa-key"></i>
        </button>

        <!-- Delete (only non-admin) -->
        <?php if ($user['role'] !== 'admin'): ?>
            <button class="icon-btn btn-del" 
                    title="Delete User"
                    onclick="if(confirm('Delete this user?')) { window.location.href='admin_delete_user.php?id=<?php echo (int)$user['user_pk']; ?>'; }">
                <i class="fa-solid fa-trash"></i>
            </button>
        <?php endif; ?>
    </div>
</td>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 30px;">No users found in database.</td></tr>
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

        // Client-side Search & Filter
        function filterUsers() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const textContent = row.innerText.toLowerCase();
                const userRole = row.getAttribute('data-role');
                
                const matchesSearch = textContent.includes(searchText);
                const matchesRole = (roleFilter === 'all') || (userRole === roleFilter);

                if (matchesSearch && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>