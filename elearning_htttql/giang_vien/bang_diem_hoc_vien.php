<?php
require_once '../config/db.php';
yeuCauQuyen(['giang_vien']);

$gv_id = $_SESSION['user']['id'];
$khoa_hoc_id = (int)($_GET['khoa_hoc_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM khoa_hoc WHERE id = ? AND giang_vien_id = ?");
$stmt->execute([$khoa_hoc_id, $gv_id]);
$khoa_hoc = $stmt->fetch();

if (!$khoa_hoc) {
    die("<div class='container mt-4 alert alert-danger'>Không tìm thấy khóa học!</div>");
}

$sql = "SELECT nd.ho_ten, nd.email, dk.ngay_dang_ky, dk.phan_tram_tien_do,
        (SELECT MAX(diem_so) FROM ket_qua_thi WHERE hoc_vien_id = nd.id AND khoa_hoc_id = ?) AS diem_cao_nhat,
        (SELECT COUNT(*) FROM ket_qua_thi WHERE hoc_vien_id = nd.id AND khoa_hoc_id = ?) AS so_lan_thi
        FROM dang_ky_hoc dk
        JOIN nguoi_dung nd ON dk.hoc_vien_id = nd.id
        WHERE dk.khoa_hoc_id = ?
        ORDER BY dk.ngay_dang_ky DESC";

$stmt_hv = $pdo->prepare($sql);
$stmt_hv->execute([$khoa_hoc_id, $khoa_hoc_id, $khoa_hoc_id]);
$danh_sach = $stmt_hv->fetchAll();

include '../includes/header.php';
?>

<div class="mb-3">
    <a href="khoa_hoc_cua_toi.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Quay lại danh sách khóa học</a>
    <h4 class="fw-bold text-primary mt-1">Bảng điểm & Tiến độ Lớp: <?= htmlspecialchars($khoa_hoc['tieu_de']) ?></h4>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Họ tên học viên</th>
                    <th>Email</th>
                    <th>Ngày đăng ký</th>
                    <th style="width: 220px;">Tiến độ học bài</th>
                    <th class="text-center">Số lần thi</th>
                    <th class="text-center">Điểm cao nhất</th>
                    <th class="text-center">Xếp loại</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danh_sach)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có học viên nào đăng ký khóa học này.</td></tr>
                <?php else: ?>
                    <?php foreach ($danh_sach as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($row['ho_ten']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="small"><?= date('d/m/Y', strtotime($row['ngay_dang_ky'])) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: <?= $row['phan_tram_tien_do'] ?>%"></div>
                                    </div>
                                    <span class="small fw-bold"><?= $row['phan_tram_tien_do'] ?>%</span>
                                </div>
                            </td>
                            <td class="text-center"><?= $row['so_lan_thi'] ?></td>
                            <td class="text-center fw-bold">
                                <?= $row['diem_cao_nhat'] !== null ? $row['diem_cao_nhat'] . ' / 10' : '-' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['diem_cao_nhat'] !== null && $row['diem_cao_nhat'] >= 8): ?>
                                    <span class="badge bg-success">Đạt chứng nhận</span>
                                <?php elseif ($row['diem_cao_nhat'] !== null): ?>
                                    <span class="badge bg-warning text-dark">Chưa đạt</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Đang học</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>