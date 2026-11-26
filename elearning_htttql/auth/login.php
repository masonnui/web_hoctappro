<?php
require_once '../config/db.php';

if (isset($_SESSION['user'])) {
    header("Location: /elearning_htttql/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Hỗ trợ mật khẩu mã hóa chuẩn hoặc mật khẩu demo '123456' cho dữ liệu mẫu
    $hop_le = false;
    if ($user) {
        if (password_verify($mat_khau, $user['mat_khau']) || ($mat_khau === '123456' && in_array($email, ['admin@gmail.com', 'giangvien@gmail.com', 'hocvien1@gmail.com', 'hocvien2@gmail.com']))) {
            $hop_le = true;
        }
    }

    if ($hop_le) {
        if ($user['trang_thai'] == 0) {
            $error = "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên!";
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'ho_ten' => $user['ho_ten'],
                'email' => $user['email'],
                'vai_tro' => $user['vai_tro']
            ];

            // Tự động phân luồng theo vai trò
            if ($user['vai_tro'] === 'admin') {
                header("Location: /elearning_htttql/admin/dashboard.php");
            } elseif ($user['vai_tro'] === 'giang_vien') {
                header("Location: /elearning_htttql/giang_vien/khoa_hoc_cua_toi.php");
            } else {
                header("Location: /elearning_htttql/hoc_vien/khoa_hoc_cua_toi.php");
            }
            exit();
        }
    } else {
        $error = "Email hoặc mật khẩu không chính xác!";
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h4 class="fw-bold text-center text-primary mb-4">Đăng nhập Hệ thống</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">Đăng ký thành công! Vui lòng đăng nhập.</div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="nhapemail@gmail.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu</label>
                        <input type="password" name="mat_khau" class="form-control" required placeholder="••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Đăng nhập</button>
                </form>

                <hr>
                <!-- <div class="small text-muted">
                    <strong>Tài khoản demo (Mật khẩu: 123456):</strong><br>
                    • Admin: <code>admin@gmail.com</code><br>
                    • Giảng viên: <code>giangvien@gmail.com</code><br>
                    • Học viên: <code>hocvien1@gmail.com</code>
                </div> -->
                <div class="text-center mt-3">
                    Chưa có tài khoản? <a href="register.php">Đăng ký Học viên</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>