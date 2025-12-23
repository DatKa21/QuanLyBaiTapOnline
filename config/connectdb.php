<?php
$host = "localhost";
$user = "root";   // mặc định của XAMPP
$pass = "";       // mặc định để trống
$db   = "web_php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// echo "Kết nối thành công!";
?>
