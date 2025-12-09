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

    // 2. Lấy danh sách tất cả học sinh
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
        
        // a. Điểm số & TB Lớp
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

        // b. Điểm danh
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

        // c. Nhận xét
        $stmt_cmt = $conn->prepare("SELECT nhan_xet FROM nhan_xet_thang WHERE so_bao_danh = ? AND lop_id = ? AND thang = ? AND nam = ?");
        $stmt_cmt->execute([$sbd, $lop_id, $month, $year]);
        $comment = $stmt_cmt->fetchColumn();

        $students_data[] = [
            'info' => $std,
            'scores' => $scores,
            'attendance' => $attendance_stats,
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
    <title>In Báo Cáo </title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #eee; }
        .bulk-container { width: 210mm; margin: 0 auto; }
        
        .single-report-page {
            background: white;
            padding: 40px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            page-break-after: always;
            position: relative;
            min-height: 297mm;
            box-sizing: border-box;
        }

        /* HEADER MỚI: Logo bên phải, Text bên trái */
        .report-header-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .report-header-text h1 {
            margin: 0; font-size: 24pt; color: #000; text-transform: uppercase;
        }
        .report-header-text p {
            margin: 5px 0 0 0; font-size: 14pt; color: #333;
        }
        .report-header-logo img {
            height: 100px; 
            object-fit: contain;
        }

        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-card { border: 1px solid #ccc; padding: 15px; border-radius: 8px; }
        
        .chart-container { 
            height: 300px; 
            margin-bottom: 20px; 
            border: 1px solid #eee;
            padding: 10px;
            background: #fff;
        }

        @media print {
            body { background: white; }
            .no-print-bulk { display: none !important; }
            .bulk-container { width: 100%; margin: 0; }
            .single-report-page { box-shadow: none; margin: 0; border: none; height: auto; min-height: 0; padding: 20px 40px !important; }
            .chart-container { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div class="no-print-bulk" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <button onclick="window.print()" class="btn-print" style="box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            <i class="fas fa-print"></i> In Tất Cả Báo Cáo
        </button>
    </div>

    <div class="bulk-container">
        
        <?php foreach ($students_data as $data): 
            $sbd = $data['info']['so_bao_danh'];
            $chart_id = "chart_" . $sbd;
        ?>
            
            <div class="single-report-page">
                
                <div class="report-header-print">
                    <div class="report-header-text">
                        <h1>PHIẾU BÁO CÁO HỌC TẬP</h1>
                        <p>Tháng: <strong><?php echo "$month/$year"; ?></strong></p>
                        <p>Lớp: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
                    </div>
                    <div class="report-header-logo">
                        <img src="nhatdao_watermark.png" alt="Logo">
                    </div>
                </div>
                <div class="report-grid">
                    <div class="info-card">
                        <h4 style="margin-top:0;"><i class="fas fa-user"></i> Học sinh</h4>
                        <p>Họ tên: <strong><?php echo htmlspecialchars($data['info']['ho_ten']); ?></strong></p>
                        <p>SBD: <?php echo htmlspecialchars($sbd); ?></p>
                        <p>Trường: <?php echo htmlspecialchars($data['info']['truong'] ?? ''); ?></p>
                    </div>
                    <div class="info-card">
                        <h4 style="margin-top:0;"><i class="fas fa-clock"></i> Chuyên cần</h4>
                        <div style="display: flex; justify-content: space-between; text-align: center; font-weight: bold; margin-top: 10px;">
                            <div style="color: green;">Có mặt<br><span style="font-size: 1.5em;"><?php echo $data['attendance']['present']; ?></span></div>
                            <div style="color: orange;">Muộn<br><span style="font-size: 1.5em;"><?php echo $data['attendance']['late']; ?></span></div>
                            <div style="color: red;">Vắng<br><span style="font-size: 1.5em;"><?php echo $data['attendance']['absent']; ?></span></div>
                        </div>
                    </div>
                </div>

                <h4><i class="fas fa-chart-area"></i> Biểu đồ phát triển năng lực</h4>
                <div class="chart-container">
                    <canvas id="<?php echo $chart_id; ?>"></canvas>
                </div>

                <h4><i class="fas fa-table"></i> Chi tiết điểm số</h4>
                <table style="width:100%; border-collapse: collapse; border: 1px solid #000; font-size: 11pt;">
                    <thead>
                        <tr style="background: #f0f0f0;">
                            <th style="border: 1px solid #000; padding: 8px;">Ngày</th>
                            <th style="border: 1px solid #000; padding: 8px;">Bài kiểm tra</th>
                            <th style="border: 1px solid #000; padding: 8px;">Điểm HS</th>
                            <th style="border: 1px solid #000; padding: 8px;">TB Lớp</th>
                            <th style="border: 1px solid #000; padding: 8px;">BTVN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $labels = []; $scores_hs = []; $scores_lop = [];
                        if (empty($data['scores'])): ?>
                            <tr><td colspan="5" style="border: 1px solid #000; padding: 8px; text-align: center;">Chưa có bài kiểm tra.</td></tr>
                        <?php else: 
                            foreach ($data['scores'] as $s):
                                if ($s['diem_so'] !== null) {
                                    $labels[] = date('d/m', strtotime($s['ngay_kiem_tra']));
                                    $scores_hs[] = (float)$s['diem_so'];
                                    $scores_lop[] = (float)$s['diem_tb_lop'];
                                }
                        ?>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo date('d/m', strtotime($s['ngay_kiem_tra'])); ?></td>
                                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($s['ten_cot_diem']); ?></td>
                                <td style="border: 1px solid #000; padding: 8px; font-weight:bold; text-align:center; color: #007bff;"><?php echo $s['diem_so'] ?? '-'; ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align:center; color: #666;"><?php echo number_format((float)$s['diem_tb_lop'], 2); ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align:center;"><?php echo $s['diem_btvn'] ?? '-'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; border: 2px solid #000; padding: 15px; border-radius: 8px; min-height: 120px;">
                    <h4 style="margin-top: 0; text-decoration: underline;">NHẬN XÉT CỦA GIÁO VIÊN:</h4>
                    <p style="font-family: 'Times New Roman', serif; font-size: 1.2em; font-weight: bold; margin-top: 10px;">
                        <?php echo nl2br(htmlspecialchars($data['comment'] ?? '')); ?>
                    </p>
                </div>
            </div>

            <script>
                new Chart(document.getElementById('<?php echo $chart_id; ?>'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'Điểm HS',
                                data: <?php echo json_encode($scores_hs); ?>,
                                borderColor: '#007bff',      
                                backgroundColor: 'rgba(0, 123, 255, 0.1)', 
                                borderWidth: 3,
                                pointRadius: 5,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#007bff',
                                pointBorderWidth: 2,
                                tension: 0, 
                                fill: true  
                            }, 
                            {
                                label: 'TB Lớp',
                                data: <?php echo json_encode($scores_lop); ?>,
                                borderColor: '#e74c3c',       
                                borderWidth: 2,
                                borderDash: [5, 5],           
                                pointRadius: 0,               
                                tension: 0,                 
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { 
                                min: 0, 
                                max: 10,
                                ticks: { stepSize: 1 }
                            }
                        },
                        plugins: {
                            legend: { display: true, position: 'top' }
                        },
                        animation: false
                    }
                });
            </script>

        <?php endforeach; ?>
    </div>
</body>
</html>