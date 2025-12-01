<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}

if (isset($_GET['sbd'])) {
    $sbd = $_GET['sbd'];
    
    $redirect_url = $_GET['redirect'] ?? 'admin.php';

    require_once 'db_config.php';

    try {
        $conn->beginTransaction(); 

        $sql1 = "DELETE FROM diem_hoc_sinh WHERE so_bao_danh = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$sbd]);

        $sql2 = "DELETE FROM users WHERE LOWER(username) = LOWER(?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$sbd]);

        $conn->commit(); 

    } catch (PDOException $e) {
        $conn->rollBack(); 
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    
    header("Location: " . $redirect_url);
    exit;
}
?>