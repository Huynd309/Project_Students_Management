<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lop_id = $_POST['lop_id'];
    $ngay_diem_danh = $_POST['ngay_diem_danh'];
    $lesson_title = $_POST['lesson_title'] ?? '';
} elseif (isset($_GET['lop_id']) && isset($_GET['ngay'])) { 
    $lop_id = $_GET['lop_id'];
    $ngay_diem_danh = $_GET['ngay'];
    $lesson_title = $_GET['lesson_title'] ?? ''; 
} else {
    die("Thiếu thông tin lớp hoặc ngày.");
}

$students_list = [];
$class_info = null;
$saved_statuses = []; 
$monthly_points = [];

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query 1: Lấy tên lớp
    $stmt_class = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmt_class->execute([$lop_id]);
    $class_info = $stmt_class->fetch(PDO::FETCH_ASSOC);

    if (!$class_info) { die("Không tìm thấy lớp này."); }

    // Query 2: Lấy danh sách học sinh
    $stmt_students = $conn->prepare("
        SELECT dhs.so_bao_danh, dhs.ho_ten
        FROM diem_hoc_sinh AS dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        WHERE ul.lop_hoc_id = ?
        ORDER BY dhs.ho_ten ASC
    ");
    $stmt_students->execute([$lop_id]);
    $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Query 3: Lấy trạng thái điểm danh CỦA NGÀY HÔM ĐÓ
    $stmt_status = $conn->prepare("
        SELECT so_bao_danh, trang_thai 
        FROM diem_danh 
        WHERE lop_id = ? AND ngay_diem_danh = ?
    ");
    $stmt_status->execute([$lop_id, $ngay_diem_danh]);
    $statuses = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

    foreach ($statuses as $status) {
        $saved_statuses[$status['so_bao_danh']] = $status['trang_thai'];
    }
    // Query 4: Tính điểm chuyên cần THÁNG HIỆN TẠI
    $current_month = date('m', strtotime($ngay_diem_danh));
    $current_year = date('Y', strtotime($ngay_diem_danh));

    $sql_points = "
        SELECT 
            so_bao_danh,
            SUM(CASE 
                WHEN trang_thai = 'present' THEN 10 
                WHEN trang_thai = 'late' THEN 7 
                ELSE 0 
            END) as total_points
        FROM diem_danh
        WHERE 
            lop_id = ? 
            AND EXTRACT(MONTH FROM ngay_diem_danh) = ?
            AND EXTRACT(YEAR FROM ngay_diem_danh) = ?
        GROUP BY so_bao_danh
    ";
    $stmt_points = $conn->prepare($sql_points);
    $stmt_points->execute([$lop_id, $current_month, $current_year]);
    $points_data = $stmt_points->fetchAll(PDO::FETCH_ASSOC);

    foreach ($points_data as $p) {
        $monthly_points[$p['so_bao_danh']] = $p['total_points'];
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
    <title>Điểm danh lớp <?php echo htmlspecialchars($class_info['ten_lop'] ?? ''); ?></title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        table { 
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
        }
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

            <a href="report_diemdanh.php"><button id="report-btn">Xem báo cáo</button></a>
            <a href="diemdanh.php"><button id="login-btn">Chọn lớp khác</button></a>
            <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container">
        <h2>Bảng điểm danh</h2>
        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h3>Lớp: <?php echo htmlspecialchars($class_info['ten_lop']); ?></h3>
                <h3>Ngày: <?php echo date('d/m/Y', strtotime($ngay_diem_danh)); ?></h3>
            </div>
            <div style="text-align: right; font-size: 0.9em; color: var(--text-color-light);">
                Điểm chuyên cần tháng <?php echo date('m/Y', strtotime($ngay_diem_danh)); ?>
            </div>
        </div>

        <?php if (!empty($lesson_title)): ?>
            <h4 style="font-weight: normal; margin-top: 5px;">Nội dung: <strong><?php echo htmlspecialchars($lesson_title); ?></strong></h4>
        <?php endif; ?>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        
        <?php if (isset($_GET['save']) && $_GET['save'] == 'success'): ?>
            <div class="success-msg">
                <strong>Đã lưu điểm danh thành công!</strong>
            </div>
        <?php endif; ?>

        <form action="save_diemdanh.php" method="POST">
            
            <input type="hidden" name="lop_id" value="<?php echo htmlspecialchars($lop_id); ?>">
            <input type="hidden" name="ngay_diem_danh" value="<?php echo htmlspecialchars($ngay_diem_danh); ?>">
            <input type="hidden" name="lesson_title" value="<?php echo htmlspecialchars($lesson_title); ?>">

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>SBD</th>
                        <th>Họ và tên</th>
                        <th style="width: 100px; text-align: center;">Điểm CC<br>(Tháng)</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students_list)): ?>
                        <tr><td colspan="5">Lớp này chưa có học sinh.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students_list as $index => $student): ?>
                            <?php
                                $current_status = $saved_statuses[$student['so_bao_danh']] ?? 'present';
                                $points = $monthly_points[$student['so_bao_danh']] ?? 0;
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($student['ho_ten']); ?></td>
                                
                                <td class="points-cell">
                                    <?php echo $points; ?>
                                </td>

                                <td>
                                    <span class="radio-group">
                                        <label>
                                            <input type="radio" 
                                                   name="status[<?php echo htmlspecialchars($student['so_bao_danh']); ?>]" 
                                                   value="present" 
                                                   <?php if ($current_status == 'present') echo 'checked'; ?> > Có mặt
                                        </label>

                                        <label>
                                            <input type="radio" 
                                                   name="status[<?php echo htmlspecialchars($student['so_bao_danh']); ?>]" 
                                                   value="late"
                                                   <?php if ($current_status == 'late') echo 'checked'; ?> > Muộn
                                        </label>

                                        <label>
                                            <input type="radio" 
                                                   name="status[<?php echo htmlspecialchars($student['so_bao_danh']); ?>]" 
                                                   value="absent"
                                                   <?php if ($current_status == 'absent') echo 'checked'; ?> > Vắng
                                        </label>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students_list)): ?>
                <button type="submit" class="submit-btn">Lưu điểm danh</button>
            <?php endif; ?>

        </form>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>