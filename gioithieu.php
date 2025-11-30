<?php
session_start();

$sbd_tra_cuu = null;
$cac_lop = [];
$error_message = null;

if (isset($_GET['sbd_tra_cuu'])) {

    $sbd_tra_cuu = trim($_GET['sbd_tra_cuu']) ?: '';

    require_once 'db_config.php';

    try {
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

        if ($so_luong_lop === 0) {
            $error_message = "Không tìm thấy học sinh với mã: " . htmlspecialchars($sbd_tra_cuu);
        } elseif ($so_luong_lop === 1) {
            $lop_id = $cac_lop[0]['id'];
            header("Location: details.php?sbd=" . urlencode($sbd_tra_cuu) . "&lop_id=" . urlencode($lop_id));
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
    <meta charset="UTF-8" />
    <title>Giới thiệu & Tra cứu</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>

<header class="header">
    <div class="logo">
        <a class="logo-link" style="text-decoration:none; color:inherit;">
            <h2>Hệ thống tra cứu điểm học sinh</h2>
        </a>
    </div>

    <nav class="nav">
        <ul>
            <li><a href="gioithieu.php">Trang chủ</a></li>
            <li><a href="gioithieu.php">Giới thiệu</a></li>
            <li><a href="#">Liên hệ</a></li>
        </ul>
    </nav>

    <div class="auth-buttons">
        <?php if (isset($_SESSION['username'])): ?>
            <span style="color: #333; margin-right: 15px;">
                Chào, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!
            </span>

            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
            <?php endif; ?>

            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        <?php else: ?>
            <a href="login.php"><button id="login-btn">Đăng nhập</button></a>
        <?php endif; ?>
    </div>
</header>

<main class="container">

    <h1>Tra cứu điểm</h1>

    <form class="search-box" action="" method="GET">
        <label for="student-id">Vui lòng nhập mã học sinh:</label>
        <input
            type="text"
            id="student-id"
            name="sbd_tra_cuu"
            placeholder="Ví dụ: HS1001"
            value="<?= htmlspecialchars($sbd_tra_cuu ?? '') ?>"
        />
        <button type="submit">Tra cứu</button>
    </form>

    <?php if ($error_message): ?>
        <div style="color: red; padding: 10px; border: 1px solid red; margin-top: 20px;">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <?php if (count($cac_lop) > 1): ?>
        <div style="background-color: #f3f3f3; padding: 20px; margin-top: 20px; border-radius: 8px;">
            <h3>Hệ thống tìm thấy nhiều lớp cho SBD "<?= htmlspecialchars($sbd_tra_cuu) ?>"</h3>
            <p>Vui lòng chọn lớp bạn muốn xem điểm:</p>
            
            <form action="details.php" method="GET">
                <input type="hidden" name="sbd" value="<?= htmlspecialchars($sbd_tra_cuu) ?>">

                <select name="lop_id" style="padding: 10px; font-size: 1em;">
                    <?php foreach ($cac_lop as $lop): ?>
                        <option value="<?= $lop['id'] ?>">
                            <?= htmlspecialchars($lop['ten_lop']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" style="padding: 10px;">Xem điểm</button>
            </form>
        </div>
    <?php endif; ?>

    <hr style="margin: 40px 0;" />

</main>

<footer class="footer">
    <div class="footer-column">
        <h4><i class="fas fa-building-columns"></i> Về chúng tôi</h4>
        <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
    </div>

    <div class="footer-column">
        <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ</h4>
        <p>136 đường Xuân Thuỷ, Phường Cầu Giấy, thành phố Hà Nội</p>
    </div>

    <div class="footer-column">
        <h4><i class="fas fa-phone"></i> Liên hệ</h4>
        <p><strong>© Copyright by:</strong> Ngô Dương Huy</p>
        <p><strong>Điện thoại:</strong> 0961223066</p>
        <p><strong>Email:</strong> ngohuy3092005@gmail.com</p>
    </div>
</footer>

</body>
</html>
