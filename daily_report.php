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

try {
    require_once 'db_config.php';
    
    // --- QUERY 1: LẤY DANH SÁCH LỚP ---
    $user_role = $_SESSION['role'] ?? 'admin'; 
    if ($user_role === 'super') {
        $stmt_classes = $conn->prepare("SELECT id, ten_lop FROM lop_hoc ORDER BY ten_lop ASC");
        $stmt_classes->execute();
    } else {
        $stmt_classes = $conn->prepare("
            SELECT lh.id, lh.ten_lop 
            FROM lop_hoc lh
            JOIN admin_lop_access ala ON lh.id = ala.lop_id
            WHERE ala.user_id = ?
            ORDER BY lh.ten_lop ASC
        ");
        $stmt_classes->execute([$_SESSION['user_id']]);
    }
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 2: LẤY DỮ LIỆU BÁO CÁO ---
    if ($lop_id_filter) {
        
        $is_allowed = false;
        foreach ($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $is_allowed = true;
                $class_name = $class['ten_lop'];
                break;
            }
        }
        if (!$is_allowed) die("BẠN KHÔNG CÓ QUYỀN TRUY CẬP LỚP NÀY!");

        // --- QUERY 2.1: LẤY NỘI DUNG BÀI HỌC (TITLE + DESC) ---
        $stmt_lesson = $conn->prepare("
            SELECT lesson_title, lesson_description 
            FROM diem_danh 
            WHERE lop_id = ? AND ngay_diem_danh = ? 
            LIMIT 1
        ");
        $stmt_lesson->execute([$lop_id_filter, $ngay_filter]);
        $lesson_row = $stmt_lesson->fetch(PDO::FETCH_ASSOC);
        
        $lesson_title = $lesson_row['lesson_title'] ?? null;
        $lesson_desc = $lesson_row['lesson_description'] ?? null;

        // --- QUERY 2.2: LẤY DANH SÁCH HỌC SINH ---
        $sql_report = "
            SELECT 
                dhs.so_bao_danh,
                dhs.ho_ten,
                dd.trang_thai AS trang_thai_diem_danh,
                dtp.diem_so AS diem_test,
                dtp.diem_btvn
            FROM 
                diem_hoc_sinh dhs
            JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
            JOIN user_lop ul ON u.id = ul.user_id
            LEFT JOIN diem_danh dd 
                ON LOWER(dhs.so_bao_danh) = LOWER(dd.so_bao_danh) 
                AND dd.ngay_diem_danh = ? 
                AND dd.lop_id = ?
            LEFT JOIN diem_thanh_phan dtp 
                ON LOWER(dhs.so_bao_danh) = LOWER(dtp.so_bao_danh) 
                AND dtp.ngay_kiem_tra = ? 
                AND dtp.lop_id = ?
            WHERE 
                ul.lop_hoc_id = ?
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

            $diem_tich_luy = ($diem_test * 2 + $diem_cc + $diem_btvn) / 4;

            $row['diem_cc_val'] = $diem_cc;
            $row['diem_tich_luy'] = $diem_tich_luy;
            
            $processed_data[] = $row;

            if ($diem_tich_luy > $max_score) {
                $max_score = $diem_tich_luy;
            }
        }

        // --- SẮP XẾP ---
        if (!function_exists('convert_vi_to_en')) {
            function convert_vi_to_en($str) {
                $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
                $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
                $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
                $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
                $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
                $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
                $str = preg_replace("/(đ)/", "d", $str);
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

        if ($max_score > 0) {
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
        
        .commendation-box {
            margin-top: 30px;
            padding: 20px;
            border: 2px solid #FFD700; 
            background-color: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            text-align: center;
            color: var(--text-color);
        }
        .commendation-box h3 { color: #d35400; margin-top: 0; text-transform: uppercase; }
        .commendation-box .student-name { font-size: 1.5em; font-weight: bold; color: #2ecc71; margin: 10px 0; }
        .commendation-box i { color: #FFD700; margin-right: 10px; }
        .lesson-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(0, 123, 255, 0.05);
            border-left: 5px solid var(--primary-color);
            border-radius: 5px;
            color: var(--text-color);
            text-align: left;
        }
        .lesson-info h4 { margin: 0 0 5px 0; font-size: 1.1em; color: var(--primary-color); }
        .lesson-info p { margin: 0; font-style: italic; color: var(--text-color-light); }
    </style>
</head>
<body class="admin-page-blue">
    
    <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">
                Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
            </span>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>
            <a href="admin.php"><button id="login-btn">Trang Quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container" style="max-width: 1300px;">
        <h2>Báo cáo tổng hợp hàng ngày</h2>
        <p>Xem trạng thái điểm danh, điểm kiểm tra và BTVN của lớp trong một ngày cụ thể.</p>
        
        <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>

        <form class="filter-form" action="daily_report.php" method="GET">
            <div>
                <label for="lop_id">Chọn lớp:</label>
                <select name="lop_id" id="lop_id" required>
                    <option value="">-- Chọn một lớp --</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if ($class['id'] == $lop_id_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="ngay">Chọn ngày:</label>
                <input type="date" name="ngay" id="ngay" value="<?php echo htmlspecialchars($ngay_filter); ?>" required>
            </div>
            <button type="submit">Xem báo cáo</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">
                Báo cáo lớp "<?php echo htmlspecialchars($class_name); ?>" - Ngày <?php echo date('d/m/Y', strtotime($ngay_filter)); ?>
            </h3>

            <?php if (!empty($lesson_title)): ?>
                <div class="lesson-info">
                    <p style="margin: 0;">
                        <strong style="color: var(--primary-color); font-size: 1.1em; margin-right: 5px;">
                            <i class="fas fa-book-open"></i> Nội dung bài học:
                        </strong>
                        
                        <?php echo htmlspecialchars($lesson_title); ?>
                    </p>
                    
                    <?php if (!empty($lesson_desc)): ?>
                        <p style="margin-top: 5px; font-style: italic; color: var(--text-color-light); padding-left: 20px;">
                            - <?php echo nl2br(htmlspecialchars($lesson_desc)); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="lesson-info" style="border-left-color: #ccc;">
                    <p>Chưa cập nhật nội dung bài học cho ngày này.</p>
                </div>
            <?php endif; ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th style="width: 90px;">SBD</th>
                        <th style="width: 150px;">Họ đệm</th>
                        <th style="width: 80px;">Tên</th>
                        <th style="width: 100px;">Trạng thái</th>
                        <th style="width: 70px;">Điểm CC</th>
                        <th style="width: 70px;">Điểm Test</th>
                        <th style="width: 70px;">Điểm BTVN</th>
                        <th style="width: 90px; background-color: rgba(0, 123, 255, 0.1);">Điểm Tích Lũy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($processed_data)): ?>
                        <tr><td colspan="9">Không tìm thấy dữ liệu (hoặc chưa điểm danh) cho ngày này.</td></tr>
                    <?php else: ?>
                        <?php $stt = 1; foreach ($processed_data as $row): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                <td class="col-ho-dem"><?php echo htmlspecialchars($row['ho_dem']); ?></td>
                                <td class="col-ten"><?php echo htmlspecialchars($row['ten']); ?></td>
                                
                                <td><?php echo getTrangThaiText($row['trang_thai_diem_danh']); ?></td>
                                
                                <td style="font-weight: bold; color: var(--primary-color);">
                                    <?php echo ($row['trang_thai_diem_danh']) ? $row['diem_cc_val'] : '-'; ?>
                                </td>

                                <td><?php echo ($row['diem_test'] !== null) ? $row['diem_test'] : '-'; ?></td>
                                <td><?php echo ($row['diem_btvn'] !== null) ? $row['diem_btvn'] : '-'; ?></td>

                                <td style="font-weight: bold; color: #e74c3c; background-color: rgba(0, 123, 255, 0.05);">
                                    <?php echo number_format($row['diem_tich_luy'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p style="font-style: italic; color: var(--text-color-light); margin-top: 10px;">
                * Điểm CC: Có mặt (10), Muộn (7), Vắng (0).<br>
                * Điểm Tích Lũy = (Điểm Test * 2 + Điểm CC + Điểm BTVN) / 4.
            </p>

            <?php if (!empty($top_students)): ?>
                <div class="commendation-box">
                    <h3> Bảng Vàng Nhất Đạo Edu </h3>
                    <p>Học sinh có điểm tích luỹ cao nhất: (<?php echo number_format($max_score, 2); ?> điểm):</p>
                    <div class="student-name">
                        <?php echo implode(', ', $top_students); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: right;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-file-pdf"></i> Xuất file PDF
                </button>
            </div>
        <?php endif; ?>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>