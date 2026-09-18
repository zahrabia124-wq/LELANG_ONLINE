<?php
session_start();
include 'connection.php';

// Validasi login - kalau belum login, tendang ke halaman login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'masyarakat') {
    header("Location: index.php");
    exit;
}

$nama_masyarakat = $_SESSION['nama'] ?? 'Pengguna';
// Ambil data barang yang status lelangnya 'dibuka' (termasuk kolom foto)
$query = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, 
                 tb_barang.deskripsi_barang, tb_barang.foto
          FROM tb_lelang 
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
          WHERE tb_lelang.status = 'dibuka'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang Lelang - Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%); color: white; position: fixed; width: 260px; top: 0; left: 0; padding-top: 25px; box-shadow: 4px 0 10px rgba(0,0,0,0.05); overflow-y: auto; }
        .sidebar h4 { letter-spacing: 1px; }
        .sidebar a { color: rgba(255, 255, 255, 0.85); text-decoration: none; display: flex; align-items: center; padding: 12px 22px; font-size: 0.95rem; transition: all 0.3s ease; margin: 4px 12px; border-radius: 8px; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255, 255, 255, 0.15); color: white; transform: translateX(4px); }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background: #ffffff; }
        .card-img-barang { width: 100%; height: 180px; object-fit: cover; border-radius: 10px; background-color: #e9ecef; }
        .card-link { text-decoration: none; color: inherit; }
        .card-link:hover .card-custom { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); transform: translateY(-2px); }
        .card-custom { transition: all 0.2s ease; }
    </style>
</head>
<body>

<!-- Sidebar Lengkap -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="daftar_barang.php" class="active"><i class="fas fa-box me-3 fa-fw"></i> Daftar Barang Lelang</a>
        <a href="riwayat_penawaran.php"><i class="fas fa-gavel me-3 fa-fw"></i> Penawaran Saya</a>
    </div>
    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Daftar Barang Lelang</h5>
            <small class="text-muted">Pilih barang yang sedang dibuka untuk mulai mengajukan penawaran</small>
        </div>
        <span class="text-secondary small">Login sebagai: <strong class="text-dark"><?= htmlspecialchars($nama_masyarakat); ?></strong></span>
    </div>

    <div class="row">
        <?php if ($result && mysqli_num_rows($result) > 0) : ?>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <?php
                    $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
                        ? "img/" . htmlspecialchars($row['foto'])
                        : "https://via.placeholder.com/400x300?text=No+Image";
                ?>
                <div class="col-md-4 mb-4">
                    <!-- Seluruh kartu jadi link menuju halaman detail barang -->
                    <a href="detail_barang.php?id_lelang=<?= $row['id_lelang']; ?>" class="card-link">
                        <div class="card card-custom h-100 p-3">
                            <img src="<?= $fotoPath; ?>" class="card-img-barang mb-3" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                            <div class="card-body d-flex flex-column p-0">
                                <h5 class="card-title fw-bold text-primary mb-2">
                                    <i class="fas fa-box me-2"></i><?= htmlspecialchars($row['nama_barang']); ?>
                                </h5>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($row['deskripsi_barang']); ?></p>

                                <div class="mb-3 mt-auto">
                                    <span class="text-secondary small d-block">Harga Awal:</span>
                                    <span class="fw-semibold text-dark fs-5">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span>
                                </div>

                                <!-- Tombol Tawar Barang Ini dipindah ke halaman detail_barang.php -->
                                <span class="btn btn-outline-primary w-100 btn-sm rounded-pill py-2">
                                    <i class="fas fa-eye me-1"></i> Lihat Detail
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="card card-custom text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3 opacity-50"></i>
                        <p class="text-muted mb-1 fw-semibold">Belum ada barang lelang yang dibuka oleh petugas saat ini.</p>
                        <small class="text-muted">Silakan cek kembali nanti atau hubungi petugas.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>