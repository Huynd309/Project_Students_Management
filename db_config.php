<?php
$database_url = getenv('DATABASE_URL');

if (!$database_url) {
    $host = '127.0.0.1';
    $port = '5432';
    $dbname = 'Student_Information';
    $user = 'postgres';
    $password = 'Ngohuy3092005';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
} else {
    $db = parse_url($database_url);
    
    $host = $db['host'];
    $port = $db['port'];
    $dbname = ltrim($db['path'], '/'); 
    $user = $db['user'];
    $password = $db['pass'];
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
}

try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if ($database_url) {
        echo "<h1>Lỗi Kết Nối Server!</h1>";
        echo "URL nhận được: " . htmlspecialchars($database_url) . "<br>";
        echo "Host giải mã: " . htmlspecialchars($host) . "<br>";
        echo "DB giải mã: " . htmlspecialchars($dbname) . "<br>";
    }
    die("Chi tiết lỗi: " . $e->getMessage());
}
?>