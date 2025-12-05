<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lop_id = $_POST['lop_id'];
    $ngay_diem_danh = $_POST['ngay_diem_danh'];
    $lesson_title = $_POST['lesson_title'] ?? '';
} elseif (isset($_GET['lop_id']) && isset($_GET['ngay'])) { 
    $lop_id = $_GET['lop_id'];
    $ngay_diem_danh = $_GET['ngay'];
    $lesson_title = $_GET['lesson_title'] ?? ''; 
} else {
    die("Thiếu thông tin lớp hoặc ngày.");
}

$students_list = [];
$class_info = null;
$saved_statuses = []; 
$monthly_points = [];
$saved_scores = []; 

$current_title = $lesson_title;
$current_desc = "";

require_once 'db_config.php';

try {
    // Query 1: Lấy tên lớp
    $stmt_class = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmt_class->execute([$lop_id]);
    $class_info = $stmt_class->fetch(PDO::FETCH_ASSOC);

    if (!$class_info) { die("Không tìm thấy lớp này."); }

    // Query 2: Lấy danh sách học sinh
    $stmt_students = $conn->prepare("
        SELECT dhs.so_bao_danh, dhs.ho_ten
        FROM diem_hoc_sinh AS dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        WHERE ul.lop_hoc_id = ?
        ORDER BY dhs.ho_ten ASC
    ");
    $stmt_students->execute([$lop_id]);
    $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Query 3: Lấy thông tin bài học & điểm danh cũ
    $stmt_info = $conn->prepare("SELECT lesson_title, lesson_description FROM diem_danh WHERE lop_id = ? AND ngay_diem_danh = ? LIMIT 1");
    $stmt_info->execute([$lop_id, $ngay_diem_danh]);
    $info_row = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if ($info_row) {
        $current_title = $info_row['lesson_title']; 
        $current_desc = $info_row['lesson_description'];
    }

    $stmt_status = $conn->prepare("SELECT so_bao_danh, trang_thai FROM diem_danh WHERE lop_id = ? AND ngay_diem_danh = ?");
    $stmt_status->execute([$lop_id, $ngay_diem_danh]);
    foreach ($stmt_status->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $saved_statuses[$s['so_bao_danh']] = $s['trang_thai'];
    }

    // Query 4: Lấy điểm số cũ
    if (!empty($current_title)) {
        $stmt_scores = $conn->prepare("SELECT so_bao_danh, diem_so, diem_btvn FROM diem_thanh_phan WHERE lop_id = ? AND ngay_kiem_tra = ? AND ten_cot_diem = ?");
        $stmt_scores->execute([$lop_id, $ngay_diem_danh, $current_title]);
        foreach ($stmt_scores->fetchAll(PDO::FETCH_ASSOC) as $sc) {
            $saved_scores[strtolower($sc['so_bao_danh'])] = [
                'test' => $sc['diem_so'],
                'btvn' => $sc['diem_btvn']
            ];
        }
    }

    // Query 5: Tính điểm chuyên cần tháng
    $current_month = date('m', strtotime($ngay_diem_danh));
    $current_year = date('Y', strtotime($ngay_diem_danh));
    $sql_points = "
        SELECT so_bao_danh, SUM(CASE WHEN trang_thai = 'present' THEN 10 WHEN trang_thai = 'late' THEN 7 ELSE 0 END) as total_points
        FROM diem_danh WHERE lop_id = ? AND EXTRACT(MONTH FROM ngay_diem_danh) = ? AND EXTRACT(YEAR FROM ngay_diem_danh) = ? GROUP BY so_bao_danh
    ";
    $stmt_points = $conn->prepare($sql_points);
    $stmt_points->execute([$lop_id, $current_month, $current_year]);
    foreach ($stmt_points->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $monthly_points[$p['so_bao_danh']] = $p['total_points'];
    }

    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điểm danh & Nhập điểm</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        .form-input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 10px; 
            box-sizing: border-box;
            
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.2); 
            color: var(--text-color);
            
            font-size: 1em;
            font-family: inherit;
            
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            outline: none;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            background-color: rgba(255, 255, 255, 0.4);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        textarea.form-input {
            min-height: 60px;
            resize: vertical;
        }

        .score-input-mini {
            width: 80px !important;
            padding: 8px 10px !important;
            text-align: center;
            font-weight: bold;
            font-size: 1em;
            margin: 0 auto !important;
            display: block;
            
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: var(--text-color);
            outline: none;
            transition: all 0.3s ease;
        }
        .score-input-mini:focus {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
            transform: scale(1.05);
        }

        th, td { vertical-align: middle; }
        th { text-align: center; }
        .points-cell { font-weight: 700; color: var(--primary-color); text-align: center; font-size: 1.1em; }
        
        .radio-group { display: flex; justify-content: center; gap: 15px; }
        .radio-group input[type="radio"][value="late"]:checked + label { color: #fd7e14; font-weight: bold; }
        [data-theme="dark"] .radio-group input[type="radio"][value="late"]:checked + label { color: #ffb74d; }
    </style>
</head>
<body class="admin-page-blue">
    
    <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox"><input type="checkbox" id="checkbox" /><div class="slider round"></div></label>
            </div>
            <a href="report_diemdanh.php"><button id="report-btn">Xem báo cáo</button></a>
            <a href="diemdanh.php"><button id="login-btn">Chọn lớp khác</button></a>
            <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container" style="max-width: 1300px;">
        <h2>Bảng điểm danh & Nhập điểm</h2>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
            <div>
                <h3>Lớp: <?php echo htmlspecialchars($class_info['ten_lop']); ?></h3>
                <h3>Ngày: <?php echo date('d/m/Y', strtotime($ngay_diem_danh)); ?></h3>
                <?php if (!empty($lesson_title)): ?>
                    <h4 style="font-weight: normal; margin-top: 5px; color: var(--primary-color);">Bài học: <strong><?php echo htmlspecialchars($lesson_title); ?></strong></h4>
                <?php endif; ?>
            </div>
            <div style="text-align: right; font-size: 0.9em; color: var(--text-color-light);">
                Điểm chuyên cần tháng <?php echo date('m/Y', strtotime($ngay_diem_danh)); ?>
            </div>
        </div>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($_GET['save']) && $_GET['save'] == 'success'): ?>
            <div class="success-msg">Đã lưu điểm danh và điểm số thành công!</div>
        <?php endif; ?>

        <form action="save_diemdanh.php" method="POST">
            <input type="hidden" name="lop_id" value="<?php echo htmlspecialchars($lop_id); ?>">
            <input type="hidden" name="ngay_diem_danh" value="<?php echo htmlspecialchars($ngay_diem_danh); ?>">

            <div style="margin-bottom: 20px; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.3);">
                
                <label style="font-weight: bold; font-size: 1.1em; margin-bottom: 8px; display: block;">Tiêu đề bài học:</label>
                <input type="text" name="lesson_title" value="<?php echo htmlspecialchars($current_title); ?>" 
                       class="form-input" required placeholder="Nhập tên bài học...">
                
                <label style="font-weight: bold; font-size: 1.1em; margin-bottom: 8px; display: block;">Mô tả bài học:</label>
                <textarea name="lesson_description" rows="3" 
                          class="form-input" placeholder="Nhập nội dung chi tiết..."><?php echo htmlspecialchars($current_desc); ?></textarea>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th style="width: 80px;">SBD</th>
                        <th style="text-align: left;">Họ và tên</th>
                        <th style="width: 80px;">CC (Tháng)</th>
                        <th>Trạng thái đi học</th>
                        <th style="width: 100px;">Điểm Test</th>
                        <th style="width: 100px;">Điểm BTVN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students_list)): ?>
                        <tr><td colspan="7">Lớp này chưa có học sinh.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students_list as $index => $student): ?>
                            <?php
                                $sbd_key = strtolower($student['so_bao_danh']);
                                $current_status = $saved_statuses[$student['so_bao_danh']] ?? null;
                                $points = $monthly_points[$student['so_bao_danh']] ?? 0;
                                
                                if (isset($saved_scores[$sbd_key])) {
                                    $score_test = $saved_scores[$sbd_key]['test'];
                                    $score_btvn = $saved_scores[$sbd_key]['btvn'];
                                } else {
                                    $score_test = 5;
                                    $score_btvn = 10;
                                }
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($student['ho_ten']); ?></td>
                                
                                <td class="points-cell"><?php echo $points; ?></td>

                                <td>
                                    <span class="radio-group">
                                        <label><input type="radio" name="status[<?php echo $student['so_bao_danh']; ?>]" value="present" <?php if ($current_status == 'present') echo 'checked'; ?>> Có mặt</label>
                                        <label><input type="radio" name="status[<?php echo $student['so_bao_danh']; ?>]" value="late" <?php if ($current_status == 'late') echo 'checked'; ?>> Muộn</label>
                                        <label><input type="radio" name="status[<?php echo $student['so_bao_danh']; ?>]" value="absent" <?php if ($current_status == 'absent') echo 'checked'; ?>> Vắng</label>
                                    </span>
                                </td>
                                
                                <td>
                                    <input type="number" step="0.01" min="0" max="10" class="score-input-mini" 
                                           name="scores_test[<?php echo $student['so_bao_danh']; ?>]" 
                                           value="<?php echo $score_test; ?>">
                                </td>

                                <td>
                                    <input type="number" step="0.01" min="0" max="10" class="score-input-mini" 
                                           name="scores_btvn[<?php echo $student['so_bao_danh']; ?>]" 
                                           value="<?php echo $score_btvn; ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students_list)): ?>
                <button type="submit" class="submit-btn">Lưu tất cả</button>
            <?php endif; ?>
        </form>
    </main>
    <script src="admin_main.js"></script>
</body>
</html>