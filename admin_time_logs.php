<?php
// admin_time_logs.php - Secured page for Admin role, fetches time logs
require_once "auth_check.php";

if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$time_logs = [];

// --- Database Query to Fetch All Time Logs (Last 7 days) ---
$sql_logs = "
    SELECT 
        tl.check_in_time, 
        tl.check_out_time,
        tl.location_lat,
        tl.location_lon,
        ed.employee_id,
        ed.full_name
    FROM time_logs tl
    JOIN employee_details ed ON tl.user_id = ed.user_id
    WHERE tl.check_in_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tl.check_in_time DESC
";

$result = $conn->query($sql_logs);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $time_logs[] = $row;
    }
}

// Function to calculate duration
function calculate_duration($in, $out) {
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
    <title>Review Time Logs | NeoEra Admin</title>
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

        .logo-area { padding: 30px 20px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%); border-radius: 10px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 1.2rem; color: white; }
        .logo-text { font-size: 1.2rem; font-weight: 600; letter-spacing: 0.5px; color: var(--text-color); }

        .nav-links { padding: 20px 0; flex: 1; }
        .nav-links ul { list-style: none; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 15px 25px; color: var(--text-muted); text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; font-weight: 500; }
        .nav-links li a:hover, .nav-links li.active a { background: rgba(102, 126, 234, 0.1); color: #667eea; border-left: 3px solid #667eea; }
        .nav-links i { width: 20px; text-align: center; }

        .user-profile-mini { padding: 20px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.5); }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 30px; transition: 0.3s ease; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { font-size: 1.8rem; font-weight: 600; color: var(--text-color); }
        .page-title p { color: var(--text-muted); }
        .mobile-toggle { display: none; font-size: 1.5rem; background: none; border: none; color: var(--text-color); cursor: pointer; }

        /* --- GLASS CARD & CONTROLS --- */
        .glass-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); height: 100%; }
        
        .controls-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .date-input, .search-input { padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); background: rgba(255,255,255,0.8); outline: none; color: var(--text-color); }
        
        .btn-report { padding: 10px 20px; background: var(--primary-gradient); color: white; border: none; border-radius: 10px; font-weight: 500; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-report:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }

        /* --- TABLE --- */
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 10px; }
        .styled-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .styled-table thead tr { background-color: rgba(102, 126, 234, 0.1); text-align: left; }
        .styled-table th { padding: 15px 20px; color: #667eea; font-weight: 600; font-size: 0.9rem; white-space: nowrap; }
        .styled-table td { padding: 12px 20px; color: var(--text-color); border-bottom: 1px solid rgba(0,0,0,0.05); vertical-align: middle; }
        .styled-table tr:hover { background-color: rgba(255,255,255,0.4); }

        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-completed { background: rgba(0, 184, 148, 0.1); color: #00b894; }
        .status-absent { background: rgba(253, 203, 110, 0.1); color: #e17055; }

        /* Location Text Style */
        .location-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .location-text i { color: #667eea; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: block; }
            .controls-bar { flex-direction: column; align-items: stretch; }
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
            .location-text { max-width: 100%; white-space: normal; overflow: visible; }

            /* Controls & touch targets */
            .mobile-toggle { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(0,0,0,0.03); }
            .btn-report { width: 100%; padding: 12px 16px; font-size: 0.95rem; }
            .table-responsive { padding: 6px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation" aria-hidden="false">
        <div class="logo-area">
            <div class="logo-icon">AD</div>
            <div class="logo-text">Admin Panel</div>
        </div>
        <nav class="nav-links">
            <ul>
                <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li><a href="admin_manage_users.php"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li class="active"><a href="admin_time_logs.php"><i class="fa-solid fa-clock-rotate-left"></i> Time Logs</a></li>
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
                <h1>Time Log Review</h1>
                <p>Monitor employee attendance and location.</p>
            </div>
            <button id="mobileToggle" class="mobile-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button> 
        </header>

        <div class="glass-card">
            <div class="controls-bar">
                <div class="filter-group">
                    <label style="font-size:0.9rem; color:var(--text-muted);">From:</label>
                    <input type="date" id="startDate" class="date-input" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    
                    <label style="font-size:0.9rem; color:var(--text-muted);">To:</label>
                    <input type="date" id="endDate" class="date-input" value="<?php echo date('Y-m-d'); ?>">
                    
                    <input type="text" id="employeeFilter" class="search-input" placeholder="Search by Employee..." onkeyup="filterLogs()">
                </div>
                <button type="button" id="exportCsv" class="btn-report" onclick="exportLogsCSV()" aria-label="Export visible logs to CSV">
                    <i class="fa-solid fa-download"></i> Export CSV
                </button>
            </div>

            <div class="table-responsive">
                <table class="styled-table" id="logsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($time_logs)): ?>
                            <?php foreach ($time_logs as $log): 
                                $in_time = new DateTime($log['check_in_time']);
                                $out_time = $log['check_out_time'] ? new DateTime($log['check_out_time']) : null;
                                $duration = calculate_duration($log['check_in_time'], $log['check_out_time']);
                                $status_text = $out_time ? "Completed" : "Active";
                                $status_class = $out_time ? "status-completed" : "status-absent";
                            ?>
                                <tr class="log-row">
                                    <td data-label="Date"><?php echo $in_time->format('M d, Y'); ?></td>
                                    <td data-label="Emp ID"><?php echo htmlspecialchars($log['employee_id']); ?></td>
                                    <td data-label="Name" style="font-weight: 500;"><?php echo htmlspecialchars($log['full_name']); ?></td>
                                    <td data-label="Check In"><?php echo $in_time->format('h:i A'); ?></td>
                                    <td data-label="Check Out"><?php echo $out_time ? $out_time->format('h:i A') : '-'; ?></td>
                                    <td data-label="Hours" style="font-weight: 600;"><?php echo $duration; ?></td>
                                    <td data-label="Status"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    
                                    <td data-label="Location">
                                        <span class="location-text" 
                                              data-lat="<?php echo $log['location_lat']; ?>" 
                                              data-lon="<?php echo $log['location_lon']; ?>">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Resolving...
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding: 30px;">No logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<script src="asset/js/admin_script.js"></script>

<script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // --- NEW & IMPROVED LOCATION RESOLVER (Uses BigDataCloud API) ---
        document.addEventListener('DOMContentLoaded', async function() {
            const locationElements = document.querySelectorAll('.location-text');
            
            for (const el of locationElements) {
                const lat = el.getAttribute('data-lat');
                const lon = el.getAttribute('data-lon');

                // Check if valid coordinates exist
                if (lat && lon && lat != 0 && lon != 0) {
                    try {
                        // Switching to BigDataCloud API (More reliable for client-side scripts)
                        const response = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=en`);
                        
                        if (response.ok) {
                            const data = await response.json();
                            
                            // Extract location details safely
                            const city = data.city || data.locality || data.principalSubdivision || 'Unknown Location';
                            const country = data.countryName || '';
                            
                            // Format: "Chennai, India"
                            let locationName = `${city}`;
                            if(country) locationName += `, ${country}`;
                            
                            // Update UI
                            el.innerHTML = `<i class="fa-solid fa-location-dot"></i> ${locationName}`;
                            el.title = `${locationName} (${lat}, ${lon})`;
                            el.style.color = "var(--text-color)";
                        } else {
                            throw new Error("API Response not OK");
                        }
                    } catch (error) {
                        console.error("Location error:", error);
                        // Fallback: Show numbers if even this API fails (e.g., no internet)
                        el.innerHTML = `<i class="fa-solid fa-map-pin"></i> ${parseFloat(lat).toFixed(4)}, ${parseFloat(lon).toFixed(4)}`;
                    }
                } else {
                    el.innerHTML = `<span style="color:#ccc;"><i class="fa-solid fa-ban"></i> No Data</span>`;
                }
            }

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

            // Export visible table rows to CSV
            window.exportLogsCSV = function() {
                const rows = Array.from(document.querySelectorAll('#logsTable tbody tr'));
                const visible = rows.filter(r => window.getComputedStyle(r).display !== 'none');
                if (visible.length === 0) {
                    alert('No visible logs to export.');
                    return;
                }

                const headers = ['Date','Emp ID','Name','Check In','Check Out','Hours','Status','Location','Lat','Lon'];
                const escapeCell = (s) => {
                    if (s == null) return '';
                    const str = String(s).replace(/"/g, '""');
                    return `"${str}"`;
                };

                const lines = [headers.map(escapeCell).join(',')];

                visible.forEach(row => {
                    const tds = row.querySelectorAll('td');
                    if (tds.length < 8) return; // skip malformed rows
                    const date = tds[0].innerText.trim();
                    const emp = tds[1].innerText.trim();
                    const name = tds[2].innerText.trim();
                    const cin = tds[3].innerText.trim();
                    const cout = tds[4].innerText.trim();
                    const hours = tds[5].innerText.trim();
                    const status = tds[6].innerText.trim();
                    const locEl = tds[7].querySelector('.location-text');
                    let locText = '';
                    let lat = '';
                    let lon = '';
                    if (locEl) {
                        locText = locEl.textContent.trim();
                        lat = locEl.getAttribute('data-lat') || '';
                        lon = locEl.getAttribute('data-lon') || '';
                    }

                    const rowArr = [date, emp, name, cin, cout, hours, status, locText, lat, lon];
                    lines.push(rowArr.map(escapeCell).join(','));
                });

                const csvContent = lines.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const ts = new Date().toISOString().slice(0,19).replace(/[:T]/g, '-');
                a.href = url;
                a.download = `time_logs_${ts}.csv`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            };

            // Filter logs by date range and employee name
            window.filterLogs = function(){
                const start = document.getElementById('startDate').value;
                const end = document.getElementById('endDate').value;
                const empQ = document.getElementById('employeeFilter').value.trim().toLowerCase();
                const rows = document.querySelectorAll('#logsTable tbody tr');

                rows.forEach(r => {
                    const tds = r.querySelectorAll('td');
                    const dateText = tds[0].innerText.trim();
                    // Parse readable date like 'Dec 17, 2025'
                    const rowDate = new Date(dateText);
                    let inRange = true;

                    if (start) {
                        const s = new Date(start + 'T00:00:00');
                        if (rowDate < s) inRange = false;
                    }
                    if (end) {
                        const e = new Date(end + 'T23:59:59');
                        if (rowDate > e) inRange = false;
                    }

                    const name = tds[2].innerText.trim().toLowerCase();
                    const matchesEmp = !empQ || name.includes(empQ);

                    if (inRange && matchesEmp) {
                        r.style.display = '';
                    } else {
                        r.style.display = 'none';
                    }
                });
            };

            // Wire date inputs to filter
            const sInput = document.getElementById('startDate');
            const eInput = document.getElementById('endDate');
            const empInput = document.getElementById('employeeFilter');
            if (sInput) sInput.addEventListener('change', window.filterLogs);
            if (eInput) eInput.addEventListener('change', window.filterLogs);
            if (empInput) empInput.addEventListener('input', window.filterLogs);

            // Run initial filter to apply defaults
            window.filterLogs();
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