<?php
require_once '../config/db.php';
yeuCauQuyen(['hoc_vien']);

$hoc_vien_id = $_SESSION['user']['id'];
$khoa_hoc_id = (int)($_GET['khoa_hoc_id'] ?? 0);

// 1. Kiểm tra học viên đã đăng ký khóa học này chưa
$stmt_dk = $pdo->prepare("SELECT dk.*, kh.tieu_de FROM dang_ky_hoc dk JOIN khoa_hoc kh ON dk.khoa_hoc_id = kh.id WHERE dk.hoc_vien_id = ? AND dk.khoa_hoc_id = ?");
$stmt_dk->execute([$hoc_vien_id, $khoa_hoc_id]);
$dang_ky = $stmt_dk->fetch();

if (!$dang_ky) {
    die("<div class='container mt-4 alert alert-danger'>Bạn chưa đăng ký khóa học này! <a href='/elearning_htttql/index.php'>Quay lại</a></div>");
}

// 2. Lấy danh sách bài học của khóa
$stmt_bh = $pdo->prepare("SELECT * FROM bai_hoc WHERE khoa_hoc_id = ? ORDER BY thu_tu ASC, id ASC");
$stmt_bh->execute([$khoa_hoc_id]);
$bai_hocs = $stmt_bh->fetchAll();

// 3. Xử lý khi học viên bấm "Hoàn thành bài học"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hoan_thanh_bai_id'])) {
    $bai_id = (int)$_POST['hoan_thanh_bai_id'];

    $stmt_ins = $pdo->prepare("INSERT IGNORE INTO tien_do_bai_hoc (hoc_vien_id, bai_hoc_id, da_hoan_thanh) VALUES (?, ?, 1)");
    $stmt_ins->execute([$hoc_vien_id, $bai_id]);

    // Tự động tính lại % tiến độ khóa học
    $tong_bai = count($bai_hocs);
    $stmt_done = $pdo->prepare("SELECT COUNT(*) FROM tien_do_bai_hoc td JOIN bai_hoc bh ON td.bai_hoc_id = bh.id WHERE td.hoc_vien_id = ? AND bh.khoa_hoc_id = ?");
    $stmt_done->execute([$hoc_vien_id, $khoa_hoc_id]);
    $so_bai_xong = (int)$stmt_done->fetchColumn();

    $phan_tram = ($tong_bai > 0) ? round(($so_bai_xong / $tong_bai) * 100, 1) : 0;

    $stmt_upd = $pdo->prepare("UPDATE dang_ky_hoc SET phan_tram_tien_do = ? WHERE hoc_vien_id = ? AND khoa_hoc_id = ?");
    $stmt_upd->execute([$phan_tram, $hoc_vien_id, $khoa_hoc_id]);

    header("Location: phong_hoc.php?khoa_hoc_id=$khoa_hoc_id&bai_id=$bai_id&completed=1");
    exit();
}

// Lấy danh sách ID bài học đã hoàn thành
$stmt_td = $pdo->prepare("SELECT bai_hoc_id FROM tien_do_bai_hoc WHERE hoc_vien_id = ?");
$stmt_td->execute([$hoc_vien_id]);
$da_hoan_thanh_ids = $stmt_td->fetchAll(PDO::FETCH_COLUMN);

// Xác định bài học đang mở
$bai_hien_tai = null;
$bai_id_chon = (int)($_GET['bai_id'] ?? 0);
if (!empty($bai_hocs)) {
    $bai_hien_tai = $bai_hocs[0];
    foreach ($bai_hocs as $bh) {
        if ($bh['id'] === $bai_id_chon) {
            $bai_hien_tai = $bh;
            break;
        }
    }
}

include '../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="khoa_hoc_cua_toi.php">Khóa học của tôi</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($dang_ky['tieu_de']) ?></li>
    </ol>
</nav>

<?php if (isset($_GET['completed'])): ?>
    <div class="alert alert-success py-2">
        <i class="bi bi-check-circle-fill me-1"></i>Đã ghi nhận hoàn thành bài học! Tiến độ hiện tại của bạn là <strong><?= $dang_ky['phan_tram_tien_do'] ?>%</strong>.
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Cột trái: Trình phát bài giảng -->
    <div class="col-lg-8">
        <?php if ($bai_hien_tai): ?>
            <div class="card shadow-sm border-0">
                <div class="ratio ratio-16x9 bg-dark rounded-top">
                    <iframe src="<?= htmlspecialchars($bai_hien_tai['link_video']) ?>" title="Video bài giảng" allowfullscreen></iframe>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($bai_hien_tai['tieu_de']) ?></h4>
                            <?php if (!empty($bai_hien_tai['tai_lieu_pdf'])): ?>
                                <a href="/elearning_htttql/<?= htmlspecialchars($bai_hien_tai['tai_lieu_pdf']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm mt-2">
                                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i>Tải tài liệu đính kèm (PDF)
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (in_array($bai_hien_tai['id'], $da_hoan_thanh_ids)): ?>
                            <button class="btn btn-success" disabled>
                                <i class="bi bi-check2-all me-1"></i>Đã hoàn thành bài này
                            </button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="hoan_thanh_bai_id" value="<?= $bai_hien_tai['id'] ?>">
                                <button type="submit" class="btn btn-primary fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i>Hoàn thành bài học
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Giảng viên chưa cập nhật bài giảng cho khóa học này.</div>
        <?php endif; ?>
    </div>

    <!-- Cột phải: Danh sách bài học & Nút làm bài thi -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-bold">
                Nội dung khóa học (<?= $dang_ky['phan_tram_tien_do'] ?>%)
            </div>
            <div class="progress rounded-0" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?= $dang_ky['phan_tram_tien_do'] ?>%"></div>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($bai_hocs as $index => $bh): ?>
                    <?php
                    $is_active = ($bai_hien_tai && $bh['id'] === $bai_hien_tai['id']);
                    $is_done = in_array($bh['id'], $da_hoan_thanh_ids);
                    ?>
                    <a href="phong_hoc.php?khoa_hoc_id=<?= $khoa_hoc_id ?>&bai_id=<?= $bh['id'] ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $is_active ? 'active' : '' ?>">
                        <span class="small">
                            <strong>Bài <?= $index + 1 ?>:</strong> <?= htmlspecialchars($bh['tieu_de']) ?>
                        </span>
                        <?php if ($is_done): ?>
                            <i class="bi bi-check-circle-fill <?= $is_active ? 'text-white' : 'text-success' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-circle <?= $is_active ? 'text-white' : 'text-muted' ?>"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Khối điều kiện làm bài thi cuối khóa -->
        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body text-center">
                <h6 class="fw-bold">Đánh giá cuối khóa</h6>
                <?php if ($dang_ky['phan_tram_tien_do'] >= 80): ?>
                    <p class="small text-success mb-3">Bạn đã đủ điều kiện làm bài kiểm tra trắc nghiệm (Tiến độ đạt >= 80%).</p>
                    <a href="lam_bai_thi.php?khoa_hoc_id=<?= $khoa_hoc_id ?>" class="btn btn-warning w-100 fw-bold">
                        <i class="bi bi-pencil-square me-1"></i>Làm bài kiểm tra ngay
                    </a>
                <?php else: ?>
                    <p class="small text-muted mb-2">Cần hoàn thành tối thiểu <strong>80%</strong> bài giảng để mở khóa bài thi.</p>
                    <button class="btn btn-secondary w-100" disabled>Chưa mở khóa bài thi</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>