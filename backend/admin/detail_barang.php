<?php
session_start();
include 'connection.php';

// Validasi login - kalau belum login, tendang ke halaman login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'masyarakat') {
    header("Location: index.php");
    exit;
}

$nama_masyarakat = $_SESSION['nama'] ?? 'Pengguna';

// Ambil id_lelang dari URL
$id_lelang = isset($_GET['id_lelang']) ? (int) $_GET['id_lelang'] : 0;

if ($id_lelang <= 0) {
    header("Location: daftar_barang.php");
    exit;
}

// Ambil detail barang berdasarkan id_lelang
$query = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, 
                 tb_barang.deskripsi_barang, tb_barang.foto
          FROM tb_lelang 
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
          WHERE tb_lelang.id_lelang = ? AND tb_lelang.status = 'dibuka'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_lelang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Kalau barang tidak ditemukan atau statusnya bukan 'dibuka', balik ke daftar
if (!$row) {
    header("Location: daftar_barang.php");
    exit;
}

$fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
    ? "img/" . htmlspecialchars($row['foto'])
    : "https://via.placeholder.com/600x400?text=No+Image";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang - <?= htmlspecialchars($row['nama_barang']); ?></title>
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
        .foto-detail { width: 100%; max-height: 420px; object-fit: cover; border-radius: 12px; background-color: #e9ecef; }
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
            <a href="daftar_barang.php" class="text-decoration-none text-secondary small">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Barang
            </a>
            <h5 class="mb-0 fw-bold text-dark mt-1">Detail Barang Lelang</h5>
        </div>
        <span class="text-secondary small">Login sebagai: <strong class="text-dark"><?= htmlspecialchars($nama_masyarakat); ?></strong></span>
    </div>

    <div class="card card-custom p-4">
        <div class="row g-4">
            <div class="col-md-6">
                <img src="<?= $fotoPath; ?>" class="foto-detail" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
            </div>
            <div class="col-md-6 d-flex flex-column">
                <h3 class="fw-bold text-primary mb-3">
                    <i class="fas fa-box me-2"></i><?= htmlspecialchars($row['nama_barang']); ?>
                </h3>
                <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($row['deskripsi_barang'])); ?></p>

                <div class="mb-4 mt-auto">
                    <span class="text-secondary small d-block">Harga Awal:</span>
                    <span class="fw-bold text-dark fs-3">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span>
                </div>

                <!-- Baru di halaman detail inilah tombol Tawar Barang Ini muncul -->
                <a href="riwayat_penawaran.php?id_lelang=<?= $row['id_lelang']; ?>" class="btn btn-primary btn-lg rounded-pill py-2 shadow-sm">
                    <i class="fas fa-gavel me-1"></i> Tawar Barang Ini
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>