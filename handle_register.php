<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI. Bạn không có quyền thực hiện hành động này.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['login_password'];

    $is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] == '1');

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    require_once 'db_config.php';

    try {
        $sql = "INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([$username, $password_hash, $is_admin]);

        header('Location: admin.php?admin_added=success');
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == '23505') {
             die("Lỗi: Tên đăng nhập '$username' đã tồn tại.");
        } else {
             die("Lỗi hệ thống: " . $e->getMessage());
        }
    }
    $conn = null;
}
?>