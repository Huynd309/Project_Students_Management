<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $sbd = $_POST['so_bao_danh'];
    $lop_id = $_POST['lop_id'];
    $ten_cot_diem = $_POST['ten_cot_diem'];
    $diem_so = $_POST['diem_so'];
    $ngay_kiem_tra = $_POST['ngay_kiem_tra'];

    require_once 'db_config.php';

    try {
        $sql = "INSERT INTO diem_thanh_phan (so_bao_danh, lop_id, ten_cot_diem, diem_so, ngay_kiem_tra) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$sbd, $lop_id, $ten_cot_diem, $diem_so, $ngay_kiem_tra]);

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
    //Back to edit_student.php
    header('Location: edit_student.php?sbd=' . $sbd);
    exit;
}
?>