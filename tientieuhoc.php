<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiền Tiểu học & Tiểu học - Nhất Đạo Edu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- 1. CẤU HÌNH CHUNG --- */
        :root {
            --primary-color: #007bff;
            --accent-color: #FF9F1C; 
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --footer-bg: #2c2c2c;
            --gold-color: #FFD700;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; line-height: 1.6; color: var(--text-color); background: #fff; }
        * { box-sizing: border-box; }

        /* --- 2. HEADER / NAVBAR --- */
        header { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .navbar { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 15px 20px; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        
        .nav-links { display: flex; align-items: center; }
        .nav-links a { text-decoration: none; color: #555; margin-left: 20px; font-weight: 500; transition: color 0.3s; font-size: 0.95rem; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-color); }

        .auth-box { margin-left: 20px; display: flex; gap: 10px; align-items: center; }
        .btn-auth { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; font-weight: bold; border: none; cursor: pointer; transition: 0.3s; }
        .btn-login { background: #eee; color: #333; }
        .btn-admin { background: var(--primary-color); color: white; }
        .btn-logout { background: #dc3545; color: white; }

        /* --- 3. BANNER --- */
        .page-banner {
            background: linear-gradient(rgba(0, 123, 255, 0.4), rgba(0, 123, 255, 0.4)), url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            height: 380px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 20px;
        }
        .banner-content { background: rgba(0, 0, 0, 0.4); padding: 30px; border-radius: 15px; backdrop-filter: blur(3px); }
        .page-banner h1 { font-size: 2.5rem; margin-bottom: 10px; text-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        .page-banner p { font-size: 1.3rem; font-weight: 500; }

        /* --- 4. CÁC KHỐI NỘI DUNG (MỤC TIÊU) --- */
        .section-wrapper { padding: 60px 20px; border-bottom: 1px solid #eee; }
        .bg-light { background-color: #f9fbfc; }
        
        .section-container { max-width: 1100px; margin: 0 auto; }
        
        .section-title { text-align: center; margin-bottom: 50px; }
        .section-title h2 { font-size: 2rem; color: var(--primary-color); margin-bottom: 10px; text-transform: uppercase; }
        .section-title .line { width: 60px; height: 4px; background: var(--accent-color); margin: 0 auto; border-radius: 2px; }
        
        .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        
        .value-card { 
            text-align: center; padding: 30px; border-radius: 15px; background: #fff; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #eee; transition: 0.3s;
            height: 100%; display: flex; flex-direction: column; align-items: center;
        }
        .value-card:hover { transform: translateY(-5px); border-color: var(--primary-color); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .value-card i { font-size: 2.5rem; color: var(--accent-color); margin-bottom: 20px; }
        .value-card h3 { margin-bottom: 15px; color: #333; font-size: 1.2rem; }
        .value-card p { color: #666; font-size: 0.95rem; }

        /* --- 5. CLB TOÁN & TIẾNG VIỆT (FEATURE BOX) --- */
        .club-box {
            background: white; border-radius: 15px; padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid var(--accent-color);
        }
        .club-list { list-style: none; padding: 0; }
        .club-list li { margin-bottom: 15px; font-size: 1.05rem; display: flex; align-items: start; }
        .club-list li i { color: #28a745; margin-right: 15px; margin-top: 5px; font-size: 1.2rem; }

        /* --- 6. GIÁO VIÊN --- */
        .teacher-section { background: #f0f7ff; padding: 80px 20px; }
        .teacher-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }

        /* Card Cố vấn (Nổi bật) */
        .advisor-card { 
            grid-column: 1 / -1; /* Chiếm toàn bộ chiều ngang nếu có thể */
            display: flex; flex-direction: row; 
            background: #fff; border-radius: 15px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 40px;
            border: 2px solid var(--gold-color);
        }
        .advisor-img { width: 35%; min-height: 300px; }
        .advisor-img img { width: 100%; height: 100%; object-fit: cover; }
        .advisor-info { width: 65%; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        .advisor-badge { background: var(--gold-color); color: #000; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; display: inline-block; margin-bottom: 10px; width: fit-content; }
        
        .accolades { margin-top: 15px; background: #fffbe6; padding: 15px; border-radius: 8px; border-left: 3px solid var(--gold-color); }
        .accolades p { margin: 5px 0; font-size: 0.9rem; color: #555; }
        .accolades i { color: #d48806; margin-right: 5px; }

        /* Card Giáo viên thường */
        .teacher-card { 
            background: white; border-radius: 15px; overflow: hidden; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.06); transition: 0.3s; 
            display: flex; flex-direction: column;
        }
        .teacher-card:hover { transform: translateY(-8px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
        .teacher-img { height: 280px; overflow: hidden; }
        .teacher-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .teacher-card:hover .teacher-img img { transform: scale(1.05); }
        .teacher-info { padding: 20px; text-align: center; flex: 1; display: flex; flex-direction: column; }
        .teacher-name { font-size: 1.2rem; font-weight: bold; color: var(--primary-color); margin-bottom: 5px; }
        .teacher-role { font-size: 0.9rem; color: #777; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; }

        /* --- FOOTER --- */
        footer { background: var(--footer-bg); color: #ccc; padding: 60px 20px 20px; font-size: 0.95rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; }
        .footer-col h4 { color: #fff; margin-bottom: 20px; font-size: 1.2rem; border-left: 3px solid var(--primary-color); padding-left: 10px; }
        .footer-bottom { text-align: center; margin-top: 60px; padding-top: 20px; border-top: 1px solid #444; color: #777; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; }
            .nav-links { flex-direction: column; gap: 10px; width: 100%; margin: 0; }
            .advisor-card { flex-direction: column; }
            .advisor-img { width: 100%; height: 250px; }
            .advisor-info { width: 100%; padding: 20px; }
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
                <a href="tientieuhoc.php" class="active">Tiền tiểu học & Tiểu học</a>
                <a href="luyenthi.php">Luyện thi (6-12)</a>
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

    <div class="page-banner">
        <div class="banner-content">
            <h1>Tiền Tiểu Học & Tiểu Học</h1>
            <p>"Nơi ươm mầm những tài năng nhí - Vững bước vào lớp 1"</p>
        </div>
    </div>

    <div class="section-wrapper">
        <div class="section-container">
            <div class="section-title">
                <h2>Mục tiêu khóa học Tiền Tiểu Học</h2>
                <div class="line"></div>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-smile-beam"></i>
                    <h3>Tâm lý vững vàng</h3>
                    <p>Giúp con <strong>TỰ TIN</strong>, hào hứng bước vào môi trường mới. Xóa bỏ nỗi sợ khi chuyển cấp.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-book-open"></i>
                    <h3>Tiếng Việt</h3>
                    <p>Làm quen <strong>Âm - Vần</strong>. Đọc thông viết thạo, chuẩn bị nền tảng ngôn ngữ vững chắc.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-calculator"></i>
                    <h3>Toán học</h3>
                    <p>Làm quen con số, phép tính cơ bản và phát triển <strong>tư duy logic</strong> ngay từ nhỏ.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-chair"></i>
                    <h3>Kỹ năng nề nếp</h3>
                    <p>Rèn tư thế ngồi chuẩn, cách cầm bút đúng (chống gù, cận). Rèn tính kỷ luật trong giờ học.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section-wrapper bg-light">
        <div class="section-container">
            <div class="section-title">
                <h2>Mục tiêu khóa học Luyện Chữ Đẹp</h2>
                <div class="line"></div>
                <p style="margin-top:10px; color:#666;">"Nét chữ - Nết người"</p>
            </div>
            <div class="values-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                <div class="value-card">
                    <i class="fas fa-user-check"></i>
                    <h3>Tư thế chuẩn</h3>
                    <p>Rèn tư thế ngồi khoa học, cách cầm bút đúng chuẩn để bảo vệ cột sống và mắt cho trẻ.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-pen-fancy"></i>
                    <h3>Viết chữ chuẩn & đẹp</h3>
                    <p>Viết đúng kĩ thuật, viết đẹp theo cỡ chữ chuẩn của Bộ Giáo dục.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>Tốc độ & Trình bày</h3>
                    <p>Tăng tốc độ viết nhưng vẫn giữ nét thanh nét đậm. Kỹ năng trình bày vở sạch đẹp, khoa học.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="section-wrapper">
        <div class="section-container">
            <div class="section-title">
                <h2>CLB Yêu Toán & Tiếng Việt Tiểu Học</h2>
                <div class="line"></div>
            </div>
            
            <div class="club-box">
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-calculator"></i> Toán Học & Tư Duy</h3>
                        <ul class="club-list">
                            <li><i class="fas fa-check-circle"></i> Rèn kĩ năng tính toán nhanh, chính xác.</li>
                            <li><i class="fas fa-check-circle"></i> Phát triển tư duy logic, kỹ năng giải quyết vấn đề.</li>
                            <li><i class="fas fa-check-circle"></i> Phát triển kĩ năng giao tiếp toán học tự tin.</li>
                        </ul>
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;"><i class="fas fa-feather-alt"></i> Tiếng Việt</h3>
                        <ul class="club-list">
                            <li><i class="fas fa-check-circle"></i> Mở rộng vốn từ vựng phong phú.</li>
                            <li><i class="fas fa-check-circle"></i> Viết câu văn sáng tạo, gãy gọn.</li>
                            <li><i class="fas fa-check-circle"></i> <strong>Phương pháp đặc biệt:</strong> Viết văn bằng công thức siêu dễ hiểu (Hiệu quả cả với HS không thích văn).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="teacher-section">
        <div class="section-title">
            <h2>Đội Ngũ Giáo Viên & Cố Vấn</h2>
            <div class="line"></div>
            <p style="margin-top: 10px; color: #666;">Kinh nghiệm dày dặn, tận tâm và yêu trẻ</p>
        </div>

        <div class="teacher-grid">
            
            <div class="advisor-card">
                <div class="advisor-img">
                    <img src="cohuonggvtieuhoc.png" alt="Cô Hương">
                </div>
                <div class="advisor-info">
                    <div class="advisor-badge">GIÁO VIÊN CỐ VẤN CHUYÊN MÔN</div>
                    <h2 style="color: var(--primary-color); margin-top: 0;">Cô Hương</h2>
                    <p style="font-size: 1.1rem; color: #555;">Giáo viên giàu kinh nghiệm, tận tâm và yêu trẻ. Người truyền lửa đam mê chữ viết và học tập cho các con.</p>
                    
                    <div class="accolades">
                        <p><i class="fas fa-medal"></i> <strong>Giải Nhất</strong> Hội thi Viết chữ đẹp ĐH Sư Phạm Hà Nội.</p>
                        <p><i class="fas fa-medal"></i> <strong>Giải Nhất</strong> Hội thi Viết chữ đẹp cấp Huyện.</p>
                        <p><i class="fas fa-star"></i> <strong>Giải Đặc biệt</strong> Hội thi Giáo viên dạy giỏi cấp huyện.</p>
                    </div>
                </div>
            </div>

            <div class="teacher-card">
                <div class="teacher-img">
                    <img src="#" alt="Cô Mai">
                </div>
                <div class="teacher-info">
                    <div class="teacher-name">Chị Trần Tuyết Mai</div>
                    <div class="teacher-role">Trợ giảng Toán Tư Duy</div>
                    <p>Tốt nghiệp khoa toán trường ĐH Sư Phạm Hà Nội, có 3 năm kinh nghiệm giảng dạy Toán cho học sinh tiểu học.</p>
                </div>
            </div>

            <div class="teacher-card">
                <div class="teacher-img">
                    <img src="#" alt="Cô Hạnh">
                </div>
                <div class="teacher-info">
                    <div class="teacher-name">Chị Lê Thu Hạnh</div>
                    <div class="teacher-role">Trợ giảng Tiếng Việt</div>
                    <p>Sinh viên năm 3 trường ĐH Khoa học xã hội và Nhân văn- ĐHQG Hà Nội, từng là học sinh chuyên văn của trường THPT chuyên Hùng Vương-Phú Thọ</p>
                </div>
            </div>
            
             <div class="teacher-card">
                <div class="teacher-img">
                    <img src="#" alt="Cô Thảo">
                </div>
                <div class="teacher-info">
                    <div class="teacher-name">Chị Phạm Thanh Thảo</div>
                    <div class="teacher-role">Trợ giảng & Quản lý lớp</div>
                </div>
            </div>

        </div>
    </section>

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