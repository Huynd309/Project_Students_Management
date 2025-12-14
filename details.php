<?php
session_start();
require_once 'db_config.php';

if (!isset($_GET['sbd']) || !isset($_GET['lop_id'])) {
    die('Vui lòng cung cấp số báo danh và lớp.');
}

$sbd = $_GET['sbd'];
$lop_id = $_GET['lop_id'];

$hocsinh = null;
$scores = [];
$comments = [];

try {
    // 1. Lấy thông tin học sinh
    $stmt_info = $conn->prepare("
        SELECT dhs.ho_ten, dhs.truong, dhs.so_bao_danh, lh.ten_lop
        FROM diem_hoc_sinh dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        JOIN lop_hoc lh ON ul.lop_hoc_id = lh.id
        WHERE LOWER(dhs.so_bao_danh) = LOWER(?) AND lh.id = ?
    ");
    $stmt_info->execute([$sbd, $lop_id]);
    $hocsinh = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$hocsinh) die("Không tìm thấy học sinh này trong lớp.");

    // 2. Lấy điểm số (SẮP XẾP MỚI NHẤT LÊN ĐẦU CHO BẢNG)
    $sql_score = "
        SELECT t1.ngay_kiem_tra, t1.ten_cot_diem, t1.diem_so, t1.diem_btvn,
        (
            SELECT AVG(t2.diem_so) 
            FROM diem_thanh_phan t2 
            WHERE t2.lop_id = t1.lop_id 
              AND t2.ngay_kiem_tra = t1.ngay_kiem_tra 
              AND t2.ten_cot_diem = t1.ten_cot_diem
        ) as diem_tb_lop
        FROM diem_thanh_phan t1
        WHERE t1.so_bao_danh = ? AND t1.lop_id = ?
        ORDER BY t1.ngay_kiem_tra DESC  -- <--- ĐỔI THÀNH DESC ĐỂ NGÀY GẦN NHẤT LÊN ĐẦU
    ";
    $stmt_score = $conn->prepare($sql_score);
    $stmt_score->execute([$sbd, $lop_id]);
    $scores = $stmt_score->fetchAll(PDO::FETCH_ASSOC);

    // 3. Lấy nhận xét
    $stmt_cmt = $conn->prepare("
        SELECT thang, nam, nhan_xet 
        FROM nhan_xet_thang 
        WHERE so_bao_danh = ? AND lop_id = ? 
        ORDER BY nam DESC, thang DESC
    ");
    $stmt_cmt->execute([$sbd, $lop_id]);
    $comments = $stmt_cmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}

$chart_data = array_reverse($scores); 

$labels = []; 
$data_hs = []; 
$data_lop = [];
$test_titles = [];

foreach ($chart_data as $s) {
    if ($s['diem_so'] !== null) {
        $labels[] = date('d/m', strtotime($s['ngay_kiem_tra']));
        $data_hs[] = (float)$s['diem_so'];
        $data_lop[] = number_format((float)$s['diem_tb_lop'], 2);
        $test_titles[] = $s['ten_cot_diem']; 
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Hồ sơ: <?php echo htmlspecialchars($hocsinh['ho_ten']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; color: #333; }
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }
        .page-header h2 { margin: 0; color: #007bff; font-size: 1.5rem; }
        .btn-back { text-decoration: none; background: #eee; color: #333; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
        .btn-back:hover { background: #ddd; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #e9ecef; }
        .info-item label { font-weight: bold; color: #6c757d; font-size: 0.85rem; display: block; margin-bottom: 4px; }
        .info-item span { font-weight: 700; font-size: 1.1rem; color: #2c3e50; }

        .chart-wrapper { position: relative; height: 350px; width: 100%; margin-bottom: 40px; }

        .comments-section { display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px; }
        .comment-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; border-left: 5px solid #28a745; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
        .comment-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #28a745; font-weight: bold; font-size: 1rem; }
        .comment-text { font-size: 1rem; line-height: 1.5; color: #333; }

        .table-responsive { width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #007bff; color: white; white-space: nowrap; }
        
        @media (max-width: 768px) {
            .container { margin: 10px; padding: 15px; }
            .chart-wrapper { height: 280px; }
            h2 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-user-graduate"></i> Chi tiết điểm số học sinh</h2>
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>

    <div class="info-grid">
        <div class="info-item"><label>Họ và tên</label><span><?php echo htmlspecialchars($hocsinh['ho_ten']); ?></span></div>
        <div class="info-item"><label>Số báo danh</label><span><?php echo htmlspecialchars($hocsinh['so_bao_danh']); ?></span></div>
        <div class="info-item"><label>Lớp</label><span><?php echo htmlspecialchars($hocsinh['ten_lop']); ?></span></div>
        <div class="info-item"><label>Trường</label><span><?php echo htmlspecialchars($hocsinh['truong'] ?? 'Chưa cập nhật'); ?></span></div>
    </div>

    <h3 style="color: #495057; border-bottom: 2px solid #eee; padding-bottom: 8px;">
        <i class="fas fa-chart-line"></i> Biểu đồ thống kê
    </h3>
    <div class="chart-wrapper">
        <canvas id="scoreChart"></canvas>
    </div>

    <?php if (!empty($comments)): ?>
        <h3 style="color: #495057; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 30px;">
            <i class="fas fa-comments"></i> Nhận xét giáo viên
        </h3>
        <div class="comments-section">
            <?php foreach ($comments as $cmt): ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <i class="far fa-calendar-check"></i> THÁNG <?php echo $cmt['thang'] . '/' . $cmt['nam']; ?>
                    </div>
                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($cmt['nhan_xet'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 style="color: #495057; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 30px;">
        <i class="fas fa-list-ol"></i> Bảng điểm chi tiết
    </h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Bài kiểm tra</th>
                    <th style="text-align: center;">Điểm Học Sinh</th>
                    <th style="text-align: center;">Điểm Trung Bình Lớp</th>
                    <th style="text-align: center;">BTVN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scores)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;">Chưa có dữ liệu điểm.</td></tr>
                <?php else: foreach ($scores as $s): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($s['ngay_kiem_tra'])); ?></td>
                        <td style="font-weight: 500; color: #333;"><?php echo htmlspecialchars($s['ten_cot_diem']); ?></td>
                        <td style="font-weight: 800; color: #007bff; font-size: 1.1em; text-align: center;"><?php echo $s['diem_so'] !== null ? $s['diem_so'] : '-'; ?></td>
                        <td style="color: #666; text-align: center;"><?php echo number_format((float)$s['diem_tb_lop'], 2); ?></td>
                        <td style="text-align: center;"><?php echo $s['diem_btvn'] !== null ? $s['diem_btvn'] : '-'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const labels = <?php echo json_encode($labels); ?>;
    const dataHS = <?php echo json_encode($data_hs); ?>;
    const dataLop = <?php echo json_encode($data_lop); ?>;
    const testTitles = <?php echo json_encode($test_titles); ?>;

    const ctx = document.getElementById('scoreChart');

    if (labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Điểm Học Sinh',
                        data: dataHS,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#fff',
                        fill: true,
                        tension: 0
                    },
                    {
                        label: 'Điểm Trung Bình Lớp',
                        data: dataLop,
                        borderColor: '#ffc107',
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
                interaction: {
                    mode: 'nearest',
                    intersect: true, 
                    axis: 'x'
                },
                scales: {
                    y: { min: 0, max: 12, ticks: { stepSize: 1, callback: function(v){return v<=10?v:null} } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        padding: 12,
                        callbacks: {
                            title: function(context) {
                                return testTitles[context[0].dataIndex];
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    },
                    legend: { position: 'top' }
                }
            }
        });
    } else {
        ctx.style.display = 'none';
        document.querySelector('.chart-wrapper').innerHTML = 
            '<div style="text-align:center; padding-top:130px; color:#999; font-style:italic;">Chưa có dữ liệu biểu đồ</div>';
    }
</script>

</body>
</html>