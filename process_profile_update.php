<?php
// process_profile_update.php - Handles profile details and password changes.

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once "db_config.php";
$user_id = $_SESSION["id"];
$response = ['success' => false, 'message' => 'Invalid Request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type'])) {
    
    if ($_POST['form_type'] === 'details_update') {
        // --- DETAILS UPDATE LOGIC ---
        $phone = trim($_POST['phone']);
        $personal_email = trim($_POST['personalEmail']);
        
        $sql = "UPDATE employee_details SET phone_number = ?, personal_email = ? WHERE user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $phone, $personal_email, $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Profile details updated successfully.";
            } else {
                $response['message'] = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
        
    } elseif ($_POST['form_type'] === 'password_change') {
        // --- PASSWORD CHANGE LOGIC ---
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        
        // 1. Verify Current Password
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            
            if ($stmt->fetch() && password_verify($current_password, $hashed_password)) {
                $stmt->close();
                
                // 2. Hash and Update New Password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("si", $new_hashed_password, $user_id);
                    if ($stmt_update->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Password changed successfully. You must re-login.";
                    } else {
                        $response['message'] = "DB Update Error: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                }
            } else {
                $response['message'] = "Error: Current password entered is incorrect.";
            }
        }
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>