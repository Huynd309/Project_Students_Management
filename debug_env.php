<?php
echo "<h1>Kiểm tra Môi trường Server</h1>";

// 1. Kiểm tra getenv
$host_getenv = getenv('DB_HOST');
echo "<strong>1. getenv('DB_HOST'):</strong> " . ($host_getenv ? $host_getenv : "<span style='color:red'>Rỗng (Không nhận được)</span>") . "<br>";

// 2. Kiểm tra $_ENV
$host_env = $_ENV['DB_HOST'] ?? null;
echo "<strong>2. \$_ENV['DB_HOST']:</strong> " . ($host_env ? $host_env : "<span style='color:red'>Rỗng (Không nhận được)</span>") . "<br>";

// 3. Kiểm tra $_SERVER
$host_server = $_SERVER['DB_HOST'] ?? null;
echo "<strong>3. \$_SERVER['DB_HOST']:</strong> " . ($host_server ? $host_server : "<span style='color:red'>Rỗng (Không nhận được)</span>") . "<br>";

echo "<hr>";

// 4. Kiểm tra file db_config.php hiện tại đang chứa nội dung gì
echo "<h3>Nội dung file db_config.php trên Server:</h3>";
echo "<pre style='background:#f4f4f4; padding:10px;'>";
echo htmlspecialchars(file_get_contents('db_config.php'));
echo "</pre>";
?>