<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'elearning_htttql';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối Cơ sở dữ liệu: " . $e->getMessage());
}

// Hàm kiểm tra phân quyền truy cập trang
function yeuCauQuyen($vai_tro_cho_phep = []) {
    if (!isset($_SESSION['user'])) {
        header("Location: /elearning_htttql/auth/login.php");
        exit();
    }
    if (!empty($vai_tro_cho_phep) && !in_array($_SESSION['user']['vai_tro'], $vai_tro_cho_phep)) {
        die("<div style='padding:20px;font-family:sans-serif;color:red;'>
                <h3>Truy cập bị từ chối!</h3>
                <p>Tài khoản của bạn không có quyền truy cập chức năng này.</p>
                <a href='/elearning_htttql/index.php'>Quay lại Trang chủ</a>
             </div>");
    }
}
?>