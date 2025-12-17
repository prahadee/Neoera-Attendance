document.addEventListener('DOMContentLoaded', function() {
    const startDateEl = document.getElementById('startDate');
    const endDateEl = document.getElementById('endDate');
    const totalDaysEl = document.getElementById('totalDays');
    const hiddenTotalDaysEl = document.getElementById('hiddenTotalDays');
    const form = document.getElementById('leaveRequestForm');

    // Function to calculate the number of days (inclusive)
    function calculateTotalDays() {
        const start = startDateEl.value;
        const end = endDateEl.value;

        if (!start || !end) {
            totalDaysEl.value = 0;
            hiddenTotalDaysEl.value = 0;
            return;
        }

        const date1 = new Date(start);
        const date2 = new Date(end);

        // Ensure start date is not after end date
        if (date1 > date2) {
            // Prevent submission and prompt the user to fix the dates
            // Do not reset the value automatically to avoid input confusion.
            totalDaysEl.value = 0; 
            hiddenTotalDaysEl.value = 0;
            return;
        }

        // Calculate time difference in milliseconds
        const timeDiff = date2.getTime() - date1.getTime();

        // Calculate day difference (add 1 day to make it inclusive)
        const dayDiff = (timeDiff / (1000 * 3600 * 24)) + 1;

        if (dayDiff > 0) {
            totalDaysEl.value = dayDiff;
            hiddenTotalDaysEl.value = dayDiff;
        } else {
            totalDaysEl.value = 1; // Minimum 1 day calculation
            hiddenTotalDaysEl.value = 1;
        }
    }

    // Attach listeners to date fields
    startDateEl.addEventListener('change', calculateTotalDays);
    endDateEl.addEventListener('change', calculateTotalDays);

    // Initial calculation (if fields have default values)
    calculateTotalDays();
    
    // --- Form Submission Check ---
    if (form) {
        form.addEventListener('submit', function(e) {
            // Final check before submission
            if (parseInt(hiddenTotalDaysEl.value) <= 0) {
                e.preventDefault();
                alert("Please select a valid leave duration where the end date is not before the start date.");
            }
        });
    }
});