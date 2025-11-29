<?php
// File: db_config.php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'Student_Information';
$user_db = 'postgres';
$password_db = 'Ngohuy3092005';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>