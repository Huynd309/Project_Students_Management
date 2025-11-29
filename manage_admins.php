<?php
session_start();
// Bảo mật: Chỉ SUPER ADMIN mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("TRUY CẬP BỊ TỪ CHỐI! Chỉ dành cho Super Admin.");
}

$all_classes = [];
$existing_admins = [];

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Lấy danh sách lớp
    $stmt = $conn->query("SELECT * FROM lop_hoc ORDER BY ten_lop ASC");
    $all_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Lấy danh sách admin hiện có (kèm theo các lớp họ quản lý)
    $stmt_admins = $conn->query("
        SELECT 
            u.id, 
            u.username,
            STRING_AGG(lh.ten_lop, ', ') as lop_quan_ly
        FROM users u
        LEFT JOIN admin_lop_access ala ON u.id = ala.user_id
        LEFT JOIN lop_hoc lh ON ala.lop_id = lh.id
        WHERE u.is_admin = true AND u.role != 'super'
        GROUP BY u.id, u.username
        ORDER BY u.username ASC
    ");
    $existing_admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Lỗi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS riêng cho checkbox chọn lớp */
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .checkbox-item {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
            color: var(--text-color);
        }
        .checkbox-item:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }
        .checkbox-item input {
            margin-right: 8px;
            accent-color: var(--primary-color);
            transform: scale(1.2);
        }
    </style>
</head>
<body class="admin-page-blue"> <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">
                Chào, <strong>Super Admin</strong>!
            </span>
            
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>

            <a href="admin.php"><button id="login-btn">Về trang chủ</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container">
        <h2>Tạo tài khoản Quản trị viên (Sub-Admin)</h2>
        <p>Tạo tài khoản mới và chỉ định lớp học được phép quản lý.</p>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="success-msg">Đã tạo admin và phân quyền thành công!</div>
        <?php endif; ?>

        <form class="form-add" action="handle_add_admin.php" method="POST">
            
            <label>Tên đăng nhập (Username):</label>
            <input type="text" name="username" required placeholder="Ví dụ: teacher_toan">
            
            <label>Mật khẩu:</label>
            <input type="password" name="password" required placeholder="Nhập mật khẩu..."> 

            <label style="margin-top: 15px; display:block; font-weight: bold; color: var(--text-color);">
                Chỉ định lớp được phép quản lý:
            </label>
            
            <div class="checkbox-group">
                <?php foreach ($all_classes as $class): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="access_classes[]" value="<?php echo $class['id']; ?>">
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="submit-btn">Tạo Admin mới</button>
        </form>

        <hr style="margin: 40px 0; border-top: 1px solid var(--border-color); opacity: 0.5;">

        <h2>Danh sách Admin phụ</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Các lớp quản lý</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($existing_admins)): ?>
                    <tr><td colspan="4">Chưa có admin phụ nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($existing_admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td style="font-weight: bold; color: var(--primary-color);">
                                <?php echo htmlspecialchars($admin['username']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($admin['lop_quan_ly'] ?: 'Chưa gán lớp'); ?>
                            </td>
                            <td>
                            <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" class="btn-edit">Sửa</a>
    
                            <a href="handle_delete_user.php?id=<?php echo $admin['id']; ?>&redirect=manage_admins.php" 
                            class="btn-delete" 
                            onclick="return confirm('Bạn chắc chắn muốn xóa admin này?');">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </main>

    <script src="admin_main.js"></script>
</body>
</html>