<?php
$servername = "localhost";
$username = "root";
$password = ""; // hoặc password của bạn
$dbname = "flower_db"; // tên CSDL của bạn

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>