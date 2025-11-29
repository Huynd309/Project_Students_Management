<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { die("TRUY CẬP BỊ TỪ CHỐI."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id = $_POST['id'];
    $title = $_POST['title'];
    $teacher_name = $_POST['teacher_name'];
    $video_url = $_POST['video_url'];
    $description = $_POST['description'];
    
    $lop_ids = isset($_POST['lop_ids']) ? $_POST['lop_ids'] : [];

    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->beginTransaction(); 

        $sql_update = "UPDATE sidebar_content 
                       SET title = ?, teacher_name = ?, video_url = ?, description = ?
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([$title, $teacher_name, $video_url, $description, $id]);

        $sql_delete_access = "DELETE FROM lesson_class_access WHERE lesson_id = ?";
        $stmt_delete_access = $conn->prepare($sql_delete_access);
        $stmt_delete_access->execute([$id]);

        $sql_insert_access = "INSERT INTO lesson_class_access (lesson_id, lop_id) VALUES (?, ?)";
        $stmt_insert_access = $conn->prepare($sql_insert_access);
        
        foreach ($lop_ids as $lop_id) {
            $stmt_insert_access->execute([$id, $lop_id]);
        }

        $conn->commit(); 

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Lỗi: " . $e->getMessage());
    }
    
    header('Location: admin.php');
    exit;
}
?>