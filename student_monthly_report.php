<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$sbd = $_GET['sbd'] ?? '';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$lop_id = $_GET['lop_id'] ?? '';

if (!$sbd || !$lop_id) die("Thiếu thông tin.");

$student = null;
$scores = [];
$attendance_stats = ['present' => 0, 'late' => 0, 'absent' => 0];
$comment = "";
$ten_lop = $lop_id; // Mặc định là ID nếu chưa tìm thấy tên

try {
    require_once 'db_config.php';
    
    // 1. Lấy thông tin học sinh
    $stmt_std = $conn->prepare("SELECT ho_ten, truong, sdt_phu_huynh FROM diem_hoc_sinh WHERE so_bao_danh = ?");
    $stmt_std->execute([$sbd]);
    $student = $stmt_std->fetch(PDO::FETCH_ASSOC);

    // --- MỚI: LẤY TÊN LỚP TỪ ID ---
    $stmt_lop = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmt_lop->execute([$lop_id]);
    $lop_row = $stmt_lop->fetch(PDO::FETCH_ASSOC);
    if ($lop_row) {
        $ten_lop = $lop_row['ten_lop'];
    }
    // -----------------------------

    // 2. Lấy chi tiết điểm VÀ TÍNH TRUNG BÌNH LỚP
    $stmt_scores = $conn->prepare("
        SELECT 
            t1.ngay_kiem_tra, 
            t1.ten_cot_diem, 
            t1.diem_so, 
            t1.diem_btvn,
            (
                SELECT AVG(t2.diem_so)
                FROM diem_thanh_phan t2
                WHERE t2.lop_id = t1.lop_id 
                  AND t2.ngay_kiem_tra = t1.ngay_kiem_tra 
                  AND t2.ten_cot_diem = t1.ten_cot_diem
            ) as diem_tb_lop
        FROM diem_thanh_phan t1
        WHERE t1.so_bao_danh = ? AND t1.lop_id = ? 
          AND EXTRACT(MONTH FROM t1.ngay_kiem_tra) = ? AND EXTRACT(YEAR FROM t1.ngay_kiem_tra) = ?
        ORDER BY t1.ngay_kiem_tra ASC
    ");
    $stmt_scores->execute([$sbd, $lop_id, $month, $year]);
    $scores = $stmt_scores->fetchAll(PDO::FETCH_ASSOC);

    // 3. Thống kê điểm danh
    $stmt_att = $conn->prepare("
        SELECT trang_thai, COUNT(*) as cnt 
        FROM diem_danh 
        WHERE so_bao_danh = ? AND lop_id = ? 
          AND EXTRACT(MONTH FROM ngay_diem_danh) = ? AND EXTRACT(YEAR FROM ngay_diem_danh) = ?
        GROUP BY trang_thai
    ");
    $stmt_att->execute([$sbd, $lop_id, $month, $year]);
    while($row = $stmt_att->fetch(PDO::FETCH_ASSOC)) {
        $attendance_stats[$row['trang_thai']] = $row['cnt'];
    }

    // 4. Lấy nhận xét
    $stmt_cmt = $conn->prepare("SELECT nhan_xet FROM nhan_xet_thang WHERE so_bao_danh = ? AND lop_id = ? AND thang = ? AND nam = ?");
    $stmt_cmt->execute([$sbd, $lop_id, $month, $year]);
    $comment = $stmt_cmt->fetchColumn();

} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tháng: <?php echo htmlspecialchars($student['ho_ten']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-card { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.3); }
        .stat-box { display: flex; justify-content: space-between; margin-top: 10px; }
        .stat-item { text-align: center; flex: 1; }
        .stat-num { font-size: 1.5em; font-weight: bold; display: block; }
        
        .chart-container { 
            background: rgba(255,255,255,0.95); 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        @media print {
            .no-print, .header, .back-link, .submit-btn, .theme-switch-wrapper { display: none !important; }
            .container { width: 100% !important; margin: 0 !important; box-shadow: none !important; border: none !important; background: white !important; }
            .chart-container { page-break-inside: avoid; border: 1px solid #ccc; }
            .info-card { border: 1px solid #000; color: #000; }
        }
    </style>
</head>
<body class="admin-page-blue">
    
    <header class="header no-print">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">Admin Mode</span>
            
            <div class="theme-switch-wrapper" style="margin-right: 15px;">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>

            <a href="monthly_report.php?lop_id=<?php echo $lop_id; ?>&thang=<?php echo $month; ?>&nam=<?php echo $year; ?>"><button id="login-btn">Quay lại</button></a>
        </div>
    </header>

    <main class="container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;">
            <div>
                <h1 style="border: none; padding: 0; margin: 0;">PHIẾU BÁO CÁO HỌC TẬP <?php echo htmlspecialchars(mb_strtoupper($student['ho_ten'])); ?></h1>
                <p style="margin: 5px 0;">Tháng: <strong><?php echo "$month/$year"; ?></strong></p>
            </div>
            <div style="text-align: right;">
                <img src="nhatdao_watermark.png" style="height: 60px; opacity: 0.8;">
            </div>
        </div>

        <div class="report-grid">
            <div class="info-card">
                <h3><i class="fas fa-user-graduate"></i> Thông tin học sinh</h3>
                <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student['ho_ten']); ?></p>
                <p><strong>SBD:</strong> <?php echo htmlspecialchars($sbd); ?></p>
                <p><strong>Trường:</strong> <?php echo htmlspecialchars($student['truong'] ?? ''); ?></p>
                <p><strong>Lớp:</strong> <?php echo htmlspecialchars($ten_lop); ?></p>
                
                <?php if (!empty($student['sdt_phu_huynh'])): ?>
                    <div class="no-print" style="margin-top: 10px;">
                        <a href="https://zalo.me/<?php echo $student['sdt_phu_huynh']; ?>" target="_blank" class="btn-zalo">
                            <i class="fas fa-comment"></i> Chat Zalo PH
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-calendar-check"></i> Chuyên cần tháng <?php echo $month; ?></h3>
                <div class="stat-box">
                    <div class="stat-item" style="color: #27ae60;">
                        <span class="stat-num"><?php echo $attendance_stats['present']; ?></span> Có mặt
                    </div>
                    <div class="stat-item" style="color: #e67e22;">
                        <span class="stat-num"><?php echo $attendance_stats['late']; ?></span> Muộn
                    </div>
                    <div class="stat-item" style="color: #c0392b;">
                        <span class="stat-num"><?php echo $attendance_stats['absent']; ?></span> Vắng
                    </div>
                </div>
            </div>
        </div>

        <h3><i class="fas fa-chart-line"></i> Biểu đồ điểm kiểm tra</h3>
        <div class="chart-container">
            <canvas id="scoreChart" height="100"></canvas>
        </div>

        <h3><i class="fas fa-list"></i> Chi tiết điểm số</h3>
        <table>
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Bài kiểm tra</th>
                    <th>Điểm HS</th>
                    <th>TB Lớp</th>
                    <th>Điểm BTVN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scores)): ?>
                    <tr><td colspan="5" style="text-align: center;">Chưa có bài kiểm tra nào trong tháng này.</td></tr>
                <?php else: ?>
                    <?php 
                        $chart_labels = [];
                        $data_student = [];
                        $data_class_avg = [];
                        
                        foreach ($scores as $s): 
                            if ($s['diem_so'] !== null) {
                                $chart_labels[] = $s['ten_cot_diem'] . " (" . date('d/m', strtotime($s['ngay_kiem_tra'])) . ")";
                                $data_student[] = (float)$s['diem_so'];
                                $data_class_avg[] = number_format((float)$s['diem_tb_lop'], 2);
                            }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($s['ngay_kiem_tra'])); ?></td>
                        <td><?php echo htmlspecialchars($s['ten_cot_diem']); ?></td>
                        <td style="font-weight: bold; color: var(--primary-color); font-size: 1.1em;"><?php echo $s['diem_so'] ?? '-'; ?></td>
                        <td style="color: #e74c3c; font-style: italic;"><?php echo number_format((float)$s['diem_tb_lop'], 2); ?></td>
                        <td><?php echo $s['diem_btvn'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px;">
            <h3><i class="fas fa-comment-dots"></i> Nhận xét của giáo viên</h3>
            
            <form action="save_monthly_comments.php" method="POST">
                <input type="hidden" name="lop_id" value="<?php echo $lop_id; ?>">
                <input type="hidden" name="thang" value="<?php echo $month; ?>">
                <input type="hidden" name="nam" value="<?php echo $year; ?>">
                <input type="hidden" name="sbd_single" value="<?php echo $sbd; ?>">

                <textarea class="comment-box" name="nhanxet[<?php echo $sbd; ?>]" 
                          placeholder="Nhập nhận xét chi tiết về học sinh này..."
                          style="width: 100%; height: 120px; padding: 15px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; font-size: 1.1em;"><?php echo htmlspecialchars($comment ?? ''); ?></textarea>
                
                <div class="no-print" style="margin-top: 15px; text-align: right;">
                    <button type="submit" class="submit-btn" style="width: auto;">Lưu nhận xét</button>
                    <button type="button" onclick="window.print()" class="btn-print" style="margin-left: 10px;">
                        <i class="fas fa-file-pdf"></i> Xuất PDF
                    </button>
                </div>
            </form>
        </div>

    </main>

    <script>
        const ctx = document.getElementById('scoreChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Điểm của học sinh',
                            data: <?php echo json_encode($data_student); ?>,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            tension: 0, 
                            fill: true
                        },
                        {
                            label: 'Trung bình lớp',
                            data: <?php echo json_encode($data_class_avg); ?>,
                            borderColor: '#e74c3c',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 4,
                            tension: 0, 
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: { y: { min: 0, max: 10, title: { display: true, text: 'Thang điểm 10' } } },
                    plugins: { legend: { position: 'top' } },
                    animation: false
                }
            });
        }
    </script>
    <script src="admin_main.js"></script>
</body>
</html>