<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI! Bạn không có quyền vào trang này.");
}

$filter_khoi = isset($_GET['khoi']) ? (int)$_GET['khoi'] : null;
$filter_lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : null;

$all_classes = [];
$grouped_classes = [];
$connection_error = null;

try {
    require_once 'db_config.php';

    // Lấy danh sách lớp học theo quyền
    $user_role = $_SESSION['role'] ?? 'admin'; 

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

    // Gom nhóm lớp theo khối cho thanh menu
    foreach ($all_classes as $class) {
        $grouped_classes[$class['khoi']][] = $class;
    }

    $conn = null; 

} catch (PDOException $e) {
    $connection_error = "Lỗi khi lấy danh sách: " . $e->getMessage();
}

$active_khoi = null;
if ($filter_khoi) {
    $active_khoi = $filter_khoi;
} elseif ($filter_lop_id) {
    foreach ($all_classes as $class) {
        if ($class['id'] == $filter_lop_id) {
            $active_khoi = $class['khoi'];
            break; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Quản Trị</title>
    <link rel="stylesheet" href="style.css?v=1">
    <link rel="icon" type="image/png" href="nhatdao_watermark2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Bố cục chia 2 cột */
        .admin-layout-row {
            display: flex;
            gap: 40px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .admin-column {
            flex: 1;
            min-width: 350px;
        }

        /* --- NÚT ĐĂNG XUẤT TRÊN HEADER --- */
        .header-logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 15px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
            transition: all 0.3s ease;
        }
        .header-logout-btn:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.4);
        }

        /* --- CỘT TRÁI: FORM LIQUID GLASS --- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(15px); 
            -webkit-backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 35px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2); 
            border-left: 1px solid rgba(255, 255, 255, 0.2); 
            box-shadow: 10px 10px 30px rgba(0, 0, 0, 0.4), inset 1px 1px 5px rgba(255, 255, 255, 0.05); 
            color: #fff;
        }

        .glass-panel h2 {
            margin-top: 0; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 20px;
        }

        .glass-panel label { display: block; margin-bottom: 8px; color: #ddd; font-weight: 500; }

        .glass-input {
            width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px;
            background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.05); color: #fff;
            box-shadow: inset 3px 3px 6px rgba(0,0,0,0.5), inset -2px -2px 4px rgba(255,255,255,0.02);
            transition: all 0.3s ease; box-sizing: border-box; font-size: 15px;
        }

        .glass-input:focus {
            outline: none; border-color: #3498db;
            box-shadow: inset 3px 3px 6px rgba(0,0,0,0.4), 0 0 10px rgba(52, 152, 219, 0.4);
        }
        .glass-input::placeholder { color: rgba(255, 255, 255, 0.3); }
        .glass-input option { background-color: #1a253c; color: white; }

        .glass-button {
            width: 100%; background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%); color: white;
            padding: 14px; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer;
            box-shadow: 0 5px 15px rgba(58, 123, 213, 0.4), inset 1px 1px 3px rgba(255,255,255,0.4);
            transition: all 0.3s ease; text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .glass-button:hover {
            transform: translateY(-2px); box-shadow: 0 8px 20px rgba(58, 123, 213, 0.6), inset 1px 1px 3px rgba(255,255,255,0.5);
        }

        /* --- CỘT PHẢI: KHỐI TÍNH NĂNG NHANH --- */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 25px 15px;
            text-align: center;
            color: #fff;
            text-decoration: none;
            box-shadow: 4px 4px 15px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .action-card i {
            font-size: 2.5em;
            color: #3498db;
            transition: transform 0.3s ease, color 0.3s ease;
            text-shadow: 0 2px 10px rgba(52, 152, 219, 0.5);
        }

        .action-card span {
            font-size: 1.1em;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .action-card:hover {
            transform: translateY(-10px) scale(1.02);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 10px 15px 25px rgba(0,0,0,0.4), inset 1px 1px 3px rgba(255,255,255,0.2);
        }
        .action-card:hover i {
            transform: scale(1.15);
            color: #00d2ff;
        }
    </style>
</head>

<body class ="admin-page-blue">
    <header class="header">
       <div class="auth-buttons" style="display: flex; align-items: center; width: 100%;">
            <?php if (isset($_SESSION['username'])): ?>
                <span style="color: #333; margin-right: 15px;">
                    Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                </span>
                <div class="theme-switch-wrapper">
                    <label class="theme-switch" for="checkbox">
                        <input type="checkbox" id="checkbox" />
                        <div class="slider round"></div>
                    </label>
                </div>
                
                <div style="margin-left: auto;">
                    <a href="logout.php" style="text-decoration: none;">
                        <button class="header-logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </button>
                    </a>
                </div>
                
            <?php else: ?>
                <div style="margin-left: auto;">
                    <a href="login.php"><button id="login-btn">Đăng nhập</button></a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <nav class="admin-nav">
        <ul>
            <?php $trang_chu_active = (!$filter_khoi && !$filter_lop_id) ? 'active' : ''; ?>
            <li><a href="admin.php" class="<?php echo $trang_chu_active; ?>">Trang Quản Trị</a></li>

            <?php if ($_SESSION['role'] === 'super'): ?>
            <li><a href="manage_admins.php" style="color: gold;">Phân quyền cho Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main style="padding: 20px; max-width: 1300px; margin: 0 auto;">
        
        <?php if (isset($_GET['error'])): ?>
            <div style="color: #ff6b6b; border: 1px solid #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 12px; margin-bottom: 15px; border-radius: 8px;">
                <strong>LỖI:</strong> 
                <?php
                    if ($_GET['error'] == 'duplicate_sbd') {
                        echo 'Số báo danh này đã tồn tại!';
                    } else {
                        echo htmlspecialchars($_GET['error']);
                    }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['add']) && $_GET['add'] == 'success'): ?>
            <div style="color: #1dd1a1; border: 1px solid #1dd1a1; background: rgba(29, 209, 161, 0.1); padding: 12px; margin-bottom: 15px; border-radius: 8px;">
                <strong>THÀNH CÔNG:</strong> Đã thêm học sinh mới.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['enroll']) && $_GET['enroll'] == 'success'): ?>
            <div style="color: #1dd1a1; border: 1px solid #1dd1a1; background: rgba(29, 209, 161, 0.1); padding: 12px; margin-bottom: 15px; border-radius: 8px;">
                <strong>THÀNH CÔNG:</strong> Đã thêm học sinh vào lớp.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate_enroll'): ?>
             <div style="color: #ff6b6b; border: 1px solid #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 12px; margin-bottom: 15px; border-radius: 8px;">
                <strong>LỖI:</strong> Học sinh này đã ở trong lớp đó rồi!
            </div>
        <?php endif; ?>

        <?php if ($connection_error): ?>
            <div style="color: #ff6b6b; border: 1px solid #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 12px; margin-bottom: 15px; border-radius: 8px;">
                <?php echo $connection_error; ?>
            </div>
        <?php endif; ?>

        <div class="admin-layout-row">
            
            <div class="admin-column glass-panel">
                <h2 style="text-align: center;">Thêm học sinh mới</h2>
                <p style="color: #ff9ff3; font-size: 0.9em; margin-bottom: 25px; line-height: 1.6;">
                    <i class="fas fa-exclamation-circle"></i> Chú ý: Số báo danh phải theo quy ước <strong>NDxxyyzz</strong><br>
                    (<strong>xx</strong>: lớp, <strong>yy</strong>: năm sinh, <strong>zz</strong>: số thứ tự). <br>
                    Ví dụ: Học sinh sinh năm 2008, học lớp Toán 12 thì là NDT0801.
                </p>
                
                <form class="form-add" action="handle_add_student.php" method="POST">
                    <label>Số báo danh:</label>
                    <input type="text" name="so_bao_danh" class="glass-input" required placeholder="VD: NDT0801">
                    
                    <label>Họ và tên:</label>
                    <input type="text" name="ho_ten" class="glass-input" required placeholder="Nhập họ tên...">
                    
                    <label>Trường:</label>
                    <input type="text" name="truong" class="glass-input" placeholder="Nhập tên trường...">

                    <label>SĐT Học sinh (Zalo):</label>
                    <input type="text" name="sdt_hoc_sinh" class="glass-input" placeholder="Nhập SĐT học sinh...">

                    <label>SĐT Phụ huynh (Zalo):</label>
                    <input type="text" name="sdt_phu_huynh" class="glass-input" placeholder="Nhập SĐT phụ huynh...">
                    
                    <label>Chọn lớp:</label>
                    <select name="lop_id" class="glass-input" required>
                        <option value="">-- Chọn một lớp --</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="glass-button">
                        <i class="fas fa-user-plus"></i> Thêm học sinh
                    </button>
                </form>
            </div>

            <div class="admin-column">
                <h2 style="color: #fff; margin-top: 0; margin-bottom: 25px; text-shadow: 0 2px 4px rgba(0,0,0,0.5); text-align: center;">
                    <i class="fas fa-th-large"></i> Quick Access
                </h2>
                
                <div class="action-grid">

                    <a href="diemdanh.php" class="action-card">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Điểm danh</span>
                    </a>

                    <a href="daily_report.php" class="action-card">
                        <i class="fas fa-calendar-day"></i>
                        <span>Báo cáo Ngày</span>
                    </a>

                    <a href="monthly_report.php" class="action-card">
                        <i class="fas fa-chart-line"></i>
                        <span>Báo cáo Tháng</span>
                    </a>

                    <a href="student_list.php" class="action-card">
                        <i class="fas fa-users"></i>
                        <span>Danh sách HS</span>
                    </a>
                </div>
            </div>

        </div>
    </main>
    
    <script src="admin_main.js"></script>
</body>
</html>