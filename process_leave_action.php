<?php
// process_leave_action.php - Handles AJAX requests for approving or rejecting leave.

session_start();

// Ensure user is logged in and is an Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once "db_config.php";

$response = ['success' => false, 'message' => 'Invalid Request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['requestId'])) {
    
    $action = $_POST['action']; // 'approve' or 'reject'
    $request_id = (int)$_POST['requestId'];
    
    // Determine the status string
    $new_status = match($action) {
        'approve' => 'Approved',
        'reject' => 'Rejected',
        default => null,
    };

    if ($new_status) {
        // Prepare SQL UPDATE statement
        $sql = "UPDATE leave_requests SET status = ? WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_status, $request_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Request ID $request_id successfully marked as $new_status.";
                    $response['newStatus'] = $new_status;
                } else {
                    $response['message'] = "Request ID $request_id not found or status already $new_status.";
                }
            } else {
                $response['message'] = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $response['message'] = 'Invalid action specified.';
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>