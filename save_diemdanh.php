<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Phương thức truy cập không hợp lệ.");
}

$lop_id = $_POST['lop_id'];
$ngay_diem_danh = $_POST['ngay_diem_danh'];
$lesson_title = $_POST['lesson_title'] ?? ''; 
$statuses = $_POST['status'] ?? [];

$redirect_url = "handle_diemdanh.php?lop_id=$lop_id&ngay=$ngay_diem_danh&lesson_title=" . urlencode($lesson_title);

if (empty($statuses)) {
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
        INSERT INTO diem_danh (so_bao_danh, lop_id, ngay_diem_danh, trang_thai, lesson_title)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (so_bao_danh, lop_id, ngay_diem_danh)
        DO UPDATE SET 
            trang_thai = EXCLUDED.trang_thai,
            lesson_title = EXCLUDED.lesson_title
    ";
    $stmt = $conn->prepare($sql);

    foreach ($statuses as $so_bao_danh => $trang_thai) {
        $stmt->execute([$so_bao_danh, $lop_id, $ngay_diem_danh, $trang_thai, $lesson_title]);
    }

    $conn->commit();

    header("Location: $redirect_url&save=success");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi khi lưu điểm danh: " . $e->getMessage());
}
?>