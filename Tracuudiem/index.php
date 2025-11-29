<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: gioithieu.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lop_id_dang_xem = $_SESSION['selected_lop_id'] ?? null;
$lop_ten_dang_xem = $_SESSION['selected_lop_ten'] ?? null;

$student_khoi = null; 
if ($lop_ten_dang_xem) {
    $parts = explode('-', $lop_ten_dang_xem);
    if (is_numeric($parts[0])) {
        $student_khoi = (int)$parts[0]; 
    }
}

$sidebar_items = [];
$all_my_classes = []; 

try {
    require_once 'db_config.php';
    $stmt_all_classes = $conn->prepare("
        SELECT lh.id, lh.ten_lop 
        FROM user_lop ul
        JOIN lop_hoc lh ON ul.lop_hoc_id = lh.id
        WHERE ul.user_id = ?
        ORDER BY lh.ten_lop
    ");
    $stmt_all_classes->execute([$user_id]);
    $all_my_classes = $stmt_all_classes->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($all_my_classes)) {
        $_SESSION['enrolled_classes_list'] = $all_my_classes;
    }
    if ($lop_id_dang_xem) {
        $sql_sidebar = "SELECT DISTINCT
                sc.id, sc.title
            FROM 
                sidebar_content sc
            JOIN 
                lesson_class_access lca ON sc.id = lca.lesson_id
            WHERE 
                lca.lop_id = ?
            ORDER BY 
                sc.title ASC;
        ";
        $stmt_sidebar = $conn->prepare($sql_sidebar);
        $stmt_sidebar->execute([$lop_id_dang_xem]); 
        $sidebar_items = $stmt_sidebar->fetchAll(PDO::FETCH_ASSOC);
    }
    $conn = null;
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Hệ thống</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-page-blue">
    
    <header class="header">
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
            <?php if ($lop_id_dang_xem): ?>
                <span style="color: #555; margin-right: 15px; font-weight: 500;">
                    (Lớp: <strong><?php echo htmlspecialchars($lop_ten_dang_xem); ?></strong>
                    <?php if (count($all_my_classes) > 1): ?>
                        <a href="select_class.php" class="btn-change-class">(Đổi lớp)</a>
                    <?php endif; ?>
                    )
                </span>
            <?php endif; ?>
            <a href="change_password.php"><button class="btn-header-secondary">Đổi mật khẩu</button></a>
            <a href="logout.php"><button class="btn-header-logout">Đăng xuất</button></a>
        </div>
    </header>

    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <h3>Nội dung học tập</h3>
            <ul>
                <?php foreach ($sidebar_items as $item): ?>
                    <li>
                        <i class="fas fa-file-alt"></i>
                        <a href="lesson.php?id=<?php echo $item['id']; ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($sidebar_items) && $lop_id_dang_xem): ?>
                    <li>Chưa có nội dung nào cho lớp này.</li>
                <?php endif; ?>
                <?php if (!$lop_id_dang_xem): ?>
                     <li>Bạn chưa chọn lớp học.</li>
                <?php endif; ?>
            </ul>
        </aside>

        <main class="main-content">
            
            <div class="lookup-widget">
                <h2>Tra cứu nhanh</h2>
                <form class="search-box" action="gioithieu.php" method="GET" target="_blank"> 
                   <button type="submit" class="submit-btn-alt">Tra cứu </button>
                   <small style="color: var(--text-color-light); margin-left: 10px;">(Hệ thống sẽ mở trong tab mới)</small>
                </form>
            </div>

            <?php if ($student_khoi): ?>
                <div class="countdown-widget" data-khoi="<?php echo $student_khoi; ?>">
                    <h2>Kỳ thi THPT Quốc Gia</h2>
                    <p class="countdown-date-label">
                        <?php
                        if ($student_khoi == 12) {
                            echo "Dự kiến: 29/06/2026";
                        } elseif ($student_khoi == 11) {
                            echo "Dự kiến: 29/06/2027";
                        } elseif ($student_khoi == 10) {
                            echo "Dự kiến: 29/06/2028";
                        }
                        ?>
                    </p>
                    <div id="countdown-timer">
                        <div class="countdown-item">
                            <span id="days">0</span>
                            <span class="label">ngày</span>
                        </div>
                </div>
            <?php endif; ?> 
        </main>
    </div>
    
    <script src="admin_main.js"></script> </body>
</html>