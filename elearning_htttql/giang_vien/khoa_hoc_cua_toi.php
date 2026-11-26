<?php
require_once '../config/db.php';
yeuCauQuyen(['giang_vien']);

$gv_id = $_SESSION['user']['id'];
$msg = '';

// 1. Xử lý Thêm khóa học mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them') {
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    $anh_bia = trim($_POST['anh_bia'] ?? '');
    if ($anh_bia === '') {
        $anh_bia = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600';
    }

    if ($tieu_de !== '') {
        $stmt = $pdo->prepare("INSERT INTO khoa_hoc (tieu_de, mo_ta, anh_bia, giang_vien_id, trang_thai_duyet) VALUES (?, ?, ?, ?, 'cho_duyet')");
        $stmt->execute([$tieu_de, $mo_ta, $anh_bia, $gv_id]);
        $msg = "Đã tạo khóa học mới! Vui lòng thêm bài giảng và chờ Admin phê duyệt.";
    }
}

// 2. Xử lý Cập nhật khóa học
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sua') {
    $id = (int)$_POST['id'];
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    $anh_bia = trim($_POST['anh_bia'] ?? '');

    $stmt = $pdo->prepare("UPDATE khoa_hoc SET tieu_de = ?, mo_ta = ?, anh_bia = ? WHERE id = ? AND giang_vien_id = ?");
    $stmt->execute([$tieu_de, $mo_ta, $anh_bia, $id, $gv_id]);
    $msg = "Cập nhật thông tin khóa học thành công!";
}

// 3. Xử lý Xóa khóa học
if (isset($_GET['xoa_id'])) {
    $xoa_id = (int)$_GET['xoa_id'];
    $stmt = $pdo->prepare("DELETE FROM khoa_hoc WHERE id = ? AND giang_vien_id = ?");
    $stmt->execute([$xoa_id, $gv_id]);
    header("Location: khoa_hoc_cua_toi.php?deleted=1");
    exit();
}

// Lấy danh sách khóa học của giảng viên này kèm số liệu thống kê nhanh
$sql = "SELECT kh.*,
        (SELECT COUNT(*) FROM bai_hoc WHERE khoa_hoc_id = kh.id) AS so_bai,
        (SELECT COUNT(*) FROM cau_hoi WHERE khoa_hoc_id = kh.id) AS so_cau_hoi,
        (SELECT COUNT(*) FROM dang_ky_hoc WHERE khoa_hoc_id = kh.id) AS so_hoc_vien
        FROM khoa_hoc kh
        WHERE kh.giang_vien_id = ?
        ORDER BY kh.ngay_tao DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$gv_id]);
$ds_khoa_hoc = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary mb-0"><i class="bi bi-easel2-fill me-2"></i>Quản lý Khóa học Giảng dạy</h3>
    <button class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalThemKH">
        <i class="bi bi-plus-circle me-1"></i>Tạo khóa học mới
    </button>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show">Đã xóa khóa học.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Khóa học</th>
                    <th class="text-center">Bài giảng</th>
                    <th class="text-center">Câu hỏi thi</th>
                    <th class="text-center">Học viên</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end">Thao tác nghiệp vụ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ds_khoa_hoc)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Bạn chưa tạo khóa học nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($ds_khoa_hoc as $kh): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($kh['tieu_de']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($kh['mo_ta'], 0, 70, '...')) ?></small>
                            </td>
                            <td class="text-center"><span class="badge bg-info text-dark"><?= $kh['so_bai'] ?> bài</span></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $kh['so_cau_hoi'] ?> câu</span></td>
                            <td class="text-center fw-bold text-primary"><?= $kh['so_hoc_vien'] ?></td>
                            <td class="text-center">
                                <?php if ($kh['trang_thai_duyet'] === 'da_duyet'): ?>
                                    <span class="badge bg-success">Đã xuất bản</span>
                                <?php elseif ($kh['trang_thai_duyet'] === 'cho_duyet'): ?>
                                    <span class="badge bg-warning text-dark">Chờ Admin duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Bị từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="quan_ly_bai_hoc.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-outline-primary" title="Quản lý bài giảng">
                                        <i class="bi bi-collection-play"></i> Bài học
                                    </a>
                                    <a href="quan_ly_cau_hoi.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-outline-dark" title="Ngân hàng câu hỏi">
                                        <i class="bi bi-question-circle"></i> Đề thi
                                    </a>
                                    <a href="bang_diem_hoc_vien.php?khoa_hoc_id=<?= $kh['id'] ?>" class="btn btn-outline-success" title="Danh sách & Điểm học viên">
                                        <i class="bi bi-people"></i> Lớp học
                                    </a>
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalSuaKH<?= $kh['id'] ?>" title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?xoa_id=<?= $kh['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Xóa khóa học sẽ xóa toàn bộ bài giảng và điểm thi liên quan. Tiếp tục?')" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Sửa Khóa học -->
                        <div class="modal fade" id="modalSuaKH<?= $kh['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Cập nhật Khóa học</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="sua">
                                        <input type="hidden" name="id" value="<?= $kh['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Tên khóa học</label>
                                            <input type="text" name="tieu_de" class="form-control" value="<?= htmlspecialchars($kh['tieu_de']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Mô tả chi tiết</label>
                                            <textarea name="mo_ta" class="form-control" rows="3"><?= htmlspecialchars($kh['mo_ta']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Link ảnh bìa (URL)</label>
                                            <input type="text" name="anh_bia" class="form-control" value="<?= htmlspecialchars($kh['anh_bia']) ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Khóa học -->
<div class="modal fade" id="modalThemKH" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tạo Khóa học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="them">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên khóa học <span class="text-danger">*</span></label>
                    <input type="text" name="tieu_de" class="form-control" required placeholder="VD: Quản trị Cơ sở dữ liệu MySQL">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả khóa học</label>
                    <textarea name="mo_ta" class="form-control" rows="3" placeholder="Mục tiêu và nội dung chính của khóa học..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Link ảnh bìa (để trống sẽ dùng ảnh mặc định)</label>
                    <input type="url" name="anh_bia" class="form-control" placeholder="https://...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Tạo khóa học</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>