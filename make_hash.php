<?php

$password = "123"; 
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Hash tạo thành công</h2>";
echo "<b>Mật khẩu gốc:</b> $password<br>";
echo "<b>Hash:</b> $hash<br><br>";

echo "<h3>Sử dụng hash này:</h3>";
echo "<pre>$hash</pre>";
