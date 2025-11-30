<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Phương thức không hợp lệ.");
}

$lop_id = $_POST['lop_id'];
$thang = $_POST['thang'];
$nam = $_POST['nam'];
$comments = $_POST['nhanxet'] ?? [];

$redirect_url = "monthly_report.php?lop_id=$lop_id&thang=$thang&nam=$nam";

try {
    require_once 'db_config.php';

    $conn->beginTransaction();

    $sql = "
        INSERT INTO nhan_xet_thang (so_bao_danh, lop_id, thang, nam, nhan_xet)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (so_bao_danh, lop_id, thang, nam)
        DO UPDATE SET nhan_xet = EXCLUDED.nhan_xet
    ";
    $stmt = $conn->prepare($sql);

    foreach ($comments as $sbd => $content) {
        $content = trim($content);
        $stmt->execute([$sbd, $lop_id, $thang, $nam, $content]);
    }

    $conn->commit();
    header("Location: $redirect_url&save=success");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi: " . $e->getMessage());
}
?>