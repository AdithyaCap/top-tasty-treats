<?php

$conn = new mysqli('127.0.0.1', 'root', '', 'gallerycafe', 3306);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
