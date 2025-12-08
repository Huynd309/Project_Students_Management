<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$lop_id_filter = $_GET['lop_id'] ?? null;
$thang_filter = $_GET['thang'] ?? date('m');
$nam_filter = $_GET['nam'] ?? date('Y');

$all_classes = [];
$report_data = [];
$processed_data = []; // Dữ liệu đã xử lý và sắp xếp
$class_name = "";
$is_lop2 = false; // Biến kiểm tra lớp 2

try {
    require_once 'db_config.php';
    
    // 1. LẤY DANH SÁCH LỚP
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

    // 2. TRUY VẤN DỮ LIỆU
    if ($lop_id_filter) {
        
        // Kiểm tra quyền và tên lớp
        $is_allowed = false;
        foreach ($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $is_allowed = true;
                $class_name = $class['ten_lop']; 
                
                // --- KIỂM TRA LỚP 2 ---
                if (strpos($class_name, '2-') === 0) {
                    $is_lop2 = true;
                }
                break;
            }
        }

        if (!$is_allowed) {
            die("<h2 style='color:red; text-align:center; margin-top:50px;'>BẠN KHÔNG CÓ QUYỀN TRUY CẬP LỚP NÀY!</h2>");
        }

        $sql = "
            SELECT 
                dhs.so_bao_danh,
                dhs.ho_ten,
                COALESCE(cc.avg_cc, 0) AS tb_chuyen_can,
                COALESCE(sc.avg_test, 0) AS tb_test,
                COALESCE(sc.avg_btvn, 0) AS tb_btvn,
                nxt.nhan_xet
            FROM diem_hoc_sinh dhs
            JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
            JOIN user_lop ul ON u.id = ul.user_id
            
            -- Subquery Trung bình Chuyên Cần
            LEFT JOIN (
                SELECT so_bao_danh, AVG(CASE WHEN trang_thai = 'present' THEN 10 WHEN trang_thai = 'late' THEN 7 ELSE 0 END) as avg_cc
                FROM diem_danh WHERE lop_id = ? AND EXTRACT(MONTH FROM ngay_diem_danh) = ? AND EXTRACT(YEAR FROM ngay_diem_danh) = ? GROUP BY so_bao_danh
            ) cc ON LOWER(dhs.so_bao_danh) = LOWER(cc.so_bao_danh)
            
            -- Subquery Trung bình Điểm Số
            LEFT JOIN (
                SELECT so_bao_danh, AVG(diem_so) as avg_test, AVG(diem_btvn) as avg_btvn
                FROM diem_thanh_phan WHERE lop_id = ? AND EXTRACT(MONTH FROM ngay_kiem_tra) = ? AND EXTRACT(YEAR FROM ngay_kiem_tra) = ? GROUP BY so_bao_danh
            ) sc ON LOWER(dhs.so_bao_danh) = LOWER(sc.so_bao_danh)
            
            -- Nhận xét tháng
            LEFT JOIN nhan_xet_thang nxt 
                ON LOWER(dhs.so_bao_danh) = LOWER(nxt.so_bao_danh)
                AND nxt.lop_id = ? AND nxt.thang = ? AND nxt.nam = ?

            WHERE ul.lop_hoc_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $params = [
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter                              
        ];
        $stmt->execute($params);
        $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- XỬ LÝ DỮ LIỆU & TÍNH ĐIỂM ---
        foreach ($raw_data as $row) {
            // Tách tên để sắp xếp
            $parts = explode(' ', trim($row['ho_ten']));
            $ten = array_pop($parts);
            $ho_dem = implode(' ', $parts);
            $row['ten'] = $ten;
            $row['ho_dem'] = $ho_dem;

            $tb_test = (float)$row['tb_test'];
            $tb_cc   = (float)$row['tb_chuyen_can'];
            $tb_btvn = (float)$row['tb_btvn'];

            // Công thức tính điểm
            if ($is_lop2) {
                // Lớp 2: (TB CC + TB BTVN) / 2
                $tb_tich_luy = ($tb_cc + $tb_btvn) / 2;
            } else {
                // Lớp thường: (TB Test + TB CC + TB BTVN) / 3
                $tb_tich_luy = ($tb_test + $tb_cc + $tb_btvn) / 3;
            }
            
            $row['tb_tich_luy'] = $tb_tich_luy;
            $processed_data[] = $row;
        }

        // --- SẮP XẾP A-Z ---
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
            if ($res === 0) return strcmp(convert_vi_to_en($a['ho_dem']), convert_vi_to_en($b['ho_dem']));
            return $res;
        });
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
    <title>Báo cáo tháng</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-table th { text-align: center; font-size: 0.95em; vertical-align: middle; }
        .report-table td { text-align: center; vertical-align: middle; }
        .col-ho-dem { text-align: left !important; padding-left: 10px !important; }
        .col-ten { text-align: left !important; font-weight: bold; }
        .col-score { font-weight: bold; color: var(--text-color); }
        .col-total { color: #e74c3c; font-weight: bold; background-color: rgba(231, 76, 60, 0.1); }
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
        <h2>Báo cáo tổng hợp tháng</h2>
        <p>Xem điểm trung bình và nhập nhận xét tháng.</p>
        
        <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
        <?php if (isset($_GET['save']) && $_GET['save'] == 'success'): ?>
            <div class="success-msg">Đã lưu nhận xét thành công!</div>
        <?php endif; ?>

        <form class="filter-form" action="monthly_report.php" method="GET">
            <div>
                <label>Chọn lớp:</label>
                <select name="lop_id" required>
                    <option value="">-- Chọn lớp --</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if ($class['id'] == $lop_id_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tháng:</label>
                <input type="number" name="thang" min="1" max="12" value="<?php echo htmlspecialchars($thang_filter); ?>" required>
            </div>
            <div>
                <label>Năm:</label>
                <input type="number" name="nam" min="2020" max="2030" value="<?php echo htmlspecialchars($nam_filter); ?>" required>
            </div>
            <button type="submit">Xem báo cáo</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">
                Báo cáo tổng thể tháng <?php echo $thang_filter . '/' . $nam_filter; ?> - Lớp <?php echo htmlspecialchars($class_name); ?>
            </h3>

            <form action="save_monthly_comments.php" method="POST">
                <input type="hidden" name="lop_id" value="<?php echo $lop_id_filter; ?>">
                <input type="hidden" name="thang" value="<?php echo $thang_filter; ?>">
                <input type="hidden" name="nam" value="<?php echo $nam_filter; ?>">

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">STT</th>
                            <th style="width: 80px;">SBD</th>
                            <th style="width: 150px;">Họ đệm</th>
                            <th style="width: 80px;">Tên</th>
                            
                            <?php if (!$is_lop2): ?>
                                <th style="width: 80px;">TB Test</th>
                            <?php endif; ?>
                            
                            <th style="width: 80px;">TB Chuyên Cần</th>
                            <th style="width: 80px;">TB BTVN</th>
                            <th style="width: 80px;">TB Tích lũy</th>
                            <th class = "no-print" style="width: 80px;">Hành động</th> </tr>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($processed_data)): ?>
                            <tr><td colspan="<?php echo $is_lop2 ? '8' : '9'; ?>">Chưa có dữ liệu cho tháng này.</td></tr>
                        <?php else: ?>
                            <?php $stt = 1; foreach ($processed_data as $row): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                    
                                    <td class="col-ho-dem"><?php echo htmlspecialchars($row['ho_dem']); ?></td>
                                    <td class="col-ten"><?php echo htmlspecialchars($row['ten']); ?></td>
                                    
                                    <?php if (!$is_lop2): ?>
                                        <td class="col-score"><?php echo number_format($row['tb_test'], 1); ?></td>
                                    <?php endif; ?>
                                    
                                    <td class="col-score" style="color: green;"><?php echo number_format($row['tb_chuyen_can'], 1); ?></td>
                                    <td class="col-score" style="color: blue;"><?php echo number_format($row['tb_btvn'], 1); ?></td>
                                    
                                    <td class="col-total"><?php echo number_format($row['tb_tich_luy'], 2); ?></td>
                                    
                                    <td class="no-print">
                                        <a href="student_monthly_report.php?sbd=<?php echo $row['so_bao_danh']; ?>&month=<?php echo $thang_filter; ?>&year=<?php echo $nam_filter; ?>&lop_id=<?php echo $lop_id_filter; ?>" 
                                            class="btn-edit" style="font-size: 0.8em; white-space: nowrap;">
                                        <i class="fas fa-chart-line"></i> Xem chi tiết
                                    </a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p class ="no-print" style="font-style: italic; color: var(--text-color-light); margin-top: 10px;">
                * Lưu ý: Khi xuất file PDF thì để chế độ Landscape.<br>
                <?php if ($is_lop2): ?>
                    * Điểm Tích Lũy = (TB CC + TB BTVN) / 2
                <?php else: ?>
                    * Điểm Tích Lũy = (TB Điểm Test + TB CC + TB BTVN) / 3.
                <?php endif; ?>
                </p>

            </form>
            
            <img src="nhatdao_watermark.png" class="watermark-print-logo" alt="Watermark">
            <div style="margin-top: 20px; text-align: right; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-file-pdf"></i> Xuất file PDF
                </button>
            </div>
        <?php endif; ?>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>