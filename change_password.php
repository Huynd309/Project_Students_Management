<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error_msg = null;
$success_msg = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    require_once 'db_config.php';
    
    try {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_hash = $user['password_hash'];

        if (!password_verify($old_password, $current_hash)) {
            $error_msg = "Mật khẩu cũ không chính xác!";
        } elseif (strlen($new_password) < 3) {
            $error_msg = "Mật khẩu mới phải có ít nhất 3 ký tự.";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "Mật khẩu mới và mật khẩu xác nhận không trùng khớp!";
        } else {
            
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_stmt->execute([$new_hash, $user_id]);
            
            $success_msg = "Đổi mật khẩu thành công!";
        }
        
        $conn = null;

    } catch (PDOException $e) {
        $error_msg = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page-blue"> <header class="header">
        <div class="auth-buttons">
            <span style="color: #333; margin-right: 15px;">
                Chào, <strong><?php echo htmlspecialchars($_SESSION['ho_ten']); ?></strong>!
            </span>
            
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>

            <a href="index.php"><button class="btn-header-secondary">Quay lại trang chủ</button></a>
            <a href="logout.php"><button class="btn-header-logout">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container" style="max-width: 600px;"> <h2>Đổi mật khẩu</h2>
        <p><i>Vui lòng nhập mật khẩu cũ và mật khẩu mới của bạn.</i></p>
        
        <?php if ($error_msg): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_msg): ?>
            <div class="success-msg">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <form class="form-edit" action="change_password.php" method="POST">
            
            <label for="old_password">Mật khẩu cũ:</label>
            <input type="password" name="old_password" id="old_password" required>
            
            <label for="new_password">Mật khẩu mới:</label>
            <input type="password" name="new_password" id="new_password" required>
            
            <label for="confirm_password">Xác nhận mật khẩu mới:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            
            <button type="submit" class="submit-btn">Cập nhật thay đổi</button>
        </form>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>