<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if (isset($_GET['sbd']) && isset($_GET['lop_id'])) {

    $sbd = $_GET['sbd'];
    $lop_id = $_GET['lop_id'];

    require_once 'db_config.php';

    try {
        $sql = "DELETE FROM sinh_vien_lop WHERE so_bao_danh = ? AND lop_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$sbd, $lop_id]);

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    header('Location: edit_student.php?sbd=' . $sbd);
    exit;
} else {
    die("Lỗi: Thiếu SBD hoặc ID Lớp.");
}
?>