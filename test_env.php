<?php
echo "<h1>Kiểm tra Biến môi trường</h1>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>Kiểm tra getenv:</h2>";
echo "DB_HOST: " . getenv('DB_HOST');
?>