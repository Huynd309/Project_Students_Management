<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$month = $_GET['month'] ?? $_GET['thang'] ?? date('m');
$year = $_GET['year'] ?? $_GET['nam'] ?? date('Y');
$lop_id = $_GET['lop_id'] ?? '';

if (!$lop_id) die("Thiếu thông tin lớp.");

$students_data = [];
$class_name = "";

try {
    require_once 'db_config.php';

    // 1. Lấy tên lớp
    $stmt_lop = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmt_lop->execute([$lop_id]);
    $class_name = $stmt_lop->fetchColumn();

    // 2. Lấy danh sách học sinh
    $stmt_list = $conn->prepare("
        SELECT dhs.so_bao_danh, dhs.ho_ten, dhs.truong 
        FROM diem_hoc_sinh dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        WHERE ul.lop_hoc_id = ?
        ORDER BY dhs.ho_ten ASC 
    ");
    $stmt_list->execute([$lop_id]);
    $student_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    // 3. Vòng lặp lấy dữ liệu từng học sinh
    foreach ($student_list as $std) {
        $sbd = $std['so_bao_danh'];
        
        // A. Lấy dữ liệu Điểm danh & Tính điểm TB Chuyên cần (CC)
        $attendance_stats = ['present' => 0, 'late' => 0, 'absent' => 0];
        $stmt_att = $conn->prepare("
            SELECT trang_thai, COUNT(*) as cnt FROM diem_danh 
            WHERE so_bao_danh = ? AND lop_id = ? AND EXTRACT(MONTH FROM ngay_diem_danh) = ? AND EXTRACT(YEAR FROM ngay_diem_danh) = ?
            GROUP BY trang_thai
        ");
        $stmt_att->execute([$sbd, $lop_id, $month, $year]);
        while($row = $stmt_att->fetch(PDO::FETCH_ASSOC)) {
            $attendance_stats[$row['trang_thai']] = $row['cnt'];
        }

        // Tính điểm TB Chuyên cần (Thang 10)
        $total_sessions = $attendance_stats['present'] + $attendance_stats['late'] + $attendance_stats['absent'];
        $total_cc_score = ($attendance_stats['present'] * 10) + ($attendance_stats['late'] * 7) + ($attendance_stats['absent'] * 0);
        
        // Nếu chưa học buổi nào thì mặc định 10, ngược lại tính trung bình
        $avg_cc_score = ($total_sessions > 0) ? ($total_cc_score / $total_sessions) : 10;


        // B. Lấy dữ liệu Điểm số
        $stmt_scores = $conn->prepare("
            SELECT t1.ngay_kiem_tra, t1.ten_cot_diem, t1.diem_so, t1.diem_btvn,
            (SELECT AVG(t2.diem_so) FROM diem_thanh_phan t2 WHERE t2.lop_id = t1.lop_id AND t2.ngay_kiem_tra = t1.ngay_kiem_tra AND t2.ten_cot_diem = t1.ten_cot_diem) as diem_tb_lop
            FROM diem_thanh_phan t1
            WHERE t1.so_bao_danh = ? AND t1.lop_id = ? 
              AND EXTRACT(MONTH FROM t1.ngay_kiem_tra) = ? AND EXTRACT(YEAR FROM t1.ngay_kiem_tra) = ?
            ORDER BY t1.ngay_kiem_tra ASC
        ");
        $stmt_scores->execute([$sbd, $lop_id, $month, $year]);
        $scores = $stmt_scores->fetchAll(PDO::FETCH_ASSOC);

        $stmt_cmt = $conn->prepare("SELECT nhan_xet FROM nhan_xet_thang WHERE so_bao_danh = ? AND lop_id = ? AND thang = ? AND nam = ?");
        $stmt_cmt->execute([$sbd, $lop_id, $month, $year]);
        $comment = $stmt_cmt->fetchColumn();

        $students_data[] = [
            'info' => $std,
            'scores' => $scores,
            'attendance' => $attendance_stats,
            'avg_cc' => $avg_cc_score, 
            'comment' => $comment
        ];
    }

} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Báo Cáo Tháng <?php echo htmlspecialchars($month) . '/' . htmlspecialchars($year); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #eee; margin: 0; padding: 0; }
        .bulk-container { width: 210mm; margin: 0 auto; }
        
        .single-report-page {
            background: white;
            padding: 30px 40px; 
            margin-bottom: 20px;
            page-break-after: always;
            position: relative;
            height: 296mm; /* Khổ A4 cố định */
            max-height: 296mm;
            box-sizing: border-box;
            overflow: hidden; 
        }

        .report-header-print {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;
        }
        .report-header-text h1 { margin: 0; font-size: 20pt; color: #000; text-transform: uppercase; }
        .report-header-text p { margin: 2px 0 0 0; font-size: 12pt; color: #333; }
        .report-header-logo img { height: 80px; object-fit: contain; }

        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .info-card { border: 1px solid #ccc; padding: 10px; border-radius: 6px; font-size: 11pt; }
        .info-card h4 { margin: 0 0 5px 0; font-size: 12pt; }
        
        .chart-container { 
            height: 220px; /* Chart nhỏ gọn */
            margin-bottom: 15px; 
            border: 1px solid #eee; padding: 5px; background: #fff;
        }

        h4.section-title { margin: 10px 0 5px 0; font-size: 12pt; border-bottom: 1px dashed #ccc; padding-bottom: 3px; }

        /* Bảng điểm gọn */
        table { font-size: 10pt; width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border: 1px solid #000; text-align: center; }

        .teacher-comment-section {
            margin-top: 15px;
            border: 2px solid #000;
            padding: 10px;
            border-radius: 8px;
            font-size: 12pt;
            line-height: 1.4;
            max-height: 150px; 
            overflow: hidden;
        }
        
        @media print {
            body { background: white; }
            .no-print-bulk { display: none !important; }
            .bulk-container { width: 100%; margin: 0; }
            .single-report-page { margin: 0; border: none; padding: 20px 30px !important; height: 100vh !important; }
            .chart-container { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="no-print-bulk" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <button onclick="window.print()" class="btn-print" style="box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            <i class="fas fa-print"></i> In Tất Cả
        </button>
    </div>

    <div class="bulk-container">
        
        <?php foreach ($students_data as $data): 
            $sbd = $data['info']['so_bao_danh'];
            $chart_id = "chart_" . $sbd;
            
            // Lấy điểm TB CC đã tính ở trên
            $avg_cc = $data['avg_cc']; 
        ?>
            
            <div class="single-report-page">
                
                <div class="report-header-print">
                    <div class="report-header-text">
                        <h1>PHIẾU BÁO CÁO HỌC TẬP</h1>
                        <p>Tháng: <strong><?php echo "$month/$year"; ?></strong> &nbsp;|&nbsp; Lớp: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
                    </div>
                    <div class="report-header-logo">
                        <img src="nhatdao_watermark.png" alt="Logo">
                    </div>
                </div>

                <div class="report-grid">
                    <div class="info-card">
                        <h4><i class="fas fa-user"></i> Học sinh</h4>
                        <p><strong><?php echo htmlspecialchars($data['info']['ho_ten']); ?></strong> (<?php echo htmlspecialchars($sbd); ?>)</p>
                        <p>Trường: <?php echo htmlspecialchars($data['info']['truong'] ?? ''); ?></p>
                    </div>
                    <div class="info-card">
                        <h4><i class="fas fa-clock"></i> Chuyên cần</h4>
                        <div style="display: flex; justify-content: space-around; font-weight: bold;">
                            <span style="color: green;">Có mặt: <?php echo $data['attendance']['present']; ?></span>
                            <span style="color: orange;">Muộn: <?php echo $data['attendance']['late']; ?></span>
                            <span style="color: red;">Vắng: <?php echo $data['attendance']['absent']; ?></span>
                        </div>
                    </div>
                </div>

                <h4 class="section-title"><i class="fas fa-chart-line"></i> Biểu đồ điểm tích lũy</h4>
                <div class="chart-container">
                    <canvas id="<?php echo $chart_id; ?>"></canvas>
                </div>

                <h4 class="section-title"><i class="fas fa-table"></i> Chi tiết điểm số</h4>
                <div style="min-height: 100px; margin-bottom: 10px;"> 
                    <table>
                        <thead>
                            <tr style="background: #f0f0f0;">
                                <th>Ngày</th>
                                <th>Bài kiểm tra</th>
                                <th>Điểm HS</th>
                                <th>TB Lớp</th>
                                <th>BTVN</th>
                                <th>Điểm Tích Lũy</th> </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $labels = []; $scores_tichluy = []; $scores_lop = [];
                            
                            if (empty($data['scores'])): ?>
                                <tr><td colspan="6">Chưa có bài kiểm tra.</td></tr>
                            <?php else: 
                                foreach ($data['scores'] as $s):
                                    if ($s['diem_so'] !== null) {
                                        $labels[] = date('d/m', strtotime($s['ngay_kiem_tra']));
                                        
                                        // --- TÍNH ĐIỂM TÍCH LŨY ---
                                        $diem_test = (float)$s['diem_so'];
                                        $diem_btvn = ($s['diem_btvn'] !== null) ? (float)$s['diem_btvn'] : 0;
                                        
                                        // Công thức: (Test*2 + CC + BTVN) / 4
                                        // Lưu ý: $avg_cc là điểm chuyên cần trung bình của cả tháng
                                        $tich_luy = ($diem_test * 2 + $avg_cc + $diem_btvn) / 4;
                                        
                                        $scores_tichluy[] = round($tich_luy, 2);
                                        $scores_lop[] = (float)$s['diem_tb_lop']; // Vẫn giữ TB lớp là điểm Test để tham chiếu
                                    }
                            ?>
                                <tr>
                                    <td><?php echo date('d/m', strtotime($s['ngay_kiem_tra'])); ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($s['ten_cot_diem']); ?></td>
                                    <td style="font-weight:bold; color: #007bff;"><?php echo $s['diem_so'] ?? '-'; ?></td>
                                    <td style="color: #666;"><?php echo number_format((float)$s['diem_tb_lop'], 2); ?></td>
                                    <td><?php echo $s['diem_btvn'] ?? '-'; ?></td>
                                    
                                    <td style="font-weight:bold; color: #e74c3c;">
                                        <?php echo isset($tich_luy) ? number_format($tich_luy, 2) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="teacher-comment-section">
                    <span style="font-weight: bold; text-decoration: underline;">NHẬN XÉT CỦA GIÁO VIÊN:</span>
                    <span style="font-family: 'Times New Roman', serif; margin-left: 5px;">
                        <?php 
                            $clean_comment = str_replace(array("\r", "\n"), ' ', $data['comment'] ?? '');
                            echo htmlspecialchars($clean_comment); 
                        ?>
                    </span>
                </div>

            </div>

            <script>
                new Chart(document.getElementById('<?php echo $chart_id; ?>'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'Điểm Tích Lũy Học Sinh', 
                                data: <?php echo json_encode($scores_tichluy); ?>, 
                                borderColor: '#50ceeb', 
                                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                borderWidth: 3, pointRadius: 4, tension: 0, fill: true
                            }, 
                            {
                                label: 'Trung Bình', 
                                data: <?php echo json_encode($scores_lop); ?>,
                                borderColor: '#95a5a6', borderWidth: 2, borderDash: [5, 5],
                                pointRadius: 0, pointRadius: 3, tension: 0, fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: { y: { min: 0, max: 12, ticks: { stepSize: 1, callback: function(v){return v<=10?v:null} } } },
                        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 10, font: { size: 10 } } } },
                        animation: false
                    }
                });
            </script>

        <?php endforeach; ?>
    </div>
</body>
</html>