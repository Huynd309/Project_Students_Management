<?php
session_start(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['password']; 

    require_once 'db_config.php';

    try {

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        //Debug 
        if (!$user) {
        die("Lỗi: Không tìm thấy user <strong>" . htmlspecialchars($username) . "</strong> trong Database trên Render.");
    }

        echo "<h3>Debug Thông Tin:</h3>";
        echo "User nhập vào: " . htmlspecialchars($username) . "<br>";
        echo "Pass nhập vào: " . htmlspecialchars($password) . "<br>";
        echo "Hash trong DB: " . htmlspecialchars($user['password_hash']) . "<br>";

        $check = password_verify($password, $user['password_hash']);
        echo "Kết quả so khớp: " . ($check ? "TRÙNG KHỚP (True)" : "KHÔNG KHỚP (False)");
        die();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)$user['is_admin']; 
            $_SESSION['role'] = $user['role']; 

            if ($_SESSION['is_admin'] === true) {
                $_SESSION['ho_ten'] = $_SESSION['username'];
            } else {
                $sbd = $_SESSION['username'];
                $sql_name = "SELECT ho_ten FROM diem_hoc_sinh WHERE so_bao_danh ILIKE ?";
                $stmt_name = $conn->prepare($sql_name);
                $stmt_name->execute([$sbd]);
                $student_info = $stmt_name->fetch(PDO::FETCH_ASSOC);

                if ($student_info && !empty($student_info['ho_ten'])) {
                    $_SESSION['ho_ten'] = $student_info['ho_ten'];
                } else {
                    $_SESSION['ho_ten'] = $_SESSION['username'];
                }
            }

            if($_SESSION['is_admin']) {
                header('Location: admin.php');
                exit;
            } else {
                $sql_classes = "
                SELECT lh.id, lh.ten_lop
                FROM user_lop ul
                JOIN lop_hoc lh ON ul.lop_hoc_id = lh.id
                WHERE ul.user_id = ?
                ";
                $stmt_classes = $conn->prepare($sql_classes);
                $stmt_classes->execute([$_SESSION['user_id']]);
                $enrolled_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
                
                $class_count = count($enrolled_classes);
                
                if ($class_count == 0) {
                    $_SESSION['selected_lop_id'] = null; 
                    header('Location: index.php');
                    exit;
                } elseif ($class_count == 1) {
                    $_SESSION['selected_lop_id'] = $enrolled_classes[0]['id'];
                    $_SESSION['selected_lop_ten'] = $enrolled_classes[0]['ten_lop'];
                    header('Location: index.php');
                    exit;
                } else {
                    $_SESSION['enrolled_classes_list'] = $enrolled_classes;
                    header('Location: select_class.php');
                    exit;
                }
            }
        } else {
            header('Location: login.php?error=1');
            exit;
        }

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
}
?>