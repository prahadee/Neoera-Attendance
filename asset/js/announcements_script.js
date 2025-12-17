document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('announcementForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const title = document.getElementById('title').value;
        const target = document.getElementById('target').value;
        const priority = document.getElementById('priority').value;
        const expiry = document.getElementById('expiryDate').value || 'None';

        alert(`--- Announcement Submitted ---\n
Title: ${title}
Target: ${target}
Priority: ${priority.toUpperCase()}
Expiry: ${expiry}
Status: PUBLISHED
        
(Simulation: In a live system, this would be saved to the database and pushed to employee dashboards.)`);
        
        form.reset();
    });
});