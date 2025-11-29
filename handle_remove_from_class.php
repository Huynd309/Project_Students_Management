<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if (isset($_GET['sbd']) && isset($_GET['lop_id'])) {

    $sbd = $_GET['sbd'];
    $lop_id = $_GET['lop_id'];

    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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