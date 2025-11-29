<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if (isset($_GET['id']) && isset($_GET['sbd'])) {

    $id_diem = $_GET['id'];
    $sbd = $_GET['sbd'];

    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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