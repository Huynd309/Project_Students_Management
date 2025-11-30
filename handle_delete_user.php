<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}

if ($_SESSION['role'] !== 'super') {
    die("CHỈ CÓ SUPER ADMIN MỚI ĐƯỢC XÓA TÀI KHOẢN!");
}
if (isset($_GET['id'])) {

    $user_id_to_delete = $_GET['id'];

    require_once 'db_config.php';

    try {
        $sql = "DELETE FROM users WHERE id = ? AND is_admin = FALSE";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id_to_delete]);

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    header('Location: admin.php?delete_user=success');
    exit;
} else {
    die("Lỗi: Thiếu ID người dùng.");
}
?>