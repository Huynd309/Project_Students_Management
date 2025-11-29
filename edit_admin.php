<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if (!isset($_GET['id'])) { die("Thiếu ID admin."); }
$admin_id = $_GET['id'];

$all_classes = [];
$admin_info = null;
$assigned_class_ids = [];

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt_admin = $conn->prepare("SELECT * FROM users WHERE id = ? AND role != 'super'");
    $stmt_admin->execute([$admin_id]);
    $admin_info = $stmt_admin->fetch(PDO::FETCH_ASSOC);

    if (!$admin_info) { die("Không tìm thấy admin này hoặc bạn không thể sửa Super Admin."); }

    $stmt_classes = $conn->query("SELECT * FROM lop_hoc ORDER BY ten_lop ASC");
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    $stmt_access = $conn->prepare("SELECT lop_id FROM admin_lop_access WHERE user_id = ?");
    $stmt_access->execute([$admin_id]);
    $assigned_class_ids = $stmt_access->fetchAll(PDO::FETCH_COLUMN, 0); 

} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa quyền Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkbox-group {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;
        }
        .checkbox-item {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: flex; align-items: center;
            color: var(--text-color);
            transition: 0.2s;
        }
        .checkbox-item:hover { background-color: rgba(255, 255, 255, 0.4); }
        .checkbox-item input { margin-right: 8px; transform: scale(1.2); accent-color: var(--primary-color); }
    </style>
</head>
<body class="admin-page-blue">
    
    <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">Chào, <strong>Super Admin</strong>!</span>
            
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>
            
            <a href="manage_admins.php"><button id="login-btn">Quay lại</button></a>
        </div>
    </header>

    <main class="container">
        <h1>Sửa quyền hạn: <?php echo htmlspecialchars($admin_info['username']); ?></h1>
        
        <form class="form-edit" action="handle_update_admin.php" method="POST">
            <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

            <label>Đổi mật khẩu (Để trống nếu không đổi):</label>
            <input type="password" name="new_password" placeholder="Nhập mật khẩu mới...">

            <label style="margin-top: 20px; display: block;">Phân quyền quản lý lớp:</label>
            <div class="checkbox-group">
                <?php foreach ($all_classes as $class): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="access_classes[]" value="<?php echo $class['id']; ?>"
                            <?php 
                                if (in_array($class['id'], $assigned_class_ids)) {
                                    echo 'checked'; 
                                }
                            ?>>
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="submit-btn">Cập nhật quyền hạn</button>
        </form>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>