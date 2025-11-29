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

    $host = '127.0.0.1';
    $port = '5432';
    $dbname = 'Student_Information';
    $user_db = 'postgres';
    $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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