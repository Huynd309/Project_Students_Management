<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI.");
}
if (!isset($_GET['sbd'])) {
    die("Thiếu Số báo danh.");
}
$sbd = $_GET['sbd'];
$ref_lop_id = $_GET['ref_lop_id'] ?? null;
$back_url = "student_list.php";
if ($ref_lop_id) {
    $back_url .= "?lop_id=" . $ref_lop_id;
}
$host = '127.0.0.1';
$port = '5432';
$dbname = 'Student_Information';
$user_db = 'postgres';
$password_db = 'Ngohuy3092005';
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
$conn = new PDO($dsn, $user_db, $password_db);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Query 1
$stmt_info = $conn->prepare("SELECT * FROM diem_hoc_sinh WHERE so_bao_danh = ?");
$stmt_info->execute([$sbd]);
$student = $stmt_info->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    die("Không tìm thấy học sinh với SBD này.");
}

// Query 2
$stmt_classes = $conn->prepare("
    SELECT
        lh.id,
        lh.ten_lop,
        u.id as user_id 
    FROM
        users u
    JOIN
        user_lop AS ul ON u.id = ul.user_id
    JOIN
        lop_hoc AS lh ON ul.lop_hoc_id = lh.id
    WHERE
        LOWER(u.username) = LOWER(?)
    ORDER BY
        lh.ten_lop
");
$stmt_classes->execute([$sbd]);
$classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

// Query 3
$stmt_scores = $conn->prepare("SELECT * FROM diem_thanh_phan WHERE so_bao_danh = ? ORDER BY ngay_kiem_tra ASC");
$stmt_scores->execute([$sbd]);
$scores = $stmt_scores->fetchAll(PDO::FETCH_ASSOC);
$conn = null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thông tin: <?php echo htmlspecialchars($student['ho_ten']); ?></title>
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
            <a href="admin.php"><button id="login-btn">Trang Quản trị</button></a>
            <a href="logout.php"><button id="login-btn">Đăng xuất</button></a>
        </div>
    </header>
    
    <main class="container">
        <h1>Sửa thông tin: <?php echo htmlspecialchars($student['ho_ten']); ?></h1>
        <p><a href="<?php echo htmlspecialchars($back_url); ?>" class="back-link">← Quay lại trang danh sách học sinh</a></p>

        <h2>Thông tin chung</h2>
        
        <form class="form-edit" action="handle_update.php" method="POST">
            <input type="hidden" name="so_bao_danh" value="<?php echo htmlspecialchars($student['so_bao_danh']); ?>">
            <input type="hidden" name="ref_lop_id" value="<?php echo htmlspecialchars($ref_lop_id); ?>">
            <label>Họ và tên:</label>
            <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($student['ho_ten']); ?>" required>
            
            <label>Trường:</label>
            <input type="text" name="truong" value="<?php echo htmlspecialchars($student['truong']); ?>">

            <label>SĐT Học sinh (Zalo):</label>
            <input type="text" name="sdt_hoc_sinh" 
            value="<?php echo htmlspecialchars($student['sdt_hoc_sinh'] ?? ''); ?>" 
            placeholder="Nhập SĐT học sinh...">

            <label>SĐT Phụ huynh (Zalo):</label>
            <input type="text" name="sdt_phu_huynh" 
                   value="<?php echo htmlspecialchars($student['sdt_phu_huynh'] ?? ''); ?>" 
                   placeholder="Nhập số điện thoại...">
            
            <button type="submit" class="submit-btn-alt">Cập nhật thông tin</button>
        </form>

        <hr>
        <h2>Các lớp học sinh đang học </h2>
        <p>Để thêm học sinh vào lớp mới, vui lòng dùng form ở trang Quản trị</p>
        
        <table>
            <thead>
                <tr>
                    <th>Tên lớp</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                <tr>
                    <td><?php echo htmlspecialchars($class['ten_lop']); ?></td>
                    <td>
                        <a class="btn-delete" 
                           href="handle_remove_from_class.php?user_id=<?php echo $class['user_id']; ?>&lop_id=<?php echo $class['id']; ?>&sbd_redirect=<?php echo $sbd; ?>"
                           onclick="return confirm('Bạn có chắc muốn XÓA học sinh này khỏi lớp <?php echo htmlspecialchars($class['ten_lop']); ?>?');">
                           Xóa khỏi lớp
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($classes)): ?>
                <tr><td colspan="2">Học sinh chưa được ghi danh vào lớp nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>Quản lý điểm thành phần</h2>
        <form class="form-edit" action="handle_add_score.php" method="POST">
             <input type="hidden" name="so_bao_danh" value="<?php echo htmlspecialchars($student['so_bao_danh']); ?>">
            
            <label>Chọn lớp cho điểm này:</label>
            <select name="lop_id" required>
                <option value="">-- Chọn lớp --</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>">
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Tên cột điểm:</label>
            <input type="text" name="ten_cot_diem" placeholder="Ví dụ: KT 15 phút (Lý)" required>
            
            <label>Điểm số:</label>
            <input type="number" step="0.01" name="diem_so" required>
            
            <label>Ngày kiểm tra:</label>
            <input type="date" name="ngay_kiem_tra" required>
            
            <button type="submit" class="submit-btn">Thêm điểm</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Tên cột điểm</th>
                    <th>Điểm số</th>
                    <th>Ngày KT</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scores as $score): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($score['ten_cot_diem']); ?></td>
                        <td><?php echo htmlspecialchars($score['diem_so']); ?></td>
                        <td><?php echo htmlspecialchars($score['ngay_kiem_tra']); ?></td>
                        <td>
                            <a class="btn-delete" 
                               href="handle_delete_score.php?id=<?php echo $score['id']; ?>&sbd=<?php echo $student['so_bao_danh']; ?>" 
                               onclick="return confirm('Bạn có chắc muốn xóa điểm này?');">
                               Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($scores)): ?>
                    <tr><td colspan="4">Chưa có đầu điểm nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>