<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTTTQL Đào Tạo Trực Tuyến (E-Learning)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/elearning_htttql/index.php">
            <i class="bi bi-mortarboard-fill me-2"></i>HỌC CÙNG TÔI
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/elearning_htttql/index.php">Khóa học</a>
                </li>

                <?php if ($user && $user['vai_tro'] === 'hoc_vien'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/elearning_htttql/hoc_vien/khoa_hoc_cua_toi.php">Khóa học của tôi</a>
                    </li>
                <?php endif; ?>

                <?php if ($user && $user['vai_tro'] === 'giang_vien'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/elearning_htttql/giang_vien/khoa_hoc_cua_toi.php">Quản lý giảng dạy</a>
                    </li>
                <?php endif; ?>

                <?php if ($user && $user['vai_tro'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-warning" href="/elearning_htttql/admin/dashboard.php">
                            <i class="bi bi-bar-chart-fill me-1"></i>Dashboard Quản trị
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/elearning_htttql/admin/quan_ly_khoa_hoc.php">Duyệt khóa học</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/elearning_htttql/admin/quan_ly_user.php">Người dùng</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center">
                <?php if ($user): ?>
                    <span class="text-white me-3 small">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($user['ho_ten']) ?> 
                        <span class="badge bg-light text-primary ms-1"><?= strtoupper($user['vai_tro']) ?></span>
                    </span>
                    <a href="/elearning_htttql/auth/logout.php" class="btn btn-outline-light btn-sm">Đăng xuất</a>
                <?php else: ?>
                    <a href="/elearning_htttql/auth/login.php" class="btn btn-light btn-sm me-2 text-primary fw-semibold">Đăng nhập</a>
                    <a href="/elearning_htttql/auth/register.php" class="btn btn-outline-light btn-sm">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container my-4 flex-grow-1">