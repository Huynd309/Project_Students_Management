<?php
// Ưu tiên $_ENV vì Dockerfile đã cấu hình EGPCS
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$user_db = $_ENV['DB_USER'] ?? getenv('DB_USER');
$password_db = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

if (empty($host)) {
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
    echo "<h1>Lỗi Kết Nối!</h1>";
    echo "Đang thử kết nối đến Host: [" . htmlspecialchars($host) . "]<br>";
    echo "User: [" . htmlspecialchars($user_db) . "]<br>";
    die("Chi tiết: " . $e->getMessage());
}
?>