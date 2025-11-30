<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lop_id = $_POST['lop_id'];
    $ngay_kiem_tra = $_POST['ngay_kiem_tra'];
    $ten_cot_diem = $_POST['ten_cot_diem'];
} elseif (isset($_GET['lop_id'])) {
    $lop_id = $_GET['lop_id'];
    $ngay_kiem_tra = $_GET['ngay'];
    $ten_cot_diem = $_GET['ten_cot_diem'];
} else {
    die("Thiếu thông tin.");
}

$students_list = [];
$class_info = null;
$existing_scores = [];

require_once 'db_config.php';

try {
    // Query 1
    $stmt_class = $conn->prepare("SELECT ten_lop FROM lop_hoc WHERE id = ?");
    $stmt_class->execute([$lop_id]);
    $class_info = $stmt_class->fetch(PDO::FETCH_ASSOC);

    // Query 2
    $stmt_students = $conn->prepare("
        SELECT dhs.so_bao_danh, dhs.ho_ten
        FROM diem_hoc_sinh AS dhs
        JOIN users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
        JOIN user_lop ul ON u.id = ul.user_id
        WHERE ul.lop_hoc_id = ?
        ORDER BY dhs.ho_ten ASC
    ");
    $stmt_students->execute([$lop_id]);
    $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Query 3
    $stmt_scores = $conn->prepare("
        SELECT so_bao_danh, diem_so, diem_btvn 
        FROM diem_thanh_phan 
        WHERE lop_id = ? AND ngay_kiem_tra = ? AND ten_cot_diem = ?
    ");
    $stmt_scores->execute([$lop_id, $ngay_kiem_tra, $ten_cot_diem]);
    foreach ($stmt_scores->fetchAll(PDO::FETCH_ASSOC) as $score) {
        $key = strtolower($score['so_bao_danh']);
        $existing_scores[$key] = [
            'diem_so' => $score['diem_so'],
            'diem_btvn' => $score['diem_btvn']
        ];
    }
    
    $conn = null;
} catch (PDOException $e) {
    $error = "Lỗi CSDL: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhập điểm: <?php echo htmlspecialchars($ten_cot_diem); ?></title>
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
            <a href="add_scores.php"><button id="login-btn">Chọn bài khác</button></a>
            <a href="admin.php"><button id="login-btn">Quay lại trang quản trị</button></a>
        </div>
    </header>

    <main class="container">
        <h2>Nhập điểm: <?php echo htmlspecialchars($class_info['ten_lop'] ?? ''); ?></h2>
        <h3>Ngày: <?php echo htmlspecialchars($ngay_kiem_tra); ?></h3>
        <h4>Bài kiểm tra: <?php echo htmlspecialchars($ten_cot_diem); ?></h4>

        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($_GET['save']) && $_GET['save'] == 'success') echo "<div class='success-msg'>Đã lưu điểm thành công!</div>"; ?>

        <form action="save_scores_bulk.php" method="POST">
            <input type="hidden" name="lop_id" value="<?php echo htmlspecialchars($lop_id); ?>">
            <input type="hidden" name="ngay_kiem_tra" value="<?php echo htmlspecialchars($ngay_kiem_tra); ?>">
            <input type="hidden" name="ten_cot_diem" value="<?php echo htmlspecialchars($ten_cot_diem); ?>">

            <table>
                <thead>
                    <tr>
                        <th>SBD</th>
                        <th>Họ và tên</th>
                        <th style="width: 150px;">Điểm bài test</th> 
                        <th style="width: 150px;">Điểm BTVN</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students_list)): ?>
                        <tr><td colspan="4">Lớp này chưa có học sinh.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students_list as $student): ?>
                            <?php
                                $sbd_key = strtolower($student['so_bao_danh']);
                                $current_score = $existing_scores[$sbd_key]['diem_so'] ?? '';
                                $current_btvn = $existing_scores[$sbd_key]['diem_btvn'] ?? '';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($student['ho_ten']); ?></td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="10" 
                                           class="score-input" 
                                           name="scores[<?php echo htmlspecialchars($student['so_bao_danh']); ?>]" 
                                           value="<?php echo htmlspecialchars($current_score); ?>">
                                </td>
                                <td>
                                     <input type="number" step="0.01" min="0" max="10" 
                                            class="score-input" 
                                            name="btvn[<?php echo htmlspecialchars($student['so_bao_danh']); ?>]" 
                                            value="<?php echo htmlspecialchars($current_btvn); ?>">
                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students_list)): ?>
                <button type="submit" class="submit-btn">Lưu tất cả điểm</button>
            <?php endif; ?>
        </form>
    </main>
    
    <script src="admin_main.js"></script>
</body>
</html>