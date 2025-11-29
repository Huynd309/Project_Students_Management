<?php
session_start();
if ( !isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true ) {
   die('Truy cập bị từ chối. Chỉ dành cho quản trị viên.');
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $so_bao_danh = $_POST['so_bao_danh'];
    $ho_ten = $_POST['ho_ten'];
    $truong = $_POST['truong'];
    $sdt_hoc_sinh = $_POST['sdt_hoc_sinh'] ?? null;
    $sdt_phu_huynh = $_POST['sdt_phu_huynh'] ?? null;
    $lop_id = $_POST['lop_id'];

    $default_username = strtolower($so_bao_danh); 
    $default_password = '123';
    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

    $host ='127.0.0.1'; $port='5432'; $dbname='Student_Information';
    $user_db='postgres'; $password_db='Ngohuy3092005';
    $dsn="pgsql:host=$host;port=$port;dbname=$dbname";

    try{
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        $sql_user = "
            INSERT INTO users (username, password_hash, is_admin)
            VALUES (?, ?, false)
            ON CONFLICT (username) DO NOTHING
            RETURNING id
        ";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([$default_username, $password_hash]);

        $new_user_id = $stmt_user->fetchColumn();

        if (!$new_user_id) {
            $sql_find_id = "SELECT id FROM users WHERE username = ?";
            $stmt_find_id = $conn->prepare($sql_find_id);
            $stmt_find_id->execute([$default_username]);
            $new_user_id = $stmt_find_id->fetchColumn();
        }
        if (!$new_user_id) {
            throw new Exception("Không thể tìm thấy hoặc tạo user.");
        }

        $sql_enroll = "INSERT INTO user_lop (user_id, lop_hoc_id) VALUES (?, ?)";
        $stmt_enroll = $conn->prepare($sql_enroll);
        $stmt_enroll->execute([$new_user_id, $lop_id]);

        $sql_student = "
            INSERT INTO diem_hoc_sinh (so_bao_danh, ho_ten, truong, sdt_phu_huynh, sdt_hoc_sinh) 
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (so_bao_danh) 
            DO UPDATE SET 
            ho_ten = EXCLUDED.ho_ten, 
            truong = EXCLUDED.truong,
            sdt_phu_huynh = EXCLUDED.sdt_phu_huynh, 
            sdt_hoc_sinh = EXCLUDED.sdt_hoc_sinh
            ";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->execute([$so_bao_danh, $ho_ten, $truong, $sdt_phu_huynh, $sdt_hoc_sinh]);

        $conn->commit();

        header('Location: admin.php?add=success');
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        
        if ($e->getCode() == '23505') { 
            header('Location: admin.php?error=duplicate_enroll'); 
            exit();
        } else {
             header('Location: admin.php?error=' . urlencode($e->getMessage()));
            exit();
        }
    }
}
?>