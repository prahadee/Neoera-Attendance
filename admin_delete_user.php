<?php
// admin_delete_user.php
require_once "auth_check.php";

if ($user_role !== 'admin') {
    header("location: index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("location: admin_manage_users.php");
    exit;
}

// Check user exists and block deleting admins if desired
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    // No such user
    header("location: admin_manage_users.php");
    exit;
}

// Optional: prevent deleting admin accounts
if ($user['role'] === 'admin') {
    header("location: admin_manage_users.php");
    exit;
}

// Delete user; related rows are removed via ON DELETE CASCADE
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("location: admin_manage_users.php");
exit;
