<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI. Trang này chỉ dành cho Quản trị viên.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo tài khoản Quản trị</title>
    <link rel="stylesheet" href="auth.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="auth-container">
        <h1>Tạo tài khoản mới</h1>
        
        <form action="handle_register.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập (Admin mới):</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center;">
                <input type="checkbox" id="is_admin" name="is_admin" value="1" 
                       style="width: auto; height: 20px; margin-right: 10px;">
                <label for="is_admin" style="margin: 0; font-weight: bold;">Đặt làm Quản trị viên (Admin)?</label>
            </div>
            <button type="submit" class="auth-button">Tạo tài khoản</button>
        </form>

        <div class="auth-link">
            <p><a href="admin.php">Quay lại trang Quản trị</a></p>
        </div>
    </div>
</body>
</html>