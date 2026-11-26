<?php
require_once 'config/db.php';
include 'includes/header.php';

$keyword = trim($_GET['q'] ?? '');
$user = $_SESSION['user'] ?? null;

// Truy vấn các khóa học đã được Admin duyệt
$sql = "SELECT kh.*, nd.ho_ten AS ten_giang_vien,
        (SELECT COUNT(*) FROM bai_hoc WHERE khoa_hoc_id = kh.id) AS so_bai_hoc
        FROM khoa_hoc kh
        JOIN nguoi_dung nd ON kh.giang_vien_id = nd.id
        WHERE kh.trang_thai_duyet = 'da_duyet'";

$params = [];
if ($keyword !== '') {
    $sql .= " AND (kh.tieu_de LIKE ? OR kh.mo_ta LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
$sql .= " ORDER BY kh.ngay_tao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$khoa_hocs = $stmt->fetchAll();

// Lấy danh sách ID các khóa học mà học viên hiện tại đã đăng ký
$da_dang_ky_ids = [];
if ($user && $user['vai_tro'] === 'hoc_vien') {
    $stmt_dk = $pdo->prepare("SELECT khoa_hoc_id FROM dang_ky_hoc WHERE hoc_vien_id = ?");
    $stmt_dk->execute([$user['id']]);
    $da_dang_ky_ids = $stmt_dk->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="p-4 mb-4 bg-white rounded-3 shadow-sm border">
    <div class="row align-items-center">
        <div class="col-md-7">
            <h2 class="fw-bold text-primary mb-2">Hệ thống Đào tạo & Học tập Trực tuyến</h2>
            <p class="text-muted mb-0">Nền tảng quản lý học liệu, theo dõi tiến độ tự động và đánh giá năng lực trực tuyến.</p>
        </div>
        <div class="col-md-5 mt-3 mt-md-0">
            <form method="GET" class="d-flex">
                <input type="text" name="q" class="form-control me-2" placeholder="Tìm kiếm tên khóa học..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'dk_thanh_cong'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Đăng ký khóa học thành công! Bạn có thể bắt đầu học ngay bây giờ.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<h4 class="fw-bold mb-3">Danh sách Khóa học hiện có (<?= count($khoa_hocs) ?>)</h4>

<div class="row g-4">
    <?php if (empty($khoa_hocs)): ?>
        <div class="col-12">
            <div class="alert alert-info">Không tìm thấy khóa học nào phù hợp.</div>
        </div>
    <?php else: ?>
        <?php foreach ($khoa_hocs as $kh): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <img src="<?= htmlspecialchars($kh['anh_bia']) ?>" class="card-img-top" alt="Ảnh bìa" style="height: 180px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($kh['tieu_de']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= htmlspecialchars(mb_strimwidth($kh['mo_ta'], 0, 120, "...")) ?>
                        </p>
                        <div class="d-flex justify-content-between text-muted small mb-3 border-top pt-2">
                            <span><i class="bi bi-person-video3 me-1"></i><?= htmlspecialchars($kh['ten_giang_vien']) ?></span>
                            <span><i class="bi bi-play-circle me-1"></i><?= $kh['so_bai_hoc'] ?> bài học</span>
                        </div>

                        <?php if (!$user): ?>
                            <a href="/elearning_htttql/auth/login.php" class="btn btn-outline-primary w-100">Đăng nhập để học</a>
                        <?php elseif ($user['vai_tro'] === 'hoc_vien'): ?>
                            <?php if (in_array($kh['id'], $da_dang_ky_ids)): ?>
                                <a href="/elearning_htttql/hoc_vien/phong_hoc.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-success w-100">
                                    <i class="bi bi-play-fill me-1"></i>Vào phòng học
                                </a>
                            <?php else: ?>
                                <form method="POST" action="/elearning_htttql/hoc_vien/xu_ly_dang_ky.php">
                                    <input type="hidden" name="khoa_hoc_id" value="<?= $kh['id'] ?>">
                                    <button type="submit" class="btn btn-primary w-100">Đăng ký khóa học</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif ($user['vai_tro'] === 'admin'): ?>
                            <a href="/elearning_htttql/admin/xem_truoc_khoa_hoc.php?id=<?= $kh['id'] ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-eye me-1"></i>Xem trước nội dung (ADMIN)
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>Chế độ xem trước (GIẢNG VIÊN)</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>