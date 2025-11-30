<?php
session_start();

$sbd_tra_cuu = null;
$cac_lop = [];
$error_message = null;

if (isset($_GET['sbd_tra_cuu'])) {
    $sbd_tra_cuu = $_GET['sbd_tra_cuu'];

    require_once 'db_config.php';

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT
                lh.id,
                lh.ten_lop
            FROM
                users u
            JOIN
                user_lop ul ON u.id = ul.user_id
            JOIN
                lop_hoc lh ON ul.lop_hoc_id = lh.id
            WHERE
                LOWER(u.username) = LOWER(?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$sbd_tra_cuu]);
        $cac_lop = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $so_luong_lop = count($cac_lop);

        if ($so_luong_lop == 0) {
            $error_message = "Không tìm thấy học sinh với mã: " . htmlspecialchars($sbd_tra_cuu);
        } elseif ($so_luong_lop == 1) {
            $lop_id = $cac_lop[0]['id'];
            header("Location: details.php?sbd=" . $sbd_tra_cuu . "&lop_id=" . $lop_id);
            exit;
        }
        
    } catch (PDOException $e) {
        $error_message = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giới thiệu & Tra cứu</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-page-blue">
    
    <header class="header">
        <div class="logo">
            <a class="logo-link"> <h2>Hệ thống tra cứu điểm học sinh</h2> </a>
        </div>
        
        <div class="auth-buttons">
            <div class="theme-switch-wrapper" style="margin-right: 20px;">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>

            <?php if (isset($_SESSION['username'])): ?>
                <span style="color: #333; margin-right: 15px;">
                    Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                </span>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                    <a href="admin.php"><button class="submit-btn-alt" style="width: auto; padding: 8px 15px; margin-top:0;">Trang quản trị</button></a>
                <?php endif; ?>
                <a href="logout.php"><button class="btn-header-logout">Đăng xuất</button></a>
            <?php else: ?>
                <a href="login.php"><button class="submit-btn" style="width: auto; padding: 8px 20px; margin-top:0;">Đăng nhập</button></a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">
        
        <h1>Tra cứu điểm</h1>
        <p>Nhập mã học sinh (SBD) để xem chi tiết điểm số.</p>
        
        <form class="search-box" action="" method="GET">
           <input type="text" id="student-id" name="sbd_tra_cuu" placeholder="Ví dụ: HS1001" 
                  value="<?php echo htmlspecialchars($sbd_tra_cuu); ?>" required />
           <button type="submit">Tra cứu</button>
        </form>

        <?php if ($error_message): ?>
            <div class="error-msg">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (count($cac_lop) > 1): ?>
            <div class="lookup-widget" style="margin-top: 30px; text-align: center;">
                <h3 style="margin-top: 0;">Tìm thấy <?php echo count($cac_lop); ?> lớp cho SBD "<?php echo htmlspecialchars($sbd_tra_cuu); ?>"</h3>
                <p style="margin-bottom: 15px;">Vui lòng chọn lớp bạn muốn xem điểm:</p>
                
                <form action="details.php" method="GET" class="filter-form" style="justify-content: center;">
                    <input type="hidden" name="sbd" value="<?php echo htmlspecialchars($sbd_tra_cuu); ?>">
                    
                    <select name="lop_id" required>
                        <?php foreach ($cac_lop as $lop): ?>
                            <option value="<?php echo $lop['id']; ?>">
                                <?php echo htmlspecialchars($lop['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit">Xem điểm</button>
                </form>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 40px 0; border-color: var(--border-color); opacity: 0.5;">
        
        <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; text-align: center;">
             <div>
                 <h4><i class="fas fa-building-columns" style="color: var(--primary-color);"></i> Về chúng tôi</h4>
                 <p style="font-size: 0.9em;">Hệ thống tra cứu điểm và quản lý học tập<br>dành cho học sinh và giáo viên.</p>
             </div>
             <div>
                 <h4><i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i> Địa chỉ</h4>
                 <p style="font-size: 0.9em;">136 đường Xuân Thuỷ, Phường Cầu Giấy,<br>thành phố Hà Nội</p>
             </div>
             <div>
                 <h4><i class="fas fa-phone" style="color: var(--primary-color);"></i> Liên hệ</h4>
                 <p style="font-size: 0.9em;"><strong>Hotline:</strong> 0961223066<br><strong>Email:</strong> ngohuy3092005@gmail.com</p>
             </div>
        </div>

    </main>

    <footer class="footer">
        <div class="footer-bottom" style="text-align: center;">
            <p>© Copyright by: Ngô Dương Huy</p>
        </div>
    </footer>

    <script src="admin_main.js"></script>
</body>
</html>