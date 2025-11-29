<?php
$host = getenv('DB_HOST') ?: $_ENV['DB_HOST'];
$port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?: '5432';
$dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'];
$user_db = getenv('DB_USER') ?: $_ENV['DB_USER'];
$password_db = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage() . " (Host: $host, Port: $port, DB: $dbname)");
}
?>
