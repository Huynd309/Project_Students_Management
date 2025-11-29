<?php
$host = getenv('DB_HOST');

if (!$host && isset($_ENV['DB_HOST'])) {
    $host = $_ENV['DB_HOST'];
}

if (!$host && isset($_SERVER['DB_HOST'])) {
    $host = $_SERVER['DB_HOST'];
}

if (!$host) {
    $host = '127.0.0.1';
}

$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'Student_Information');
$user_db = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'postgres');
$password_db = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? 'Ngohuy3092005');

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<h1>Lỗi kết nối CSDL!</h1>";
    echo "Host đang nhận diện là: <strong>[" . htmlspecialchars($host) . "]</strong> (Nếu là 127.0.0.1 nghĩa là chưa nhận được biến môi trường)<br>";
    echo "Lỗi chi tiết: " . $e->getMessage();
    die();
}
?>