<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $access_classes = $_POST['access_classes'] ?? []; 

    require_once 'db_config.php';

    try {
        $conn->beginTransaction();
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql_user = "INSERT INTO users (username, password_hash, is_admin, role) VALUES (?, ?, true, 'admin') RETURNING id";
        $stmt = $conn->prepare($sql_user);
        $stmt->execute([$username, $hash]);
        $new_admin_id = $stmt->fetchColumn();

        $sql_access = "INSERT INTO admin_lop_access (user_id, lop_id) VALUES (?, ?)";
        $stmt_access = $conn->prepare($sql_access);

        foreach ($access_classes as $lop_id) {
            $stmt_access->execute([$new_admin_id, $lop_id]);
        }

        $conn->commit();
        header("Location: manage_admins.php?msg=success");

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Lỗi: " . $e->getMessage());
    }
}
?>