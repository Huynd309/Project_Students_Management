<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST['title'];
    $teacher_name = $_POST['teacher_name'];
    $video_url = $_POST['video_url']; 
    $description = $_POST['description'];

    $selected_lop_id = $_POST['lop_id'] ?? null; 

    $outline_file_path = ''; 
    if (isset($_FILES['file_upload_outline']) && $_FILES['file_upload_outline']['error'] == 0) {
        
        $upload_dir = __DIR__ . '/uploads/';
        $file_info = $_FILES['file_upload_outline'];
        $original_filename = basename($file_info['name']);
        $new_filename = time() . "_" . str_replace(' ', '_', $original_filename);
        $target_file_path = $upload_dir . $new_filename;

        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        if (!is_writable($upload_dir)) {
             die("LỖI QUYỀN: Máy chủ không có quyền ghi vào thư mục 'uploads'.");
        }

        if (move_uploaded_file($file_info['tmp_name'], $target_file_path)) {
            $outline_file_path = 'uploads/' . $new_filename;
        } else {
            die("Lỗi không xác định khi di chuyển tệp đề cương.");
        }
    }

    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $conn->beginTransaction();

        $sql_lesson = "INSERT INTO sidebar_content (title, teacher_name, video_url, description, outline) 
                       VALUES (?, ?, ?, ?, ?)
                       RETURNING id"; 
                       
        $stmt_lesson = $conn->prepare($sql_lesson);
        $stmt_lesson->execute([$title, $teacher_name, $video_url, $description, $outline_file_path]);
        
        $new_lesson_id = $stmt_lesson->fetchColumn();

        if (!$new_lesson_id) {
            throw new Exception("Không thể tạo bài học mới.");
        }

        if (!empty($selected_lop_id)) {
            
            $sql_access = "INSERT INTO lesson_class_access (lesson_id, lop_id) 
                           VALUES (?, ?)";
            $stmt_access = $conn->prepare($sql_access);

            $stmt_access->execute([$new_lesson_id, $selected_lop_id]);
        }
        
        $conn->commit();

    } catch (PDOException $e) {
        $conn->rollBack(); 
        die("Lỗi: " . $e->getMessage());
    }
    
    header('Location: admin.php?lesson_add=success');
    exit;
}
?>