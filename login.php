<?php
// login.php - Handles user authentication, session creation, and redirection

// Check if a session is already started (needed for PHP 8.2+)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define the project folder name (MUST MATCH FOLDER NAME EXACTLY)
define('PROJECT_ROOT', '/'); 

// Check if the user is already logged in, redirect them to the respective dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] === "admin") {
        header("location: " . PROJECT_ROOT . "admin_dashboard.php");
        exit;
    } elseif ($_SESSION["role"] === "employee") {
        header("location: " . PROJECT_ROOT . "employee_dashboard.php");
        exit;
    }
}

require_once "db_config.php";

$username = $password = "";
// FIX: Initialize $role to an empty string to ensure no default option is selected
$role = ""; 
$login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get and Sanitize Input
    if (isset($_POST["username"])) {
        $username = trim($_POST["username"]);
    }
    if (isset($_POST["password"])) {
        $password = trim($_POST["password"]);
    }
    // Only set $role if it was actually sent via POST (after user selects an option)
    if (isset($_POST["role"])) {
        $role = trim($_POST["role"]);
    }

    // 2. Prepare SQL statement
    if (empty($username) || empty($password) || empty($role)) { // Ensure $role is checked for emptiness too
        $login_err = "Please select an access role, and enter your ID and password.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ? AND role = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $param_username, $param_role);

            // Set parameters
            $param_username = $username;
            $param_role = $role;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password, $db_role);

                    if ($stmt->fetch()) {
                        // 3. Verify Password
                        if ($hashed_password !== null && password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_regenerate_id(true); // Security measure

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $db_role;

                            // 4. Redirect user based on role (CRITICAL PATHS)
                            if ($db_role === "admin") {
                                header("location: " . PROJECT_ROOT . "admin_dashboard.php");
                            } else {
                                // Default to employee
                                header("location: " . PROJECT_ROOT . "employee_dashboard.php");
                            }
                            exit;
                        } else {
                            // Password is not valid
                            $login_err = "Invalid password.";
                        }
                    }
                } else {
                    // Username doesn't exist or role mismatch
                    $login_err = "No account found with that ID and role.";
                }
            } else {
                $login_err = "Oops! Something went wrong with the database query.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection (if not already closed by statement)
    if (isset($conn)) {
        $conn->close();
    }
}
?>