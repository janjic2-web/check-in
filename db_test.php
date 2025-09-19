<?php
$conn = new mysqli('127.0.0.1', 'checkin', '123456', null, 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connection successful!";
$conn->close();