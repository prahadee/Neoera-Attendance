<?php
// process_admin_action.php - Handles user deletion and password reset for Admin Portal

session_start();
// Security Check: Must be logged in AND be an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once "db_config.php";

$response = ['success' => false, 'message' => 'Invalid Request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['userId'])) {
    
    $action = $_POST['action'];
    $user_id = (int)$_POST['userId'];
    
    // Safety check: Admin cannot modify their own account
    if ($user_id === $_SESSION['id']) {
        $response['message'] = 'Error: Cannot perform this action on your own account.';
    } else {
        
        switch ($action) {
            case 'delete':
                // DELETE logic: CASCADE DELETE should remove rows from employee_details too.
                $sql = "DELETE FROM users WHERE id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "User ID $user_id successfully DELETED.";
                    } else {
                        $response['message'] = "Database Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'reset':
                // RESET logic: Set a temporary password (e.g., "reset123")
                $temp_password = 'reset123';
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Password for User ID $user_id reset to temporary password ('$temp_password').";
                    } else {
                        $response['message'] = "Database Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
                break;
                
            default:
                $response['message'] = 'Unsupported action.';
        }
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>