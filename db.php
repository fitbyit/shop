<?php
// db.php - Database connection file
$servername = "sdb-79.hosting.stackcp.net";
$username = "user_auth-353038332687";
$password = "Asad@1212";
$database = "user_auth-353038332687";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    $errorMessage = "Database Connection Failed: " . $conn->connect_error;
    die($errorMessage);
}
?>
