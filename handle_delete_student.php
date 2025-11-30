<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}

if (isset($_GET['sbd'])) {

    $sbd = $_GET['sbd'];
    require_once 'db_config.php';

    try {
        $sql = "DELETE FROM diem_hoc_sinh WHERE so_bao_danh = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$sbd]);

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    header('Location: admin.php');
    exit;
}
?>