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

// 1. Thêm câu hỏi mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_cau_hoi'])) {
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $a = trim($_POST['dap_an_a'] ?? '');
    $b = trim($_POST['dap_an_b'] ?? '');
    $c = trim($_POST['dap_an_c'] ?? '');
    $d = trim($_POST['dap_an_d'] ?? '');
    $dung = $_POST['dap_an_dung'] ?? 'A';

    if ($noi_dung !== '' && $a !== '' && $b !== '' && $c !== '' && $d !== '') {
        $stmt_ins = $pdo->prepare("INSERT INTO cau_hoi (khoa_hoc_id, noi_dung, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->execute([$khoa_hoc_id, $noi_dung, $a, $b, $c, $d, $dung]);
        header("Location: quan_ly_cau_hoi.php?khoa_hoc_id=$khoa_hoc_id&added=1");
        exit();
    }
}

// 2. Xóa câu hỏi
if (isset($_GET['xoa_ch_id'])) {
    $ch_id = (int)$_GET['xoa_ch_id'];
    $stmt_del = $pdo->prepare("DELETE FROM cau_hoi WHERE id = ? AND khoa_hoc_id = ?");
    $stmt_del->execute([$ch_id, $khoa_hoc_id]);
    header("Location: quan_ly_cau_hoi.php?khoa_hoc_id=$khoa_hoc_id&deleted=1");
    exit();
}

$stmt_ch = $pdo->prepare("SELECT * FROM cau_hoi WHERE khoa_hoc_id = ? ORDER BY id ASC");
$stmt_ch->execute([$khoa_hoc_id]);
$cau_hois = $stmt_ch->fetchAll();

include '../includes/header.php';
?>

<div class="mb-3">
    <a href="khoa_hoc_cua_toi.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Quay lại danh sách khóa học</a>
    <h4 class="fw-bold text-primary mt-1">Ngân hàng Câu hỏi: <?= htmlspecialchars($khoa_hoc['tieu_de']) ?></h4>
</div>

<div class="row g-4">
    <!-- Form thêm câu hỏi -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Thêm câu hỏi trắc nghiệm</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung câu hỏi <span class="text-danger">*</span></label>
                        <textarea name="noi_dung" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="dap_an_a" class="form-control form-control-sm" placeholder="Đáp án A" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="dap_an_b" class="form-control form-control-sm" placeholder="Đáp án B" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="dap_an_c" class="form-control form-control-sm" placeholder="Đáp án C" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="dap_an_d" class="form-control form-control-sm" placeholder="Đáp án D" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Đáp án đúng</label>
                        <select name="dap_an_dung" class="form-select">
                            <option value="A">Đáp án A</option>
                            <option value="B">Đáp án B</option>
                            <option value="C">Đáp án C</option>
                            <option value="D">Đáp án D</option>
                        </select>
                    </div>
                    <button type="submit" name="them_cau_hoi" class="btn btn-primary w-100 fw-semibold">Thêm vào bộ đề</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách câu hỏi -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Danh sách câu hỏi (<?= count($cau_hois) ?> câu)</div>
            <div class="card-body">
                <?php if (empty($cau_hois)): ?>
                    <p class="text-muted text-center my-3">Chưa có câu hỏi nào cho khóa học này.</p>
                <?php else: ?>
                    <?php foreach ($cau_hois as $idx => $ch): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <strong>Câu <?= $idx + 1 ?>: <?= htmlspecialchars($ch['noi_dung']) ?></strong>
                                <a href="?khoa_hoc_id=<?= $khoa_hoc_id ?>&xoa_ch_id=<?= $ch['id'] ?>" class="text-danger" onclick="return confirm('Xóa câu hỏi này?')"><i class="bi bi-trash"></i></a>
                            </div>
                            <ul class="list-unstyled small mt-2 mb-0">
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