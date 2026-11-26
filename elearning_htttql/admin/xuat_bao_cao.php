<?php
require_once '../config/db.php';
yeuCauQuyen(['admin']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Bao_Cao_Dao_Tao_ELearning_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
// Ghi BOM UTF-8 để Excel hiển thị đúng tiếng Việt có dấu
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Dòng tiêu đề cột trong file Excel
fputcsv($output, [
    'STT',
    'Họ tên Học viên',
    'Email',
    'Khóa học đăng ký',
    'Giảng viên phụ trách',
    'Ngày đăng ký',
    'Tiến độ học (%)',
    'Điểm thi cao nhất',
    'Kết quả xếp loại'
]);

$sql = "SELECT hv.ho_ten AS ten_hv, hv.email, kh.tieu_de AS ten_kh, gv.ho_ten AS ten_gv,
        dk.ngay_dang_ky, dk.phan_tram_tien_do,
        (SELECT MAX(diem_so) FROM ket_qua_thi WHERE hoc_vien_id = hv.id AND khoa_hoc_id = kh.id) AS diem_cao_nhat
        FROM dang_ky_hoc dk
        JOIN nguoi_dung hv ON dk.hoc_vien_id = hv.id
        JOIN khoa_hoc kh ON dk.khoa_hoc_id = kh.id
        JOIN nguoi_dung gv ON kh.giang_vien_id = gv.id
        ORDER BY kh.tieu_de ASC, hv.ho_ten ASC";

$rows = $pdo->query($sql)->fetchAll();

foreach ($rows as $index => $r) {
    $diem = $r['diem_cao_nhat'] !== null ? $r['diem_cao_nhat'] : 'Chưa thi';
    if ($r['diem_cao_nhat'] !== null && $r['diem_cao_nhat'] >= 8) {
        $xep_loai = 'Đạt chứng nhận';
    } elseif ($r['diem_cao_nhat'] !== null) {
        $xep_loai = 'Chưa đạt';
    } else {
        $xep_loai = 'Đang học';
    }

    fputcsv($output, [
        $index + 1,
        $r['ten_hv'],
        $r['email'],
        $r['ten_kh'],
        $r['ten_gv'],
        date('d/m/Y', strtotime($r['ngay_dang_ky'])),
        $r['phan_tram_tien_do'] . '%',
        $diem,
        $xep_loai
    ]);
}

fclose($output);
exit();