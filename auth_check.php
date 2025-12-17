<?php
// auth_check.php - Ensures user is logged in, checks role, and fetches dynamic data

// FIX: Check if a session is already started before calling session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if the user is NOT logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Note: Use 'index.php' for redirecting to the login page if not logged in
    header("location: index.php");
    exit;
}

// Since we are now using session_start() reliably at the top of the file,
// we can proceed with database connection.
require_once "db_config.php";

// Set variables from the current session
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$user_role = $_SESSION["role"];

// Initialize user detail variables 
$full_name = "User";
$employee_id = "";
$department = "";
$job_title = "";
$work_email = "";
$personal_email = "";
$phone_number = "";
$joining_date = "";

// 2. Fetch User Details
$sql_details = "SELECT 
                    ed.full_name, 
                    ed.employee_id, 
                    ed.department, 
                    ed.job_title, 
                    ed.work_email,
                    ed.personal_email,
                    ed.phone_number,
                    ed.joining_date
                FROM employee_details ed
                WHERE ed.user_id = ?";

if ($stmt = $conn->prepare($sql_details)) {
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
            
            // Populate variables
            $full_name = htmlspecialchars($user_data['full_name']);
            $employee_id = htmlspecialchars($user_data['employee_id']);
            $department = htmlspecialchars($user_data['department']);
            $job_title = htmlspecialchars($user_data['job_title']);
            $work_email = htmlspecialchars($user_data['work_email']);
            $personal_email = htmlspecialchars($user_data['personal_email']);
            $phone_number = htmlspecialchars($user_data['phone_number']);
            $joining_date = htmlspecialchars($user_data['joining_date']);
        }
    }
    $stmt->close();
}
// NOTE: $conn remains open for use in the dashboard scripts unless explicitly closed.
?>