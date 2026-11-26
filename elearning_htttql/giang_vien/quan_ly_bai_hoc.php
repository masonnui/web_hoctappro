<?php
require_once '../config/db.php';
yeuCauQuyen(['giang_vien']);

$gv_id = $_SESSION['user']['id'];
$khoa_hoc_id = (int)($_GET['khoa_hoc_id'] ?? 0);

// Kiểm tra khóa học thuộc quyền quản lý của giảng viên này
$stmt = $pdo->prepare("SELECT * FROM khoa_hoc WHERE id = ? AND giang_vien_id = ?");
$stmt->execute([$khoa_hoc_id, $gv_id]);
$khoa_hoc = $stmt->fetch();

if (!$khoa_hoc) {
    die("<div class='container mt-4 alert alert-danger'>Không tìm thấy khóa học hoặc bạn không có quyền chỉnh sửa!</div>");
}

// Hàm chuyển link YouTube thường sang link embed
function chuyenLinkYoutubeEmbed($url) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return $url;
}

// 1. Xử lý Thêm bài học
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_bai_hoc'])) {
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $link_video = chuyenLinkYoutubeEmbed(trim($_POST['link_video'] ?? ''));
    $thu_tu = (int)($_POST['thu_tu'] ?? 1);
    $tai_lieu_path = null;

    // Xử lý upload file PDF nếu có
    if (isset($_FILES['tai_lieu_pdf']) && $_FILES['tai_lieu_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['tai_lieu_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0777, true);
            }
            $file_name = 'tailieu_' . time() . '_' . rand(100, 999) . '.pdf';
            if (move_uploaded_file($_FILES['tai_lieu_pdf']['tmp_name'], '../uploads/' . $file_name)) {
                $tai_lieu_path = 'uploads/' . $file_name;
            }
        }
    }

    if ($tieu_de !== '') {
        $stmt_ins = $pdo->prepare("INSERT INTO bai_hoc (khoa_hoc_id, tieu_de, link_video, tai_lieu_pdf, thu_tu) VALUES (?, ?, ?, ?, ?)");
        $stmt_ins->execute([$khoa_hoc_id, $tieu_de, $link_video, $tai_lieu_path, $thu_tu]);
        header("Location: quan_ly_bai_hoc.php?khoa_hoc_id=$khoa_hoc_id&added=1");
        exit();
    }
}

// 2. Xử lý Xóa bài học
if (isset($_GET['xoa_bai_id'])) {
    $bai_id = (int)$_GET['xoa_bai_id'];
    $stmt_del = $pdo->prepare("DELETE FROM bai_hoc WHERE id = ? AND khoa_hoc_id = ?");
    $stmt_del->execute([$bai_id, $khoa_hoc_id]);
    header("Location: quan_ly_bai_hoc.php?khoa_hoc_id=$khoa_hoc_id&deleted=1");
    exit();
}

$stmt_bh = $pdo->prepare("SELECT * FROM bai_hoc WHERE khoa_hoc_id = ? ORDER BY thu_tu ASC, id ASC");
$stmt_bh->execute([$khoa_hoc_id]);
$bai_hocs = $stmt_bh->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="khoa_hoc_cua_toi.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Quay lại danh sách khóa học</a>
        <h4 class="fw-bold text-primary mt-1">Bài giảng: <?= htmlspecialchars($khoa_hoc['tieu_de']) ?></h4>
    </div>
</div>

<div class="row g-4">
    <!-- Form thêm bài học mới -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Thêm bài giảng mới</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Thứ tự bài học</label>
                        <input type="number" name="thu_tu" class="form-control" value="<?= count($bai_hocs) + 1 ?>" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề bài giảng <span class="text-danger">*</span></label>
                        <input type="text" name="tieu_de" class="form-control" required placeholder="VD: Bài 1: Giới thiệu tổng quan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link Video YouTube</label>
                        <input type="url" name="link_video" class="form-control" required placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tài liệu đính kèm (.PDF - tùy chọn)</label>
                        <input type="file" name="tai_lieu_pdf" class="form-control" accept=".pdf">
                    </div>
                    <button type="submit" name="them_bai_hoc" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>Lưu bài giảng
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách bài học hiện có -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Danh sách bài giảng (<?= count($bai_hocs) ?> bài)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>STT</th>
                            <th>Tiêu đề bài học</th>
                            <th>Tài liệu</th>
                            <th class="text-end">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bai_hocs)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có bài giảng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bai_hocs as $bh): ?>
                                <tr>
                                    <td class="fw-bold"><?= $bh['thu_tu'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($bh['tieu_de']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($bh['link_video']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($bh['tai_lieu_pdf']): ?>
                                            <a href="/elearning_htttql/<?= htmlspecialchars($bh['tai_lieu_pdf']) ?>" target="_blank" class="badge bg-danger text-decoration-none">Xem PDF</a>
                                        <?php else: ?>
                                            <span class="text-muted small">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?khoa_hoc_id=<?= $khoa_hoc_id ?>&xoa_bai_id=<?= $bh['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa bài giảng này?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>