<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") { die("Phương thức không hợp lệ."); }

$lop_id = $_POST['lop_id'];
$thang = $_POST['thang'];
$nam = $_POST['nam'];
$comments = $_POST['nhanxet'] ?? [];
$sbd_single = $_POST['sbd_single'] ?? null; 

require_once 'db_config.php';

try {
    $conn->beginTransaction();

    $sql = "INSERT INTO nhan_xet_thang (so_bao_danh, lop_id, thang, nam, nhan_xet)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (so_bao_danh, lop_id, thang, nam)
            DO UPDATE SET nhan_xet = EXCLUDED.nhan_xet";
    $stmt = $conn->prepare($sql);

    foreach ($comments as $sbd => $content) {
        $content = trim($content);
        $stmt->execute([$sbd, $lop_id, $thang, $nam, $content]);
    }

    $conn->commit();

    if ($sbd_single) {
        header("Location: student_monthly_report.php?sbd=$sbd_single&month=$thang&year=$nam&lop_id=$lop_id&save=success");
    } else {
        header("Location: monthly_report.php?lop_id=$lop_id&thang=$thang&nam=$nam&save=success");
    }
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi: " . $e->getMessage());
}
?>