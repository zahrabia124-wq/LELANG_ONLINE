<?php
session_start();
include 'connection.php';

// Validasi Login - hanya masyarakat yang boleh akses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'masyarakat') {
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'] ?? ($_SESSION['id_masyarakat'] ?? null);

// Ambil data lelang yang MASIH DIBUKA, join dengan data barang
$query = "SELECT tb_lelang.id_lelang, tb_lelang.tgl_lelang, tb_lelang.harga_akhir, tb_lelang.status,
                 tb_barang.id_barang, tb_barang.nama_barang, tb_barang.deskripsi_barang,
                 tb_barang.harga_awal, tb_barang.foto
          FROM tb_lelang
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
          WHERE tb_lelang.status = 'dibuka'
          ORDER BY tb_lelang.tgl_lelang ASC";
$result = mysqli_query($conn, $query);

// Pesan notifikasi (opsional, misal setelah menawar / redirect dari halaman lain)
$notif = '';
$tipe  = 'success';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'tawar_sukses': $notif = 'Penawaran Anda berhasil dikirim.'; break;
        case 'tawar_gagal':  $notif = 'Penawaran gagal, silakan coba lagi.'; $tipe = 'danger'; break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang Lelang - E-Lelang</title>
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
        .sidebar h4 { letter-spacing: 1px; }
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
        .main-content { margin-left: 260px; padding: 30px; }

        .card-lelang {
            border: none;
            border-radius: 14px;
            box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08);
            background: #ffffff;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }
        .card-lelang:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }
        .card-img-lelang {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background-color: #e9ecef;
        }
        .badge-status {
            font-size: 0.75rem;
            padding: 6px 12px;
        }
        .harga-box {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 10px 14px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>

    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="daftar_lelang.php" class="active"><i class="fas fa-list me-3 fa-fw"></i> Daftar Barang Lelang</a>
        <a href="riwayat_penawaran.php"><i class="fas fa-gavel me-3 fa-fw"></i> Penawaran Saya</a>
    </div>

    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Daftar Barang Lelang</h5>
            <small class="text-muted">Barang yang sedang dibuka untuk penawaran saat ini</small>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
            <i class="fas fa-circle fa-2xs me-1"></i> <?= mysqli_num_rows($result); ?> Lelang Aktif
        </span>
    </div>

    <?php if ($notif !== '') : ?>
        <div class="alert alert-<?= $tipe; ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-circle-check me-2"></i> <?= htmlspecialchars($notif); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (mysqli_num_rows($result) > 0) : ?>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <?php
                    $harga_tertinggi = !empty($row['harga_akhir']) ? $row['harga_akhir'] : $row['harga_awal'];
                    $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
                        ? "img/" . htmlspecialchars($row['foto'])
                        : "https://via.placeholder.com/400x300?text=No+Image";
                ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card card-lelang">
                        <img src="<?= $fotoPath; ?>" alt="<?= htmlspecialchars($row['nama_barang']); ?>" class="card-img-lelang">
                        <div class="p-3 d-flex flex-column h-100">
                            <span class="badge bg-primary-subtle text-primary badge-status mb-2 align-self-start">
                                <i class="fas fa-bolt me-1"></i> Sedang Dilelang
                            </span>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_barang']); ?></h6>
                            <p class="text-muted small mb-3 text-truncate"><?= htmlspecialchars($row['deskripsi_barang']); ?></p>

                            <div class="harga-box mb-3">
                                <small class="text-muted d-block">Harga Tertinggi Saat Ini</small>
                                <span class="fw-bold text-success fs-6">Rp <?= number_format($harga_tertinggi, 0, ',', '.'); ?></span>
                            </div>

                            <small class="text-muted mb-3">
                                <i class="far fa-calendar-alt me-1"></i>
                                Berakhir: <?= date('d M Y', strtotime($row['tgl_lelang'])); ?>
                            </small>

                            <a href="detail_lelang.php?id=<?= $row['id_lelang']; ?>" class="btn btn-primary rounded-pill mt-auto fw-semibold">
                                <i class="fas fa-gavel me-2"></i> Ikuti Lelang
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="card card-lelang p-5 text-center text-muted">
                    <i class="fas fa-box-open fa-3x text-secondary mb-3 opacity-50"></i>
                    <p class="mb-0">Belum ada barang lelang yang sedang dibuka saat ini.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>