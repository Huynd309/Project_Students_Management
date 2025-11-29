<?php
session_start();

if (!isset($_SESSION['user_id']) || isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: login.php');
    exit;
}

$enrolled_classes = isset($_SESSION['enrolled_classes_list']) ? $_SESSION['enrolled_classes_list'] : [];

if (empty($enrolled_classes)) {
    header('Location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['lop_id']) && !empty($_POST['lop_id'])) {
        
        $selected_lop_id = $_POST['lop_id'];
        $selected_lop_ten = "Unknown"; 

        $is_valid = false;
        foreach ($enrolled_classes as $class) {
            if ($class['id'] == $selected_lop_id) {
                $is_valid = true;
                $selected_lop_ten = $class['ten_lop'];
                break;
            }
        }

        if ($is_valid) {
            $_SESSION['selected_lop_id'] = $selected_lop_id;
            $_SESSION['selected_lop_ten'] = $selected_lop_ten;
            
            unset($_SESSION['enrolled_classes_list']);
            
            header('Location: index.php');
            exit;
        } else {
            $error = "Lựa chọn không hợp lệ.";
        }
    } else {
        $error = "Vui lòng chọn một lớp.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chọn lớp học</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .selection-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            width: 400px;
        }
        .selection-container select, .selection-container button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            font-size: 1em;
        }
        .selection-container button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="selection-container">
        <h2>Chào, <?php echo htmlspecialchars($_SESSION['ho_ten']); ?>!</h2>
        <p>Bạn đã tham gia vào nhiều lớp học. Vui lòng chọn một lớp để học:</p>
        
        <form action="select_class.php" method="POST">
            <select name="lop_id" required>
                <option value="">-- Chọn một lớp --</option>
                <?php foreach ($enrolled_classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>">
                        <?php echo htmlspecialchars($class['ten_lop']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Vào học</button>
            
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
        </form>
        <div style="margin-top: 0px;">
            <a href="logout.php"><button class="btn-logout">Thoát</button></a>
        </div>
    </div>
</body>
</html>