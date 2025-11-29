<?php 
$host ='127.0.0.1';
$port='5432';
$dbname='Student_Information';
$user='postgres';
$password='Ngohuy3092005';

//Create header for JSON response
header('Content-Type: application/json');

if(!isset($_GET['sbd'])){
    echo json_encode(['error' => 'Vui lòng cung cấp số báo danh']);
    exit;
}
$sbd = $_GET['sbd'];

$dsn="pgsql:host=$host;port=$port;dbname=$dbname";

try{
    //Connect to the database by PDO
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //Prepare and execute the SQL query
    $stmt = $conn->prepare('SELECT so_bao_danh, ho_ten From diem_hoc_sinh WHERE so_bao_danh = ?');
    $stmt->execute([$sbd]);
    //Fetch the result
    $hocSinh = $stmt->fetch(PDO::FETCH_ASSOC);
    if($hocSinh){
        echo json_encode($hocSinh);
    } else {
        echo json_encode(['error' => 'Không tìm thấy học sinh với số báo danh đã cho']);
    }
} catch (PDOException $e){
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
//Close the database connection
$conn = null;
?>
