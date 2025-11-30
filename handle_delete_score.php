<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if (isset($_GET['id']) && isset($_GET['sbd'])) {

    $id_diem = $_GET['id'];
    $sbd = $_GET['sbd'];

    require_once 'db_config.php';

    try {
        $sql = "DELETE FROM diem_thanh_phan WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_diem]);

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    //Back to edit_student.php
    header('Location: edit_student.php?sbd=' . $sbd);
    exit;
}
?>