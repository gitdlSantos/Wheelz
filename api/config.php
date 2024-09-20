<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "wheelz";  // Cambia por tu base de datos

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>