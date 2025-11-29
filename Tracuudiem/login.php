<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="auth.css"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="auth-container">
        <h1>Đăng nhập hệ thống</h1>

        <?php if (isset($_GET['register']) && $_GET['register'] == 'success'): ?>
            <div class="message message-success">
                Đăng ký thành công! Vui lòng đăng nhập.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message message-error">
                Sai tên đăng nhập hoặc mật khẩu.
            </div>
        <?php endif; ?>

        <form action="handle_login.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="auth-button">Đăng nhập</button>
        </form>

        <div class="auth-link">
            <p>Chưa có tài khoản? <a href="#"> Liên hệ với quản trị viên</a></p>
            <p><a href="gioithieu.php">Quay lại trang Giới thiệu</a></p>
        </div>
    </div>
</body>
</html>