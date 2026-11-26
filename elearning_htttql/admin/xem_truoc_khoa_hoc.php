<?php
require_once '../config/db.php';
yeuCauQuyen(['admin']);

$khoa_hoc_id = (int)($_GET['id'] ?? 0);

// Lấy thông tin khóa học và giảng viên
$stmt = $pdo->prepare("SELECT kh.*, nd.ho_ten AS ten_giang_vien, nd.email AS email_gv 
                       FROM khoa_hoc kh 
                       JOIN nguoi_dung nd ON kh.giang_vien_id = nd.id 
                       WHERE kh.id = ?");
$stmt->execute([$khoa_hoc_id]);
$kh = $stmt->fetch();

if (!$kh) {
    die("<div class='container mt-4 alert alert-danger'>Không tìm thấy khóa học!</div>");
}

// Lấy danh sách bài giảng
$stmt_bh = $pdo->prepare("SELECT * FROM bai_hoc WHERE khoa_hoc_id = ? ORDER BY thu_tu ASC, id ASC");
$stmt_bh->execute([$khoa_hoc_id]);
$bai_hocs = $stmt_bh->fetchAll();

// Lấy danh sách câu hỏi trắc nghiệm
$stmt_ch = $pdo->prepare("SELECT * FROM cau_hoi WHERE khoa_hoc_id = ? ORDER BY id ASC");
$stmt_ch->execute([$khoa_hoc_id]);
$cau_hois = $stmt_ch->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <a href="quan_ly_khoa_hoc.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Quay lại danh sách duyệt</a>
        <h4 class="fw-bold text-primary mt-1 mb-0">Kiểm duyệt nội dung: <?= htmlspecialchars($kh['tieu_de']) ?></h4>
    </div>
    <div class="d-flex gap-2">
        <?php if ($kh['trang_thai_duyet'] !== 'da_duyet'): ?>
            <a href="quan_ly_khoa_hoc.php?action=da_duyet&id=<?= $kh['id'] ?>" class="btn btn-success fw-semibold">
                <i class="bi bi-check-lg me-1"></i>Phê duyệt xuất bản
            </a>
        <?php endif; ?>
        <?php if ($kh['trang_thai_duyet'] !== 'tu_choi'): ?>
            <a href="quan_ly_khoa_hoc.php?action=tu_choi&id=<?= $kh['id'] ?>" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-x-lg me-1"></i>Từ chối / Ẩn
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Thông tin chung -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-1"><strong>Giảng viên:</strong> <?= htmlspecialchars($kh['ten_giang_vien']) ?> (<?= htmlspecialchars($kh['email_gv']) ?>)</p>
                <p class="mb-0 text-muted"><strong>Mô tả:</strong> <?= htmlspecialchars($kh['mo_ta']) ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <span class="me-2">Trạng thái hiện tại:</span>
                <?php if ($kh['trang_thai_duyet'] === 'da_duyet'): ?>
                    <span class="badge bg-success fs-6">Đã xuất bản</span>
                <?php elseif ($kh['trang_thai_duyet'] === 'cho_duyet'): ?>
                    <span class="badge bg-warning text-dark fs-6">Chờ duyệt</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">Từ chối</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Cột trái: Xem trước Video & Tài liệu PDF -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-collection-play-fill text-primary me-1"></i>Danh sách Bài giảng (<?= count($bai_hocs) ?> bài)
            </div>
            <div class="card-body">
                <?php if (empty($bai_hocs)): ?>
                    <div class="alert alert-warning mb-0">Khóa học này chưa có bài giảng nào!</div>
                <?php else: ?>
                    <?php foreach ($bai_hocs as $bh): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">Bài <?= $bh['thu_tu'] ?>: <?= htmlspecialchars($bh['tieu_de']) ?></span>
                                <?php if (!empty($bh['tai_lieu_pdf'])): ?>
                                    <a href="/elearning_htttql/<?= htmlspecialchars($bh['tai_lieu_pdf']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-file-earmark-pdf"></i> Mở PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($bh['link_video'])): ?>
                                <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                                    <iframe src="<?= htmlspecialchars($bh['link_video']) ?>" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải: Kiểm tra Ngân hàng câu hỏi & Đáp án -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-question-circle-fill text-success me-1"></i>Ngân hàng Câu hỏi thi (<?= count($cau_hois) ?> câu)
            </div>
            <div class="card-body">
                <?php if (empty($cau_hois)): ?>
                    <div class="alert alert-warning mb-0">Khóa học này chưa có câu hỏi kiểm tra!</div>
                <?php else: ?>
                    <?php foreach ($cau_hois as $idx => $ch): ?>
                        <div class="border rounded p-3 mb-3 bg-light small">
                            <div class="fw-bold mb-2">Câu <?= $idx + 1 ?>: <?= htmlspecialchars($ch['noi_dung']) ?></div>
                            <ul class="list-unstyled mb-0">
                                <li class="<?= $ch['dap_an_dung'] === 'A' ? 'text-success fw-bold' : '' ?>">A. <?= htmlspecialchars($ch['dap_an_a']) ?></li>
                                <li class="<?= $ch['dap_an_dung'] === 'B' ? 'text-success fw-bold' : '' ?>">B. <?= htmlspecialchars($ch['dap_an_b']) ?></li>
                                <li class="<?= $ch['dap_an_dung'] === 'C' ? 'text-success fw-bold' : '' ?>">C. <?= htmlspecialchars($ch['dap_an_c']) ?></li>
                                <li class="<?= $ch['dap_an_dung'] === 'D' ? 'text-success fw-bold' : '' ?>">D. <?= htmlspecialchars($ch['dap_an_d']) ?></li>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>