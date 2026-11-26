<?php
require_once '../config/db.php';
yeuCauQuyen(['admin']);

$admin_id = $_SESSION['user']['id'];

// Khóa hoặc mở khóa tài khoản
if (isset($_GET['toggle_id'])) {
    $uid = (int)$_GET['toggle_id'];
    if ($uid !== $admin_id) {
        $stmt = $pdo->prepare("UPDATE nguoi_dung SET trang_thai = IF(trang_thai = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$uid]);
    }
    header("Location: quan_ly_user.php?updated=1");
    exit();
}

// Đổi vai trò người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doi_quyen'])) {
    $uid = (int)$_POST['user_id'];
    $vai_tro_moi = $_POST['vai_tro'];
    if ($uid !== $admin_id && in_array($vai_tro_moi, ['admin', 'giang_vien', 'hoc_vien'])) {
        $stmt = $pdo->prepare("UPDATE nguoi_dung SET vai_tro = ? WHERE id = ?");
        $stmt->execute([$vai_tro_moi, $uid]);
    }
    header("Location: quan_ly_user.php?updated=1");
    exit();
}

$users = $pdo->query("SELECT * FROM nguoi_dung ORDER BY ngay_tao DESC")->fetchAll();

include '../includes/header.php';
?>

<h3 class="fw-bold text-primary mb-4"><i class="bi bi-people-fill me-2"></i>Quản lý Tài khoản & Phân quyền</h3>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">Đã cập nhật thông tin tài khoản!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Vai trò (Phân quyền)</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($u['ho_ten']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['id'] === $admin_id): ?>
                                <span class="badge bg-primary">ADMIN (Bạn)</span>
                            <?php else: ?>
                                <form method="POST" class="d-flex gap-1" style="max-width: 220px;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="vai_tro" class="form-select form-select-sm">
                                        <option value="hoc_vien" <?= $u['vai_tro'] === 'hoc_vien' ? 'selected' : '' ?>>Học viên</option>
                                        <option value="giang_vien" <?= $u['vai_tro'] === 'giang_vien' ? 'selected' : '' ?>>Giảng viên</option>
                                        <option value="admin" <?= $u['vai_tro'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="doi_quyen" class="btn btn-sm btn-outline-primary">Lưu</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($u['trang_thai'] == 1): ?>
                                <span class="badge bg-success">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($u['id'] !== $admin_id): ?>
                                <a href="?toggle_id=<?= $u['id'] ?>" class="btn btn-sm <?= $u['trang_thai'] == 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                    <?= $u['trang_thai'] == 1 ? 'Khóa tài khoản' : 'Mở khóa' ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>