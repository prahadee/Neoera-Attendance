<?php
// apply_leave.php - Secured and handles Leave Request form submission
require_once "auth_check.php";

// Ensure only employees access this page
if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$leave_balance = 15; // Placeholder balance (Fetch from DB in real app)

$submission_message = "";
$error_type = ""; // 'success' or 'error'

// -------------------------------------------------------------------
// LEAVE SUBMISSION LOGIC
// -------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Get and Sanitize Input
    $leave_type = trim($_POST['leaveType']);
    $start_date = trim($_POST['startDate']);
    $end_date = trim($_POST['endDate']);
    $total_days = floatval($_POST['hiddenTotalDays']);
    $reason = trim($_POST['reason']);
    
    // Simple Validation
    if (empty($leave_type) || empty($start_date) || empty($end_date) || $total_days <= 0 || empty($reason)) {
        $submission_message = "Please fill in all required fields and select a valid duration.";
        $error_type = "error";
    } elseif ($total_days > $leave_balance) { // Simple balance check
        $submission_message = "Requested days exceed your available leave balance ($leave_balance days).";
        $error_type = "error";
    } else {
        // 2. Prepare SQL INSERT Statement
        // Note: Ensure your DB has a 'leave_requests' table with these columns
        $sql = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isssds", $user_id, $leave_type, $start_date, $end_date, $total_days, $reason);
            
            if ($stmt->execute()) {
                $submission_message = "Leave request submitted successfully! Awaiting Admin approval.";
                $error_type = "success";
                // Optionally decrease $leave_balance here for display purposes
            } else {
                $submission_message = "Error submitting request: " . $stmt->error;
                $error_type = "error";
            }
            $stmt->close();
        } else {
             // Fallback if table doesn't exist or SQL error
             $submission_message = "Database Error: Could not prepare statement. " . $conn->error;
             $error_type = "error";
        }
    }
    // Close connection after POST processing
    $conn->close(); 
}

// Re-establish connection if needed (handled by auth_check usually)
if (!isset($conn) || $conn->connect_error) {
    require_once "db_config.php";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave | NeoEra Infotech</title>
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

        /* --- LEAVE FORM LAYOUT --- */
        .leave-container {
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
        }

        /* Form Inputs */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
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
            border-color: #d19931ff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Balance Card Styling */
        .balance-card {
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .balance-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 8px solid rgba(102, 126, 234, 0.2);
            border-top-color: #d19931ff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 20px auto;
            position: relative;
        }

        .balance-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1;
        }

        .balance-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Messages */
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }
        .alert-success { background: rgba(0, 184, 148, 0.15); color: #00b894; border: 1px solid rgba(0, 184, 148, 0.3); }
        .alert-error { background: rgba(255, 118, 117, 0.15); color: #d63031; border: 1px solid rgba(255, 118, 117, 0.3); }

        /* Buttons */
        .btn-submit {
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
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        .btn-reset {
            padding: 12px 25px;
            background: transparent;
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-reset:hover { background: var(--danger-color); color: white; }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .leave-container { grid-template-columns: 1fr; }
            .balance-card { display: flex; align-items: center; justify-content: space-between; padding: 20px 40px; }
            .balance-circle { margin: 0; width: 80px; height: 80px; border-width: 5px; }
            .balance-number { font-size: 1.5rem; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
            .balance-card { flex-direction: column; text-align: center; gap: 15px; }
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
                <li class="active"><a href="apply_leave.php"><i class="fa-solid fa-calendar-days"></i> Apply Leave</a></li>
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
                <h1>Leave Application</h1>
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

        <div class="leave-container">
            
            <div class="glass-card">
                <h3 style="margin-bottom: 25px; color: var(--text-color);">New Request</h3>
                
                <form id="leaveRequestForm" action="apply_leave.php" method="POST"> 
                    <div class="form-group">
                        <label for="leaveType">Leave Type</label>
                        <select id="leaveType" name="leaveType" required>
                            <option value="" disabled selected>Select Type</option>
                            <option value="annual">Annual Leave (PTO)</option>
                            <option value="sick">Sick Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="wfh">Work From Home Request</option>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="startDate" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" name="endDate" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Calculated Duration</label>
                        <input type="text" id="totalDaysDisplay" value="0 Days" disabled 
                               style="background: rgba(102, 126, 234, 0.1); color: #d19931ff; font-weight: 600; border-color: transparent;">
                        <input type="hidden" id="hiddenTotalDays" name="hiddenTotalDays" value="0"> 
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason for Leave</label>
                        <textarea id="reason" name="reason" rows="4" placeholder="Briefly describe why you are requesting leave..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-reset">Reset</button>
                        <button type="submit" class="btn-submit">
                            <i class="fa-solid fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-card balance-card">
                <div>
                    <h3 style="margin-bottom: 5px; color: var(--text-color);">Your Balance</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Annual Paid Leave</p>
                </div>
                
                <div class="balance-circle">
                    <div class="balance-number"><?php echo $leave_balance; ?></div>
                    <div class="balance-label">Days Left</div>
                </div>

                <div style="margin-top: 20px; text-align: left;">
                    <h4 style="font-size: 0.95rem; margin-bottom: 10px;">Policy Note:</h4>
                    <ul style="font-size: 0.85rem; color: var(--text-muted); padding-left: 20px; line-height: 1.6;">
                        <li>Sick leave requires a certificate if exceeding 2 days.</li>
                        <li>Annual leave must be applied 1 week in advance.</li>
                    </ul>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // --- Date Calculation Logic ---
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const totalDaysDisplay = document.getElementById('totalDaysDisplay');
            const hiddenTotalDays = document.getElementById('hiddenTotalDays');

            function calculateDays() {
                const startVal = startDateInput.value;
                const endVal = endDateInput.value;

                if (startVal && endVal) {
                    const start = new Date(startVal);
                    const end = new Date(endVal);

                    // Ensure end date is not before start date
                    if (end < start) {
                        totalDaysDisplay.value = "Invalid Range";
                        hiddenTotalDays.value = 0;
                        return;
                    }

                    // Calculate difference in time
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include start date

                    totalDaysDisplay.value = diffDays + (diffDays === 1 ? " Day" : " Days");
                    hiddenTotalDays.value = diffDays;
                } else {
                    totalDaysDisplay.value = "0 Days";
                    hiddenTotalDays.value = 0;
                }
            }

            startDateInput.addEventListener('change', calculateDays);
            endDateInput.addEventListener('change', calculateDays);
        });
    </script>
</body>
</html>