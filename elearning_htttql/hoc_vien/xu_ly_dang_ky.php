<?php
require_once '../config/db.php';
yeuCauQuyen(['hoc_vien']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoc_vien_id = $_SESSION['user']['id'];
    $khoa_hoc_id = (int)($_POST['khoa_hoc_id'] ?? 0);

    // Kiểm tra khóa học hợp lệ và đã duyệt
    $stmt = $pdo->prepare("SELECT id FROM khoa_hoc WHERE id = ? AND trang_thai_duyet = 'da_duyet'");
    $stmt->execute([$khoa_hoc_id]);

    if ($stmt->fetch()) {
        $stmt_dk = $pdo->prepare("INSERT IGNORE INTO dang_ky_hoc (hoc_vien_id, khoa_hoc_id, phan_tram_tien_do) VALUES (?, ?, 0)");
        $stmt_dk->execute([$hoc_vien_id, $khoa_hoc_id]);
        header("Location: /elearning_htttql/index.php?msg=dk_thanh_cong");
        exit();
    }
}

header("Location: /elearning_htttql/index.php");
exit();