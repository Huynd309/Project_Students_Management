<?php
session_start();

require_once 'db_config.php';
if (!isset($_GET['sbd']) || !isset($_GET['lop_id'])) {
    die('Vui lòng cung cấp số báo danh và lớp.');
}
$sbd = $_GET['sbd'];
$lop_id = $_GET['lop_id'];

$hocsinh = null;
$diem_chi_tiet = [];
$comment_history = [];
try {
//Query 1
    $stmt_info = $conn->prepare("
        SELECT
            dhs.ho_ten,
            dhs.truong,
            lh.ten_lop
        FROM
            users u
        LEFT JOIN
            diem_hoc_sinh dhs ON LOWER(u.username) = LOWER(dhs.so_bao_danh)
        JOIN
            user_lop ul ON u.id = ul.user_id
        JOIN
            lop_hoc lh ON ul.lop_hoc_id = lh.id
        WHERE
            LOWER(u.username) = LOWER(?) AND ul.lop_hoc_id = ?
    ");
    $stmt_info->execute([$sbd, $lop_id]);
    $hocsinh = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if(!$hocsinh){
        die("Không tìm thấy học sinh với số báo danh " . htmlspecialchars($sbd) . " trong lớp này.");
    }
//Query 2
    $sql_diem = "
        WITH 
        student_scores AS (
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
            COALESCE(ca.diem_trung_binh_lop, ss.diem_so) AS diem_trung_binh_lop
        FROM 
            student_scores ss
        LEFT JOIN 
            class_averages ca 
        ON 
            ss.ten_cot_diem = ca.ten_cot_diem AND ss.ngay_kiem_tra = ca.ngay_kiem_tra
        ORDER BY 
            ss.ngay_kiem_tra ASC;
    ";
    
    $stmt_diem = $conn->prepare($sql_diem);
    $stmt_diem->execute([$sbd, $lop_id, $lop_id]); 
    $diem_chi_tiet = $stmt_diem->fetchAll(PDO::FETCH_ASSOC);
    //Query 3
    $comment_history = [];
    $sql_comments = "
        SELECT thang, nam, nhan_xet 
        FROM nhan_xet_thang
        WHERE 
            LOWER(so_bao_danh) = LOWER(?)
            AND lop_id = ?
            AND nhan_xet IS NOT NULL AND nhan_xet != ''
        ORDER BY
            nam DESC, thang DESC
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
</head>
<body>
    <header class="header">
    
    <div class="logo">
        <a href="index.php" class="logo-link" style="text-decoration: none; color: inherit;"> 
            <h2>Hệ thống tra cứu điểm học sinh</h2>
        </a>
    </div>

    <div class="auth-buttons">
        <a href="gioithieu.php">
            <button id="register-btn">Tra cứu điểm mà không cần đăng nhập</button>
        </a>
        <a href="login.php">
            <button id="register-btn">Đăng nhập </button>
        </a>
    </div>

</header>
    
    <main class="container">
        <h1>Thông tin học sinh</h1>
        
        <div class="profile-info" style="text-align: left;">
            <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($hocsinh['ho_ten']); ?></p>
            <p><strong>Mã học sinh:</strong> <?php echo htmlspecialchars($sbd); ?></p>
            <p><strong>Trường:</strong> <?php echo htmlspecialchars($hocsinh['truong']); ?></p>
            <p><strong>Lớp:</strong> <?php echo htmlspecialchars($hocsinh['ten_lop']); ?></p>
        </div>

        <hr>

        <h2>Biểu đồ thống kê</h2>
        <div style="width:100%;">
            <canvas id="myChart"></canvas>
        </div>
        
        <hr>

        <h2>Nhận xét tổng thể hàng tháng</h2>
        <table style="width: 100%; text-align: left;">
            <thead>
                <tr>
                    <th>Thời gian (Tháng/Năm)</th>
                    <th>Nhận xét của giáo viên</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comment_history)): ?>
                    <tr><td colspan="2">Chưa có nhận xét nào cho học sinh này.</td></tr>
                <?php else: ?>
                    <?php foreach ($comment_history as $comment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['thang']); ?>/<?php echo htmlspecialchars($comment['nam']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($comment['nhan_xet'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <hr>
        <h2>Chi tiết các đầu điểm</h2>
        <table style="width: 100%; text-align: left;">
            <thead>
                <tr>
                    <th>Tên cột điểm</th>
                    <th>Ngày kiểm tra</th>
                    <th>Điểm số</th>
                    <th>Trung bình lớp</th> </tr>
            </thead>
            <tbody>
                <?php foreach ($diem_chi_tiet as $diem): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($diem['ten_cot_diem']); ?></td>
                        <td><?php echo htmlspecialchars($diem['ngay_kiem_tra']); ?></td>
                        <td><?php echo htmlspecialchars($diem['diem_so']); ?></td>
                        <td><?php echo number_format((float)$diem['diem_trung_binh_lop'], 2, '.', ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($diem_chi_tiet)): ?>
                    <tr><td colspan="3">Chưa có dữ liệu điểm thành phần.</td></tr> <?php endif; ?>
            </tbody>
        </table>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const diemData = <?php echo json_encode($diem_chi_tiet); ?>;
        const labels = diemData.map(d => d.ngay_kiem_tra);
        const studentScores = diemData.map(d => d.diem_so);
        const classAvgScores = diemData.map(d => parseFloat(d.diem_trung_binh_lop).toFixed(2));

        const ctx = document.getElementById('myChart');
        new Chart(ctx, {
            type: 'line', 
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Điểm của học sinh',
                        data: studentScores,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        borderWidth: 3
                    },
                    {
                        label: 'Trung bình lớp',
                        data: classAvgScores,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        borderWidth: 1.5,
                        borderDash: [5, 5]
                    }
                ]
            }
        });
    </script>
    
        <footer class="footer">
        <div class="footer-column">
            <h4><i class="fas fa-building-columns"></i> Về chúng tôi</h4>
            <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
        </div>
        
        <div class="footer-column">
            <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ</h4>
            <p>136 đường Xuân Thuỷ, Phường Cầu Giấy, thành phố Hà Nội</p>
        </div>
        
        <div class="footer-column">
            <h4><i class="fas fa-phone"></i> Liên hệ</h4>
            <p><strong>© Copyright by:</strong> Ngô Dương Huy </p>
            <p><strong>Điện thoại:</strong> 0961223066</p>
            <p><strong>Email:</strong> ngohuy3092005@gmail.com</p>
        </div>
    </footer>
</body>
</html>