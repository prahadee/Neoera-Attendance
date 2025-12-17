<?php
// employee_profile.php - Secured and displays dynamic profile data
require_once "auth_check.php";

// Ensure only employees access this page
if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

// Helper for avatar initials
$first_two_letters = strtoupper(substr($full_name, 0, 2)); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | NeoEra Infotech</title>
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

        .nav-links {
            padding: 20px 0;
            flex: 1;
        }

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

        /* --- PROFILE SPECIFIC STYLES --- */
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        /* Profile Card (Left) */
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--glass-shadow);
            height: fit-content;
        }

        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            font-size: 3rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            position: relative;
        }

        .profile-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-role {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            padding: 20px 0;
            border-top: 1px solid rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .stat-box h4 { font-size: 1.2rem; color: #d19931ff; margin-bottom: 0; }
        .stat-box span { font-size: 0.8rem; color: var(--text-muted); }

        /* Tabs (Right) */
        .tabs-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .tabs-header {
            display: flex;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.3);
        }

        .tab-btn {
            padding: 20px 25px;
            border: none;
            background: none;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .tab-btn:hover { color: #d19931ff; }

        .tab-btn.active {
            color: #d19931ff;
            background: rgba(255,255,255,0.5);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #d19931ff;
        }

        .tab-content {
            padding: 30px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: var(--input-bg);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .form-group input:focus {
            background: white;
            border-color: #d19931ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:disabled {
            background: rgba(0,0,0,0.03);
            color: #888;
            cursor: not-allowed;
            border-color: transparent;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .section-header h3 { font-size: 1.2rem; }

        .edit-toggle-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #d19931ff;
            background: transparent;
            color: #d19931ff;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .edit-toggle-btn:hover {
            background: #d19931ff;
            color: white;
        }

        .save-btn {
            padding: 12px 24px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            transition: 0.3s;
        }

        .save-btn:hover { transform: translateY(-2px); }

        /* Responsive */
        @media (max-width: 1024px) {
            .profile-layout { grid-template-columns: 1fr; }
            .profile-card { margin-bottom: 0; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
            .tabs-header { overflow-x: auto; }
            .tab-btn { white-space: nowrap; }
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
                <li class="active"><a href="employee_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="time_tracking.php"><i class="fa-solid fa-clock"></i> History</a></li>
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
                <h1>My Profile</h1>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="profile-layout">
            
            <div class="profile-card">
                <div class="profile-pic-large">
                    <?php echo $first_two_letters; ?>
                    <div style="position:absolute; bottom:5px; right:5px; width:30px; height:30px; background:white; border-radius:50%; color:#d19931ff; font-size:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,0.2); cursor:pointer;">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                </div>
                <h2 class="profile-name"><?php echo $full_name; ?></h2>
                <p class="profile-role"><?php echo $job_title; ?> | <?php echo $department; ?></p>
                
                <div class="profile-stats">
                    <div class="stat-box">
                        <h4><?php echo $employee_id; ?></h4>
                        <span>Emp ID</span>
                    </div>
                    <div class="stat-box">
                        <h4>Active</h4>
                        <span>Status</span>
                    </div>
                    <div class="stat-box">
                        <h4>5.2</h4>
                        <span>Years</span>
                    </div>
                </div>

                <div style="text-align: left; padding: 0 10px;">
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 8px;"><i class="fa-solid fa-envelope" style="margin-right: 10px; color: #d19931ff;"></i> <?php echo $work_email; ?></p>
                    <p style="font-size: 0.9rem; color: var(--text-muted);"><i class="fa-solid fa-phone" style="margin-right: 10px; color: #d19931ff;"></i> +91 <?php echo $phone_number; ?></p>
                </div>
            </div>

            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="openTab('personal')">Personal Info</button>
                    <button class="tab-btn" onclick="openTab('organization')">Organization</button>
                    <button class="tab-btn" onclick="openTab('security')">Security</button>
                </div>

                <div id="personal" class="tab-content active">
                    <div class="section-header">
                        <h3>Contact Information</h3>
                        <button class="edit-toggle-btn" onclick="toggleEdit('personalForm')">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Details
                        </button>
                    </div>
                    
                    <form id="personalForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" value="<?php echo $full_name; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" value="1995-05-20" disabled>
                            </div>
                            <div class="form-group">
                                <label>Personal Email</label>
                                <input type="email" value="<?php echo $personal_email; ?>" disabled class="editable">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" value="<?php echo $phone_number; ?>" disabled class="editable">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Residential Address</label>
                            <textarea rows="3" disabled class="editable">456 Tech Lane, Pune, Maharashtra, 411001</textarea>
                        </div>

                        <div class="form-actions" style="display:none; text-align: right; margin-top: 20px;">
                            <button type="button" class="edit-toggle-btn" style="border: 1px solid #ff7675; color: #ff7675; margin-right: 10px;" onclick="cancelEdit('personalForm')">Cancel</button>
                            <button type="button" class="save-btn" onclick="saveData('personalForm')">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div id="organization" class="tab-content">
                    <div class="section-header">
                        <h3>Employment Details</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted); background: rgba(0,0,0,0.05); padding: 5px 10px; border-radius: 5px;">Read Only</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" value="<?php echo $department; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Job Title</label>
                            <input type="text" value="<?php echo $job_title; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Date of Joining</label>
                            <input type="text" value="<?php echo $joining_date; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Reporting Manager</label>
                            <input type="text" value="Mr. Rakesh Sharma" disabled>
                        </div>
                        <div class="form-group">
                            <label>Work Location</label>
                            <input type="text" value="Bangalore HQ" disabled>
                        </div>
                        <div class="form-group">
                            <label>Employment Type</label>
                            <input type="text" value="Full-Time Permanent" disabled>
                        </div>
                    </div>
                </div>

                <div id="security" class="tab-content">
                    <div class="section-header">
                        <h3>Account Security</h3>
                    </div>

                    <form id="securityForm">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" placeholder="Enter current password">
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" placeholder="Min 8 characters">
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" placeholder="Re-enter new password">
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 10px;">
                            <button type="button" class="save-btn">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Tab Switching Logic
        function openTab(tabName) {
            // Hide all tabs
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            // Deactivate all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab and activate button
            document.getElementById(tabName).classList.add('active');
            
            // Find the button that called this function (event handling simplified for demo)
            const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.textContent.includes(tabName === 'personal' ? 'Personal' : (tabName === 'organization' ? 'Organization' : 'Security')));
            if(clickedBtn) clickedBtn.classList.add('active');
            
            // Re-apply active class specifically to clicked button logic if needed more strictly
            event.currentTarget.classList.add('active');
        }

        // Edit Mode Logic
        function toggleEdit(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('.editable');
            const actions = form.querySelector('.form-actions');
            const toggleBtn = form.previousElementSibling.querySelector('.edit-toggle-btn');

            inputs.forEach(input => input.disabled = false);
            actions.style.display = 'block';
            toggleBtn.style.display = 'none';
        }

        function cancelEdit(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('.editable');
            const actions = form.querySelector('.form-actions');
            const toggleBtn = form.previousElementSibling.querySelector('.edit-toggle-btn');

            inputs.forEach(input => input.disabled = true);
            actions.style.display = 'none';
            toggleBtn.style.display = 'inline-block';
            // Ideally reset form values here to original state
        }

        function saveData(formId) {
            // Simulate saving
            const btn = document.querySelector(`#${formId} .save-btn`);
            const originalText = btn.textContent;
            
            btn.textContent = 'Saving...';
            btn.disabled = true;

            setTimeout(() => {
                btn.textContent = originalText;
                btn.disabled = false;
                alert('Profile updated successfully!');
                cancelEdit(formId);
            }, 1000);
        }
    </script>
</body>
</html>