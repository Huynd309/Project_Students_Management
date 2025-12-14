<?php
// Tắt báo lỗi hiển thị để giao diện sạch sẽ
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start(); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI! Bạn không có quyền vào trang này.");
}

$all_classes = [];
$recent_sessions = [];

try {
    require_once 'db_config.php';
    $user_role = $_SESSION['role'] ?? 'admin'; 

    // QUERY 1: Lấy danh sách lớp
    if ($user_role === 'super') {
        $stmt_classes = $conn->prepare("SELECT * FROM lop_hoc ORDER BY khoi, ten_lop ASC");
        $stmt_classes->execute();
    } else {
        $stmt_classes = $conn->prepare("
            SELECT lh.* FROM lop_hoc lh
            JOIN admin_lop_access ala ON lh.id = ala.lop_id
            WHERE ala.user_id = ?
            ORDER BY lh.khoi, lh.ten_lop ASC
        ");
        $stmt_classes->execute([$_SESSION['user_id']]);
    }
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // QUERY 2: Lịch sử (Để đơn giản, query này giữ nguyên)
    $sql_recent = "
        SELECT dd.ngay_diem_danh, dd.lop_id, dd.lesson_title, lh.ten_lop
        FROM diem_danh dd
        JOIN lop_hoc lh ON dd.lop_id = lh.id
        " . ($user_role !== 'super' ? "JOIN admin_lop_access ala ON lh.id = ala.lop_id WHERE ala.user_id = ?" : "") . "
        GROUP BY dd.ngay_diem_danh, dd.lop_id, lh.ten_lop, dd.lesson_title
        ORDER BY dd.ngay_diem_danh DESC LIMIT 20";

    $stmt_recent = $conn->prepare($sql_recent);
    if ($user_role !== 'super') $stmt_recent->execute([$_SESSION['user_id']]);
    else $stmt_recent->execute();
    
    $recent_sessions = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điểm danh & Nhập điểm</title>
    <link rel="stylesheet" href="style.css?v=3"> 
    <style>
        :root {
            --card-bg: #ffffff;
            --input-bg: #f8f9fa;
            --text-main: #2c3e50;
            --text-sub: #6c757d;
            --border-color: #e9ecef;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.08);
            --primary-gradient: linear-gradient(135deg, #007bff, #0056b3);
        }

        [data-theme="dark"] {
            --card-bg: #1e1e1e;
            --input-bg: #2d2d2d;
            --text-main: #e0e0e0;
            --text-sub: #a0a0a0;
            --border-color: #404040;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.3);
        }

        .form-diemdanh {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .form-group {
            flex: 1;
            min-width: 280px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i { color: var(--primary-color); }

        .form-group input, 
        .form-group select {
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--input-bg);
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.2s ease;
            width: 100%;
            box-sizing: border-box; 
        }

        .form-group input:focus, 
        .form-group select:focus {
            border-color: var(--primary-color);
            background-color: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        ::placeholder { color: #adb5bd; opacity: 1; }

        .btn-submit-main {
            background: var(--primary-gradient);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
        }

        .btn-submit-main:active {
            transform: translateY(0);
        }

        .recent-sessions-container { margin-top: 40px; }
        
        .recent-sessions-container h3 {
            font-size: 1.2rem;
            color: var(--text-main);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            display: inline-block;
        }

        .recent-sessions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .session-item {
            display: grid;
            grid-template-columns: 80px 1fr; 
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .session-item:hover {
            transform: translateY(-3px) scale(1.005);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .session-date-box {
            background-color: rgba(0, 123, 255, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-right: 1px solid var(--border-color);
            color: var(--primary-color);
        }

        .date-day { font-size: 1.4rem; font-weight: 800; line-height: 1; }
        .date-month { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-top: 4px; }

        .session-content {
            padding: 15px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .session-class {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .class-badge {
            background: #eee;
            padding: 2px 8px;
            border-radius: 4px;
            color: #333;
        }

        .session-lesson {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.4;
        }

        /* Responsive cho Mobile */
        @media (max-width: 600px) {
            .form-diemdanh { padding: 20px; gap: 15px; }
            .session-item { grid-template-columns: 65px 1fr; }
            .date-day { font-size: 1.2rem; }
            .session-lesson { font-size: 1rem; }
        }
    </style>
</head>

<body class="admin-page-blue"> 
    <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">Admin: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox"><input type="checkbox" id="checkbox" /><div class="slider round"></div></label>
            </div>
            <a href="report_diemdanh.php"><button id="report-btn">Xem báo cáo</button></a>
            <a href="admin.php"><button id="login-btn">Quay lại</button></a>
        </div>
    </header>

    <main class="container">
        <h2><i class="fas fa-calendar-check"></i> Điểm danh & Nhập điểm</h2>
        
        <?php if (isset($error)) echo "<p style='color:red; background: #ffe6e6; padding: 10px;'>$error</p>"; ?>

        <form class="form-diemdanh" action="handle_diemdanh.php" method="POST">
            
            <div style="display: flex; gap: 20px; width: 100%;">
                <div class="form-group">
                    <label>1. Chọn lớp:</label>
                    <select name="lop_id" required>
                        <option value="">-- Chọn lớp --</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>2. Chọn ngày:</label>
                    <input type="date" name="ngay_diem_danh" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div style="display: flex; gap: 20px; width: 100%;">
                <div class="form-group">
                    <label><i class="fas fa-book"></i> Nội dung bài dạy (Lưu điểm danh):</label>
                    <input type="text" name="lesson_title" placeholder="VD: Bài 5: Phương trình bậc hai..." required>
                </div>
                
                <div class="form-group">
                    <label style="color: #e74c3c;"><i class="fas fa-pen-alt"></i> Tên bài kiểm tra (Lưu cột điểm):</label>
                    <input type="text" name="test_title" placeholder="VD: Kiểm tra 15 phút (Nếu không có điểm thì để trống)">
                </div>
            </div>
            
            <div style="width: 100%; margin-top: 10px;">
                <button type="submit" class="btn-submit-main">Tiếp tục lấy danh sách <i class="fas fa-arrow-right"></i></button>
            </div>
        </form>

        <div class="recent-sessions-container" style="margin-top: 30px;">
            <h3>Các buổi học gần đây</h3>
            <div class="recent-sessions">
            <?php if (empty($recent_sessions)): ?>
                <div style="text-align: center; padding: 40px; color: #999; background: var(--card-bg); border-radius: 12px; border: 1px dashed var(--border-color);">
                    <i class="far fa-folder-open" style="font-size: 40px; margin-bottom: 10px;"></i><br>
                    Chưa có dữ liệu điểm danh nào.
                </div>
            <?php else: ?>
                <?php foreach ($recent_sessions as $session): 
                    $dateObj = strtotime($session['ngay_diem_danh']);
                    $day = date('d', $dateObj);
                    $month = "THG " . date('m', $dateObj);
                ?>
                    <a class="session-item" href="handle_diemdanh.php?lop_id=<?php echo $session['lop_id']; ?>&ngay=<?php echo $session['ngay_diem_danh']; ?>&lesson_title=<?php echo urlencode($session['lesson_title']); ?>">
                        
                        <div class="session-date-box">
                            <span class="date-day"><?php echo $day; ?></span>
                            <span class="date-month"><?php echo $month; ?></span>
                        </div>

                        <div class="session-content">
                            <div class="session-class">
                                <span class="class-badge"><i class="fas fa-users"></i> <?php echo htmlspecialchars($session['ten_lop']); ?></span>
                            </div>
                            <div class="session-lesson">
                                <?php echo htmlspecialchars($session['lesson_title']); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script src="admin_main.js"></script>
</body>
</html>