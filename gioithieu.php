<?php
session_start();

$sbd_tra_cuu = null;
$cac_lop = [];
$error_message = null;

if (isset($_GET['sbd_tra_cuu'])) {
    $sbd_tra_cuu = trim($_GET['sbd_tra_cuu']) ?: '';
    require_once 'db_config.php';

    try {
        $sql = "SELECT lh.id, lh.ten_lop 
                FROM users u 
                JOIN user_lop ul ON u.id = ul.user_id 
                JOIN lop_hoc lh ON ul.lop_hoc_id = lh.id 
                WHERE LOWER(u.username) = LOWER(?)";

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Giáo dục Nhất Đạo - Tra cứu & Giới thiệu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
    :root {
        --primary-color: #007bff; 
        --secondary-color: #f8f9fa;
        --text-color: #333;
        --footer-bg: #2c2c2c;
    }
    html { scroll-behavior: smooth; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; line-height: 1.6; color: var(--text-color); background: #f9f9f9; }
    * { box-sizing: border-box; }

    header { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
    .navbar { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 15px 20px; }
    .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 10px; }
    
    .nav-links { display: flex; align-items: center; }
    
    .nav-links > a { 
        text-decoration: none; 
        color: var(--primary-color); 
        margin-left: 20px; 
        font-weight: 500; 
        transition: color 0.3s; 
        font-size: 0.95rem; 
    }
    .nav-links > a:hover, .nav-links > a.active { 
        color: #0056b3; 
    }

    .dropdown {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
        margin-left: 20px;
    }

    .dropdown > a {
        text-decoration: none; 
        color: var(--primary-color); 
        font-weight: 500; transition: color 0.3s; font-size: 0.95rem;
    }
    .dropdown > a:hover { color: #0056b3; }

    .dropdown::after {
        content: ""; position: absolute; bottom: -20px; left: 0; width: 100%; height: 30px; background: transparent; z-index: 100;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 240px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        z-index: 9999;
        top: 100%; left: 0;
        border-radius: 8px;
        padding: 10px 0;
        margin-top: 10px;
        border-top: 3px solid var(--primary-color);
    }
    
    .dropdown-content::before {
        content: ""; position: absolute; top: -16px; left: 30px;
        border-width: 8px; border-style: solid; border-color: transparent transparent var(--primary-color) transparent;
    }

    .dropdown:hover .dropdown-content { display: block; animation: slideUp 0.3s ease; }

    .dropdown-content a {
        color: #333; padding: 12px 20px; text-decoration: none; display: block;
        font-size: 0.95rem; transition: 0.2s; text-align: left; border-bottom: 1px dashed #eee; margin-left: 0 !important;
    }
    .dropdown-content a:last-child { border-bottom: none; }
    .dropdown-content a:hover { background-color: #f0f7ff; color: var(--primary-color); padding-left: 25px; }
    .dropdown-content i { width: 25px; color: var(--primary-color); text-align: center; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

    /* --- 4. AUTH BUTTONS --- */
    .auth-box { margin-left: 20px; display: flex; gap: 10px; align-items: center; }
    .btn-auth { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; font-weight: bold; border: none; cursor: pointer; transition: 0.3s; }
    .btn-login { background: #eee; color: #333; }
    .btn-login:hover { background: #ddd; }
    .btn-admin { background: var(--primary-color); color: white; }
    .btn-logout { background: #dc3545; color: white; }

    /* --- 5. HERO & SEARCH --- */
    .hero {
        background: linear-gradient(rgba(0, 60, 130, 0.7), rgba(0, 60, 130, 0.7)), url('https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
        background-size: cover; background-position: center; min-height: 600px; 
        display: flex; justify-content: center; align-items: center; padding: 20px;
    }

    .search-container {
        background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 600px; width: 100%; text-align: center;
    }
    .search-container h1 { color: var(--primary-color); margin-bottom: 10px; font-size: 2rem; }
    .search-container p { color: #666; margin-bottom: 30px; }

    .search-form { display: flex; gap: 10px; margin-bottom: 20px; }
    .search-input { flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 1rem; outline: none; transition: 0.3s; }
    .search-input:focus { border-color: var(--primary-color); }
    .search-btn { padding: 15px 30px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; }
    .search-btn:hover { background: #0056b3; }

    .alert { padding: 15px; border-radius: 6px; margin-top: 15px; text-align: left; font-size: 0.95rem; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

    .class-select-form { display: flex; gap: 10px; margin-top: 10px; }
    .class-select { flex: 1; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }

    /* --- 6. BLOG SECTIONS --- */
    .section { padding: 80px 20px; max-width: 1100px; margin: 0 auto; }
    .section-header { text-align: center; margin-bottom: 60px; }
    .section-header h2 { font-size: 2.2rem; color: #333; margin-bottom: 15px; }
    .section-header .line { width: 60px; height: 4px; background: var(--primary-color); margin: 0 auto; }

    .blog-card { display: flex; background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 60px; border: 1px solid #eee; transition: transform 0.3s; }
    .blog-card:hover { transform: translateY(-5px); }
    .blog-card:nth-child(even) { flex-direction: row-reverse; } 

    .blog-img { flex: 1; min-height: 350px; position: relative; }
    .blog-img img { width: 100%; height: 100%; object-fit: cover; position: absolute; }
    
    .blog-content { flex: 1; padding: 50px; display: flex; flex-direction: column; justify-content: center; }
    .blog-content h3 { font-size: 1.8rem; color: #222; margin-bottom: 20px; }
    .blog-content p { color: #555; margin-bottom: 20px; line-height: 1.8; text-align: justify; }

    .features { list-style: none; padding: 0; }
    .features li { margin-bottom: 12px; display: flex; align-items: center; color: #444; }
    .features i { color: #28a745; margin-right: 12px; }

    footer {
        background-color: #2c2c2c; 
        background-color: var(--footer-bg); 
        color: #ccc; 
        padding: 60px 20px 20px;
        font-size: 0.95rem;
    }
    .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; }
    .footer-col h4 { color: #fff; margin-bottom: 20px; font-size: 1.2rem; border-left: 3px solid var(--primary-color); padding-left: 10px; }
    .footer-bottom { text-align: center; margin-top: 60px; padding-top: 20px; border-top: 1px solid #444; color: #777; }

    /* --- 8. RESPONSIVE --- */
    @media (max-width: 768px) {
        .navbar { flex-direction: column; gap: 15px; }
        .nav-links { flex-direction: column; gap: 10px; margin-left: 0; width: 100%; }
        .nav-links > a, .dropdown { margin: 0; width: 100%; text-align: center; }
        
        .dropdown::after { display: none; }
        .dropdown-content { position: static; box-shadow: none; border-top: none; width: 100%; padding-left: 20px; margin-top: 0; display: none; }
        .dropdown:hover .dropdown-content { display: block; }

        .auth-box { margin: 10px 0 0 0; }
        .hero { padding: 100px 20px 50px; }
        .search-form { flex-direction: column; }
        .search-btn { width: 100%; }
        .blog-card { flex-direction: column !important; }
        .blog-img { height: 250px; }
        .blog-content { padding: 30px; }
    }
</style>
</head>
<body>

    <header>
        <div class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap fa-lg"></i> TRUNG TÂM NHẤT ĐẠO EDU
            </a>
            
            <div class="nav-links">
                <a href="tientieuhoc.php">Tiền tiểu học & Tiểu học</a>
                
                <div class="dropdown">
                    <a href="#" class="active">Luyện thi (6-12) <i class="fas fa-caret-down"></i></a>
                    
                    <div class="dropdown-content">
                        <a href="luyenthi.php"><i class="fas fa-map-marker-alt"></i> Cơ sở 1: Lại Đà</a>
                        
                        <a href="luyenthi_cs2.php"><i class="fas fa-map-marker-alt"></i> Cơ sở 2: Thôn Hương</a>
                        <a href="luyenthi_cs3.php"><i class="fas fa-map-marker-alt"></i> Cơ sở 3: Mai Hiên</a>
                        <a href="#"><i class="fas fa-clock"></i> Cơ sở 4</a>
                    </div>
                </div>
                <a href="#">Nhất Đạo Gia sư</a>
                <a href="#">Nhất Đạo BookStore</a>
            </div>

            <div class="auth-box">
                <?php if (isset($_SESSION['username'])): ?>
                    <span style="font-weight:bold; color:#333;">Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="admin.php" class="btn-auth btn-admin"><i class="fas fa-cogs"></i></a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-auth btn-logout"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="btn-auth btn-login"><i class="fas fa-user"></i> Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="search-container">
            <h1>Tra cứu kết quả học tập</h1>
            <p>Nhập mã học sinh để xem bảng điểm chi tiết & nhận xét giáo viên.</p>

            <form class="search-form" action="" method="GET">
                <input 
                    type="text" 
                    name="sbd_tra_cuu" 
                    class="search-input" 
                    placeholder="Nhập Mã Học Sinh (VD: NDT0801)..." 
                    value="<?= htmlspecialchars($sbd_tra_cuu ?? '') ?>" 
                    required
                >
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tra cứu</button>
            </form>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <?php if (count($cac_lop) > 1): ?>
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle"></i> Tìm thấy nhiều lớp học!</strong>
                    <p>Mã HS <strong><?= htmlspecialchars($sbd_tra_cuu) ?></strong> tồn tại ở nhiều lớp. Vui lòng chọn:</p>
                    
                    <form action="details.php" method="GET" class="class-select-form">
                        <input type="hidden" name="sbd" value="<?= htmlspecialchars($sbd_tra_cuu) ?>">
                        <select name="lop_id" class="class-select">
                            <?php foreach ($cac_lop as $lop): ?>
                                <option value="<?= $lop['id'] ?>"><?= htmlspecialchars($lop['ten_lop']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-auth btn-admin">Xem</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px; font-size: 0.9rem; color: #777;">
                <i class="fas fa-arrow-down"></i> Cuộn xuống để tìm hiểu về chương trình học
            </div>
        </div>
    </section>

    <div id="chuong-trinh-hoc" class="section">
        <div class="section-header">
            <h2>Hệ Sinh Thái Giáo Dục Đông Anh</h2>
            <div class="line"></div>
            <p style="margin-top:15px; color:#666;">"Làm mọi hành động để học sinh tiến bộ"</p>
        </div>

        <div class="blog-card">
            <div class="blog-img">
                <img src="tieuhocnhatdaoedu.png" alt="Tiền tiểu học - Tâm Trí Thành">
            </div>
            <div class="blog-content">
                <h3><i class="fas fa-child"></i> Nhất Đạo Edu - Tâm Trí Thành (Tiền Tiểu Học & Tiểu Học)</h3>
                <p>Giai đoạn chuyển giao từ Mầm non lên Tiểu học là bước ngoặt quan trọng. Lớp học được thiết kế đặc biệt để giúp các con làm quen với môi trường mới đầy hứng khởi, tập trung phát triển tư duy và kỹ năng nền tảng.</p>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> <strong>CLB Tiền Tiểu Học:</strong> Chuẩn bị tâm thế và kĩ năng vững vàng cho bé vào lớp 1.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>CLB Yêu Toán & Tiếng Việt:</strong> Khơi dậy đam mê, xây dựng gốc rễ tư duy ngôn ngữ và logic.</li>
                </ul>
            </div>
        </div>

        <div class="blog-card">
            <div class="blog-img">
                <img src="luyenthilop612.png" alt="Luyện thi Nhất Đạo Edu">
            </div>
            <div class="blog-content">
                <h3><i class="fas fa-graduation-cap"></i> Nhất Đạo Edu - Hệ thống luyện thi (Lớp 6 - 12)</h3>
                <p>Chương trình luyện thi vào 10 và Đại Học top đầu. Bám sát khung của Bộ GD&ĐT, đồng thời mở rộng tư duy logic giúp học sinh phát triển năng lực tự học.</p>
                <p><strong>Môn học:</strong> Toán - Lý - Hóa - Văn - Anh - IELTS</p>
                
                <p><em>Hệ thống có 3 cơ sở chiến lược:</em></p>
                <ul class="features">
                    <li><i class="fas fa-map-marker-alt"></i> <strong>Cơ sở 1 (Lại Đà):</strong> Trung tâm điều hành và đào tạo lõi.</li>
                    <li><i class="fas fa-map-marker-alt"></i> <strong>Cơ sở 2 (Thôn Hương):</strong> Mũi nhọn luyện thi chất lượng cao.</li>
                    <li><i class="fas fa-map-marker-alt"></i> <strong>Cơ sở 3 (Mai Hiên):</strong> Cơ sở quy mô lớn với uy tín vượt trội.</li>
                </ul>
            </div>
        </div>
        
        <div class="blog-card">
            <div class="blog-img">
                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Nhất Đạo Gia Sư">
            </div>
            <div class="blog-content">
                <h3><i class="fas fa-chalkboard-teacher"></i> Nhất Đạo Gia Sư - Giải Pháp Kèm Cặp Cá Nhân 1-1 Tại Nhà</h3>
                <p>Giải pháp học tập cá nhân hóa dành cho học sinh cần bồi dưỡng kiến thức riêng biệt hoặc muốn tăng tốc nhanh chóng. Chúng tôi kết nối phụ huynh với đội ngũ gia sư ưu tú, tận tâm.</p>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> <strong>Mô hình 1 kèm 1:</strong> Lộ trình riêng biệt, sửa lỗi sai chi tiết cho từng học sinh.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Đội ngũ:</strong> Tuyển chọn từ các sinh viên ưu tú và giáo viên có nghiệp vụ sư phạm tốt.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Giá trị:</strong> Sát sao lộ trình học tập cho từng học sinh.</li>
                </ul>
            </div>
        </div>

        <div class="blog-card">
            <div class="blog-img">
                <img src="https://images.unsplash.com/photo-1519682337058-a94d519337bc?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Nhất Đạo BookStore">
            </div>
            <div class="blog-content">
                <h3><i class="fas fa-book-open"></i> Nhất Đạo BookStore Hệ Thống Cung Cấp Học Liệu & Giáo Trình Độc Quyền</h3>
                <p>Nơi cung cấp đầy đủ các nguồn tài liệu học tập chính thống, giúp học sinh tiết kiệm thời gian tìm kiếm và tiếp cận nguồn tri thức chuẩn xác nhất.</p>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> <strong>Cung cấp:</strong> Các bộ tài liệu ôn thi, sách bài tập do đội ngũ Nhất Đạo Edu biên soạn.</li>
                    <li><i class="fas fa-check-circle"></i> <strong>Đảm bảo:</strong> Học sinh có nguồn tài liệu chuẩn xác và cập nhật mới nhất.</li>
                </ul>
            </div>
        </div>

    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Về Nhất Đạo Edu</h4>
                <p>Hệ thống giáo dục uy tín, tập trung vào chất lượng giảng dạy và sự phát triển toàn diện của học sinh.</p>
                <p style="margin-top:15px;"><strong>© Designed by:</strong> Ngô Dương Huy</p>
            </div>
            <div class="footer-col">
                <h4>Địa chỉ</h4>
                <p><i class="fas fa-map-marker-alt"></i> Xóm 1, thôn Lại Đà, xã Đông Anh, thành phố Hà Nội</p>
                <p><i class="fas fa-clock"></i> 8:00 - 21:00 (Hàng ngày)</p>
            </div>
            <div class="footer-col">
                <h4>Liên hệ</h4>
                <p><strong>Ngô Duy Nhất Đạo</strong></p>
                <p><i class="fas fa-phone-alt"></i> 0965 601 055</p>
                <p><i class="fas fa-envelope"></i> nhatdaoedu@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
        </div>
    </footer>

</body>
</html>