<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}

if (!isset($_GET['id'])) {
    die("Thiếu ID bài học.");
}
$lesson_id = $_GET['id'];

$all_classes = [];
$lesson = null;
$current_class_ids = []; 

try {
    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_lesson = $conn->prepare("SELECT * FROM sidebar_content WHERE id = ?");
    $stmt_lesson->execute([$lesson_id]);
    $lesson = $stmt_lesson->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) { die("Bài học không tồn tại."); }

    $stmt_classes = $conn->prepare("SELECT * FROM lop_hoc ORDER BY ten_lop ASC");
    $stmt_classes->execute();
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    $stmt_current = $conn->prepare("SELECT lop_id FROM lesson_class_access WHERE lesson_id = ?");
    $stmt_current->execute([$lesson_id]);
    $rows = $stmt_current->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $current_class_ids[] = $row['lop_id'];
    }

} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa bài học: <?php echo htmlspecialchars($lesson['title']); ?></title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-item {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
            color: var(--text-color);
        }
        .checkbox-item:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }
        .checkbox-item input {
            margin-right: 8px;
            accent-color: var(--primary-color);
            transform: scale(1.2);
        }
    </style>
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

            <a href="admin.php"><button id="login-btn">Trang Quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>
    
    <main class="container">
        <h1>Sửa bài học: <?php echo htmlspecialchars($lesson['title']); ?></h1>
        <p><a href="admin.php" class="back-link">← Quay lại trang Quản trị</a></p>

        <form class="form-edit" action="handle_update_lesson.php" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">

            <label>Tiêu đề bài học:</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
            
            <label>Tên giáo viên:</label>
            <input type="text" name="teacher_name" value="<?php echo htmlspecialchars($lesson['teacher_name']); ?>">

            <label>Link Video (YouTube Embed):</label>
            <input type="text" name="video_url" value="<?php echo htmlspecialchars($lesson['video_url']); ?>">

            <label>Mô tả khóa học:</label>
            <textarea class="comment-box" name="description" rows="5"><?php echo htmlspecialchars($lesson['description']); ?></textarea>
            
            <?php if (!empty($lesson['outline'])): ?>
                <p style="margin-top: 10px;">
                    <strong>Đề cương hiện tại:</strong> 
                    <a href="<?php echo htmlspecialchars($lesson['outline']); ?>" target="_blank" style="color: var(--primary-color);">Xem file</a>
                </p>
            <?php endif; ?>

            <label style="margin-top: 20px; display: block;">Dành cho các lớp (chọn một hoặc nhiều):</label>
            <div class="checkbox-group">
                <?php foreach ($all_classes as $class): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="lop_ids[]" value="<?php echo $class['id']; ?>"
                               <?php if (in_array($class['id'], $current_class_ids)) echo "checked"; ?>>
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="submit-btn">Cập nhật bài học</button>
        </form>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>