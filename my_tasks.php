<?php
// my_tasks.php - Employee interface for managing their tasks.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Placeholder for auth logic
$full_name = "Jane Doe"; 
$job_title = "Developer";
// In a real app, you would check $_SESSION["loggedin"] here.

// 1. Initialize Tasks in Session
if (!isset($_SESSION['employee_tasks'])) {
    $_SESSION['employee_tasks'] = [
        ['id' => 1, 'title' => 'Complete Annual Security Training', 'due_date' => '2025-12-14', 'status' => 'pending', 'urgency' => 'red-text', 'project' => 'HR/Compliance'],
        ['id' => 2, 'title' => 'Q4 Expense Report Submission', 'due_date' => '2025-12-15', 'status' => 'pending', 'urgency' => 'orange-text', 'project' => 'HR/Finance'],
        ['id' => 4, 'title' => 'Schedule 1-on-1 with Manager', 'due_date' => '2025-12-30', 'status' => 'pending', 'urgency' => 'green-text', 'project' => 'Self-Development'],
        ['id' => 3, 'title' => 'Review Project X Documentation', 'due_date' => '2025-12-08', 'status' => 'completed', 'urgency' => 'green-text', 'project' => 'Internal QA'],
    ];
}

$task_message = "";
$task_message_class = '';
$project_options = ['Client X Migration', 'HR/Compliance', 'HR/Finance', 'Internal QA', 'Self-Development'];

// Calculate Stats for the Top Cards
$total_tasks = count($_SESSION['employee_tasks']);
$pending_count = 0;
$completed_count = 0;
foreach ($_SESSION['employee_tasks'] as $t) {
    if ($t['status'] == 'completed') $completed_count++;
    else $pending_count++;
}

// 2. Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // --- ADD TASK ---
    if ($_POST['action'] == 'add_task') {
        $taskTitle = trim($_POST['taskTitle']);
        $dueDate = trim($_POST['dueDate']);
        $taskProject = trim($_POST['taskProject']);
        
        if (!empty($taskTitle) && !empty($dueDate)) {
            $max_id = 0;
            foreach ($_SESSION['employee_tasks'] as $task) {
                if ($task['id'] > $max_id) $max_id = $task['id'];
            }
            
            $_SESSION['employee_tasks'][] = [
                'id' => $max_id + 1,
                'title' => $taskTitle,
                'due_date' => $dueDate,
                'status' => 'pending',
                'urgency' => 'orange-text', 
                'project' => $taskProject
            ];
            
            $_SESSION['task_message'] = "Task created successfully!";
            header("Location: my_tasks.php");
            exit;
        }
    } 
    
    // --- DELETE TASK ---
    if ($_POST['action'] == 'delete_task' && isset($_POST['task_id'])) {
        $taskIdToDelete = intval($_POST['task_id']);
        $_SESSION['employee_tasks'] = array_filter($_SESSION['employee_tasks'], function($task) use ($taskIdToDelete) {
            return $task['id'] !== $taskIdToDelete;
        });
        $_SESSION['employee_tasks'] = array_values($_SESSION['employee_tasks']); // Re-index
        $_SESSION['task_message'] = "Task deleted successfully.";
        header("Location: my_tasks.php");
        exit;
    }

    // --- MARK DONE (NEW LOGIC) ---
    if ($_POST['action'] == 'mark_done' && isset($_POST['task_id'])) {
        $taskIdToUpdate = intval($_POST['task_id']);
        foreach ($_SESSION['employee_tasks'] as &$task) {
            if ($task['id'] === $taskIdToUpdate) {
                $task['status'] = 'completed';
                $task['urgency'] = 'green-text'; // Remove urgency visual
                break;
            }
        }
        $_SESSION['task_message'] = "Task marked as completed.";
        header("Location: my_tasks.php");
        exit;
    }
}

// Check for messages
if (isset($_SESSION['task_message'])) {
    $task_message = $_SESSION['task_message'];
    $task_message_class = 'success';
    unset($_SESSION['task_message']);
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | NeoEra Infotech</title>
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

        /* --- STATS CARDS --- */
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
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            color: white;
        }

        .stat-info h3 { margin: 0; font-size: 1.5rem; }
        .stat-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); }

        .bg-purple { background: #d19931ff; }
        .bg-orange { background: var(--warning-color); }
        .bg-green { background: var(--success-color); }

        /* --- ADD TASK FORM --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .input-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: var(--input-bg);
            outline: none;
            transition: 0.3s;
            color: var(--text-color);
        }

        .form-group input:focus, .form-group select:focus {
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            border-color: #d19931ff;
        }

        .btn-add {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            height: fit-content;
            align-self: flex-end;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* --- TASK TABLE --- */
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .filter-select {
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            background: var(--glass-bg);
            outline: none;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 20px;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
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
        }

        .styled-table td {
            color: var(--text-color);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-completed { background: rgba(0, 184, 148, 0.15); color: #00b894; }
        .status-pending { background: rgba(253, 203, 110, 0.15); color: #e17055; }

        /* Urgency Colors for Due Date */
        .red-text { color: var(--danger-color); font-weight: 600; }
        .orange-text { color: #e17055; font-weight: 500; }
        .green-text { color: var(--success-color); }

        /* Action Buttons */
        .action-form { display: inline-block; }
        .btn-icon {
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            margin-left: 5px;
        }
        .btn-check { background: rgba(0, 184, 148, 0.1); color: #00b894; }
        .btn-check:hover { background: #00b894; color: white; }
        
        .btn-delete { background: rgba(255, 118, 117, 0.1); color: #d63031; }
        .btn-delete:hover { background: #d63031; color: white; }

        /* Alerts */
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            background: rgba(0, 184, 148, 0.15); 
            color: #00b894; 
            border: 1px solid rgba(0, 184, 148, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .input-grid { grid-template-columns: 1fr; }
            .btn-add { width: 100%; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-icon">NE</div>
            <div class="logo-text">NeoEra Portal</div>
        </div>
        
        <nav class="nav-links">
            <ul>
                <li><a href="employee_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="employee_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="time_tracking.php"><i class="fa-solid fa-clock"></i> History</a></li>
                <li><a href="apply_leave.php"><i class="fa-solid fa-calendar-days"></i> Apply Leave</a></li>
                <li class="active"><a href="my_tasks.php"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
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
                <h1>Task Manager</h1>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <?php if (!empty($task_message)): ?>
            <div class="alert-box">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo $task_message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fa-solid fa-list-ul"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_tasks; ?></h3>
                    <p>Total Tasks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_count; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <h3 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--text-color);">Add New Task</h3>
            <form action="my_tasks.php" method="POST" id="newTaskForm">
                <input type="hidden" name="action" value="add_task">
                <div class="input-grid">
                    <div class="form-group">
                        <label for="taskTitle">Task Title</label>
                        <input type="text" id="taskTitle" name="taskTitle" placeholder="e.g. Prepare monthly report" required>
                    </div>
                    <div class="form-group">
                        <label for="taskProject">Project Category</label>
                        <select id="taskProject" name="taskProject" required>
                            <option value="" disabled selected>Select project</option>
                            <?php foreach ($project_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dueDate">Due Date</label>
                        <input type="date" id="dueDate" name="dueDate" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 15px;">
                    <button type="submit" class="btn-add">
                        <i class="fa-solid fa-plus"></i> Create Task
                    </button>
                </div>
            </form>
        </div>

        <div class="controls-bar">
            <h3 style="font-size: 1.2rem; color: var(--text-color);">My To-Do List</h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label style="font-size: 0.9rem; color: var(--text-muted);"><i class="fa-solid fa-filter"></i> Filter:</label>
                <select id="statusFilter" class="filter-select" onchange="filterTasks()">
                    <option value="all">All Tasks</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="styled-table" id="taskTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Project</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($_SESSION['employee_tasks'])): ?>
                        <tr><td colspan="6" style="text-align:center; color: var(--text-muted);">No tasks found. Add one above!</td></tr>
                    <?php else: ?>
                        <?php foreach ($_SESSION['employee_tasks'] as $task): 
                            $is_completed = ($task['status'] === 'completed');
                            $status_class = $is_completed ? 'status-completed' : 'status-pending';
                            $due_class = $is_completed ? 'green-text' : $task['urgency'];
                            $display_due = date('M j, Y', strtotime($task['due_date']));
                        ?>
                            <tr class="task-row" data-status="<?php echo $task['status']; ?>">
                                <td style="color: var(--text-muted);">#<?php echo $task['id']; ?></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><span style="font-size: 0.85rem; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 5px;"><?php echo htmlspecialchars($task['project']); ?></span></td>
                                <td class="<?php echo $due_class; ?>"><?php echo $display_due; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($task['status']); ?></span>
                                </td>
                                <td>
                                    <?php if (!$is_completed): ?>
                                        <form method="POST" action="my_tasks.php" class="action-form">
                                            <input type="hidden" name="action" value="mark_done">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="btn-icon btn-check" title="Mark as Done">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="my_tasks.php" class="action-form">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="btn-icon btn-delete" title="Delete Task" onclick="return confirm('Are you sure?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Client-side Filtering
        function filterTasks() {
            const filter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.task-row');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>