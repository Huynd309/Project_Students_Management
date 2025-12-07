<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$lop_id_filter = $_GET['lop_id'] ?? null;
$ngay_filter = $_GET['ngay'] ?? date('Y-m-d');

$all_classes = [];
$processed_data = []; 
$top_students = [];   
$class_name = "";
$lesson_title = "";
$lesson_desc = "";
$max_score = -1;
$is_lop2 = false;
$is_lop8 = false; // Thêm biến kiểm tra Lớp 8

try {
    require_once 'db_config.php';
    
    // 1. LẤY DANH SÁCH LỚP
    $user_role = $_SESSION['role'] ?? 'admin'; 
    if ($user_role === 'super') {
        $stmt_classes = $conn->prepare("SELECT id, ten_lop FROM lop_hoc ORDER BY ten_lop ASC");
        $stmt_classes->execute();
    } else {
        $stmt_classes = $conn->prepare("
            SELECT lh.id, lh.ten_lop FROM lop_hoc lh JOIN admin_lop_access ala ON lh.id = ala.lop_id WHERE ala.user_id = ? ORDER BY lh.ten_lop ASC
        ");
        $stmt_classes->execute([$_SESSION['user_id']]);
    }
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // 2. LẤY DỮ LIỆU
    if ($lop_id_filter) {
        $is_allowed = false;
        foreach ($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $is_allowed = true;
                $class_name = $class['ten_lop'];
                
                // Kiểm tra loại lớp
                if (strpos($class_name, '2-') === 0) $is_lop2 = true; // Lớp 2
                if (strpos($class_name, '8-') === 0) $is_lop8 = true; // Lớp 8 (Mới)
                
                break;
            }
        }
        if (!$is_allowed) die("BẠN KHÔNG CÓ QUYỀN TRUY CẬP LỚP NÀY!");

        // 2.1 Lấy nội dung bài học
        $stmt_lesson = $conn->prepare("SELECT lesson_title, lesson_description FROM diem_danh WHERE lop_id = ? AND ngay_diem_danh = ? LIMIT 1");
        $stmt_lesson->execute([$lop_id_filter, $ngay_filter]);
        $lesson_row = $stmt_lesson->fetch(PDO::FETCH_ASSOC);
        $lesson_title = $lesson_row['lesson_title'] ?? null;
        $lesson_desc = $lesson_row['lesson_description'] ?? null;

        // 2.2 Lấy danh sách
        $sql_report = "
            SELECT 
                dhs.so_bao_danh, dhs.ho_ten,
                dd.trang_thai AS trang_thai_diem_danh,
                dd.nhan_xet, 
                dtp.diem_so AS diem_test, dtp.diem_btvn
            FROM diem_hoc_sinh dhs
            JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
            JOIN user_lop ul ON u.id = ul.user_id
            LEFT JOIN diem_danh dd ON LOWER(dhs.so_bao_danh) = LOWER(dd.so_bao_danh) AND dd.ngay_diem_danh = ? AND dd.lop_id = ?
            LEFT JOIN diem_thanh_phan dtp ON LOWER(dhs.so_bao_danh) = LOWER(dtp.so_bao_danh) AND dtp.ngay_kiem_tra = ? AND dtp.lop_id = ?
            WHERE ul.lop_hoc_id = ?
        ";
        
        $stmt_report = $conn->prepare($sql_report);
        $stmt_report->execute([$ngay_filter, $lop_id_filter, $ngay_filter, $lop_id_filter, $lop_id_filter]);
        $raw_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw_data as $row) {
            $parts = explode(' ', trim($row['ho_ten']));
            $ten = array_pop($parts);
            $ho_dem = implode(' ', $parts);
            $row['ten'] = $ten;
            $row['ho_dem'] = $ho_dem;

            $diem_cc = getDiemChuyenCan($row['trang_thai_diem_danh']);
            $diem_test = ($row['diem_test'] !== null) ? (float)$row['diem_test'] : 0;
            $diem_btvn = ($row['diem_btvn'] !== null) ? (float)$row['diem_btvn'] : 0;

            // Công thức tính điểm
            $diem_tich_luy = 0;
            if ($is_lop2) {
                $diem_tich_luy = ($diem_cc + $diem_btvn) / 2;
            } elseif (!$is_lop8) { // Lớp 8 không tính tích lũy
                $diem_tich_luy = ($diem_test * 2 + $diem_cc + $diem_btvn) / 4;
            }

            $row['diem_cc_val'] = $diem_cc;
            $row['diem_tich_luy'] = $diem_tich_luy;
            $processed_data[] = $row;

            if ($diem_tich_luy > $max_score) {
                $max_score = $diem_tich_luy;
            }
        }

        // Sắp xếp A-Z
        if (!function_exists('convert_vi_to_en')) {
            function convert_vi_to_en($str) {
                $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
                $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
                $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
                $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
                $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
                $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
                $str = preg_replace("/(đ)/", "d", $str);
                // CHỮ HOA
                $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
                $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
                $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
                $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
                $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
                $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
                $str = preg_replace("/(Đ)/", "D", $str); 
                return strtolower($str);
            }
        }

        usort($processed_data, function($a, $b) {
            $tenA = convert_vi_to_en($a['ten']);
            $tenB = convert_vi_to_en($b['ten']);
            
            $res = strcmp($tenA, $tenB);
            
            if ($res === 0) {
                $hoA = convert_vi_to_en($a['ho_dem']);
                $hoB = convert_vi_to_en($b['ho_dem']);
                return strcmp($hoA, $hoB);
            }
            return $res;
        });

        if (!$is_lop8 && $max_score > 0) {
            foreach ($processed_data as $student) {
                if (abs($student['diem_tich_luy'] - $max_score) < 0.01) {
                    $top_students[] = $student['ho_ten'];
                }
            }
        }
    }
    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}

function getDiemChuyenCan($status) {
    if ($status == 'present') return 10;
    if ($status == 'late') return 7;
    if ($status == 'absent') return 0;
    return 0; 
}
function getTrangThaiText($status) {
    if ($status == 'present') return '<span style="color:green; font-weight:bold;">Có mặt</span>';
    if ($status == 'late') return '<span style="color:orange; font-weight:bold;">Muộn</span>';
    if ($status == 'absent') return '<span style="color:red; font-weight:bold;">Vắng</span>';
    return '<span style="color:gray;">--</span>';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo hàng ngày</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-table th { text-align: center; font-size: 0.95em; vertical-align: middle; }
        .report-table td { text-align: center; vertical-align: middle; }
        .col-ho-dem { text-align: left !important; padding-left: 10px !important; }
        .col-ten { text-align: left !important; font-weight: bold; }
        .lesson-info {
            margin-bottom: 20px; padding: 15px;
            background-color: rgba(0, 123, 255, 0.05);
            border-left: 5px solid var(--primary-color);
            border-radius: 5px; color: var(--text-color); text-align: left; 
        }
    </style>
</head>
<body class="admin-page-blue">
    <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox"><input type="checkbox" id="checkbox" /><div class="slider round"></div></label>
            </div>
            <a href="admin.php"><button id="login-btn">Trang Quản trị</button></a>
        </div>
    </header>

    <main class="container" style="max-width: 1300px;">
        <h2>Báo cáo tổng hợp hàng ngày</h2>
        
        <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
        <?php if (isset($_GET['save']) && $_GET['save'] == 'success'): ?>
            <div class="success-msg">Đã lưu nhận xét thành công!</div>
        <?php endif; ?>

        <form class="filter-form" action="daily_report.php" method="GET">
            <div>
                <label>Chọn lớp:</label>
                <select name="lop_id" required>
                    <option value="">-- Chọn một lớp --</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if ($class['id'] == $lop_id_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Ngày:</label>
                <input type="date" name="ngay" id="ngay" value="<?php echo htmlspecialchars($ngay_filter); ?>" required>
            </div>
            <button type="submit">Xem báo cáo</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">Báo cáo lớp "<?php echo htmlspecialchars($class_name); ?>" - Ngày <?php echo date('d/m/Y', strtotime($ngay_filter)); ?></h3>

            <?php if (!empty($lesson_title)): ?>
                <div class="lesson-info">
                    <p style="margin: 0;"><strong style="color: var(--primary-color); font-size: 1.1em; margin-right: 5px;"><i class="fas fa-book-open"></i> Nội dung bài học:</strong><?php echo htmlspecialchars($lesson_title); ?></p>
                    <?php if (!empty($lesson_desc)): ?><p style="margin-top: 5px; font-style: italic; color: var(--text-color-light); padding-left: 20px;">- <?php echo nl2br(htmlspecialchars($lesson_desc)); ?></p><?php endif; ?>
                </div>
            <?php else: ?>
                <div class="lesson-info" style="border-left-color: #ccc;"><p>Chưa cập nhật nội dung bài học.</p></div>
            <?php endif; ?>

            <form action="save_daily_comments.php" method="POST">
                <input type="hidden" name="lop_id" value="<?php echo $lop_id_filter; ?>">
                <input type="hidden" name="ngay" value="<?php echo $ngay_filter; ?>">

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">STT</th>
                            <th style="width: 90px;">SBD</th>
                            <th style="width: 140px;">Họ đệm</th>
                            <th style="width: 70px;">Tên</th>
                            <th style="width: 80px;">Trạng thái</th>
                            
                            <?php if (!$is_lop8): ?>
                                <th style="width: 50px;">Chuyên Cần</th>
                            <?php endif; ?>
                            
                            <?php if (!$is_lop2): ?>
                                <th style="width: 50px;">Điểm Test</th>
                            <?php endif; ?>

                            <th style="width: 50px;">Điểm BTVN</th>

                            <?php if (!$is_lop8): ?>
                                <th style="width: 80px; background-color: rgba(0, 123, 255, 0.1);">Điểm tích Lũy</th>
                            <?php endif; ?>
                            
                            <?php if ($is_lop2 || $is_lop8): ?>
                                <th>Nhận xét của giáo viên</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($processed_data)): ?>
                            <tr><td colspan="10">Không tìm thấy dữ liệu.</td></tr>
                        <?php else: ?>
                            <?php $stt = 1; foreach ($processed_data as $row): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                    <td class="col-ho-dem"><?php echo htmlspecialchars($row['ho_dem']); ?></td>
                                    <td class="col-ten"><?php echo htmlspecialchars($row['ten']); ?></td>
                                    <td><?php echo getTrangThaiText($row['trang_thai_diem_danh']); ?></td>
                                    
                                    <?php if (!$is_lop8): ?>
                                        <td style="font-weight:bold; color:var(--primary-color);"><?php echo ($row['trang_thai_diem_danh']) ? $row['diem_cc_val'] : '-'; ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if (!$is_lop2): ?>
                                        <td><?php echo ($row['diem_test'] !== null) ? $row['diem_test'] : '-'; ?></td>
                                    <?php endif; ?>
                                    
                                    <td><?php echo ($row['diem_btvn'] !== null) ? $row['diem_btvn'] : '-'; ?></td>
                                    
                                    <?php if (!$is_lop8): ?>
                                        <td style="font-weight:bold; color:#e74c3c; background-color: rgba(0, 123, 255, 0.05);"><?php echo number_format($row['diem_tich_luy'], 2); ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_lop2 || $is_lop8): ?>
                                        <td>
                                            <textarea class="comment-box" 
                                                      name="nhanxet[<?php echo htmlspecialchars($row['so_bao_danh']); ?>]" 
                                                      placeholder="Nhận xét..."><?php echo htmlspecialchars($row['nhan_xet'] ?? ''); ?></textarea>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p class="no-print" style="font-style: italic; color: var(--text-color-light); margin-top: 10px;">
                    * Lưu ý: Xuất file PDF ở chế độ Landscape.<br>
                    <?php if (!$is_lop8): ?>
                        * Điểm CC: Có mặt (10), Muộn (7), Vắng (0).
                    <?php endif; ?>
                </p>

                <?php if (!empty($top_students) && !$is_lop8): ?>
                <div class="commendation-box">
                    <div class="commendation-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="commendation-content">
                        <h3>BẢNG VÀNG THÀNH TÍCH HÔM NAY</h3>
                        <p class="subtitle">Congratulations</p>
                        
                        <div class="student-name">
                            <?php echo implode('<br>', $top_students); ?>
                        </div>
                        
                        <div class="score-badge">
                            Điểm tích lũy: <strong><?php echo number_format($max_score, 2); ?></strong>
                        </div>
                        
                        <p class="encouragement">"Nhất Đạo Education- làm mọi hành động vì học sinh !"</p>
                    </div>
                </div>
            <?php endif; ?>
                
                <?php if (!empty($processed_data) && ($is_lop2 || $is_lop8)): ?>
                    <button type="submit" class="submit-btn">Lưu tất cả nhận xét</button>
                <?php endif; ?>
            </form>
            
            <img src="nhatdao_watermark.png" class="watermark-print-logo" alt="Watermark">
            <div style="margin-top: 30px; text-align: right;">
                <button onclick="window.print()" class="btn-print"><i class="fas fa-file-pdf"></i> Xuất file PDF</button>
            </div>
        <?php endif; ?>
    </main>
    <script src="admin_main.js"></script>
</body>
</html>