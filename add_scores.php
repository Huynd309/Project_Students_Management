<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$all_classes = [];
try {
    require_once 'db_config.php';
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
    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý điểm</title>
    <link rel="stylesheet" href="style.css">
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
            <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>

    <main class="container" style="max-width: 800px;">
        <h2>Quản lý điểm thành phần</h2>
        <p>Chọn lớp, ngày và tên cột điểm để bắt đầu nhập điểm hàng loạt.</p>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form class="filter-form" action="handle_add_scores_bulk.php" method="POST">
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
                <label for="ngay_kiem_tra">Chọn ngày kiểm tra:</label>
                <input type="date" name="ngay_kiem_tra" id="ngay_kiem_tra" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div style="flex-basis: 100%; margin-top: 10px;">
                <label for="ten_cot_diem">Tên cột điểm:</label>
                <input type="text" name="ten_cot_diem" id="ten_cot_diem" placeholder="Ví dụ: Bài 1: Phương trình vi phân với biến số phân li" required>
            </div>
            
            <div style="flex-basis: 100%;">
                <button type="submit">Lấy danh sách học sinh</button>
            </div>
        </form>
    </main>
    
    <script src="admin_main.js"></script>
</body>
</html>