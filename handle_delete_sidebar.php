<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { 
    die("TRUY CẬP BỊ TỪ CHỐI."); 
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    require_once 'db_config.php';

    try {
        if ($_SESSION['role'] !== 'super') {
            $stmt_check = $conn->prepare("
                SELECT 1 
                FROM lesson_class_access lca
                JOIN admin_lop_access ala ON lca.lop_id = ala.lop_id
                WHERE lca.lesson_id = ? AND ala.user_id = ?
            ");
            $stmt_check->execute([$id, $_SESSION['user_id']]);
            
            if ($stmt_check->rowCount() == 0) {
                die("CẢNH BÁO: BẠN KHÔNG CÓ QUYỀN XÓA BÀI HỌC NÀY! (Bài học thuộc lớp bạn không quản lý)");
            }
        }
        $sql = "DELETE FROM sidebar_content WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        $conn = null;

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }

    header('Location: admin.php?msg=deleted');
    exit;
} else {
    die("Không tìm thấy ID bài học.");
}
?>