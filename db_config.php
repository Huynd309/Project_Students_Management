<?php
// Lấy trực tiếp từ biến môi trường
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user_db = getenv('DB_USER');
$password_db = getenv('DB_PASSWORD');

// Nếu không lấy được bằng getenv, thử $_ENV (cho chắc ăn)
if (!$host) $host = $_ENV['DB_HOST'] ?? null;
if (!$port) $port = $_ENV['DB_PORT'] ?? '5432';
if (!$dbname) $dbname = $_ENV['DB_NAME'] ?? null;
if (!$user_db) $user_db = $_ENV['DB_USER'] ?? null;
if (!$password_db) $password_db = $_ENV['DB_PASSWORD'] ?? null;

// Nếu vẫn không có host -> Chắc chắn đang chạy localhost ở nhà
if (!$host) {
    $host = '127.0.0.1';
    $port = '5432';
    $dbname = 'Student_Information';
    $user_db = 'postgres';
    $password_db = 'Ngohuy3092005';
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // In lỗi ra để debug lần cuối
    die("Lỗi kết nối CSDL: " . $e->getMessage() . " (Host: $host)");
}
?>