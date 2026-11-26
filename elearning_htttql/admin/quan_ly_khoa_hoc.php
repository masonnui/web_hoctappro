<?php
require_once '../config/db.php';
yeuCauQuyen(['admin']);

// Xử lý phê duyệt hoặc từ chối khóa học
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if (in_array($action, ['da_duyet', 'tu_choi', 'cho_duyet'])) {
        $stmt = $pdo->prepare("UPDATE khoa_hoc SET trang_thai_duyet = ? WHERE id = ?");
        $stmt->execute([$action, $id]);
        header("Location: quan_ly_khoa_hoc.php?updated=1");
        exit();
    }
}

$sql = "SELECT kh.*, nd.ho_ten AS ten_giang_vien,
        (SELECT COUNT(*) FROM bai_hoc WHERE khoa_hoc_id = kh.id) AS so_bai,
        (SELECT COUNT(*) FROM cau_hoi WHERE khoa_hoc_id = kh.id) AS so_cau_hoi
        FROM khoa_hoc kh
        JOIN nguoi_dung nd ON kh.giang_vien_id = nd.id
        ORDER BY FIELD(kh.trang_thai_duyet, 'cho_duyet', 'da_duyet', 'tu_choi'), kh.ngay_tao DESC";
$khoa_hocs = $pdo->query($sql)->fetchAll();

include '../includes/header.php';
?>

<h3 class="fw-bold text-primary mb-4"><i class="bi bi-check2-square me-2"></i>Kiểm duyệt Xuất bản Khóa học</h3>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">Đã cập nhật trạng thái kiểm duyệt khóa học!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Tên khóa học</th>
                    <th>Giảng viên</th>
                    <th class="text-center">Nội dung</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end">Thao tác kiểm duyệt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($khoa_hocs as $kh): ?>
                    <tr>
                        <td>#<?= $kh['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($kh['tieu_de']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($kh['mo_ta'], 0, 80, '...')) ?></small>
                        </td>
                        <td><?= htmlspecialchars($kh['ten_giang_vien']) ?></td>
                        <td class="text-center small">
                            <?= $kh['so_bai'] ?> bài giảng<br>
                            <?= $kh['so_cau_hoi'] ?> câu hỏi
                        </td>
                        <td class="text-center">
                            <?php if ($kh['trang_thai_duyet'] === 'da_duyet'): ?>
                                <span class="badge bg-success">Đã xuất bản</span>
                            <?php elseif ($kh['trang_thai_duyet'] === 'cho_duyet'): ?>
                                <span class="badge bg-warning text-dark">Chờ duyệt</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Từ chối</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="xem_truoc_khoa_hoc.php?id=<?= $kh['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Xem trước
                            </a>
                            <?php if ($kh['trang_thai_duyet'] !== 'da_duyet'): ?>
                                <a href="?action=da_duyet&id=<?= $kh['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg"></i> Phê duyệt
                                </a>
                            <?php endif; ?>
                            <?php if ($kh['trang_thai_duyet'] !== 'tu_choi'): ?>
                                <a href="?action=tu_choi&id=<?= $kh['id'] ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-lg"></i> Từ chối / Ẩn
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