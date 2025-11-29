<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: gioithieu.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("Không tìm thấy bài học.");
}
$lesson_id = $_GET['id'];

$host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
$user_db = 'postgres'; $password_db = 'Ngohuy3092005';
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
$conn = new PDO($dsn, $user_db, $password_db);

$stmt = $conn->prepare("SELECT * FROM sidebar_content WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die("Bài học không tồn tại.");
}

$video_embed_url = $lesson['video_url'];

$conn = null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($lesson['title']); ?></title>
    <link rel="stylesheet" href="style.css">
    </head>
<body class="admin-page-blue"> <header class="header">
        <div class="auth-buttons">
            <?php if (isset($_SESSION['ho_ten'])): ?>
                <span style="color: #333; margin-right: 15px;">
                    Chào, <strong><?php echo htmlspecialchars($_SESSION['ho_ten']); ?></strong>!
                </span>
            <?php endif; ?>
            
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>

            <a href="index.php"><button class="btn-header-secondary">Quay lại trang chủ</button></a>
            <a href="change_password.php"><button class="btn-header-secondary">Đổi mật khẩu</button></a>
            <a href="logout.php"><button class="btn-header-logout">Đăng xuất</button></a>
        </div>
    </header>
    
    <main class="container">
        
        <h1><?php echo htmlspecialchars($lesson['title']); ?></h1>
        <p class="lesson-teacher">Giáo viên: <?php echo htmlspecialchars($lesson['teacher_name']); ?></p>

        <?php if ($video_embed_url && str_contains($video_embed_url, 'embed')): ?>
            <div class="video-player-wrapper">
                <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
                </iframe>
            </div>
        <?php else: ?>
            <div class="error-msg">Link video không hợp lệ. (Phải là link 'embed' từ YouTube).</div>
        <?php endif; ?>

        <div class="lesson-content">
            <h2>Mô tả bài học</h2>
            <p><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
            
            <hr> <h2>Đề cương bài học</h2>       
            <?php if (!empty($lesson['outline'])): ?>
                <a href="<?php echo htmlspecialchars($lesson['outline']); ?>" target="_blank"
                   class="submit-btn" style="width: auto; display: inline-block;">
                   Tải về 
                </a>
            <?php else: ?>
                <p>Chưa có đề cương cho bài học này.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="footer">
        <div class="footer-column">
            <h4><i class="fas fa-building-columns"></i> Về chúng tôi</h4>
            <p><strong>Trường THPT Cổ Loa </strong></p>
            <p>Hệ thống tra cứu điểm và quản lý học tập dành cho học sinh.</p>
        </div>
        
        <div class="footer-column">
            <h4><i class="fas fa-map-marker-alt"></i> Địa chỉ</h4>
            <p>136 đường Xuân Thuỷ, Phường Cầu Giấy, thành phố Hà Nội</p>
        </div>
        
        <div class="footer-column">
            <h4><i class="fas fa-phone"></i> Liên hệ</h4>
            <p><strong>Điện thoại:</strong> 0961223066</p>
            <p><strong>Email:</strong> ngohuy3092005@gmail.com</p>
        </div>
    </footer>
    
    <script src="admin_main.js"></script>
</body>
</html>