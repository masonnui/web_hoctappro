<?php
require_once '../config/db.php';
yeuCauQuyen(['admin']);

// 1. Truy vấn các chỉ số KPI tổng quan
$tong_hoc_vien = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'hoc_vien'")->fetchColumn();
$tong_giang_vien = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'giang_vien'")->fetchColumn();
$tong_khoa_hoc = $pdo->query("SELECT COUNT(*) FROM khoa_hoc WHERE trang_thai_duyet = 'da_duyet'")->fetchColumn();
$khoa_cho_duyet = $pdo->query("SELECT COUNT(*) FROM khoa_hoc WHERE trang_thai_duyet = 'cho_duyet'")->fetchColumn();
$tong_luot_dk = $pdo->query("SELECT COUNT(*) FROM dang_ky_hoc")->fetchColumn();
$diem_tb_he_thong = $pdo->query("SELECT ROUND(AVG(diem_so), 2) FROM ket_qua_thi")->fetchColumn() ?: 0;

// 2. Dữ liệu biểu đồ 1: Số lượng học viên đăng ký & Tiến độ trung bình theo từng khóa học
$sql_chart1 = "SELECT kh.tieu_de, 
               COUNT(dk.id) AS so_hv,
               COALESCE(ROUND(AVG(dk.phan_tram_tien_do), 1), 0) AS tien_do_tb
               FROM khoa_hoc kh
               LEFT JOIN dang_ky_hoc dk ON kh.id = dk.khoa_hoc_id
               WHERE kh.trang_thai_duyet = 'da_duyet'
               GROUP BY kh.id, kh.tieu_de";
$data_chart1 = $pdo->query($sql_chart1)->fetchAll();

$labels_kh = [];
$values_hv = [];
$values_tiendo = [];
foreach ($data_chart1 as $row) {
    $labels_kh[] = mb_strimwidth($row['tieu_de'], 0, 28, '...');
    $values_hv[] = (int)$row['so_hv'];
    $values_tiendo[] = (float)$row['tien_do_tb'];
}

// 3. Dữ liệu biểu đồ 2: Phân loại kết quả thi (Đạt chứng nhận >= 8.0 vs Chưa đạt < 8.0)
$so_dat = (int)$pdo->query("SELECT COUNT(*) FROM ket_qua_thi WHERE diem_so >= 8")->fetchColumn();
$so_chua_dat = (int)$pdo->query("SELECT COUNT(*) FROM ket_qua_thi WHERE diem_so < 8")->fetchColumn();

// 4. Bảng thống kê chi tiết hiệu quả đào tạo từng khóa học
$sql_bang = "SELECT kh.id, kh.tieu_de, nd.ho_ten AS giang_vien,
             COUNT(DISTINCT dk.hoc_vien_id) AS tong_hv,
             COALESCE(ROUND(AVG(dk.phan_tram_tien_do), 1), 0) AS tien_do_tb,
             COALESCE(ROUND(AVG(kq.diem_so), 2), 0) AS diem_tb,
             COUNT(DISTINCT CASE WHEN kq.diem_so >= 8 THEN kq.hoc_vien_id END) AS hv_dat_chung_nhan
             FROM khoa_hoc kh
             JOIN nguoi_dung nd ON kh.giang_vien_id = nd.id
             LEFT JOIN dang_ky_hoc dk ON kh.id = dk.khoa_hoc_id
             LEFT JOIN ket_qua_thi kq ON kh.id = kq.khoa_hoc_id
             WHERE kh.trang_thai_duyet = 'da_duyet'
             GROUP BY kh.id, kh.tieu_de, nd.ho_ten
             ORDER BY tong_hv DESC";
$bao_cao_khoa_hoc = $pdo->query($sql_bang)->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h3 class="fw-bold text-primary mb-1"><i class="bi bi-speedometer2 me-2"></i>Dashboard Quản trị & Thống kê Đào tạo</h3>
        <p class="text-muted small mb-0">Hệ thống tổng hợp dữ liệu hỗ trợ đánh giá chất lượng và ra quyết định quản lý.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($khoa_cho_duyet > 0): ?>
            <a href="quan_ly_khoa_hoc.php" class="btn btn-warning fw-semibold">
                <i class="bi bi-bell-fill me-1"></i><?= $khoa_cho_duyet ?> khóa chờ duyệt
            </a>
        <?php endif; ?>
        <a href="xuat_bao_cao.php" class="btn btn-success fw-semibold">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Xuất Báo cáo Excel (.CSV)
        </a>
    </div>
</div>

<!-- 4 Thẻ chỉ số KPI tổng quan -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Tổng Học viên / Giảng viên</div>
                <div class="fs-3 fw-bold text-primary my-1"><?= $tong_hoc_vien ?> <span class="fs-6 text-muted fw-normal">HV / <?= $tong_giang_vien ?> GV</span></div>
                <div class="small text-success"><i class="bi bi-people-fill me-1"></i>Tài khoản đang hoạt động</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Khóa học đang mở</div>
                <div class="fs-3 fw-bold text-success my-1"><?= $tong_khoa_hoc ?> <span class="fs-6 text-muted fw-normal">khóa</span></div>
                <div class="small text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i><?= $khoa_cho_duyet ?> khóa đang chờ duyệt</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Tổng lượt đăng ký học</div>
                <div class="fs-3 fw-bold text-info my-1"><?= $tong_luot_dk ?> <span class="fs-6 text-muted fw-normal">lượt</span></div>
                <div class="small text-muted"><i class="bi bi-journal-check me-1"></i>Dữ liệu ghi nhận toàn hệ thống</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Điểm thi trung bình</div>
                <div class="fs-3 fw-bold text-warning my-1"><?= $diem_tb_he_thong ?> <span class="fs-6 text-muted fw-normal">/ 10 điểm</span></div>
                <div class="small text-muted"><i class="bi bi-award me-1"></i>Chuẩn đạt chứng nhận: &ge; 8.0</div>
            </div>
        </div>
    </div>
</div>

<!-- Khu vực Biểu đồ thống kê -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Biểu đồ Số lượng học viên & Tiến độ hoàn thành trung bình (%) theo Khóa học</div>
            <div class="card-body">
                <canvas id="chartKhoaHoc" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Tỷ lệ Kết quả Đánh giá cuối khóa</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartKetQua"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Bảng tổng hợp báo cáo quản trị -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
        <span>Bảng Tổng hợp Hiệu quả Đào tạo theo Khóa học</span>
        <span class="badge bg-primary">Cập nhật thời gian thực</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên khóa học</th>
                    <th>Giảng viên phụ trách</th>
                    <th class="text-center">Số học viên</th>
                    <th class="text-center">Tiến độ TB</th>
                    <th class="text-center">Điểm thi TB</th>
                    <th class="text-center">Đạt chứng nhận</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bao_cao_khoa_hoc as $bc): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($bc['tieu_de']) ?></td>
                        <td><?= htmlspecialchars($bc['giang_vien']) ?></td>
                        <td class="text-center fw-bold text-primary"><?= $bc['tong_hv'] ?></td>
                        <td class="text-center"><?= $bc['tien_do_tb'] ?>%</td>
                        <td class="text-center fw-bold"><?= $bc['diem_tb'] > 0 ? $bc['diem_tb'] : '-' ?></td>
                        <td class="text-center">
                            <span class="badge bg-success"><?= $bc['hv_dat_chung_nhan'] ?> học viên</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biểu đồ cột kết hợp đường: Số học viên & Tiến độ trung bình
const ctx1 = document.getElementById('chartKhoaHoc').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_kh) ?>,
        datasets: [
            {
                label: 'Số học viên đăng ký',
                data: <?= json_encode($values_hv) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.75)',
                borderRadius: 5,
                yAxisID: 'y'
            },
            {
                label: 'Tiến độ hoàn thành TB (%)',
                data: <?= json_encode($values_tiendo) ?>,
                type: 'line',
                borderColor: '#198754',
                backgroundColor: '#198754',
                borderWidth: 2,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Số học viên' } },
            y1: { beginAtZero: true, max: 100, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Tiến độ (%)' } }
        }
    }
});

// Biểu đồ tròn: Tỷ lệ Đạt / Chưa đạt
const ctx2 = document.getElementById('chartKetQua').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Đạt chứng nhận (>= 8.0)', 'Chưa đạt (< 8.0)'],
        datasets: [{
            data: [<?= $so_dat ?>, <?= $so_chua_dat ?>],
            backgroundColor: ['#198754', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include '../includes/footer.php'; ?>