<?php
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mat_khau = $_POST['mat_khau'] ?? '';
    $vai_tro = $_POST['vai_tro'] ?? 'hoc_vien';

    // Chỉ cho phép đăng ký vai trò hoc_vien hoặc giang_vien
    if (!in_array($vai_tro, ['hoc_vien', 'giang_vien'])) {
        $vai_tro = 'hoc_vien';
    }

    if (strlen($mat_khau) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // Kiểm tra email trùng
        $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email này đã được sử dụng!";
        } else {
            $hash = password_hash($mat_khau, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO nguoi_dung (ho_ten, email, mat_khau, vai_tro) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$ho_ten, $email, $hash, $vai_tro]);

            header("Location: login.php?registered=1");
            exit();
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h4 class="fw-bold text-center text-primary mb-4">Đăng ký Tài khoản</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên</label>
                        <input type="text" name="ho_ten" class="form-control" required placeholder="Nguyễn Văn A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" required placeholder="email@gmail.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu (tối thiểu 6 ký tự)</label>
                        <input type="password" name="mat_khau" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bạn đăng ký với tư cách</label>
                        <select name="vai_tro" class="form-select">
                            <option value="hoc_vien">Học viên</option>
                            <option value="giang_vien">Giảng viên</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Đăng ký ngay</button>
                </form>

                <div class="text-center mt-3">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>