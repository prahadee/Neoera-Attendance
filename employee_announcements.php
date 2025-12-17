<?php
// employee_announcements.php - Employee view for company news
require_once "auth_check.php";

// 1. Security Check
if ($user_role !== 'employee') {
    header("location: index.php");
    exit;
}

$first_two_letters = strtoupper(substr($full_name, 0, 2));
$announcements = [];

// 2. Map Employee Department to Database Target
$dept_target = 'other_dept';
if (stripos($department, 'software') !== false) {
    $dept_target = 'dept_software';
}

// 3. Fetch Active Announcements
$sql = "
    SELECT id, title, body, priority, published_on, target 
    FROM announcements 
    WHERE (target = 'all' OR target = 'employees' OR target = ?)
    AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ORDER BY priority DESC, published_on DESC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $dept_target);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | NeoEra Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- THEME VARIABLES --- */
        :root {
            --primary-gradient: linear-gradient(135deg, #d19931ff 0%, #c38728ff 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: 1px solid rgba(255, 255, 255, 0.6);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --text-color: #2d3748;
            --text-muted: #718096;
            --sidebar-width: 260px;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #ff7675;
            --info-color: #0984e3;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        html, body { height: 100%; }
        body {
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Prevent scrolling when sidebar is open */
        body.no-scroll { overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-right: var(--glass-border);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            box-shadow: var(--glass-shadow);
        }

        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 90; }
        .sidebar.active + .sidebar-backdrop { display: block; }
        .sidebar-backdrop.active { display: block; }

        .logo-area { padding: 24px 18px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .logo-icon { width: 40px; height: 40px; background: var(--primary-gradient); border-radius: 10px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 1.2rem; color: white; }
        .logo-text { font-size: 1.05rem; font-weight: 600; letter-spacing: 0.5px; color: var(--text-color); }

        .nav-links { padding: 18px 0; flex: 1; overflow-y: auto; }
        .nav-links ul { list-style: none; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 12px 20px; color: var(--text-muted); text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; font-weight: 500; }
        .nav-links li a:hover, .nav-links li.active a { background: rgba(102, 126, 234, 0.06); color: #d19931ff; border-left: 3px solid #d19931ff; }
        .nav-links i { width: 20px; text-align: center; }

        .user-profile-mini { padding: 14px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.5); }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-gradient); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 30px; transition: margin-left 0.3s ease; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; }
        .page-title h1 { font-size: 1.6rem; font-weight: 600; color: var(--text-color); }
        .page-title p { color: var(--text-muted); font-size: 0.95rem; }
        .mobile-toggle { display: none; font-size: 1.4rem; background: none; border: none; color: var(--text-color); cursor: pointer; }

        /* --- SEARCH BAR --- */
        .controls-bar {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
        }
        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 480px;
        }
        .search-wrapper input {
            width: 100%;
            padding: 10px 16px 10px 44px;
            border-radius: 999px;
            border: 1px solid rgba(0,0,0,0.08);
            background: var(--glass-bg);
            backdrop-filter: blur(8px);
            outline: none;
            transition: 0.2s;
            box-shadow: var(--glass-shadow);
            font-size: 0.95rem;
        }
        .search-wrapper input:focus {
            background: white;
            border-color: #d19931ff;
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.12);
        }
        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* --- NEWS CARDS --- */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .news-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .news-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.08); }

        /* Priority Styling */
        .priority-stripe { position: absolute; top: 0; left: 0; width: 6px; height: 100%; }
        .priority-high { background: var(--danger-color); }
        .priority-normal { background: #d19931ff; }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; padding-left: 10px; }

        .badges { display: flex; gap: 8px; flex-wrap: wrap; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-red { background: rgba(255, 118, 117, 0.15); color: var(--danger-color); }
        .badge-blue { background: rgba(102, 126, 234, 0.12); color: #d19931ff; }
        .badge-new { background: rgba(0, 184, 148, 0.12); color: #00b894; }

        .news-title { font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px; padding-left: 10px; line-height: 1.3; }

        .news-meta { padding-left: 10px; font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px; display: flex; gap: 12px; }
        .news-meta i { margin-right: 6px; color: #d19931ff; }

        .news-preview { padding-left: 10px; font-size: 0.92rem; color: #555; line-height: 1.6; margin-bottom: 16px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        .read-btn { margin-left: 10px; padding: 10px 18px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; color: #d19931ff; font-weight: 600; cursor: pointer; transition: 0.2s; align-self: flex-start; display: inline-flex; align-items: center; gap: 8px; }
        .read-btn:hover { background: #d19931ff; color: white; border-color: #d19931ff; }

        /* --- MODAL --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; width: 100%; max-width: 680px; border-radius: 18px; padding: 28px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.18); animation: slideUp 0.28s ease; max-height: 90vh; overflow-y: auto; }

        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .close-modal { position: absolute; top: 16px; right: 16px; background: #f1f2f6; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; color: #555; display: flex; justify-content: center; align-items: center; transition: 0.2s; }
        .close-modal:hover { background: #ff7675; color: white; }

        .modal-body-text { font-size: 1rem; line-height: 1.8; color: #333; margin-top: 20px; white-space: pre-wrap; }

        /* Responsive tweaks */
        @media (max-width: 992px) {
            .main-content { padding: 20px; }
            .search-wrapper { max-width: 360px; }
            .news-grid { gap: 18px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-110%); }
            .sidebar.active { transform: translateX(0); }
            .sidebar.active + .sidebar-backdrop { display: block; }
            .main-content { margin-left: 0; padding: 18px; }
            .mobile-toggle { display: block; }
            .news-grid { grid-template-columns: 1fr; }
            .page-title h1 { font-size: 1.3rem; }
            .controls-bar { flex-direction: column; align-items: stretch; gap: 12px; }
            .read-btn { width: 100%; justify-content: center; }
            .news-card { padding: 16px; border-radius: 12px; }
            .modal-content { max-width: 95%; padding: 18px; }
        }

        @media (max-width: 420px) {
            .search-wrapper input { padding-left: 40px; font-size: 0.9rem; }
            .page-title p { font-size: 0.9rem; }
            .logo-area { padding: 18px; }
            .nav-links li a { padding: 10px 14px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar" role="navigation" aria-hidden="false">
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
                <li><a href="my_tasks.php"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li class="active"><a href="employee_announcements.php"><i class="fa-solid fa-bullhorn"></i> Announcements</a></li>
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
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="page-title">
                <h1>Company News</h1>
                <p>Stay updated with the latest team alerts.</p>
            </div>
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-controls="sidebar" aria-expanded="false" id="mobileToggle" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
        </header>

        <div class="controls-bar">
            <div class="search-wrapper">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search announcements..." onkeyup="filterNews()">
            </div>
        </div>

        <div class="news-grid">
            <?php if (empty($announcements)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: var(--text-muted);">
                    <i class="fa-regular fa-folder-open" style="font-size: 3rem; opacity: 0.5; margin-bottom: 15px;"></i>
                    <p>No active announcements at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $news): 
                    $is_high = ($news['priority'] === 'high');
                    $stripe_class = $is_high ? 'priority-high' : 'priority-normal';
                    $badge_class = $is_high ? 'badge-red' : 'badge-blue';
                    $date = date('M j, Y', strtotime($news['published_on']));
                    
                    // Check if posted within last 3 days for "NEW" badge
                    $is_new = (strtotime($news['published_on']) > strtotime('-3 days'));
                ?>
                    <article class="news-card">
                        <div class="priority-stripe <?php echo $stripe_class; ?>"></div>
                        
                        <div class="card-header">
                            <div class="badges">
                                <?php if($is_new): ?><span class="badge badge-new">NEW</span><?php endif; ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $news['priority']; ?></span>
                            </div>
                            <i class="fa-solid fa-bullhorn" style="color: rgba(0,0,0,0.1); font-size: 1.5rem;"></i>
                        </div>

                        <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                        
                        <div class="news-meta">
                            <span><i class="fa-regular fa-clock"></i> <?php echo $date; ?></span>
                        </div>

                        <div class="news-preview">
                            <?php echo htmlspecialchars(substr($news['body'], 0, 150)) . '...'; ?>
                        </div>

                        <button class="read-btn" onclick='openModal(<?php echo json_encode($news); ?>)'>
                            Read Full Update <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="newsModal" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <button class="close-modal" onclick="closeModal()" aria-label="Close modal"><i class="fa-solid fa-times" aria-hidden="true"></i></button>
            
            <div style="margin-bottom: 15px;">
                <span id="modalBadge" class="badge badge-blue">NORMAL</span>
                <span id="modalDate" style="font-size: 0.85rem; color: #888; margin-left: 10px;"></span>
            </div>
            
            <h2 id="modalTitle" style="font-size: 1.8rem; margin-bottom: 20px; color: var(--text-color);"></h2>
            <hr style="border: 0; border-top: 1px solid #eee;">
            
            <div id="modalBody" class="modal-body-text"></div>
        </div>
    </div>

    <script>
        let sidebarLastFocused;
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('mobileToggle');
            const isActive = sidebar.classList.toggle('active');
            if (backdrop) backdrop.classList.toggle('active', isActive);
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
            sidebar.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            document.body.classList.toggle('no-scroll', isActive);

            if (isActive) {
                sidebarLastFocused = document.activeElement;
                const firstLink = sidebar.querySelector('a');
                if (firstLink) firstLink.focus();
            } else {
                if (sidebarLastFocused) sidebarLastFocused.focus();
            }
        }

        // Search Filter
        function filterNews() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.news-card');

            cards.forEach(card => {
                const title = card.querySelector('.news-title').innerText.toLowerCase();
                const body = card.querySelector('.news-preview').innerText.toLowerCase();
                
                if (title.includes(input) || body.includes(input)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Modal Logic
        const modal = document.getElementById('newsModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const modalBadge = document.getElementById('modalBadge');
        const modalDate = document.getElementById('modalDate');

        let modalLastFocused;
        function openModal(newsData) {
            modalLastFocused = document.activeElement;
            modalTitle.innerText = newsData.title;
            modalBody.innerText = newsData.body; // Using innerText handles \n as line breaks
            
            // Format Date
            const d = new Date(newsData.published_on);
            modalDate.innerText = d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' });

            // Badge Style
            modalBadge.innerText = newsData.priority.toUpperCase();
            modalBadge.className = (newsData.priority === 'high') ? 'badge badge-red' : 'badge badge-blue';

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
            document.querySelector('main').setAttribute('aria-hidden', 'true');

            const closeBtn = modal.querySelector('.close-modal');
            if (closeBtn) closeBtn.focus();
        }

        function closeModal() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
            document.querySelector('main').removeAttribute('aria-hidden');
            if (modalLastFocused) modalLastFocused.focus();
        }

        // Close modal when clicking outside or pressing Escape
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (modal.classList.contains('active')) {
                    closeModal();
                    return;
                }
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });

        // Focus trap for modal when open
        modal.addEventListener('keydown', function(e) {
            if (!modal.classList.contains('active')) return;
            const focusable = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length -1];
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
    </script>
</body>
</html>