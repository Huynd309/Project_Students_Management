<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Phương thức không hợp lệ.");
}

$lop_id = $_POST['lop_id'];
$ngay = $_POST['ngay'];
$comments = $_POST['nhanxet'] ?? [];

$redirect_url = "daily_report.php?lop_id=$lop_id&ngay=$ngay";

require_once 'db_config.php';

try {
    $conn->beginTransaction(); 

    $sql = "
        UPDATE diem_danh 
        SET nhan_xet = ? 
        WHERE so_bao_danh = ? AND lop_id = ? AND ngay_diem_danh = ?
    ";
    $stmt = $conn->prepare($sql);

    foreach ($comments as $sbd => $content) {
        $content = trim($content);
        
        $val = ($content === '') ? null : $content;
        
        $stmt->execute([$val, $sbd, $lop_id, $ngay]);
    }

    $conn->commit(); 
    header("Location: $redirect_url&save=success");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi khi lưu nhận xét: " . $e->getMessage());
}
?>