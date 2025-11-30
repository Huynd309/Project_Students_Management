<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

$lop_id_filter = $_GET['lop_id'] ?? null;

$all_classes = [];
$student_list = [];
$class_name = "";

try {
    require_once 'db_config.php';
    
    //QUERY 1
    $user_role = $_SESSION['role'] ?? 'admin'; 

    if ($user_role === 'super') {
        $stmt_classes = $conn->prepare("SELECT id, ten_lop FROM lop_hoc ORDER BY ten_lop ASC");
        $stmt_classes->execute();
    } else {
        $stmt_classes = $conn->prepare("
            SELECT lh.id, lh.ten_lop 
            FROM lop_hoc lh
            JOIN admin_lop_access ala ON lh.id = ala.lop_id
            WHERE ala.user_id = ?
            ORDER BY lh.ten_lop ASC
        ");
        $stmt_classes->execute([$_SESSION['user_id']]);
    }
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);


    // Query 2
    if ($lop_id_filter) {
        
        $is_allowed = false;
        if ($user_role === 'super') {
            $is_allowed = true;
        } else {
            foreach ($all_classes as $class) {
                if ($class['id'] == $lop_id_filter) {
                    $is_allowed = true;
                    break;
                }
            }
        }

        if (!$is_allowed) {
            die("<h2 style='color:red; text-align:center; margin-top:50px;'>BẠN KHÔNG CÓ QUYỀN TRUY CẬP LỚP HỌC NÀY!</h2>");
        }

       $sql = "
            SELECT
                dhs.so_bao_danh,
                dhs.ho_ten,
                dhs.truong,
                dhs.sdt_phu_huynh,
                dhs.sdt_hoc_sinh,
                STRING_AGG(lh.ten_lop, ', ') AS danh_sach_lop,
                u.id as user_id
            FROM
                diem_hoc_sinh AS dhs
            /* === CÁC DÒNG BỊ THIẾU ĐÃ ĐƯỢC THÊM VÀO === */
            LEFT JOIN
                users u ON LOWER(dhs.so_bao_danh) = LOWER(u.username)
            LEFT JOIN
                user_lop AS ul ON u.id = ul.user_id
            LEFT JOIN
                lop_hoc AS lh ON ul.lop_hoc_id = lh.id
            /* ========================================== */
            
            WHERE ul.lop_hoc_id = ?
            
            GROUP BY dhs.so_bao_danh, dhs.ho_ten, dhs.truong, dhs.sdt_phu_huynh, dhs.sdt_hoc_sinh, u.id
            ORDER BY dhs.so_bao_danh ASC
        ";
        
        $stmt_list = $conn->prepare($sql);
        $stmt_list->execute([$lop_id_filter]);
        $student_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

        foreach($all_classes as $class) {
            if ($class['id'] == $lop_id_filter) {
                $class_name = $class['ten_lop'];
                break;
            }
        }
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
    <title>Danh sách học sinh</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-form { justify-content: flex-start; }
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

    <main class="container" style="max-width: 1200px;">
        <h2>Danh sách học sinh</h2>
        <p>Chọn một lớp để xem danh sách chi tiết.</p>
        
        <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>

        <form class="filter-form" action="student_list.php" method="GET">
            <div>
                <label for="lop_id">Chọn lớp:</label>
                <select name="lop_id" id="lop_id" required>
                    <option value="">-- Chọn một lớp --</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php if ($class['id'] == $lop_id_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['ten_lop']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Hiển thị</button>
        </form>

        <?php if ($lop_id_filter): ?>
            <h3 style="margin-top: 30px;">
                Danh sách lớp: <?php echo htmlspecialchars($class_name); ?>
                <span style="font-size: 0.8em; font-weight: normal;">(<?php echo count($student_list); ?> học sinh)</span>
            </h3>

            <table>
                <thead>
                    <tr>
                        <th>SBD</th>
                        <th>Họ và tên</th>
                        <th>Trường</th>
                        <th>SĐT Học sinh</th>
                        <th>SĐT Phụ huynh</th>
                        <th>Lớp</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($student_list)): ?>
                        <tr><td colspan="6">Không tìm thấy học sinh nào trong lớp này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($student_list as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['so_bao_danh']); ?></td>
                                <td><?php echo htmlspecialchars($student['ho_ten']); ?></td>
                                <td><?php echo htmlspecialchars($student['truong']); ?></td>
                                <td>
                                    <?php if (!empty($student['sdt_hoc_sinh'])): ?>
                                        <a href="https://zalo.me/<?php echo htmlspecialchars($student['sdt_hoc_sinh']); ?>" 
                                           target="_blank" 
                                           class="btn-zalo">
                                           Chat Zalo
                                        </a>
                                        <br>
                                        <span style="font-size:0.8em; color:var(--text-color-light);">
                                            <?php echo htmlspecialchars($student['sdt_hoc_sinh']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-color-light);">--</span>
                                    <?php endif; ?>
                                <td>
                                    <?php if (!empty($student['sdt_phu_huynh'])): ?>
                                        <a href="https://zalo.me/<?php echo htmlspecialchars($student['sdt_phu_huynh']); ?>" 
                                           target="_blank" 
                                           class="btn-zalo">
                                           Chat Zalo
                                        </a>
                                        <br>
                                        <span style="font-size:0.8em; color:var(--text-color-light);">
                                            <?php echo htmlspecialchars($student['sdt_phu_huynh']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-color-light);">--</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['danh_sach_lop']); ?></td>
                                <td>
                                    <a class="btn-edit" href="edit_student.php?sbd=<?php echo $student['so_bao_danh']; ?>&ref_lop_id=<?php echo $lop_id_filter; ?>">Sửa</a> 
                                    |
                                    <a class="btn-delete" 
                                       href="handle_delete_student.php?sbd=<?php echo $student['so_bao_danh']; ?>&redirect=student_list.php?lop_id=<?php echo $lop_id_filter; ?>" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa học sinh này?');">
                                       Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script src="admin_main.js"></script>
</body>
</html>