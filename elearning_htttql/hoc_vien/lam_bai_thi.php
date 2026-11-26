<?php
require_once '../config/db.php';
yeuCauQuyen(['hoc_vien']);

$hoc_vien_id = $_SESSION['user']['id'];
$khoa_hoc_id = (int)($_GET['khoa_hoc_id'] ?? 0);

// Kiểm tra điều kiện tiến độ >= 80%
$stmt_dk = $pdo->prepare("SELECT dk.*, kh.tieu_de FROM dang_ky_hoc dk JOIN khoa_hoc kh ON dk.khoa_hoc_id = kh.id WHERE dk.hoc_vien_id = ? AND dk.khoa_hoc_id = ?");
$stmt_dk->execute([$hoc_vien_id, $khoa_hoc_id]);
$dang_ky = $stmt_dk->fetch();

if (!$dang_ky || $dang_ky['phan_tram_tien_do'] < 80) {
    die("<div class='container mt-4 alert alert-warning'>Bạn cần hoàn thành tối thiểu 80% bài học trước khi dự thi! <a href='khoa_hoc_cua_toi.php'>Quay lại</a></div>");
}

// Lấy bộ câu hỏi của khóa học
$stmt_ch = $pdo->prepare("SELECT * FROM cau_hoi WHERE khoa_hoc_id = ? ORDER BY id ASC");
$stmt_ch->execute([$khoa_hoc_id]);
$cau_hois = $stmt_ch->fetchAll();

$ket_qua_vua_nop = null;

// Xử lý chấm điểm tự động khi nộp bài
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cau_hois)) {
    $tra_loi = $_POST['dap_an'] ?? [];
    $tong_cau = count($cau_hois);
    $so_dung = 0;

    foreach ($cau_hois as $ch) {
        $chon = $tra_loi[$ch['id']] ?? '';
        if ($chon === $ch['dap_an_dung']) {
            $so_dung++;
        }
    }

    $diem_so = round(($so_dung / $tong_cau) * 10, 2);

    $stmt_save = $pdo->prepare("INSERT INTO ket_qua_thi (hoc_vien_id, khoa_hoc_id, diem_so, so_cau_dung, tong_so_cau) VALUES (?, ?, ?, ?, ?)");
    $stmt_save->execute([$hoc_vien_id, $khoa_hoc_id, $diem_so, $so_dung, $tong_cau]);

    $ket_qua_vua_nop = [
        'diem_so' => $diem_so,
        'so_dung' => $so_dung,
        'tong_cau' => $tong_cau
    ];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold text-primary mb-0">Bài kiểm tra: <?= htmlspecialchars($dang_ky['tieu_de']) ?></h4>
    <a href="phong_hoc.php?khoa_hoc_id=<?= $khoa_hoc_id ?>" class="btn btn-outline-secondary btn-sm">Quay lại phòng học</a>
</div>

<?php if ($ket_qua_vua_nop): ?>
    <!-- Hiển thị kết quả & Chứng nhận nếu đạt >= 8.0 -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body text-center p-4">
            <h3 class="fw-bold mb-2">Kết quả bài làm của bạn</h3>
            <div class="display-4 fw-bold <?= $ket_qua_vua_nop['diem_so'] >= 8 ? 'text-success' : 'text-danger' ?> my-3">
                <?= $ket_qua_vua_nop['diem_so'] ?> / 10 điểm
            </div>
            <p class="lead">Số câu trả lời đúng: <strong><?= $ket_qua_vua_nop['so_dung'] ?> / <?= $ket_qua_vua_nop['tong_cau'] ?></strong> câu</p>

            <?php if ($ket_qua_vua_nop['diem_so'] >= 8): ?>
                <div class="alert alert-success mt-3">
                    <i class="bi bi-award-fill me-1"></i>Chúc mừng! Bạn đã đạt điều kiện cấp <strong>Chứng nhận hoàn thành khóa học</strong>.
                </div>

                <!-- Khung Chứng nhận trực tuyến -->
                <div class="border border-3 border-warning rounded p-4 my-4 mx-auto bg-light" style="max-width: 650px;">
                    <div class="text-uppercase text-warning fw-bold small">Hệ thống Đào tạo Trực tuyến E-Learning MIS</div>
                    <h2 class="fw-bold text-primary my-2">CHỨNG NHẬN HOÀN THÀNH</h2>
                    <p class="mb-1">Trân trọng chứng nhận học viên:</p>
                    <h4 class="fw-bold text-dark text-uppercase my-2"><?= htmlspecialchars($_SESSION['user']['ho_ten']) ?></h4>
                    <p class="mb-1">Đã hoàn thành xuất sắc khóa học:</p>
                    <h5 class="fw-bold text-primary mb-3"><?= htmlspecialchars($dang_ky['tieu_de']) ?></h5>
                    <div class="small text-muted">Điểm đánh giá: <strong><?= $ket_qua_vua_nop['diem_so'] ?>/10</strong> — Ngày cấp: <?= date('d/m/Y') ?></div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">
                    Bạn chưa đạt mức điểm tối thiểu (8.0 điểm) để nhận chứng nhận. Hãy ôn tập và thử lại nhé!
                </div>
                <a href="lam_bai_thi.php?khoa_hoc_id=<?= $khoa_hoc_id ?>" class="btn btn-primary">Làm lại bài kiểm tra</a>
            <?php endif; ?>
        </div>
    </div>

<?php elseif (empty($cau_hois)): ?>
    <div class="alert alert-info">Khóa học này chưa có câu hỏi kiểm tra. Vui lòng liên hệ Giảng viên!</div>

<?php else: ?>
    <!-- Giao diện làm bài trắc nghiệm có đồng hồ đếm ngược -->
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span><i class="bi bi-info-circle me-1"></i>Chọn 1 đáp án đúng nhất cho mỗi câu hỏi. Đạt từ <strong>8.0 điểm</strong> trở lên sẽ được cấp chứng nhận.</span>
        <span class="badge bg-danger fs-6">Thời gian còn lại: <span id="timer">15:00</span></span>
    </div>

    <form method="POST" id="quizForm">
        <?php foreach ($cau_hois as $idx => $ch): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <p class="fw-bold mb-3">Câu <?= $idx + 1 ?>: <?= htmlspecialchars($ch['noi_dung']) ?></p>
                    <?php foreach (['A' => 'dap_an_a', 'B' => 'dap_an_b', 'C' => 'dap_an_c', 'D' => 'dap_an_d'] as $key => $col): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="dap_an[<?= $ch['id'] ?>]" id="q<?= $ch['id'] . $key ?>" value="<?= $key ?>">
                            <label class="form-check-label" for="q<?= $ch['id'] . $key ?>">
                                <strong><?= $key ?>.</strong> <?= htmlspecialchars($ch[$col]) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="text-end mb-5">
            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold" onclick="return confirm('Bạn chắc chắn muốn nộp bài?')">
                Nộp bài & Chấm điểm
            </button>
        </div>
    </form>

    <script>
        // Đồng hồ đếm ngược 15 phút (900 giây), hết giờ tự động nộp bài
        let timeLeft = 15 * 60;
        const timerEl = document.getElementById('timer');
        const countdown = setInterval(() => {
            let mins = Math.floor(timeLeft / 60);
            let secs = timeLeft % 60;
            timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            if (timeLeft <= 0) {
                clearInterval(countdown);
                alert('Đã hết thời gian làm bài! Hệ thống sẽ tự động thu bài.');
                document.getElementById('quizForm').submit();
            }
            timeLeft--;
        }, 1000);
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>