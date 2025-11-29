<?php
// 1. Ưu tiên dùng hàm getenv() (Hoạt động tốt trên Render)
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user_db = getenv('DB_USER');
$password_db = getenv('DB_PASSWORD');

// 2. Nếu getenv trả về false (nghĩa là đang chạy ở Localhost), dùng cấu hình mặc định
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
    // Chỉ hiển thị lỗi chung chung để bảo mật
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.");
}
?>