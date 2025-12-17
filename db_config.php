<?php
// db_config.php

define('DB_SERVER', 'localhost'); // Force IPv4 loopback
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'attendance_2');
 // <--- NEW PORT DEFINITION

/* The mysqli constructor takes 5 parameters:
   host, username, password, dbname, port
*/
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("ERROR: Could not connect. " . $conn->connect_error);
}

// Ensure character set is UTF-8
if (!$conn->set_charset("utf8")) {
    // Optional: Log error
}
?>