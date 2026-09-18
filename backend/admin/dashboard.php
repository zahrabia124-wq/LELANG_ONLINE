<?php
session_start();
include 'connection.php';

// Cek apakah user sudah login (bisa sebagai petugas atau masyarakat)
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role']; // Isinya 'petugas' atau 'masyarakat'

// Jika yang login adalah Petugas / Admin
if ($role === 'petugas') {
    $id_level = $_SESSION['id_level'] ?? 2; 

    // Hitung statistik khusus petugas dengan aman
    $t_barang = 0;
    $t_lelang = 0;
    $t_user   = 0;
    $t_dibuka = 0;
    $t_ditutup = 0;

    $q_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang");
    if ($q_barang) {
        $d_barang = mysqli_fetch_assoc($q_barang);
        $t_barang = $d_barang['total'];
    }

    $q_lelang = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang");
    if ($q_lelang) {
        $d_lelang = mysqli_fetch_assoc($q_lelang);
        $t_lelang = $d_lelang['total'];
    }

    $q_user = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_masyarakat");
    if ($q_user) {
        $d_user = mysqli_fetch_assoc($q_user);
        $t_user = $d_user['total'];
    }

    $q_dibuka = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'dibuka'");
    if ($q_dibuka) {
        $t_dibuka = mysqli_fetch_assoc($q_dibuka)['total'];
    }

    $q_ditutup = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'ditutup'");
    if ($q_ditutup) {
        $t_ditutup = mysqli_fetch_assoc($q_ditutup)['total'];
    }
}

// Jika yang login adalah Masyarakat -> hitung statistik pribadinya
if ($role === 'masyarakat') {
    $id_user = $_SESSION['id_user'] ?? 0;

    $t_barang_dibuka  = 0;
    $t_penawaran_saya = 0;
    $t_menang         = 0;

    $q_dibuka = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'dibuka'");
    if ($q_dibuka) {
        $t_barang_dibuka = mysqli_fetch_assoc($q_dibuka)['total'];
    }

    $stmt_tawar = mysqli_prepare($conn, "SELECT COUNT(DISTINCT id_lelang) as total FROM history_lelang WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_tawar, "i", $id_user);
    mysqli_stmt_execute($stmt_tawar);
    $r_tawar = mysqli_stmt_get_result($stmt_tawar);
    if ($r_tawar) {
        $t_penawaran_saya = mysqli_fetch_assoc($r_tawar)['total'];
    }

    $stmt_menang = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'ditutup' AND id_user = ?");
    mysqli_stmt_bind_param($stmt_menang, "i", $id_user);
    mysqli_stmt_execute($stmt_menang);
    $r_menang = mysqli_stmt_get_result($stmt_menang);
    if ($r_menang) {
        $t_menang = mysqli_fetch_assoc($r_menang)['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= ($role === 'petugas') ? (($id_level == 1) ? 'Administrator' : 'Petugas') : 'Masyarakat'; ?> - E-Lelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar { 
            height: 100vh; 
            background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%); 
            color: white; 
            position: fixed; 
            width: 260px; 
            top: 0; 
            left: 0; 
            padding-top: 25px; 
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar h4 {
            letter-spacing: 1px;
        }
        .sidebar a { 
            color: rgba(255, 255, 255, 0.85); 
            text-decoration: none; 
            display: flex;
            align-items: center;
            padding: 12px 22px; 
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin: 4px 12px;
            border-radius: 8px;
        }
        .sidebar a:hover, .sidebar a.active { 
            background-color: rgba(255, 255, 255, 0.15); 
            color: white;
            transform: translateX(4px);
        }
        .main-content { 
            margin-left: 260px; 
            padding: 30px; 
        }
        .card-stat {
            border: none;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #ffffff;
        }
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .icon-box {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 1.75rem;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0d6efd 0%, #6ea8fe 100%);
            border-radius: 20px;
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .hero-banner i.bg-icon {
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 9rem;
            opacity: 0.15;
        }
        .quick-link {
            border: none;
            border-radius: 12px;
            background: #ffffff;
            text-decoration: none;
            color: #212529;
            display: flex;
            align-items: center;
            padding: 18px;
            transition: all 0.25s ease;
        }
        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            color: #0d6efd;
        }
        .quick-link .icon-box { width: 50px; height: 50px; font-size: 1.25rem; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    
    <div class="flex-grow-1">
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>

        <!-- MENU JIKA YANG LOGIN PETUGAS / ADMIN -->
        <?php if ($role === 'petugas') : ?>
            <a href="pendataan_barang.php"><i class="fas fa-box me-3 fa-fw"></i> Pendataan Barang</a>
            <a href="kelola_lelang.php"><i class="fas fa-balance-scale me-3 fa-fw"></i> Kelola Lelang</a>
            <a href="history_lelang.php"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
            <a href="laporan.php"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
            <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

            <!-- MENU KHUSUS ADMIN -->
            <?php if ($id_level == 1) : ?>
                <hr class="text-white-50 mx-4 my-3">
                <small class="text-warning px-4 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">MENU ADMIN</small>
                <a href="registrasi_petugas.php"><i class="fas fa-user-shield me-3 fa-fw"></i> Registrasi Petugas </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- MENU JIKA YANG LOGIN MASYARAKAT -->
        <?php if ($role === 'masyarakat') : ?>
            <a href="daftar_barang.php"><i class="fas fa-list me-3 fa-fw"></i> Daftar Barang Lelang</a>
            <a href="riwayat_penawaran.php"><i class="fas fa-gavel me-3 fa-fw"></i> Penawaran Saya</a>
        <?php endif; ?>
    </div>
    
    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Bar Header -->
    <div class="bg-white p-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold text-dark">
                <?php if ($role === 'petugas'): ?>
                    <?= ($id_level == 1) ? 'Dashboard Administrator' : 'Dashboard Petugas'; ?>
                <?php else: ?>
                    Dashboard Masyarakat (Penawar)
                <?php endif; ?>
            </h4>
            <p class="text-muted mb-0">Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna'); ?></strong></p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-normal">
                <i class="fas fa-user-tag text-primary me-1"></i> 
                <?php if ($role === 'petugas'): ?>
                    <?= ($id_level == 1) ? 'Administrator' : 'Petugas'; ?>
                <?php else: ?>
                    Masyarakat
                <?php endif; ?>
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="fas fa-circle fa-2xs me-1"></i> Online
            </span>
        </div>
    </div>

    <!-- KONTEN BERBEDA BERDASARKAN ROLE -->
    <?php if ($role === 'petugas'): ?>
        <!-- Statistik Cards khusus Petugas/Admin -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-primary border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Total Barang</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_barang; ?></h2>
                        </div>
                        <div class="icon-box bg-primary-subtle text-primary">
                            <i class="fas fa-box-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-success border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Sesi Lelang</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_lelang; ?></h2>
                        </div>
                        <div class="icon-box bg-success-subtle text-success">
                            <i class="fas fa-gavel"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-warning border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Masyarakat Terdaftar</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_user; ?></h2>
                        </div>
                        <div class="icon-box bg-warning-subtle text-warning">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown status lelang + akses cepat -->
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card card-stat shadow-sm p-4 h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-chart-pie text-primary me-2"></i>Status Lelang</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary"><i class="fas fa-circle text-success me-2" style="font-size:0.6rem;"></i>Dibuka</span>
                        <span class="fw-bold text-dark"><?= $t_dibuka; ?></span>
                    </div>
                    <div class="progress mb-4" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar bg-success" style="width: <?= $t_lelang > 0 ? round($t_dibuka / $t_lelang * 100) : 0; ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary"><i class="fas fa-circle text-secondary me-2" style="font-size:0.6rem;"></i>Ditutup</span>
                        <span class="fw-bold text-dark"><?= $t_ditutup; ?></span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar bg-secondary" style="width: <?= $t_lelang > 0 ? round($t_ditutup / $t_lelang * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card card-stat shadow-sm p-4 h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-bolt text-primary me-2"></i>Akses Cepat</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="pendataan_barang.php" class="quick-link">
                                <div class="icon-box bg-primary-subtle text-primary me-3"><i class="fas fa-box"></i></div>
                                <span class="fw-semibold">Tambah Barang</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="kelola_lelang.php" class="quick-link">
                                <div class="icon-box bg-success-subtle text-success me-3"><i class="fas fa-balance-scale"></i></div>
                                <span class="fw-semibold">Kelola Lelang</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="laporan.php" class="quick-link">
                                <div class="icon-box bg-warning-subtle text-warning me-3"><i class="fas fa-file-alt"></i></div>
                                <span class="fw-semibold">Generate Laporan</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="pendataan_masyarakat.php" class="quick-link">
                                <div class="icon-box bg-info-subtle text-info me-3"><i class="fas fa-users"></i></div>
                                <span class="fw-semibold">Data Masyarakat</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tampilan khusus Masyarakat -->
        <div class="hero-banner mb-4">
            <i class="fas fa-gavel bg-icon"></i>
            <h3 class="fw-bold mb-2">Selamat Datang di Portal Lelang Umum</h3>
            <p class="mb-4" style="max-width: 600px; opacity: 0.95;">Anda dapat melihat daftar barang yang sedang dilelang, mengajukan harga penawaran tertinggi, dan memantau status lelang secara real-time.</p>
            <a href="daftar_barang.php" class="btn btn-light text-primary px-4 py-2 fw-semibold rounded-pill shadow-sm">
                <i class="fas fa-gavel me-2"></i> Mulai Lihat Barang Lelang
            </a>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-primary border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Barang Sedang Dibuka</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_barang_dibuka; ?></h2>
                        </div>
                        <div class="icon-box bg-primary-subtle text-primary">
                            <i class="fas fa-box-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-info border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Penawaran Saya</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_penawaran_saya; ?></h2>
                        </div>
                        <div class="icon-box bg-info-subtle text-info">
                            <i class="fas fa-gavel"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat shadow-sm p-4 border-start border-warning border-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-semibold mb-2">Lelang Dimenangkan</h6>
                            <h2 class="fw-bold mb-0 text-dark display-6"><?= $t_menang; ?></h2>
                        </div>
                        <div class="icon-box bg-warning-subtle text-warning">
                            <i class="fas fa-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>