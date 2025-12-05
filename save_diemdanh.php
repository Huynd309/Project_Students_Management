<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Phương thức không hợp lệ.");
}

$lop_id = $_POST['lop_id'];
$ngay_diem_danh = $_POST['ngay_diem_danh'];
$lesson_title = $_POST['lesson_title'];
$statuses = $_POST['status'] ?? [];     
$scores_test = $_POST['scores_test'] ?? []; 
$scores_btvn = $_POST['scores_btvn'] ?? []; 

$redirect_url = "handle_diemdanh.php?lop_id=$lop_id&ngay=$ngay_diem_danh&lesson_title=" . urlencode($lesson_title);

require_once 'db_config.php';

try {
    $conn->beginTransaction(); 

    $sql_dd = "
        INSERT INTO diem_danh (so_bao_danh, lop_id, ngay_diem_danh, trang_thai, lesson_title)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (so_bao_danh, lop_id, ngay_diem_danh)
        DO UPDATE SET trang_thai = EXCLUDED.trang_thai, lesson_title = EXCLUDED.lesson_title
    ";
    $stmt_dd = $conn->prepare($sql_dd);

    foreach ($statuses as $sbd => $trang_thai) {
        $stmt_dd->execute([$sbd, $lop_id, $ngay_diem_danh, $trang_thai, $lesson_title]);
    }

    if (!empty($lesson_title)) {
        $sql_score = "
            INSERT INTO diem_thanh_phan (so_bao_danh, lop_id, ngay_kiem_tra, ten_cot_diem, diem_so, diem_btvn)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (so_bao_danh, lop_id, ngay_kiem_tra, ten_cot_diem)
            DO UPDATE SET 
                diem_so = EXCLUDED.diem_so,
                diem_btvn = EXCLUDED.diem_btvn
        ";
        $stmt_score = $conn->prepare($sql_score);

        foreach ($statuses as $sbd => $trang_thai) {
            $test = $scores_test[$sbd] ?? null;
            $btvn = $scores_btvn[$sbd] ?? null;
            
            if ($test !== null || $btvn !== null) {
                $stmt_score->execute([$sbd, $lop_id, $ngay_diem_danh, $lesson_title, $test, $btvn]);
            }
        }
    }

    $conn->commit();
    header("Location: $redirect_url&save=success");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi khi lưu dữ liệu: " . $e->getMessage());
}
?>