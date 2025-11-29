<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$sbd = $_GET['sbd'] ?? null;
$thang = $_GET['thang'] ?? null;
$nam = $_GET['nam'] ?? null;
$status = $_GET['status'] ?? null;
$lop_id = $_GET['lop_id'] ?? null; 

if (!$sbd || !$thang || !$nam || !$status || !$lop_id) {
    die("Thiếu thông tin.");
}

$redirect_url = "hocphi.php?lop_id=$lop_id&thang=$thang&nam=$nam";

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        INSERT INTO hoc_phi_thang (so_bao_danh, thang, nam, trang_thai_dong_phi)
        VALUES (?, ?, ?, ?)
        ON CONFLICT (so_bao_danh, thang, nam)
        DO UPDATE SET trang_thai_dong_phi = EXCLUDED.trang_thai_dong_phi
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$sbd, $thang, $nam, $status]);

    $conn = null;

    header("Location: $redirect_url&status=success");
    exit;

} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>