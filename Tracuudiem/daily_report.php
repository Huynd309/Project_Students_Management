<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$lop_id_filter = $_GET['lop_id'] ?? null;
$ngay_filter = $_GET['ngay'] ?? date('Y-m-d');

$all_classes = [];
$report_data = [];
$class_name = "";

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    //QUERY 1
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
            ORDER BY 
                dhs.ho_ten ASC
        ";
        
        $stmt_report = $conn->prepare($sql_report);
        $stmt_report->execute([$ngay_filter, $lop_id_filter, $ngay_filter, $lop_id_filter, $lop_id_filter]);
        $report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);
    }
    $conn = null;

} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e->getMessage();
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
        .report-table th { text-align: center; }
        .report-table td { text-align: center; }
        .report-table td:nth-child(2) { text-align: left; }
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

    <main class="container">
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

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th style="width: 100px;">SBD</th>
                        <th>Họ và tên</th>
                        <th style="width: 100px;">Trạng thái</th>
                        <th style="width: 80px;">Điểm CC</th>
                        <th style="width: 80px;">Điểm Test</th>
                        <th style="width: 80px;">Điểm BTVN</th>
                        <th style="width: 100px; background-color: rgba(0, 123, 255, 0.1);">Điểm Tích Lũy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr><td colspan="8">Không tìm thấy học sinh nào trong lớp này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $index => $row): ?>
                            <?php 
                                $diem_cc = getDiemChuyenCan($row['trang_thai_diem_danh']);
                                $diem_test = ($row['diem_test'] !== null) ? $row['diem_test'] : 0;
                                $diem_btvn = ($row['diem_btvn'] !== null) ? $row['diem_btvn'] : 0;
                                
                                // Công thức: (Điểm Test * 2 + Điểm CC + Điểm BTVN) / 4
                                $diem_tich_luy = ($diem_test * 2 + $diem_cc + $diem_btvn) / 4;
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                
                                <td><?php echo getTrangThaiText($row['trang_thai_diem_danh']); ?></td>
                                
                                <td style="font-weight: bold; color: var(--primary-color);">
                                    <?php echo ($row['trang_thai_diem_danh']) ? $diem_cc : '-'; ?>
                                </td>

                                <td><?php echo ($row['diem_test'] !== null) ? $row['diem_test'] : '-'; ?></td>
                                <td><?php echo ($row['diem_btvn'] !== null) ? $row['diem_btvn'] : '-'; ?></td>

                                <td style="font-weight: bold; color: #e74c3c; background-color: rgba(0, 123, 255, 0.05);">
                                    <?php echo number_format($diem_tich_luy, 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p style="font-style: italic; color: var(--text-color-light); margin-top: 10px;">
                * Điểm CC: Điểm chuyên cần tính theo trạng thái điểm danh (Có mặt: 10, Muộn: 7, Vắng: 0).<br>
                * Điểm Tích Lũy được tính theo công thức: (Điểm Test * 2 + Điểm CC + Điểm BTVN) / 4.
            </p>
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