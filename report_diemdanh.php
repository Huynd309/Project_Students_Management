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
$lesson_title_report = "";

try {
    require_once 'db_config.php';
    
    $stmt_classes = $conn->prepare("SELECT id, ten_lop FROM lop_hoc ORDER BY ten_lop ASC");
    $stmt_classes->execute();
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    if ($lop_id_filter) {
        $sql_report = "
            SELECT 
                dhs.ho_ten, 
                dhs.so_bao_danh, 
                dd.trang_thai,
                dd.lesson_title, 
                lh.ten_lop
            FROM 
                diem_danh dd
            JOIN 
                diem_hoc_sinh dhs ON LOWER(dd.so_bao_danh) = LOWER(dhs.so_bao_danh)
            JOIN
                lop_hoc lh ON dd.lop_id = lh.id
            WHERE 
                dd.lop_id = ? AND dd.ngay_diem_danh = ?
            ORDER BY 
                dhs.ho_ten ASC
        ";
        $stmt_report = $conn->prepare($sql_report);
        $stmt_report->execute([$lop_id_filter, $ngay_filter]);
        $report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($report_data)) {
            $class_name = $report_data[0]['ten_lop'];
            $lesson_title_report = $report_data[0]['lesson_title'];
        } else {
            foreach($all_classes as $class) {
                if ($class['id'] == $lop_id_filter) {
                    $class_name = $class['ten_lop'];
                    break;
                }
            }
        }
    }
    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e.getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo điểm danh</title>
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

            <a href="diemdanh.php"><button id="report-btn">Quay lại Điểm danh</button></a>
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
        <h2>Báo cáo điểm danh</h2>
        <p>Chọn lớp và ngày để xem báo cáo.</p>
        
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form class="filter-form" action="report_diemdanh.php" method="GET">
            <label for="lop_id">Chọn lớp:</label>
            <select name="lop_id" id="lop_id" required>
                <option value="">-- Chọn một lớp --</option>
                <?php foreach ($all_classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php if ($class['id'] == $lop_id_filter) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="ngay">Chọn ngày:</label>
            <input type="date" name="ngay" id="ngay" value="<?php echo htmlspecialchars($ngay_filter); ?>" required>
            
            <button type="submit">Xem báo cáo</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">
                Kết quả điểm danh lớp "<?php echo htmlspecialchars($class_name); ?>"
                cho ngày <?php echo htmlspecialchars($ngay_filter); ?>
            </h3>
            
            <?php if (!empty($lesson_title_report)): ?>
                <h4 style="font-weight: normal;">Nội dung: <strong><?php echo htmlspecialchars($lesson_title_report); ?></strong></h4>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã học sinh (SBD)</th>
                        <th>Họ và tên</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr><td colspan="4">Không tìm thấy dữ liệu điểm danh cho lớp và ngày này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                <td>
                                    <?php if ($row['trang_thai'] == 'present'): ?>
                                        <span class="status-present">Có mặt</span>
                                    <?php else: ?>
                                        <span class="status-absent">Vắng</span>
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