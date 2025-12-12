<?php
session_start();
require_once 'db_config.php';

// Kiểm tra tham số đầu vào
if (!isset($_GET['sbd']) || !isset($_GET['lop_id'])) {
    die('Vui lòng cung cấp số báo danh và lớp.');
}

$sbd = $_GET['sbd'];
$lop_id = $_GET['lop_id'];

$hocsinh = null;
$diem_chi_tiet = [];
$comment_history = [];

try {
    // 1. Lấy thông tin học sinh
    $stmt_info = $conn->prepare("
        SELECT dhs.ho_ten, dhs.truong, lh.ten_lop
        FROM diem_hoc_sinh dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        JOIN lop_hoc lh ON ul.lop_hoc_id = lh.id
        WHERE LOWER(dhs.so_bao_danh) = LOWER(?) AND lh.id = ?
    ");
    $stmt_info->execute([$sbd, $lop_id]);
    $hocsinh = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$hocsinh) {
        die("Không tìm thấy học sinh với số báo danh " . htmlspecialchars($sbd) . " trong lớp này.");
    }

    // 2. Lấy điểm chi tiết & Trung bình lớp
    $sql_diem = "
        WITH student_scores AS (
            SELECT ten_cot_diem, ngay_kiem_tra, diem_so
            FROM diem_thanh_phan
            WHERE LOWER(so_bao_danh) = LOWER(?) AND lop_id = ?
        ),
        class_averages AS (
            SELECT ten_cot_diem, ngay_kiem_tra, AVG(diem_so) AS diem_trung_binh_lop
            FROM diem_thanh_phan
            WHERE lop_id = ?
            GROUP BY ten_cot_diem, ngay_kiem_tra
        )
        SELECT 
            ss.ten_cot_diem, ss.ngay_kiem_tra, ss.diem_so, 
            COALESCE(ca.diem_trung_binh_lop, 0) AS diem_trung_binh_lop
        FROM student_scores ss
        LEFT JOIN class_averages ca 
        ON ss.ten_cot_diem = ca.ten_cot_diem AND ss.ngay_kiem_tra = ca.ngay_kiem_tra
        ORDER BY ss.ngay_kiem_tra ASC;
    ";
    $stmt_diem = $conn->prepare($sql_diem);
    $stmt_diem->execute([$sbd, $lop_id, $lop_id]);
    $diem_chi_tiet = $stmt_diem->fetchAll(PDO::FETCH_ASSOC);

    // 3. Lấy lịch sử nhận xét
    $sql_comments = "
        SELECT thang, nam, nhan_xet 
        FROM nhan_xet_thang
        WHERE LOWER(so_bao_danh) = LOWER(?) AND lop_id = ? AND nhan_xet IS NOT NULL AND nhan_xet != ''
        ORDER BY nam DESC, thang DESC
    ";
    $stmt_comments = $conn->prepare($sql_comments);
    $stmt_comments->execute([$sbd, $lop_id]);
    $comment_history = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết điểm - <?php echo htmlspecialchars($hocsinh['ho_ten']); ?></title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* CSS BỔ SUNG ĐỂ FIX GIAO DIỆN MOBILE & MÀU TRẮNG */
        body {
            background-color: #f9f9f9; /* Nền trắng xám nhẹ cho dịu mắt */
            color: #333;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Cho phép xuống dòng trên mobile */
        }
        
        /* Responsive cho bảng */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px; /* Đảm bảo bảng không bị bóp méo trên mobile */
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th { background-color: #f8f9fa; }

        /* Responsive cho biểu đồ */
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .chart-wrapper { height: 300px; }
            .header { flex-direction: column; text-align: center; }
            .auth-buttons { margin-top: 10px; }
            h1 { font-size: 1.5em; }
        }
        
        hr { border: 0; border-top: 1px solid #eee; margin: 30px 0; }
    </style>
</head>
<body>
    
    <header class="header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: #007bff;"> 
                <h2 style="margin: 0;"><i class="fas fa-search"></i> Tra cứu điểm học sinh</h2>
            </a>
        </div>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <button onclick="history.back()" style="padding: 8px 15px; cursor: pointer;">Quay lại Admin</button>
            <?php else: ?>
                <a href="login.php"><button style="padding: 8px 15px; cursor: pointer;">Đăng nhập Giáo Viên</button></a>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="container">
        <h1 style="color: #007bff;">Thông tin học sinh</h1>
        
        <div class="profile-info">
            <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($hocsinh['ho_ten']); ?></p>
            <p><strong>Mã học sinh:</strong> <?php echo htmlspecialchars($sbd); ?></p>
            <p><strong>Trường:</strong> <?php echo htmlspecialchars($hocsinh['truong'] ?? ''); ?></p>
            <p><strong>Lớp:</strong> <?php echo htmlspecialchars($hocsinh['ten_lop']); ?></p>
        </div>

        <hr>

        <h2><i class="fas fa-chart-line"></i> Biểu đồ thống kê</h2>
        <div class="chart-wrapper">
            <canvas id="myChart"></canvas>
        </div>
        
        <hr>

        <h2><i class="fas fa-comments"></i> Nhận xét tổng thể hàng tháng</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 150px;">Thời gian</th>
                        <th>Nhận xét của giáo viên</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comment_history)): ?>
                        <tr><td colspan="2">Chưa có nhận xét nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($comment_history as $comment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($comment['thang']); ?>/<?php echo htmlspecialchars($comment['nam']); ?></strong></td>
                                <td><?php echo nl2br(htmlspecialchars($comment['nhan_xet'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr>

        <h2><i class="fas fa-list-ol"></i> Chi tiết các đầu điểm</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ngày kiểm tra</th>
                        <th>Tên bài kiểm tra</th>
                        <th>Điểm số</th>
                        <th>TB Lớp</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diem_chi_tiet as $diem): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($diem['ngay_kiem_tra'])); ?></td>
                            <td><?php echo htmlspecialchars($diem['ten_cot_diem']); ?></td>
                            <td style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($diem['diem_so']); ?></td>
                            <td style="color: #666;"><?php echo number_format((float)$diem['diem_trung_binh_lop'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($diem_chi_tiet)): ?>
                        <tr><td colspan="4">Chưa có dữ liệu điểm.</td></tr> 
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <footer class="footer" style="background: #333; color: white; padding: 20px; margin-top: 40px;">
        <div style="max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="footer-column">
                <h4><i class="fas fa-building-columns"></i> Về chúng tôi</h4>
                <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
                <p><strong>© Copyright by:</strong> Ngô Dương Huy</p>
            </div>
            <div class="footer-column">
                <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ</h4>
                <p>Xóm 1, thôn Lại Đà, xã Đông Anh, thành phố Hà Nội</p>
            </div>
            <div class="footer-column">
                <h4><i class="fas fa-phone"></i> Liên hệ</h4>
                <p><strong>Điện thoại:</strong> 0965601055</p>
                <p><strong>Email:</strong> nhatdaoedu@gmail.com</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // 1. Dữ liệu từ PHP
        const diemData = <?php echo json_encode($diem_chi_tiet); ?>;
        
        // --- XỬ LÝ LABEL: Chỉ lấy ngày tháng năm ---
        function formatDateLabel(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN'); // Ra định dạng dd/mm/yyyy
        }

        const labels = diemData.map(d => formatDateLabel(d.ngay_kiem_tra));
        const studentScores = diemData.map(d => parseFloat(d.diem_so));
        const classAvgScores = diemData.map(d => parseFloat(d.diem_trung_binh_lop).toFixed(2));

        // 2. HÀM TÍNH HỒI QUY TUYẾN TÍNH (Giữ nguyên logic của bạn)
        function calculateLinearRegression(yValues) {
            const n = yValues.length;
            if (n === 0) return [];

            let sumX = 0; let sumY = 0; let sumXY = 0; let sumXX = 0;

            for (let i = 0; i < n; i++) {
                sumX += i;
                sumY += yValues[i];
                sumXY += i * yValues[i];
                sumXX += i * i;
            }

            const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
            const intercept = (sumY - slope * sumX) / n;

            const regressionLine = [];
            for (let i = 0; i < n; i++) {
                let val = slope * i + intercept;
                val = Math.max(0, Math.min(10, val)); 
                regressionLine.push(val);
            }
            return regressionLine;
        }

        const trendData = calculateLinearRegression(studentScores);

        // 3. VẼ BIỂU ĐỒ
        const ctx = document.getElementById('myChart');
        new Chart(ctx, {
            type: 'line', 
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Điểm test học sinh',
                        data: studentScores,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0, 
                        borderWidth: 3,
                        pointRadius: 6, 
                        pointHoverRadius: 8,
                        order: 2
                    },
                    {
                        label: 'Hồi quy tuyến tính (Xu hướng)', 
                        data: trendData,
                        borderColor: 'rgb(255, 159, 64)',
                        borderWidth: 2,
                        borderDash: [10, 5],
                        pointRadius: 0, 
                        fill: false,
                        tension: 0,
                        order: 1
                    },
                    {
                        label: 'Trung bình lớp',
                        data: classAvgScores,
                        borderColor: 'rgb(153, 102, 255)',
                        backgroundColor: 'rgba(153, 102, 255, 0.1)',
                        tension: 0,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 4,
                        fill: false,
                        order: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, 

                interaction: {
                    mode: 'nearest', 
                    intersect: true, 
                },
                
                scales: { 
                    y: { 
                        min: 0, 
                        max: 12, 
                        ticks: { stepSize: 1, callback: function(v){return v<=10?v:null} } 
                    } 
                },
                
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const lessonName = diemData[index].ten_cot_diem;
                                return 'Bài: ' + lessonName;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>