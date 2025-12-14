<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lop_id = $_POST['lop_id'];
    $ngay_diem_danh = $_POST['ngay_diem_danh'];
    $lesson_title = $_POST['lesson_title'];
    $lesson_description = $_POST['lesson_description'] ?? '';
    
    $test_title = $_POST['test_title']; 
    if (empty(trim($test_title))) $test_title = $lesson_title;

    $status_data = $_POST['status'] ?? [];
    $scores_test = $_POST['scores_test'] ?? [];
    $scores_btvn = $_POST['scores_btvn'] ?? [];

    try {
        $conn->beginTransaction();

        foreach ($status_data as $sbd => $status) {
            $stmt = $conn->prepare("
                INSERT INTO diem_danh (lop_id, so_bao_danh, ngay_diem_danh, trang_thai, lesson_title, lesson_description)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (lop_id, so_bao_danh, ngay_diem_danh) 
                DO UPDATE SET trang_thai = EXCLUDED.trang_thai, 
                              lesson_title = EXCLUDED.lesson_title,
                              lesson_description = EXCLUDED.lesson_description
            ");
            $stmt->execute([$lop_id, $sbd, $ngay_diem_danh, $status, $lesson_title, $lesson_description]);

            // 2. LƯU ĐIỂM SỐ (Dùng test_title làm ten_cot_diem)
            // Xóa điểm cũ của ngày này để tránh trùng lặp tên bài
            $stmt_del = $conn->prepare("DELETE FROM diem_thanh_phan WHERE lop_id=? AND so_bao_danh=? AND ngay_kiem_tra=?");
            $stmt_del->execute([$lop_id, $sbd, $ngay_diem_danh]);

            $diem_test = isset($scores_test[$sbd]) && $scores_test[$sbd] !== '' ? $scores_test[$sbd] : null;
            $diem_btvn = isset($scores_btvn[$sbd]) && $scores_btvn[$sbd] !== '' ? $scores_btvn[$sbd] : null;

            if ($diem_test !== null || $diem_btvn !== null) {
                $stmt_score = $conn->prepare("
                    INSERT INTO diem_thanh_phan (lop_id, so_bao_danh, ngay_kiem_tra, ten_cot_diem, diem_so, diem_btvn)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt_score->execute([$lop_id, $sbd, $ngay_diem_danh, $test_title, $diem_test, $diem_btvn]);
            }
        }

        $conn->commit();
        // Quay lại trang xử lý với thông báo thành công
        header("Location: handle_diemdanh.php?lop_id=$lop_id&ngay=$ngay_diem_danh&save=success");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Lỗi: " . $e->getMessage());
    }
}
?>