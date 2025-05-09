<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "hireperhour";

// Create a new mysqli connection
$conn = new mysqli($host, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
