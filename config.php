<?php
// config.php
$host = 'localhost';
$user = 'hanbi';         // ← 修改为你的数据库用户名
$password = 'XaA8wF7Sw7kszFcm';         // ← 修改为你的数据库密码
$database = 'hanbi';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>