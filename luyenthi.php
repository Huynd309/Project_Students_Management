<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Luyện thi Chất lượng cao (6-12) - Nhất Đạo Edu</title>
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
        .nav-links a { text-decoration: none; color: #555; margin-left: 20px; font-weight: 500; transition: color 0.3s; font-size: 0.95rem; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-color); }

        .auth-box { margin-left: 20px; display: flex; gap: 10px; align-items: center; }
        .btn-auth { padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; font-weight: bold; border: none; cursor: pointer; transition: 0.3s; }
        .btn-login { background: #eee; color: #333; }
        .btn-admin { background: var(--primary-color); color: white; }
        .btn-logout { background: #dc3545; color: white; }

        .page-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            height: 350px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 20px;
        }
        .page-banner h1 { font-size: 2.5rem; margin-bottom: 10px; text-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .page-banner p { font-size: 1.2rem; opacity: 0.9; max-width: 700px; }

        .intro-section { padding: 60px 20px; max-width: 1000px; margin: 0 auto; text-align: center; }
        .intro-section h2 { color: var(--primary-color); margin-bottom: 20px; font-size: 2rem; }
        .intro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px; text-align: left; }
        .intro-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .intro-card i { color: var(--primary-color); font-size: 2rem; margin-bottom: 15px; }
        .intro-card h3 { margin-bottom: 10px; }

        .teacher-section { background: #fff; padding: 80px 20px; }
        .section-container { max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 { font-size: 2.2rem; color: #333; margin-bottom: 10px; }
        .section-title .line { width: 60px; height: 4px; background: var(--primary-color); margin: 0 auto; }

        .teacher-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
            gap: 40px; 
        }

        .teacher-card { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
            transition: transform 0.3s; 
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        .teacher-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

        .teacher-img { height: 300px; overflow: hidden; position: relative; }
        .teacher-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .teacher-card:hover .teacher-img img { transform: scale(1.05); }

        .teacher-info { padding: 25px; text-align: center; flex: 1; display: flex; flex-direction: column; }
        .teacher-name { font-size: 1.3rem; font-weight: bold; color: #222; margin-bottom: 5px; }
        .teacher-subject { color: var(--primary-color); font-weight: bold; font-size: 0.9rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
        .teacher-bio { font-size: 0.95rem; color: #666; margin-bottom: 20px; flex: 1; }
        
        .achievements { list-style: none; padding: 0; text-align: left; font-size: 0.9rem; color: #555; background: #f8f9fa; padding: 15px; border-radius: 6px; }
        .achievements li { margin-bottom: 5px; display: flex; align-items: start; }
        .achievements i { color: #28a745; margin-right: 8px; margin-top: 4px; }

        /* --- 6. FOOTER --- */
        footer { background: var(--footer-bg); color: #ccc; padding: 60px 20px 20px; font-size: 0.95rem; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; }
        .footer-col h4 { color: #fff; margin-bottom: 20px; font-size: 1.2rem; border-left: 3px solid var(--primary-color); padding-left: 10px; }
        .footer-bottom { text-align: center; margin-top: 60px; padding-top: 20px; border-top: 1px solid #444; color: #777; }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; }
            .nav-links { flex-direction: column; gap: 10px; margin-left: 0; width: 100%; }
            .nav-links a { margin: 0; }
            .auth-box { margin-top: 10px; }
            .page-banner { height: 250px; }
            .page-banner h1 { font-size: 1.8rem; }
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
                <a href="luyenthi.php" class="active">Luyện thi (6-12)</a> 
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
        <div>
            <h1>Hệ thống Luyện thi (Lớp 6 - Lớp 12)</h1>
            <p>Ôn thi vào 10 - Luyện thi Đại học Top đầu - Bứt phá điểm số</p>
        </div>
    </div>

    <section class="intro-section">
        <h2>Về Hệ thống Luyện thi Nhất Đạo</h2>
        <p>Với hơn 10 năm kinh nghiệm trong lĩnh vực giáo dục, chúng tôi tự hào là đơn vị tiên phong áp dụng phương pháp cá nhân hóa, giúp học sinh không chỉ nắm vững kiến thức SGK mà còn phát triển tư duy giải quyết vấn đề.</p>
        
        <div class="intro-grid">
            <div class="intro-card">
                <i class="fas fa-bullseye"></i>
                <h3>Mục tiêu rõ ràng</h3>
                <p>Cam kết đầu ra cho từng khóa học. Lộ trình ôn thi bài bản từ mất gốc đến nâng cao.</p>
            </div>
            <div class="intro-card">
                <i class="fas fa-users"></i>
                <h3>Lớp học tối ưu</h3>
                <p>Sĩ số lớp nhỏ (10-15 HS) để giáo viên có thể quan tâm sát sao từng em.</p>
            </div>
        </div>
    </section>

    <section class="teacher-section">
        <div class="section-container">
            <div class="section-title">
                <h2>Đội Ngũ Giáo Viên Trung Tâm</h2>
                <div class="line"></div>
                <p style="margin-top: 15px; color: #666;">Giàu kinh nghiệm - Tận tâm - Phương pháp hiện đại</p>
            </div>

            <div class="teacher-grid">
                
                <div class="teacher-card">
                    <div class="teacher-img">
                        <img src="imgThai.png" alt="GV 1">
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name">Cô Nguyễn Thị Hồng Thái</div>
                        <div class="teacher-subject">Môn Toán Cấp 2</div>
                        <div class="teacher-bio">Với 3 năm kinh nghiệm luyện thi vào 10. Phong cách giảng dạy nghiêm khắc nhưng dễ hiểu.</div>
                        <ul class="achievements">
                            <li><i class="fas fa-star"></i> Tốt nghiệp xuất sắc ngành Toán Học- ĐH Sư Phạm HN 2</li>
                            <li><i class="fas fa-star"></i> Thạc sĩ Toán Giải Tích ĐH Sư Phạm HN</li>
                            <li><i class="fas fa-star"></i> Giải nhất kì thi "Olympic Toán sinh viên" cấp trường năm học 2024-2025</li>
                        </ul>
                    </div>
                </div>

                <div class="teacher-card">
                    <div class="teacher-img">
                        <img src="imgHoan.png" alt="GV 2">
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name">Thầy Trương Việt Hoàn</div>
                        <div class="teacher-subject">Môn Vật Lý</div>
                        <div class="teacher-bio">Chuyên gia gỡ rối các bài tập Lý khó. Phương pháp "Sơ đồ tư duy" giúp học sinh nhớ công thức siêu tốc.</div>
                        <ul class="achievements">
                            <li><i class="fas fa-star"></i> Cử nhân Đại Học Bách Khoa Hà Nội</li>
                            <li><i class="fas fa-star"></i> Nghiên cứu sinh chuyên ngành Vật Lí lượng tử</li>
                            <li><i class="fas fa-star"></i> Tác giả nhiều bài báo khoa học trong nước và quốc tế</li>
                            <li><i class="fas fa-star"></i> Được NASA mời về nghiên cứu chế tạo tên lửa đạn đạo xuyên lục địa trong 2 năm</li>
                        </ul>
                    </div>
                </div>

                <div class="teacher-card">
                    <div class="teacher-img">
                        <img src="imgDao.png" alt="GV 3">
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name">Thầy Ngô Duy Nhất Đạo</div>
                        <div class="teacher-subject">Môn Toán Cấp 3</div>
                        <div class="teacher-bio">Giúp học sinh mất gốc lấy lại căn bản chỉ sau 3 tháng. Luyện thi cam kết đầu ra 8+.</div>
                        <ul class="achievements">
                            <li><i class="fas fa-star"></i> Tốt nghiệp Đại Học Bách Khoa Hà Nội</li>
                            <li><i class="fas fa-star"></i> Thủ khoa lớp A1 trường THPT Cổ Loa năm 2011</li>
                            <li><i class="fas fa-star"></i> Có 10 năm kinh nghiệm luyện thi vào 10 sở Hà Nội</li>
                        </ul>
                    </div>
                </div>

                <div class="teacher-card">
                    <div class="teacher-img">
                        <img src="imgManh.png" alt="GV 4">
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name">Anh Chu Đức Mạnh</div>
                        <div class="teacher-subject">Trợ Giảng môn Toán</div>
                        <div class="teacher-bio">Biến những công thức toán học khô khan thành những kiến thức thú vị.</div>
                        <ul class="achievements">
                            <li><i class="fas fa-star"></i> Sinh viên học viện Bưu Chính Viễn Thông</li>
                            <li><i class="fas fa-star"></i> Giải nhất cuộc thi Hackrathon 2 lần liên tiếp</li>
                            <li><i class="fas fa-star"></i> Nằm trong Top 10 đội đứng đầu ICCP Asia</li>
                        </ul>
                    </div>
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