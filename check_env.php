<?php
echo "<h1>Kiểm tra biến môi trường</h1>";
echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
echo "DB_USER: " . getenv('DB_USER') . "<br>";
echo "DB_NAME: " . getenv('DB_NAME') . "<br>";

// Thử kết nối trực tiếp
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

echo "<hr>Đang thử kết nối đến: $host ...<br>";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    echo "<h2 style='color:green'>KẾT NỐI THÀNH CÔNG!</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>LỖI KẾT NỐI: " . $e->getMessage() . "</h2>";
}
?>