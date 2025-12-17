document.addEventListener('DOMContentLoaded', function() {
    
    // --- Global Time Clock Elements (Placeholders) ---
    const checkInOutBtn = document.getElementById('checkInOutBtn');
    const timeStatusEl = document.getElementById('timeStatus');
    const timeClockWidget = document.getElementById('timeClockWidget');
    const locationStatusEl = document.getElementById('locationStatus');
    const errorMsgEl = document.getElementById('error-message');
    
    // --- Camera Elements (Placeholders) ---
    const cameraToggleBtn = document.getElementById('cameraToggleBtn');
    const captureSelfieBtn = document.getElementById('captureSelfieBtn');
    const videoElement = document.getElementById('liveCameraFeed');
    const canvasElement = document.getElementById('photoCanvas');

    // Get initial state from PHP embedded in the body tag
    let isCheckedIn = document.body.getAttribute('data-initial-checkin') === 'true'; 
    let currentLocation = null; 
    let localStream = null; 
    let selfieCaptured = false; 

    // ==============================================
    // --- LOCAL STORAGE FUNCTIONS (FOR TASK PERSISTENCE) ---
    // ==============================================

    const STORAGE_KEY = 'completedTasks';

    function loadCompletedTasks() {
        const completed = localStorage.getItem(STORAGE_KEY);
        return completed ? JSON.parse(completed) : [];
    }

    function saveTaskAsComplete(taskId) {
        const taskIdString = String(taskId); 
        const completedTasks = loadCompletedTasks();
        if (!completedTasks.includes(taskIdString)) {
            completedTasks.push(taskIdString);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(completedTasks));
        }
        applyCompletedStatus(taskIdString);
    }
    
    function removeTaskFromCompleted(taskId) {
        const taskIdString = String(taskId);
        let completedTasks = loadCompletedTasks();
        
        // Filter out the deleted task ID
        completedTasks = completedTasks.filter(id => id !== taskIdString);
        
        localStorage.setItem(STORAGE_KEY, JSON.stringify(completedTasks));
    }

    // 3. Applies completion status to all matching elements on the page (Dashboard & My Tasks)
    function applyCompletedStatus(taskId) {
        const taskItems = document.querySelectorAll(`.task-item[data-task-id="${taskId}"]`);
        
        taskItems.forEach(taskItem => {
            if (taskItem.classList.contains('completed')) return; 

            taskItem.classList.add('completed');
            taskItem.setAttribute('data-status', 'completed');
            
            // 1. Update the Action cell
            const actionCell = taskItem.querySelector('.action-buttons-cell');
            if (actionCell) {
                // Find the delete form which is now permanent
                const deleteForm = actionCell.querySelector('.delete-task-btn').closest('form');
                
                // Clear the cell content (removing 'Mark Done' button)
                actionCell.innerHTML = '';
                
                // Add Completed badge
                const completedBadge = document.createElement('span');
                completedBadge.className = 'status-badge status-completed';
                completedBadge.textContent = 'Completed';
                actionCell.appendChild(completedBadge);
                
                // Re-append the delete form
                if (deleteForm) {
                    actionCell.appendChild(deleteForm);
                }
            }
            
            // 2. Update Status Badge (if separately present, like in dashboard view)
            const statusBadge = taskItem.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = 'Completed';
                statusBadge.classList.remove('status-absent');
                statusBadge.classList.add('status-completed');
            }

            // 3. Update Due Date Text/Style
            let taskDueEl = taskItem.querySelector('.task-due');
            
            if (taskItem.classList.contains('task-due')) {
                taskDueEl = taskItem;
            }

            if (taskDueEl) {
                taskDueEl.textContent = 'Completed';
                taskDueEl.classList.remove('red-text', 'orange-text');
                taskDueEl.classList.add('green-text');
            }
        });
        updatePendingCount();
    }
    
    // ==============================================
    // --- TIME CLOCK LOGIC (PLACEHOLDER) ---
    // ==============================================
    async function handleCheckInOut(actionType) {
        if (errorMsgEl) errorMsgEl.style.display = 'none';
        // ... (Time clock logic unchanged for brevity)
    }
    
    function captureLocation() {
        if (!locationStatusEl) return;
        // ... (Location logic unchanged for brevity)
    }
    
    function stopCamera() {
        // ... (Camera logic unchanged for brevity)
    }
    
    function startCamera() {
        // ... (Camera logic unchanged for brevity)
    }
    
    if (cameraToggleBtn) cameraToggleBtn.addEventListener('click', startCamera);
    
    if (captureSelfieBtn) {
        captureSelfieBtn.addEventListener('click', function() {
           // ... (Selfie logic unchanged for brevity)
        });
    }
    
    function checkPrerequisites() {
        // ... (Prerequisites logic unchanged for brevity)
    }
    
    function updateTimeClock(checkedIn, newTime = null) {
       // ... (Time clock update logic unchanged for brevity)
    }
    
    if (checkInOutBtn) {
        checkInOutBtn.addEventListener('click', function() {
            const actionType = isCheckedIn ? 'check_out' : 'check_in';
            // handleCheckInOut(actionType); // Commented out to prevent errors with missing employee_dashboard.php
        });
    }

    if (document.getElementById('timeClockWidget')) {
        // captureLocation(); // Commented out to prevent geolocation errors
    }


    // ==============================================
    // --- TASK MANAGEMENT LOGIC (FIXED) ---
    // ==============================================
    const pendingTaskCountEl = document.getElementById('pendingTaskCount');
    
    function getPendingCount() {
        return document.querySelectorAll('.task-item:not(.completed)').length;
    }

    function updatePendingCount() {
        const count = getPendingCount();
        if (pendingTaskCountEl) {
            pendingTaskCountEl.textContent = count + (count === 1 ? ' Item' : ' Items');
        }
    }
    
    // --- Centralized Click Handler ---
    document.addEventListener('click', function(event) {
        const target = event.target;
        
        // 1. Handle Mark Done
        if (target.classList.contains('mark-done-btn')) {
            const taskItem = target.closest('.task-item');
            const taskId = taskItem ? taskItem.getAttribute('data-task-id') : null;
            
            if (!taskItem || !taskId || taskItem.classList.contains('completed')) return;

            alert(`Task ID ${taskId} marked as complete (Saved locally).`); 
            
            // Use the centralized save function, which triggers UI updates
            saveTaskAsComplete(taskId); 
        }
        
        // 2. Handle Delete (Removes Local Storage status before PHP redirects and removes session data)
        if (target.classList.contains('delete-task-btn')) {
            const taskId = target.getAttribute('data-task-id');
            if (taskId) {
                // CRITICAL: Ensure the completed status is removed from local storage 
                // BEFORE the PHP redirect occurs, so it doesn't try to mark the
                // deleted task completed upon page reload.
                removeTaskFromCompleted(taskId);
                
                // Note: The form submission will handle the PHP deletion and page redirect.
            }
        }
    });

    // --- Sidebar Toggle Logic (Handles Hamburger menu) ---
    const menuToggleBtn = document.getElementById('menuToggleBtn');
    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });

        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768 && document.body.classList.contains('sidebar-open')) {
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
    }

    // ==============================================
    // --- INITIALIZATION: Apply stored status on page load ---
    // ==============================================
    const completedTasksOnLoad = loadCompletedTasks();
    if (completedTasksOnLoad.length > 0) {
        completedTasksOnLoad.forEach(applyCompletedStatus);
    }
    
    updatePendingCount(); 
});