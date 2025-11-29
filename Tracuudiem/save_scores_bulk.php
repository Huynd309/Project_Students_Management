<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Phương thức truy cập không hợp lệ.");
}

$lop_id = $_POST['lop_id'];
$ngay_kiem_tra = $_POST['ngay_kiem_tra'];
$ten_cot_diem = $_POST['ten_cot_diem'];

$scores = $_POST['scores'] ?? [];
$btvn = $_POST['btvn'] ?? [];

$redirect_url = "handle_add_scores_bulk.php?lop_id=$lop_id&ngay=$ngay_kiem_tra&ten_cot_diem=" . urlencode($ten_cot_diem);

if (empty($scores)) {
    header("Location: $redirect_url");
    exit;
}

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->beginTransaction();

    $sql = "
        INSERT INTO diem_thanh_phan (so_bao_danh, lop_id, ngay_kiem_tra, ten_cot_diem, diem_so, diem_btvn)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (so_bao_danh, lop_id, ngay_kiem_tra, ten_cot_diem)
        DO UPDATE SET 
            diem_so = EXCLUDED.diem_so,
            diem_btvn = EXCLUDED.diem_btvn
    ";
    $stmt = $conn->prepare($sql);

    foreach ($scores as $so_bao_danh => $diem_so) {
        $diem_btvn_val = $btvn[$so_bao_danh] ?? null;

        if (($diem_so !== '' && $diem_so !== null) || ($diem_btvn_val !== '' && $diem_btvn_val !== null)) {
            
            $val1 = ($diem_so === '') ? null : $diem_so;
            $val2 = ($diem_btvn_val === '') ? null : $diem_btvn_val;

            $stmt->execute([$so_bao_danh, $lop_id, $ngay_kiem_tra, $ten_cot_diem, $val1, $val2]);
        }
    }

    $conn->commit();

    header("Location: $redirect_url&save=success");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi khi lưu điểm: " . $e->getMessage());
}
?>