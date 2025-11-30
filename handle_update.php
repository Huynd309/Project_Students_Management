<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Truy cập bị từ chối.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $sbd = $_POST['so_bao_danh'];
    $ho_ten = $_POST['ho_ten'];
    $truong = $_POST['truong'];
    $sdt_phu_huynh = $_POST['sdt_phu_huynh'] ?? null; 
    $sdt_hoc_sinh = $_POST['sdt_hoc_sinh'] ?? null;

    try {
        require_once 'db_config.php';
        $sql = "UPDATE diem_hoc_sinh 
                SET ho_ten = ?, truong = ?, sdt_phu_huynh = ?, sdt_hoc_sinh = ?
                WHERE so_bao_danh = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ho_ten, $truong, $sdt_phu_huynh, $sdt_hoc_sinh, $sbd]);
        header("Location: edit_student.php?sbd=$sbd");
        exit;

    } catch (PDOException $e) {
        die("Lỗi cập nhật: " . $e->getMessage());
    }
}
?>