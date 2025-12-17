document.addEventListener('DOMContentLoaded', function() {
    
    // --- AJAX Function to Handle Leave Actions ---
    async function handleLeaveAction(action, requestId, employeeName) {
        if (action === 'reject') {
            if (!confirm(`WARNING: Are you sure you want to REJECT the request (${requestId}) from ${employeeName}?`)) {
                return; 
            }
        }
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('requestId', requestId);

        try {
            const response = await fetch('process_leave_action.php', {
                method: 'POST',
                body: formData 
            });
            const data = await response.json();

            if (data.success) {
                alert(`SUCCESS: ${data.message}`);
                
                const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
                if (row) {
                    const statusCell = row.querySelector('.status-badge');
                    const actionsCell = row.querySelector('.action-buttons-cell');
                    
                    const statusText = data.newStatus;
                    const statusClass = statusText === 'Approved' ? 'status-completed' : 'status-absent';

                    statusCell.textContent = statusText;
                    statusCell.className = `status-badge ${statusClass}`; 
                    actionsCell.innerHTML = '-';
                    row.setAttribute('data-status', statusText.toLowerCase());
                    row.style.backgroundColor = 'white'; 
                }
            } else {
                alert(`Error performing action: ${data.message}`);
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            alert('A network error occurred while communicating with the server.');
        }
    }
    
    // --- AJAX Function for User Management ---
    async function handleUserAction(action, userId) {
        if (action === 'delete' && !confirm(`WARNING: Are you sure you want to PERMANENTLY DELETE User ID ${userId}?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('userId', userId);
        
        try {
            const response = await fetch('process_admin_action.php', {
                method: 'POST',
                body: formData 
            });
            const data = await response.json();

            alert(data.message);
            
            if (data.success && action === 'delete') {
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) row.remove();
            }
            
        } catch (error) {
            console.error('AJAX Error:', error);
            alert('A network error occurred while communicating with the server.');
        }
    }
    
    // ==============================================
    // --- Dashboard Link Handlers ---
    // ==============================================

    document.querySelectorAll('.recent-requests .review-btn').forEach(button => {
        button.addEventListener('click', function() {
            window.location.href = 'admin_leave_requests.php'; 
        });
    });
    
    
    // ==============================================
    // --- User Management Button Handlers (AJAX INTEGRATED) ---
    // ==============================================
    document.querySelectorAll('.user-management-table button').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const userId = row.getAttribute('data-user-id');
            const action = this.getAttribute('data-action');
            
            if (action === 'edit' || action === 'activate') {
                // Simulation for 'edit' and 'activate' (complex front-end form/logic)
                alert(`Simulating action: ${action} on User ID: ${userId}`);
            } else if (action === 'reset' || action === 'delete') {
                // AJAX for Reset and Delete
                handleUserAction(action, userId);
            }
        });
    });
    
    
    // ==============================================
    // --- LEAVE MANAGEMENT HANDLERS (Existing AJAX) ---
    // ==============================================
    document.querySelectorAll('.leave-requests-table button').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const requestId = row.getAttribute('data-request-id');
            const employeeName = row.cells[1].textContent;
            const action = this.getAttribute('data-action');
            
            if (action === 'approve' || action === 'reject') {
                handleLeaveAction(action, requestId, employeeName);
            }
        });
    });

});