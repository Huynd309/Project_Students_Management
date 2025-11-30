<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}
$lop_id_filter = $_GET['lop_id'] ?? null;
$thang_filter = $_GET['thang'] ?? date('m');
$nam_filter = $_GET['nam'] ?? date('Y');
$all_classes = [];
$hoc_phi_data = [];
$class_name = "";
$hoc_phi_moi_buoi = 70000;
try {
    require_once 'db_config.php';
    $stmt_classes = $conn->prepare("SELECT id, ten_lop FROM lop_hoc ORDER BY ten_lop ASC");
    $stmt_classes->execute();
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
    if ($lop_id_filter) {
        $sql = "
            SELECT
                dhs.so_bao_danh,
                dhs.ho_ten,
                COALESCE(ac.so_buoi_hoc, 0) AS so_buoi_hoc,
                (COALESCE(ac.so_buoi_hoc, 0) * ?) AS hoc_phi_hien_tai, 
                COALESCE(hpt.trang_thai_dong_phi, 'unpaid') AS trang_thai_dong_phi
            FROM diem_hoc_sinh AS dhs
            JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
            JOIN user_lop ul ON u.id = ul.user_id
            LEFT JOIN (
                SELECT
                    so_bao_danh,
                    COUNT(*) AS so_buoi_hoc
                FROM diem_danh
                WHERE
                    lop_id = ? 
                    AND trang_thai = 'present'
                    AND EXTRACT(MONTH FROM ngay_diem_danh) = ?
                    AND EXTRACT(YEAR FROM ngay_diem_danh) = ?
                GROUP BY so_bao_danh
            ) AS ac ON LOWER(dhs.so_bao_danh) = LOWER(ac.so_bao_danh)
            LEFT JOIN hoc_phi_thang hpt
                ON LOWER(dhs.so_bao_danh) = LOWER(hpt.so_bao_danh)
                AND hpt.thang = ?
                AND hpt.nam = ?
            WHERE
                ul.lop_hoc_id = ?
            ORDER BY
                dhs.ho_ten;
        ";
        $stmt_hocphi = $conn->prepare($sql);
        $params = [
            $hoc_phi_moi_buoi,
            $lop_id_filter, $thang_filter, $nam_filter, 
            $thang_filter, $nam_filter,                
            $lop_id_filter                            
        ];
        $stmt_hocphi->execute($params);
        $hoc_phi_data = $stmt_hocphi->fetchAll(PDO::FETCH_ASSOC);
        foreach($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $class_name = $class['ten_lop'];
                break;
            }
        }
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
    <title>Quản lý học phí</title>
    <link rel="stylesheet" href="style.css">
    </head>
<body class="admin-page-blue"> <header class="header">
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
    <style>table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px; 
            margin-top: 20px;
            
            background-color: rgba(255, 255, 255, 0.1); 
            border: 1px solid rgba(255, 255, 255, 0.2); 
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: var(--shadow);
        }

        th, td { 
            padding: 14px 18px; 
            text-align: left; 
            color: var(--text-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] th, [data-theme="dark"] td {
            border-color: rgba(255, 255, 255, 0.1);
        }
        [data-theme="light"] th, [data-theme="light"] td {
             border-color: rgba(0, 0, 0, 0.1);
        }

        th { 
            background-color: rgba(255, 255, 255, 0.1); 
            font-weight: 600;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }
        .submit-btn {
            width: 100%;
            padding: 14px 20px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(145deg, #4CAF50, #28a745);
            color: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
            transition: all 0.2s ease-out;
            margin-top: 20px; 
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        .submit-btn:active {
            transform: translateY(1px) scale(0.98);
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.2);
        }

        .radio-group label {
            margin-right: 15px;
            cursor: pointer;
            font-weight: 500;
            transition: color 0.2s ease;
            color: var(--text-color); 
        }
        .radio-group input[type="radio"] {
            margin-right: 5px;
            accent-color: var(--primary-color);
        }
        .radio-group input[type="radio"][value="absent"]:checked + label {
            color: #dc3545; 
            font-weight: bold;
        }
        [data-theme="dark"] .radio-group input[type="radio"][value="absent"]:checked + label {
            color: #ff6b7b; 
        }


        .success-msg {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        [data-theme="dark"] .success-msg {
            color: #d4edda;
            background-color: #155724;
            border-color: #28a745;
        }</style>
    <main class="container">
        <h2>Quản lý học phí</h2>
        <p>Chọn lớp và tháng/năm để xem học phí.</p>
        
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="success-msg">
                <strong>Đã cập nhật trạng thái thành công!</strong>
            </div>
        <?php endif; ?>

        <form class="filter-form" action="hocphi.php" method="GET">
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
                <label for="thang">Chọn tháng:</label>
                <input type="number" name="thang" id="thang" min="1" max="12" value="<?php echo htmlspecialchars($thang_filter); ?>" required>
            </div>
            <div>
                <label for="nam">Chọn năm:</label>
                <input type="number" name="nam" id="nam" min="2020" max="2030" value="<?php echo htmlspecialchars($nam_filter); ?>" required>
            </div>
            <button type="submit">Xem học phí</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">
                Học phí lớp "<?php echo htmlspecialchars($class_name); ?>"
                (Tháng <?php echo htmlspecialchars($thang_filter); ?>/<?php echo htmlspecialchars($nam_filter); ?>)
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Mã học sinh (SBD)</th>
                        <th>Họ và tên</th>
                        <th>Số buổi học (Có mặt)</th>
                        <th>Học phí (VNĐ)</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hoc_phi_data)): ?>
                        <tr><td colspan="6">Không tìm thấy dữ liệu cho lớp/tháng này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($hoc_phi_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                <td><?php echo htmlspecialchars($row['so_buoi_hoc']); ?></td>
                                <td><?php echo number_format($row['hoc_phi_hien_tai'], 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <?php if ($row['trang_thai_dong_phi'] == 'paid'): ?>
                                        <span class="status-paid">Đã đóng</span>
                                    <?php else: ?>
                                        <span class="status-unpaid">Chưa đóng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['trang_thai_dong_phi'] == 'unpaid'): ?>
                                        <a href="handle_update_hocphi.php?sbd=<?php echo htmlspecialchars($row['so_bao_danh']); ?>&thang=<?php echo $thang_filter; ?>&nam=<?php echo $nam_filter; ?>&lop_id=<?php echo $lop_id_filter; ?>&status=paid"
                                           class="btn-paid"
                                           onclick="return confirm('Xác nhận học sinh này ĐÃ ĐÓNG tiền?');">
                                           Đánh dấu đã đóng
                                        </a>
                                    <?php else: ?>
                                        <a href="handle_update_hocphi.php?sbd=<?php echo htmlspecialchars($row['so_bao_danh']); ?>&thang=<?php echo $thang_filter; ?>&nam=<?php echo $nam_filter; ?>&lop_id=<?php echo $lop_id_filter; ?>&status=unpaid"
                                           class="btn-unpaid" 
                                           onclick="return confirm('HỦY BỎ trạng thái đã đóng?');">
                                           (Hủy)
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>