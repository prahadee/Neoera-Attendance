<?php
// employee_dashboard.php - Secured, dynamic, and handles Time Clock logic
require_once "auth_check.php";

// -------------------------------------------------------------------
// 1. SECURITY CHECK
// -------------------------------------------------------------------
if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

// -------------------------------------------------------------------
// 2. TIME CLOCK LOGIC (PHP Functions)
// -------------------------------------------------------------------

// Function to get the user's current time clock status
function getCurrentStatus($conn, $user_id) {
    $status = ['checked_in' => false, 'log_id' => null, 'in_time' => null];
    
    // Find the last record where check_out_time is NULL
    $sql = "SELECT id, check_in_time FROM time_logs 
            WHERE user_id = ? AND check_out_time IS NULL 
            ORDER BY check_in_time DESC LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $status['checked_in'] = true;
            $status['log_id'] = $row['id'];
            $status['in_time'] = (new DateTime($row['check_in_time']))->format('h:i A');
        }
        $stmt->close();
    }
    return $status;
}

// Get initial status for display
$time_status = getCurrentStatus($conn, $user_id);
$is_checked_in = $time_status['checked_in'];
$initial_time_status_text = $is_checked_in ? "Checked In @ " . $time_status['in_time'] : "Currently Checked Out";

// -------------------------------------------------------------------
// 3. AJAX REQUEST HANDLER (Handles Check-in/Check-out POSTs)
// -------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    $current_time = date('Y-m-d H:i:s');
    
    if ($action === 'check_in') {
        $sql = "INSERT INTO time_logs (user_id, check_in_time, location_lat, location_lon) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isdd", $user_id, $current_time, $_POST['lat'], $_POST['lon']);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Check-in successful!";
                $response['new_time'] = (new DateTime())->format('h:i A');
            } else {
                $response['message'] = "Database Error on Check-in.";
            }
            $stmt->close();
        }
    } 
    elseif ($action === 'check_out') {
        $open_log = getCurrentStatus($conn, $user_id);
        
        if ($open_log['checked_in']) {
            $sql = "UPDATE time_logs SET check_out_time = ? WHERE id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $current_time, $open_log['log_id']);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Check-out successful!";
                } else {
                    $response['message'] = "Database Error on Check-out.";
                }
                $stmt->close();
            }
        } else {
             $response['message'] = "Error: Not currently checked in.";
        }
    }
    
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; 
}

// -------------------------------------------------------------------
// 4. FRONTEND DATA POPULATION
// -------------------------------------------------------------------
$pending_task_count = 3; 
$leave_balance = 15;     
$first_two_letters = strtoupper(substr($full_name, 0, 2));

$conn->close(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | NeoEra Infotech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- NEW LIGHT THEME VARIABLES --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #d19931ff 0%, #c38728ff 100%);
            /* More opaque white for glass on light background */
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            /* Dark text colors */
            --text-color: #2d3748;
            --text-muted: #718096;
            --sidebar-width: 260px;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #ff7675;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Clean light gradient background */
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
        }

        /* --- SIDEBAR (Light Glass) --- */
        .sidebar {
            width: var(--sidebar-width);
            /* Light glass sidebar */
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

        .nav-links ul {
            list-style: none;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: var(--text-muted); /* Darker nav links */
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .nav-links li a:hover, .nav-links li.active a {
            background: rgba(102, 126, 234, 0.1); /* Light purple hover */
            color: #d19931ff; /* Primary color on active/hover */
            border-left: 3px solid #d19931ff;
        }

        .nav-links i {
            width: 20px;
            text-align: center;
        }

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

        .user-info-mini h4 {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .user-info-mini span {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* --- MAIN CONTENT --- */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: 0.3s ease;
        }

        /* Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .welcome-section p {
            color: var(--text-muted);
        }

        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
        }

        /* --- WIDGET GRID SYSTEM --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 25px;
        }

        /* Glass Cards (Light Theme) */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .glass-card h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
        }
        
        .glass-card h3 i {
            color: #d19931ff; /* Icon color */
        }

        /* Time Clock Widget (Big Feature) */
        .time-clock-widget {
            grid-column: span 6;
            /* Subtle gradient for the main widget */
            background: linear-gradient(145deg, rgba(255,255,255,0.9) 0%, rgba(240,242,245,0.8) 100%);
        }

        .clock-display {
            text-align: center;
            padding: 10px 0;
        }

        .status-text {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: var(--success-color);
        }

        .status-text.checked-out {
            color: var(--text-muted);
        }

        .action-btn {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff7675, #d63031);
            color: white;
        }

        .btn-outline {
            background: white;
            border: 1px solid #e2e8f0;
            color: var(--text-color);
            margin-top: 10px;
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        .location-info {
            text-align: center;
            font-size: 0.8rem;
            margin-top: 15px;
            color: var(--text-muted);
        }

        /* Stats Cards */
        .stat-card {
            grid-column: span 3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: var(--text-color);
        }

        .stat-icon-bg {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.05;
            color: #d19931ff;
            transform: rotate(-15deg);
        }

        /* Tasks List */
        .tasks-widget {
            grid-column: span 8;
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: 0.2s;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-item:hover {
            background: rgba(0,0,0,0.02);
        }

        .task-info label {
            display: block;
            font-weight: 500;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .task-due {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        .text-red { color: #c0392b; background: rgba(255, 118, 117, 0.2); }
        .text-orange { color: #d35400; background: rgba(253, 203, 110, 0.2); }
        .text-green { color: #27ae60; background: rgba(0, 184, 148, 0.2); }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: white;
            color: var(--text-color);
            transition: 0.2s;
        }

        .btn-sm:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        /* Profile & News */
        .profile-widget {
            grid-column: span 4;
        }

        .profile-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--text-color);
        }
        
        .profile-detail-row strong {
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Announcements */
        .news-widget {
            grid-column: span 12;
        }

        .news-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--text-color);
        }
        .news-date {
            color: #d19931ff;
            font-weight: 600;
            font-size: 0.85rem;
            min-width: 90px;
        }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 1024px) {
            .time-clock-widget { grid-column: span 12; }
            .stat-card { grid-column: span 6; }
            .tasks-widget { grid-column: span 12; }
            .profile-widget { grid-column: span 12; }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
            }
            .stat-card { grid-column: span 12; }
            .top-header { flex-direction: row-reverse; justify-content: space-between; }
            .glass-card { padding: 20px; }
        }
    </style>
</head>
<body data-initial-checkin="<?php echo $is_checked_in ? 'true' : 'false'; ?>">

    <aside class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-icon">NE</div>
            <div class="logo-text">NeoEra Portal</div>
        </div>
        
        <nav class="nav-links">
            <ul>
                <li class="active"><a href="employee_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="employee_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
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
                <h4><?php echo htmlspecialchars($full_name); ?></h4>
                <span><?php echo htmlspecialchars($job_title); ?></span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        
        <header class="top-header">
            <div class="welcome-section">
                <h1>Hello, <?php echo explode(' ', $full_name)[0]; ?></h1>
                <p>Here's what's happening today.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <div class="dashboard-grid">

            <div class="glass-card time-clock-widget" id="timeClockWidget">
                <h3><i class="fa-solid fa-fingerprint"></i> Smart Attendance</h3>
                
                <div class="clock-display">
                    <div class="status-text <?php echo $is_checked_in ? '' : 'checked-out'; ?>" id="timeStatus">
                        <?php echo $initial_time_status_text; ?>
                    </div>
                    
                    <div class="camera-wrapper" style="overflow: hidden; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <video id="liveCameraFeed" autoplay muted style="display:none; width: 100%;"></video>
                        <canvas id="photoCanvas" style="display:none;"></canvas>
                    </div>

                    <button id="cameraToggleBtn" class="action-btn btn-outline">
                        <i class="fa-solid fa-camera"></i> Enable Camera Verification
                    </button>
                    
                    <button id="captureSelfieBtn" class="action-btn btn-danger" style="display:none; margin-top:10px;">
                        <i class="fa-solid fa-circle-dot"></i> Capture & Verify
                    </button>

                    <div style="margin-top: 20px;">
                        <button class="action-btn btn-primary" id="checkInOutBtn" disabled>
                            <?php echo $is_checked_in ? '<i class="fa-solid fa-person-walking-arrow-right"></i> Check Out' : '<i class="fa-solid fa-person-walking-arrow-right"></i> Check In'; ?>
                        </button>
                    </div>

                    <p class="location-info" id="locationStatus"><i class="fa-solid fa-location-dot"></i> Waiting for location...</p>
                    <p id="error-message" style="color: var(--danger-color); font-size: 0.9em; margin-top: 10px; display:none;"></p>
                </div>
            </div>

            <div class="glass-card stat-card">
                <h3><i class="fa-solid fa-umbrella-beach"></i> Leave Balance</h3>
                <div class="stat-value"><?php echo $leave_balance; ?> <span style="font-size: 1rem; opacity: 0.6; font-weight: 400;">Days</span></div>
                <button class="btn-sm" onclick="window.location.href='apply_leave.php'">Apply Now &rarr;</button>
                <i class="fa-solid fa-plane stat-icon-bg"></i>
            </div>

            <div class="glass-card stat-card">
                <h3><i class="fa-solid fa-bell"></i> Pending Tasks</h3>
                <div class="stat-value" id="pendingTaskCount"><?php echo $pending_task_count; ?></div>
                <button class="btn-sm" onclick="window.location.href='my_tasks.php'">View All &rarr;</button>
                <i class="fa-solid fa-clipboard-list stat-icon-bg"></i>
            </div>

            <div class="glass-card profile-widget">
                <h3><i class="fa-solid fa-id-card"></i> ID Card</h3>
                <div style="margin-top: 15px;">
                    <div class="profile-detail-row">
                        <strong>ID:</strong> <span><?php echo $employee_id; ?></span>
                    </div>
                    <div class="profile-detail-row">
                        <strong>Dept:</strong> <span><?php echo $department; ?></span>
                    </div>
                    <div class="profile-detail-row">
                        <strong>Email:</strong> <span><?php echo $work_email; ?></span>
                    </div>
                    <button class="btn-sm" style="width: 100%; margin-top: 15px;" onclick="window.location.href='employee_profile.php'">Edit Profile</button>
                </div>
            </div>

            <div class="glass-card tasks-widget">
                <h3><i class="fa-solid fa-list-check"></i> Priority Tasks</h3>
                <div class="task-list-container" id="taskList">
                    <div class="task-item">
                        <div class="task-info">
                            <label>Complete Annual Security Training</label>
                            <span class="task-due text-red"><i class="fa-solid fa-circle-exclamation"></i> Due: 2 days</span>
                        </div>
                        <button class="btn-sm">Mark Done</button>
                    </div>
                    <div class="task-item">
                        <div class="task-info">
                            <label>Submit Q4 Expense Report</label>
                            <span class="task-due text-orange">Due: Dec 15</span>
                        </div>
                        <button class="btn-sm">Mark Done</button>
                    </div>
                    <div class="task-item">
                        <div class="task-info">
                            <label style="text-decoration: line-through; opacity: 0.5;">Review Project X Docs</label>
                            <span class="task-due text-green">Completed</span>
                        </div>
                        <button class="btn-sm" disabled>Done</button>
                    </div>
                </div>
            </div>

            <div class="glass-card news-widget">
                <h3><i class="fa-solid fa-bullhorn"></i> Company News</h3>
                <div class="news-item">
                    <span class="news-date">[12:35 PM]</span>
                    <span><strong>HR Update:</strong> New work-from-home policy has been updated. Please review in the portal.</span>
                </div>
                <div class="news-item">
                    <span class="news-date">[Yesterday]</span>
                    <span><strong>IT Alert:</strong> Server maintenance scheduled for tonight 11 PM - 1 AM IST.</span>
                </div>
            </div>

        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
    
<script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            
            // --- Elements ---
            const checkInOutBtn = document.getElementById('checkInOutBtn');
            const timeStatusEl = document.getElementById('timeStatus');
            const locationStatusEl = document.getElementById('locationStatus');
            const errorMsgEl = document.getElementById('error-message');
            const cameraToggleBtn = document.getElementById('cameraToggleBtn');
            const captureSelfieBtn = document.getElementById('captureSelfieBtn');
            const videoElement = document.getElementById('liveCameraFeed');
            const canvasElement = document.getElementById('photoCanvas');

            // --- State ---
            let isCheckedIn = document.body.getAttribute('data-initial-checkin') === 'true'; 
            let currentLocation = null; 
            let localStream = null; 
            let selfieCaptured = false; 

            // --- 1. SMART LOCATION LOGIC (The Fix) ---
            function captureLocation() {
                if (!locationStatusEl) return;
                
                locationStatusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
                if(errorMsgEl) errorMsgEl.style.display = 'none';

                if ("geolocation" in navigator) {
                    // Try High Accuracy (GPS) first
                    navigator.geolocation.getCurrentPosition(
                        handleSuccess, 
                        (error) => {
                            console.warn("GPS failed, trying Wi-Fi location...");
                            // If GPS fails, try Low Accuracy (Wi-Fi/IP) automatically
                            if(error.code !== error.PERMISSION_DENIED) { 
                                navigator.geolocation.getCurrentPosition(
                                    handleSuccess, 
                                    handleError, 
                                    { enableHighAccuracy: false, timeout: 10000 }
                                );
                            } else {
                                handleError(error); // User actually clicked Block
                            }
                        }, 
                        { enableHighAccuracy: true, timeout: 5000 }
                    );
                } else {
                    locationStatusEl.innerHTML = "Geolocation not supported";
                }
            }

            function handleSuccess(position) {
                currentLocation = {
                    lat: position.coords.latitude,
                    lon: position.coords.longitude
                };
                locationStatusEl.innerHTML = '<i class="fa-solid fa-location-dot"></i> Location Verified';
                locationStatusEl.style.color = "var(--success-color)";
                checkPrerequisites();
            }

            function handleError(error) {
                let msg = "Location Failed.";
                if (error.code === error.PERMISSION_DENIED) {
                    msg = "Access Denied. Please reload page or reset permissions.";
                } else if (error.code === error.TIMEOUT) {
                    msg = "Location timed out. Please try again.";
                }
                locationStatusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + msg;
                locationStatusEl.style.color = "var(--danger-color)";
            }

            // --- 2. CAMERA LOGIC ---
            async function startCamera() {
                try {
                    if(errorMsgEl) errorMsgEl.style.display = 'none';
                    localStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    videoElement.srcObject = localStream;
                    videoElement.style.display = 'block';
                    cameraToggleBtn.style.display = 'none';
                    captureSelfieBtn.style.display = 'inline-flex';
                } catch (err) {
                    alert("Camera Error: " + err.message);
                }
            }

            if (captureSelfieBtn) {
                captureSelfieBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!localStream) return;
                    canvasElement.width = videoElement.videoWidth;
                    canvasElement.height = videoElement.videoHeight;
                    canvasElement.getContext('2d').drawImage(videoElement, 0, 0);
                    videoElement.style.border = "3px solid #00b894"; 
                    captureSelfieBtn.innerHTML = '<i class="fa-solid fa-check"></i> Verified';
                    captureSelfieBtn.disabled = true;
                    selfieCaptured = true;
                    checkPrerequisites();
                });
            }

            function stopCamera() {
                if (localStream) localStream.getTracks().forEach(track => track.stop());
                videoElement.style.display = 'none';
                captureSelfieBtn.style.display = 'none';
                cameraToggleBtn.style.display = 'inline-flex';
            }

            if (cameraToggleBtn) cameraToggleBtn.addEventListener('click', (e) => { e.preventDefault(); startCamera(); });

            function checkPrerequisites() {
                let ready = !!currentLocation;
                if (localStream && !selfieCaptured) ready = false; 
                if (checkInOutBtn) checkInOutBtn.disabled = !ready;
            }

            // --- 3. CHECK IN/OUT ACTION ---
            if (checkInOutBtn) {
                checkInOutBtn.addEventListener('click', function() {
                    const actionType = isCheckedIn ? 'check_out' : 'check_in';
                    const formData = new FormData();
                    formData.append('action', actionType);
                    if (currentLocation) {
                        formData.append('lat', currentLocation.lat);
                        formData.append('lon', currentLocation.lon);
                    }

                    const originalText = checkInOutBtn.innerHTML;
                    checkInOutBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                    checkInOutBtn.disabled = true;

                    fetch('employee_dashboard.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            isCheckedIn = !isCheckedIn;
                            timeStatusEl.innerHTML = isCheckedIn ? ("Checked In @ " + data.new_time) : "Checked Out just now";
                            timeStatusEl.className = isCheckedIn ? "status-text" : "status-text checked-out";
                            checkInOutBtn.innerHTML = isCheckedIn ? '<i class="fa-solid fa-person-walking-arrow-right"></i> Check Out' : '<i class="fa-solid fa-person-walking-arrow-right"></i> Check In';
                            stopCamera();
                            selfieCaptured = false;
                            videoElement.style.border = "none";
                            alert(data.message);
                        } else {
                            alert(data.message);
                            checkInOutBtn.innerHTML = originalText;
                        }
                        checkInOutBtn.disabled = false;
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Connection Error");
                        checkInOutBtn.innerHTML = originalText;
                        checkInOutBtn.disabled = false;
                    });
                });
            }

            // Init
            captureLocation();
        });
    </script>
</body>
</html>