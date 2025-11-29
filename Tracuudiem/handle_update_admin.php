<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("TRUY CẬP BỊ TỪ CHỐI!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_POST['admin_id'];
    $new_password = $_POST['new_password'];
    $access_classes = $_POST['access_classes'] ?? [];

    $host = '127.0.0.1'; $port = '5432'; $dbname = 'Student_Information';
    $user_db = 'postgres'; $password_db = 'Ngohuy3092005';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    try {
        $conn = new PDO($dsn, $user_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pwd = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt_pwd->execute([$hash, $admin_id]);
        }

        $stmt_del = $conn->prepare("DELETE FROM admin_lop_access WHERE user_id = ?");
        $stmt_del->execute([$admin_id]);

        if (!empty($access_classes)) {
            $stmt_ins = $conn->prepare("INSERT INTO admin_lop_access (user_id, lop_id) VALUES (?, ?)");
            foreach ($access_classes as $lop_id) {
                $stmt_ins->execute([$admin_id, $lop_id]);
            }
        }

        $conn->commit();
        header("Location: manage_admins.php?msg=update_success");
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Lỗi: " . $e->getMessage());
    }
}
?>