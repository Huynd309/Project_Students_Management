<?php
// Lấy biến môi trường
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? null);
$user_db = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
$password_db = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? null);

if (!$host) {
    $host = '127.0.0.1';
    $dbname = 'Student_Information';
    $user_db = 'postgres';
    $password_db = 'Ngohuy3092005';
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage() . "<br>Thông tin đang dùng: Host=$host | DB=$dbname | User=$user_db");
}
?>