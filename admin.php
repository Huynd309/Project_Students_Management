<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("TRUY CẬP BỊ TỪ CHỐI! Bạn không có quyền vào trang này.");
}

$filter_khoi = isset($_GET['khoi']) ? (int)$_GET['khoi'] : null;
$filter_lop_id = isset($_GET['lop_id']) ? (int)$_GET['lop_id'] : null;

$all_students = []; 
$sidebar_items = []; 
$all_classes = [];
$grouped_classes = [];
$all_users = [];
$connection_error = null;

try {
    require_once 'db_config.php';

    //Query 1
    $user_role = $_SESSION['role'] ?? 'admin'; 

    if ($user_role === 'super') {
        $stmt_classes = $conn->prepare("SELECT * FROM lop_hoc ORDER BY khoi, ten_lop ASC");
        $stmt_classes->execute();
    } else {
        $stmt_classes = $conn->prepare("
            SELECT lh.* FROM lop_hoc lh
            JOIN admin_lop_access ala ON lh.id = ala.lop_id
            WHERE ala.user_id = ?
            ORDER BY lh.khoi, lh.ten_lop ASC
        ");
        $stmt_classes->execute([$_SESSION['user_id']]);
    }
    
    $all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

//Query 2
    $sql_sidebar_base = "
    SELECT 
        sc.id, 
        sc.title, 
        sc.teacher_name,
        STRING_AGG(lp.ten_lop, ', ') AS danh_sach_lop
    FROM 
        sidebar_content sc
    LEFT JOIN 
        lesson_class_access lca ON sc.id = lca.lesson_id
    LEFT JOIN 
        lop_hoc lp ON lca.lop_id = lp.id
    ";

    if ($_SESSION['role'] !== 'super') {
        $sql_sidebar_base .= "
            JOIN admin_lop_access ala ON lca.lop_id = ala.lop_id
            WHERE ala.user_id = :user_id
        ";
    }

    $sql_sidebar_base .= "
    GROUP BY 
        sc.id, sc.title, sc.teacher_name
    ORDER BY 
        sc.title ASC;
    ";

    $stmt_sidebar = $conn->prepare($sql_sidebar_base);
    
    if ($_SESSION['role'] !== 'super') {
        $stmt_sidebar->execute(['user_id' => $_SESSION['user_id']]);
    } else {
        $stmt_sidebar->execute();
    }

    $sidebar_items = $stmt_sidebar->fetchAll(PDO::FETCH_ASSOC);

//Query 4
$sql_users = "
    SELECT
        users.id,
        users.username,
        STRING_AGG(lop_hoc.ten_lop, ', ') AS danh_sach_lop_cua_user
    FROM
        users
    LEFT JOIN
        user_lop ON users.id = user_lop.user_id
    LEFT JOIN
        lop_hoc ON user_lop.lop_hoc_id = lop_hoc.id
    WHERE
        users.is_admin = false
    GROUP BY
        users.id, users.username
    ORDER BY
        users.username;
";

$stmt_users = $conn->prepare($sql_users);
$stmt_users->execute();
$all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    $conn = null; 

} catch (PDOException $e) {
    $connection_error = "Lỗi khi lấy danh sách: " . $e->getMessage();
}
$active_khoi = null;

if ($filter_khoi) {
    $active_khoi = $filter_khoi;
} elseif ($filter_lop_id) {
    foreach ($all_classes as $class) {
        if ($class['id'] == $filter_lop_id) {
            $active_khoi = $class['khoi'];
            break; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Quản Trị</title>
    <link rel="stylesheet" href="style.css?v=1">
    <style>
        #lessonTable tbody tr,
        #userTable tbody tr {
            display: none;
        }
    </style>
</head>

<body class ="admin-page-blue">
    <header class="header">
   <div class="auth-buttons">
        <?php if (isset($_SESSION['username'])): ?>
            <span style="color: #333; margin-right: 15px;">
                Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
            </span>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>
            <?php else: ?>
            <a href="login.php"><button id="login-btn">Đăng nhập</button></a>
        <?php endif; ?>
    </div>
    </header>

    <nav class="admin-nav">
            <ul>
                <?php 
                $trang_chu_active = (!$filter_khoi && !$filter_lop_id) ? 'active' : ''; 
                ?>
                <li><a href="admin.php" class="<?php echo $trang_chu_active; ?>">Trang Quản Trị</a></li>

                <?php if ($_SESSION['role'] === 'super'): ?>
                <li><a href="manage_admins.php" style="color: gold;">Phân quyền cho Admin</a></li>
                <?php endif; ?>
                
                <?php foreach ($grouped_classes as $khoi => $classes_in_khoi): ?>
                    <?php
                    $khoi_active = ($active_khoi == $khoi) ? 'active' : '';
                    ?>
                    
                    <li class="nav-item dropdown">
                        
                        <a href="admin.php?khoi=<?php echo $khoi; ?>" class="<?php echo $khoi_active; ?>">
                            Khối <?php echo $khoi; ?>
                        </a>
                        
                        <ul class="dropdown-menu"> 
                            <?php foreach ($classes_in_khoi as $class): ?>
                                <?php
                                $lop_active = ($filter_lop_id == $class['id']) ? 'active' : '';
                                ?>
                                <li>
                                    <a href="admin.php?lop_id=<?php echo $class['id']; ?>" class="<?php echo $lop_active; ?>">
                                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul> </li> <?php endforeach; ?>
            </ul>
        <ul class="admin-nav-right">
            <li class="nav-item dropdown">
                <a href="#">Danh mục</a>
                <ul class="dropdown-menu">
                    <li><a href="gioithieu.php">Tra cứu điểm học sinh</a></li>
                    <li><a href="student_list.php">Danh sách học sinh</a></li>
                    <li><a href="diemdanh.php">Điểm danh học sinh</a></li>
                    <li><a href="daily_report.php">Báo cáo hàng ngày</a></li>
                    <li><a href="monthly_report.php">Báo cáo hàng tháng</a></li>
                    <li><a href="logout.php">Đăng xuất</a></li>
                    </ul>
            </li>
        </ul> </nav>
        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <strong>LỖI:</strong> 
                <?php
                    if ($_GET['error'] == 'duplicate_sbd') {
                        echo 'Số báo danh này đã tồn tại!';
                    } else {
                        echo htmlspecialchars($_GET['error']);
                    }
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['add']) && $_GET['add'] == 'success'): ?>
            <div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <strong>THÀNH CÔNG:</strong> Đã thêm học sinh mới.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['enroll']) && $_GET['enroll'] == 'success'): ?>
            <div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <strong>THÀNH CÔNG:</strong> Đã thêm học sinh vào lớp.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate_enroll'): ?>
             <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <strong>LỖI:</strong> Học sinh này đã ở trong lớp đó rồi!
            </div>
        <?php endif; ?>

        <?php if ($connection_error): ?>
            <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <?php echo $connection_error; ?>
            </div>
        <?php endif; ?>

        <div class="admin-layout-row">
            <div class="admin-column"> 
                <h2>Thêm nội dung bài học</h2>
                    <form class="form-add" action="handle_add_lesson.php" method="POST" enctype="multipart/form-data">
        <label>Tiêu đề bài học:</label>
        <input type="text" name="title" required>
        
        <label>Tên giáo viên:</label>
        <input type="text" name="teacher_name">

        <label>Link Video (YouTube Embed):</label>
        <input type="text" name="video_url" placeholder="https://www.youtube.com/embed/...">
        
        <label>Tải Đề cương bài giảng (PDF/Docx):</label>
        <div id="drop-zone">
                        <p>Kéo & Thả tệp vào đây</p>
                        <p>hoặc</p>
                        <label for="file_upload_input" class="file-upload-button">Chọn tệp</label>
                        <input type="file" name="file_upload_outline" id="file_upload_input">
                        <p id="file-name"></p>
        </div>
        
        <label>Mô tả bài học:</label>
        <textarea name="description" rows="4"></textarea> <br> <hr>
         <label>Bài học này dành cho lớp:</label>
                    <select name="lop_id" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
                        <option value="">-- Chọn một lớp --</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
        <button type="submit" style="background-color: #28a745; color: white;">Thêm bài học</button>      
</form>
</div> <div class="admin-column">
                <h2>Thêm học sinh mới</h2>
                <p style="color: #e74c3c; font-style: italic; font-size: 0.9em; margin-bottom: 15px;">
                <i class="fas fa-exclamation-circle"></i> Chú ý: Số báo danh phải theo quy ước <strong>NDxxyyzz</strong> 
                (trong đó  <strong>xx</strong> lớp học sinh đó học, <strong>yy</strong> năm sinh học sinh đó, <strong>zz</strong> là số thứ tự). <br>
                Ví dụ: Học sinh lớp 12 (sinh năm 2008), học lớp Toán 12 thì sẽ là NDT0801.
                </p>
                <form class="form-add" action="handle_add_student.php" method="POST">
                    <label>Số báo danh:</label>
                    <input type="text" name="so_bao_danh" required>
                    
                    <label>Họ và tên:</label>
                    <input type="text" name="ho_ten" required>
                    
                    <label>Trường:</label>
                    <input type="text" name="truong">

                    <label>SĐT Học sinh (Zalo):</label>
                    <input type="text" name="sdt_hoc_sinh" placeholder="Nhập SĐT học sinh...">

                    <label>SĐT Phụ huynh (Zalo):</label>
                    <input type="text" name="sdt_phu_huynh" placeholder="Nhập SĐT phụ huynh...">
                    
                    <label>Chọn lớp:</label>
                    <select name="lop_id" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
                        <option value="">-- Chọn một lớp --</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['ten_lop']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background-color: green; color: white;">Thêm học sinh</button> <hr style="margin: 20px 0;">
</select>
</form>
        </div> </div> 
        <hr style="margin-top: 40px;">

        <h2>Nội dung bài học đã thêm</h2>
        <div class="filter-container" style="margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
    
    <div>
        <label for="filterKhoi" style="font-weight: bold;">Lọc theo khối:</label>
        <select id="filterKhoi" style="padding: 5px;">
            <option value="">-- Tất cả khối --</option>
            <option value="6">Khối 6</option>
            <option value="9">Khối 9</option>
            <option value="10">Khối 10</option>
            <option value="11">Khối 11</option>
            <option value="12">Khối 12</option>
        </select>
    </div>

    <div>
        <label for="filterMon" style="font-weight: bold;">Lọc theo môn:</label>
        <select id="filterMon" style="padding: 5px;">
            <option value="">-- Tất cả môn --</option>
            <option value="Toán">Toán</option>
            <option value="Lý">Lý</option>
            <option value="Hoá">Hoá</option>
        </select>
    </div>

    <div>
        <button id="filterButton" style="padding: 5px 10px;">Lọc</button>
    </div>

</div>

<table id="lessonTable">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Giáo viên</th> <th>Dành cho lớp</th> <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sidebar_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo htmlspecialchars($item['teacher_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['danh_sach_lop']); ?></td>
                        <td>
                            <a class="btn-edit" href="edit_lesson.php?id=<?php echo $item['id']; ?>">Sửa</a>
                            | <a class="btn-delete" 
                               href="handle_delete_sidebar.php?id=<?php echo $item['id']; ?>"
                               onclick="return confirm('Bạn có chắc muốn xóa mục này?');">
                               Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                 <?php if (empty($sidebar_items)): ?> 
                    <tr><td colspan="4">Chưa có nội dung nào.</td></tr>
                <?php endif; ?>
            </tbody>
</table> <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super'): ?>

            <hr style="margin-top: 40px;">

            <h2>Quản lý tài khoản User (Học sinh & Admin)</h2>
            
            <div class="filter-container" style="margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end;">
                <div>
                    <label for="filterUserKhoi" style="font-weight: bold;">Lọc User theo khối:</label>
                    <select id="filterUserKhoi" style="padding: 5px;">
                        <option value="">-- Tất cả khối --</option>
                        <option value="6">Khối 6</option>
                        <option value="9">Khối 9</option>
                        <option value="10">Khối 10</option>
                        <option value="11">Khối 11</option>
                        <option value="12">Khối 12</option>
                    </select>
                </div>
                <div>
                    <button id="filterUserButton" style="padding: 5px 10px;">Lọc User</button>
                </div>
            </div>

            <table id="userTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Lớp mà user đã đăng kí</th> 
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['danh_sach_lop_cua_user']); ?></td>
                            <td>
                                <a class="btn-delete" 
                                   href="handle_delete_user.php?id=<?php echo $user['id']; ?>" 
                                   onclick="return confirm('CẢNH BÁO: Bạn có chắc muốn xóa tài khoản này?');">
                                   Xóa vĩnh viễn
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($all_users)): ?>
                        <tr><td colspan="3">Không có tài khoản user nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php endif; ?>
        </main>
    
    <script src="admin_main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file_upload_input');
    const fileNameDisplay = document.getElementById('file-name');

    if(dropZone && fileInput && fileNameDisplay){
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
        } else {
            fileNameDisplay.textContent = '';
        }
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    }, false);

    dropZone.addEventListener('drop', (e) => {
        dropZone.classList.remove('dragover');

        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files; 
            fileNameDisplay.textContent = files[0].name; 
        }
    }, false);
    }
});
    </script>
</body>
</html>