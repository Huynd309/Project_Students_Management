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
$class_name = "";

try {
    require_once 'db_config.php';
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

    // 2. Truy vấn dữ liệu tổng hợp
    if ($lop_id_filter) {
        
        $is_allowed = false;
        foreach ($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $is_allowed = true;
                $class_name = $class['ten_lop']; 
                break;
            }
        }

        if (!$is_allowed) {
            die("<h2 style='color:red; text-align:center; margin-top:50px;'>BẠN KHÔNG CÓ QUYỀN TRUY CẬP BÁO CÁO CỦA LỚP NÀY!</h2>");
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
            
            LEFT JOIN (
                SELECT 
                    so_bao_danh,
                    AVG(CASE 
                        WHEN trang_thai = 'present' THEN 10 
                        WHEN trang_thai = 'late' THEN 7 
                        ELSE 0 
                    END) as avg_cc
                FROM diem_danh
                WHERE lop_id = ? 
                  AND EXTRACT(MONTH FROM ngay_diem_danh) = ? 
                  AND EXTRACT(YEAR FROM ngay_diem_danh) = ?
                GROUP BY so_bao_danh
            ) cc ON LOWER(dhs.so_bao_danh) = LOWER(cc.so_bao_danh)
            
            LEFT JOIN (
                SELECT 
                    so_bao_danh,
                    AVG(diem_so) as avg_test,
                    AVG(diem_btvn) as avg_btvn
                FROM diem_thanh_phan
                WHERE lop_id = ? 
                  AND EXTRACT(MONTH FROM ngay_kiem_tra) = ? 
                  AND EXTRACT(YEAR FROM ngay_kiem_tra) = ?
                GROUP BY so_bao_danh
            ) sc ON LOWER(dhs.so_bao_danh) = LOWER(sc.so_bao_danh)
            
            LEFT JOIN nhan_xet_thang nxt 
                ON LOWER(dhs.so_bao_danh) = LOWER(nxt.so_bao_danh)
                AND nxt.lop_id = ?
                AND nxt.thang = ?
                AND nxt.nam = ?

            WHERE ul.lop_hoc_id = ?
            ORDER BY dhs.ho_ten ASC
        ";

        $stmt = $conn->prepare($sql);
        $params = [
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter, $thang_filter, $nam_filter, 
            $lop_id_filter                              
        ];
        $stmt->execute($params);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        .report-table th { text-align: center; font-size: 0.95em; }
        .report-table td { text-align: center; vertical-align: middle; }
        .report-table td:nth-child(2) { text-align: left; } 
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

    <main class="container" style="max-width: 1200px;">
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
                Báo cáo tháng <?php echo $thang_filter . '/' . $nam_filter; ?> - Lớp <?php echo htmlspecialchars($class_name); ?>
            </h3>

            <form action="save_monthly_comments.php" method="POST">
                <input type="hidden" name="lop_id" value="<?php echo $lop_id_filter; ?>">
                <input type="hidden" name="thang" value="<?php echo $thang_filter; ?>">
                <input type="hidden" name="nam" value="<?php echo $nam_filter; ?>">

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">SBD</th>
                            <th style="width: 180px;">Họ và tên</th>
                            <th style="width: 80px;">TB Test</th>
                            <th style="width: 80px;">TB CC</th>
                            <th style="width: 80px;">TB BTVN</th>
                            <th style="width: 80px;">TB Tích lũy</th>
                            <th>Nhận xét tháng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr><td colspan="7">Chưa có dữ liệu cho tháng này.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $row): ?>
                                <?php 
                                    $tb_test = (float)$row['tb_test'];
                                    $tb_cc   = (float)$row['tb_chuyen_can'];
                                    $tb_btvn = (float)$row['tb_btvn'];

                                    $tb_tich_luy = ($tb_test + $tb_cc + $tb_btvn) / 3;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                    
                                    <td class="col-score"><?php echo number_format($tb_test, 1); ?></td>
                                    <td class="col-score" style="color: green;"><?php echo number_format($tb_cc, 1); ?></td>
                                    <td class="col-score" style="color: blue;"><?php echo number_format($tb_btvn, 1); ?></td>
                                    
                                    <td class="col-total"><?php echo number_format($tb_tich_luy, 2); ?></td>
                                    
                                    <td>
                                        <textarea class="comment-box" 
                                                  name="nhanxet[<?php echo htmlspecialchars($row['so_bao_danh']); ?>]" 
                                                  placeholder="Nhập nhận xét..."><?php echo htmlspecialchars($row['nhan_xet'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p style="font-style: italic; color: var(--text-color-light); margin-top: 10px;">
                * Lưu ý: Khi xuất file PDF thì để chế độ Landscape thay vì Portrait để hiển thị nhận xét rõ hơn nhé.<br>
                * Điểm Tích Lũy được tính theo công thức: (TB Điểm Test + Điểm CC + Điểm BTVN) / 3.
                </p>

                <?php if (!empty($report_data)): ?>
                    <button type="submit" class="submit-btn">Lưu nhận xét</button>
                <?php endif; ?>
            </form>
            <div style="margin-top: 20px; text-align: right; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-file-pdf"></i> Xuất file PDF
                </button>
            </div>
        <?php endif; ?>
    </main>
    <img src="nhatdao_watermark.png" class="watermark-print-logo" alt="Watermark">
    <script src="admin_main.js"></script>
</body>
</html>