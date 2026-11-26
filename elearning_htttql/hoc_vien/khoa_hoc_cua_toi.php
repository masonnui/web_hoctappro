<?php
require_once '../config/db.php';
yeuCauQuyen(['hoc_vien']);

$hoc_vien_id = $_SESSION['user']['id'];

$sql = "SELECT kh.*, dk.ngay_dang_ky, dk.phan_tram_tien_do, nd.ho_ten AS ten_giang_vien,
        (SELECT MAX(diem_so) FROM ket_qua_thi WHERE hoc_vien_id = dk.hoc_vien_id AND khoa_hoc_id = kh.id) AS diem_cao_nhat
        FROM dang_ky_hoc dk
        JOIN khoa_hoc kh ON dk.khoa_hoc_id = kh.id
        JOIN nguoi_dung nd ON kh.giang_vien_id = nd.id
        WHERE dk.hoc_vien_id = ?
        ORDER BY dk.ngay_dang_ky DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$hoc_vien_id]);
$ds_khoa_hoc = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary mb-0"><i class="bi bi-journal-bookmark-fill me-2"></i>Khóa học của tôi</h3>
    <a href="/elearning_htttql/index.php" class="btn btn-outline-primary btn-sm">+ Tìm thêm khóa học</a>
</div>

<?php if (empty($ds_khoa_hoc)): ?>
    <div class="alert alert-info">
        Bạn chưa đăng ký khóa học nào. Hãy quay lại <a href="/elearning_htttql/index.php" class="alert-link">Trang chủ</a> để chọn khóa học phù hợp!
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($ds_khoa_hoc as $kh): ?>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="row g-0 h-100">
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars($kh['anh_bia']) ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="Ảnh khóa học">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body d-flex flex-column h-100">
                                <h5 class="card-title fw-bold"><?= htmlspecialchars($kh['tieu_de']) ?></h5>
                                <p class="text-muted small mb-2">Giảng viên: <?= htmlspecialchars($kh['ten_giang_vien']) ?></p>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                                        <span>Tiến độ học tập</span>
                                        <span class="text-primary"><?= $kh['phan_tram_tien_do'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= $kh['phan_tram_tien_do'] ?>%"></div>
                                    </div>
                                </div>

                                <div class="small mb-3">
                                    Kết quả thi cao nhất:
                                    <?php if ($kh['diem_cao_nhat'] !== null): ?>
                                        <strong class="<?= $kh['diem_cao_nhat'] >= 8 ? 'text-success' : 'text-danger' ?>">
                                            <?= $kh['diem_cao_nhat'] ?> / 10 điểm
                                        </strong>
                                        <?php if ($kh['diem_cao_nhat'] >= 8): ?>
                                            <span class="badge bg-success ms-1">Đạt chứng nhận</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa dự thi</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-auto d-flex gap-2">
                                    <a href="phong_hoc.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                                        <i class="bi bi-play-circle me-1"></i>Vào học
                                    </a>
                                    <?php if ($kh['phan_tram_tien_do'] >= 80): ?>
                                        <a href="lam_bai_thi.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-warning btn-sm fw-semibold">
                                            <i class="bi bi-pencil-square me-1"></i>Thi cuối khóa
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>