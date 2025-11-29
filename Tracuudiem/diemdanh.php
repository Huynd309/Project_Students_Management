<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI! Bạn không có quyền vào trang này.");
}

$all_classes = [];
$recent_sessions = [];

try {
    $host = '127.0.0.1';
    $port = '5432';
    $dbname = 'Student_Information';
    $user_db = 'postgres';
    $password_db = 'Ngohuy3092005'; 
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_role = $_SESSION['role'] ?? 'admin'; 

    //QUERY 1
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


    //QUERY 2
    if ($user_role === 'super') {
        $stmt_recent = $conn->prepare("
            SELECT 
                dd.ngay_diem_danh,
                dd.lop_id,
                dd.lesson_title,
                lh.ten_lop
            FROM 
                diem_danh dd
            JOIN 
                lop_hoc lh ON dd.lop_id = lh.id
            GROUP BY 
                dd.ngay_diem_danh, dd.lop_id, lh.ten_lop, dd.lesson_title
            ORDER BY 
                dd.ngay_diem_danh DESC
            LIMIT 30
        ");
        $stmt_recent->execute();
    } else {
        $stmt_recent = $conn->prepare("
            SELECT 
                dd.ngay_diem_danh,
                dd.lop_id,
                dd.lesson_title,
                lh.ten_lop
            FROM 
                diem_danh dd
            JOIN 
                lop_hoc lh ON dd.lop_id = lh.id
            JOIN 
                admin_lop_access ala ON lh.id = ala.lop_id -- JOIN để lọc quyền
            WHERE 
                ala.user_id = ?
            GROUP BY 
                dd.ngay_diem_danh, dd.lop_id, lh.ten_lop, dd.lesson_title
            ORDER BY 
                dd.ngay_diem_danh DESC
            LIMIT 30
        ");
        $stmt_recent->execute([$_SESSION['user_id']]);
    }
    
    $recent_sessions = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);


    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điểm danh học sinh</title>
    <link rel="stylesheet" href="style.css?v=1"> 
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
            
            background-color: var(--card-bg-color) !important;
            border: 2px solid var(--border-color) !important;
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
            <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container">
        <h2>Trang điểm danh học sinh</h2>
        <p><i>Để bắt đầu, vui lòng chọn lớp, ngày, bài học cần điểm danh:</i></p>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form class="form-diemdanh" action="handle_diemdanh.php" method="POST">
            <div>
                <label for="lop_id">Chọn lớp:</label>
                <select name="lop_id" id="lop_id" required>
                    <option value="">-- Chọn một lớp --</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>">
                            <?php echo htmlspecialchars($class['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="ngay_diem_danh">Chọn ngày:</label>
                <input type="date" name="ngay_diem_danh" id="ngay_diem_danh" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div style="flex-basis: 100%; margin-top: 10px;">
                <label for="lesson_title">Nội dung bài học:</label>
                <input type="text" name="lesson_title" id="lesson_title" placeholder="Ví dụ: Bài 1: Phương trình Bernoulli" style="width: 300px;" required>
            </div>
            
            <div style="flex-basis: 100%;">
                <button type="submit">Lấy danh sách</button>
            </div>
        </form>

        <div class="recent-sessions-container">
            <hr> 
            <h2>Các buổi điểm danh gần đây</h2>
            
            <div class="recent-sessions">
                <?php if (empty($recent_sessions)): ?>
                    <p>Chưa có dữ liệu điểm danh nào trong hệ thống (hoặc bạn chưa quản lý lớp nào).</p>
                <?php else: ?>
                    <?php foreach ($recent_sessions as $session): ?>
                        
                        <a class="session-item" 
                           href="handle_diemdanh.php?lop_id=<?php echo $session['lop_id']; ?>&ngay=<?php echo $session['ngay_diem_danh']; ?>&lesson_title=<?php echo urlencode($session['lesson_title']); ?>">
                            
                            <span class="session-date"><?php echo date('d/m/Y', strtotime($session['ngay_diem_danh'])); ?></span>
                            
                            <span class="session-class"><?php echo htmlspecialchars($session['ten_lop']); ?></span>
                            
                            <?php if (!empty($session['lesson_title'])): ?>
                                <span class="session-lesson"><?php echo htmlspecialchars($session['lesson_title']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="admin_main.js"></script>
</body>
</html>