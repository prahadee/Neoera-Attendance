document.addEventListener('DOMContentLoaded', function() {
    const editProfileBtn = document.getElementById('editProfileBtn');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const cancelProfileBtn = document.getElementById('cancelProfileBtn');
    const profileActions = document.getElementById('profileActions');
    const tabLinks = document.querySelectorAll('.profile-tabs .tab-link');
    const inputFields = document.querySelectorAll('#personal input:not([disabled])');
    const addressField = document.getElementById('address');

    // --- Tab Switching Logic ---
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const targetTabId = this.getAttribute('data-tab');

            document.querySelectorAll('.profile-tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.querySelectorAll('.profile-tabs .tab-link').forEach(tab => {
                tab.classList.remove('active');
            });

            document.getElementById(targetTabId).style.display = 'block';
            this.classList.add('active');
        });
    });

    // --- Toggle Edit Mode (Client-side) ---
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            inputFields.forEach(input => input.disabled = false);
            if (addressField) addressField.disabled = false;
            profileActions.style.display = 'flex';
            editProfileBtn.style.display = 'none';
        });
    }
    
    if (cancelProfileBtn) {
        cancelProfileBtn.addEventListener('click', function() {
            // NOTE: To truly cancel changes, you would need to store initial values
            // Here, we just revert the UI state
            inputFields.forEach(input => input.disabled = true);
            if (addressField) addressField.disabled = true;
            profileActions.style.display = 'none';
            editProfileBtn.style.display = 'block';
        });
    }

    // --- AJAX Submissions Handler (Details Update) ---
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', async function() {
            const phone = document.getElementById('phoneNumber').value;
            const personalEmail = document.getElementById('personalEmail').value;
            
            const formData = new FormData();
            formData.append('form_type', 'details_update');
            formData.append('phone', phone);
            formData.append('personalEmail', personalEmail);
            
            // Simple client-side validation
            if (!phone || !personalEmail) {
                alert("Phone and Personal Email are required fields for update.");
                return;
            }

            try {
                const response = await fetch('process_profile_update.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.message);

                if (data.success) {
                    // Revert to disabled view mode
                    cancelProfileBtn.click();
                }
                
            } catch (error) {
                console.error('AJAX Error:', error);
                alert('A network error occurred during profile update.');
            }
        });
    }

    // --- AJAX Submissions Handler (Password Change) ---
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', async function() {
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (!currentPass || !newPass || !confirmPass) {
                alert("All password fields must be filled.");
                return;
            }
            if (newPass !== confirmPass) {
                alert("New passwords do not match.");
                return;
            }
            
            const formData = new FormData();
            formData.append('form_type', 'password_change');
            formData.append('currentPassword', currentPass);
            formData.append('newPassword', newPass);
            
            try {
                const response = await fetch('process_profile_update.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.message);
                
                // Clear fields regardless of success/failure
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                
                // If successful, force logout for security
                if (data.success) {
                    window.location.href = 'logout.php';
                }

            } catch (error) {
                console.error('AJAX Error:', error);
                alert('A network error occurred during password change.');
            }
        });
    }

});