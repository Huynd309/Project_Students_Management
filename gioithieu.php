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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    
    <title>Giới thiệu & Tra cứu</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    
    <style>
        /* === CSS BỔ SUNG CHO GIAO DIỆN ĐIỆN THOẠI === */
        
        /* Đảm bảo không bị vỡ khung */
        * { box-sizing: border-box; } 

        /* CSS mặc định cho Footer (nếu trong style.css chưa có grid/flex) */
        .footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            background: #333;
            color: white;
            padding: 40px 20px;
        }
        .footer-column {
            flex: 1;
            min-width: 250px; /* Đảm bảo cột không bị bóp quá nhỏ */
            margin-bottom: 20px;
        }

        /* === MEDIA QUERY: CHỈ ÁP DỤNG KHI MÀN HÌNH NHỎ HƠN 768px (Mobile/Tablet) === */
        @media (max-width: 768px) {
            /* 1. Header: Xếp chồng dọc thay vì ngang */
            .header {
                flex-direction: column;
                text-align: center;
                padding: 15px;
                height: auto !important; /* Ghi đè chiều cao cố định nếu có */
            }
            
            .header .logo, 
            .header .nav, 
            .header .auth-buttons {
                width: 100%;
                margin-bottom: 15px;
                justify-content: center;
            }

            /* Menu căn giữa */
            .nav ul {
                padding: 0;
                display: flex;
                justify-content: center;
                gap: 15px;
            }

            /* Nút đăng nhập/xuất giãn cách đều */
            .auth-buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .auth-buttons button {
                width: 100%;
                padding: 10px;
            }

            /* 2. Main Container: Giảm padding */
            .container {
                padding: 15px;
                width: 95%; /* Chiếm gần hết màn hình */
                margin: 20px auto;
            }

            h1 { font-size: 1.8em; }

            /* 3. Form tìm kiếm: Xếp dọc input và button */
            .search-box {
                display: flex;
                flex-direction: column;
                align-items: stretch; /* Kéo giãn full chiều ngang */
            }

            .search-box input {
                width: 100%;
                margin: 10px 0;
                padding: 12px;
                font-size: 16px; /* Chữ to dễ nhìn */
            }

            .search-box button {
                width: 100%;
                padding: 12px;
                margin-left: 0; /* Xóa margin cũ nếu có */
                font-size: 16px;
            }

            /* 4. Footer: Xếp chồng các cột */
            .footer {
                flex-direction: column;
                text-align: center;
                padding: 30px 15px;
            }
            
            .footer-column {
                width: 100%;
                border-bottom: 1px solid #444;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            .footer-column:last-child {
                border-bottom: none;
            }
        }
    </style>
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
            <span style="color: #333; margin-right: 15px; display:inline-block; margin-bottom:10px;">
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
        <label for="student-id" style="font-weight:bold; display:block; margin-bottom:10px; text-align:left;">Vui lòng nhập mã học sinh:</label>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <input
                type="text"
                id="student-id"
                name="sbd_tra_cuu"
                placeholder="Ví dụ: NDT0801"
                value="<?= htmlspecialchars($sbd_tra_cuu ?? '') ?>"
                style="flex:1; padding:10px; border:1px solid #ccc; border-radius:5px;"
            />
            <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Tra cứu</button>
        </div>
    </form>

    <?php if ($error_message): ?>
        <div style="color: #721c24; background-color: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-top: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
        </div>
    <?php endif; ?>

    <?php if (count($cac_lop) > 1): ?>
        <div style="background-color: #f8f9fa; padding: 20px; margin-top: 20px; border-radius: 8px; border: 1px solid #ddd;">
            <h3 style="margin-top:0;">Hệ thống tìm thấy nhiều lớp cho SBD "<?= htmlspecialchars($sbd_tra_cuu) ?>"</h3>
            <p>Vui lòng chọn lớp bạn muốn xem điểm:</p>
            
            <form action="details.php" method="GET" style="margin-top:15px;">
                <input type="hidden" name="sbd" value="<?= htmlspecialchars($sbd_tra_cuu) ?>">

                <select name="lop_id" style="padding: 10px; font-size: 1em; width:100%; max-width:300px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc;">
                    <?php foreach ($cac_lop as $lop): ?>
                        <option value="<?= $lop['id'] ?>">
                            <?= htmlspecialchars($lop['ten_lop']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Xem điểm</button>
            </form>
        </div>
    <?php endif; ?>

    <hr style="margin: 40px 0; border:0; border-top:1px solid #eee;" />

</main>

<footer class="footer">
    <div class="footer-column">
        <h4><i class="fas fa-building-columns"></i> Về chúng tôi</h4>
        <p>Designed and Developed by Ngô Dương Huy.</p>
        <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
        <p><strong>© Copyright by:</strong> Ngô Dương Huy</p>
    </div>

    <div class="footer-column">
        <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ</h4>
        <p>Xóm 1, thôn Lại Đà, xã Đông Anh, thành phố Hà Nội</p>
    </div>

    <div class="footer-column">
        <h4><i class="fas fa-phone"></i> Liên hệ</h4>
        <p>Ngô Duy Nhất Đạo</p>
        <p><strong>Điện thoại:</strong> 0965601055</p>
        <p><strong>Email:</strong> nhatdaoedu@gmail.com</p>
    </div>
</footer>

</body>
</html>