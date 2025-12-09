<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}

if (isset($_GET['lop_id']) && isset($_GET['ngay'])) {
    $lop_id = $_GET['lop_id'];
    $ngay = $_GET['ngay'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'admin';

    require_once 'db_config.php';

    try {
        if ($user_role !== 'super') {
            $stmt_check = $conn->prepare("SELECT 1 FROM admin_lop_access WHERE user_id = ? AND lop_id = ?");
            $stmt_check->execute([$user_id, $lop_id]);
            if ($stmt_check->rowCount() == 0) {
                die("BẠN KHÔNG CÓ QUYỀN XÓA DỮ LIỆU CỦA LỚP NÀY!");
            }
        }

        $conn->beginTransaction();

        $sql1 = "DELETE FROM diem_danh WHERE lop_id = ? AND ngay_diem_danh = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$lop_id, $ngay]);

        $sql2 = "DELETE FROM diem_thanh_phan WHERE lop_id = ? AND ngay_kiem_tra = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$lop_id, $ngay]);

        $conn->commit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    header('Location: diemdanh.php?msg=deleted');
    exit;
} else {
    die("Thiếu thông tin để xóa.");
}
?>