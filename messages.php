<?php
// messages.php - Secured page for Employee role
require_once "auth_check.php";

// Ensure only employees access this page
if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages | NeoEra Infotech</title>
    <link rel="stylesheet" href="css/dashboard_style.css">
    <link rel="stylesheet" href="css/profile_style.css"> 
</head>
<body>
    <div class="portal-layout">
        
        <aside class="sidebar">
            <div class="logo">NeoEra Infotech</div>
            <nav class="nav-menu">
                <ul>
                    <li><a href="employee_dashboard.php"><span class="icon">🏠</span> Dashboard</a></li>
                    <li><a href="employee_profile.php"><span class="icon">👤</span> My Profile</a></li>
                    <li><a href="time_tracking.php"><span class="icon">⏱️</span> Check-in / Check-out</a></li>
                    <li><a href="apply_leave.php"><span class="icon">🗓️</span> Apply Leave</a></li> 
                    <li><a href="my_tasks.php"><span class="icon">✅</span> My Tasks</a></li>
                    <li class="active"><a href="messages.php"><span class="icon">✉️</span> Messages</a></li>
                    <li><a href="logout.php" class="logout-btn"><span class="icon">➡️</span> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="welcome-text">Internal Message Inbox</div>
                <div class="user-info">
                    <span class="user-role"><?php echo $job_title; ?>, <?php echo $department; ?></span>
                    <div class="user-avatar"><?php echo $first_two_letters; ?></div>
                </div>
            </header>

            <section class="messages-inbox card">
                <h2>Inbox (<span id="unreadCount">3</span> Unread)</h2>

                <div class="controls" style="margin-bottom: 20px;">
                    <button class="edit-btn" onclick="alert('Simulating sending a new message.')">Compose New Message</button>
                    <button class="cancel-btn" onclick="alert('Simulating refreshing the inbox.')" style="background-color: #6c757d;">Refresh Inbox</button>
                </div>

                <div class="message-list">
                    <div class="message-item unread" onclick="alert('Reading message from HR.')">
                        <div class="sender-info">
                            <strong>HR Department</strong>
                            <span>New Policy Update Required Reading</span>
                        </div>
                        <span>10:30 AM</span>
                    </div>
                    <div class="message-item unread" onclick="alert('Reading message from Rakesh Sharma.')">
                        <div class="sender-info">
                            <strong>Rakesh Sharma (Manager)</strong>
                            <span>Re: Project X documentation feedback</span>
                        </div>
                        <span>Yesterday</span>
                    </div>
                    <div class="message-item read" onclick="alert('Reading older message.')">
                        <div class="sender-info">
                            <strong>System Admin</strong>
                            <span>Server Maintenance Completed</span>
                        </div>
                        <span>3 days ago</span>
                    </div>
                     <div class="message-item read" onclick="alert('Reading older message.')">
                        <div class="sender-info">
                            <strong>Team Lead</strong>
                            <span>Reminder: Team lunch tomorrow at 12:30 PM.</span>
                        </div>
                        <span>1 week ago</span>
                    </div>
                </div>

            </section>
        </main>
    </div>
    <script src="js/dashboard_script.js"></script> 
</body>
</html>