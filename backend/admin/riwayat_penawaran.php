<?php
session_start();
include 'connection.php';

// Validasi login
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'masyarakat') {
    header("Location: index.php");
    exit;
}

$nama_masyarakat = $_SESSION['nama'] ?? 'Masyarakat';
$id_user = $_SESSION['id_user'];

if (isset($_POST['tawar'])) {
    $id_lelang = $_POST['id_lelang'];
    $id_barang = $_POST['id_barang'];
    $penawaran_harga = $_POST['penawaran_harga'];

    $stmt = mysqli_prepare($conn, "INSERT INTO history_lelang (id_lelang, id_barang, id_user, penawaran_harga) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiid", $id_lelang, $id_barang, $id_user, $penawaran_harga);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$query_lelang = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, tb_barang.deskripsi_barang 
                 FROM tb_lelang 
                 JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
                 WHERE tb_lelang.status = 'dibuka'";
$result_lelang = mysqli_query($conn, $query_lelang);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penawaran Saya - E-Lelang</title>
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
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="daftar_barang.php"><i class="fas fa-box me-3 fa-fw"></i> Daftar Barang Lelang</a>
        <a href="riwayat_penawaran.php" class="active"><i class="fas fa-gavel me-3 fa-fw"></i> Penawaran Saya</a>
    </div>
    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Penawaran & Riwayat Barang</h5>
            <small class="text-muted">Kelola dan ajukan penawaran harga terbaik Anda</small>
        </div>
        <span class="text-secondary small">Login sebagai: <strong class="text-dark"><?= htmlspecialchars((string)$nama_masyarakat); ?></strong></span>
    </div>

    <div class="row">
        <?php if ($result_lelang && mysqli_num_rows($result_lelang) > 0) : ?>
            <?php while ($row = mysqli_fetch_assoc($result_lelang)) : ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-custom h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-primary mb-2">
                                <i class="fas fa-box me-2"></i><?= htmlspecialchars($row['nama_barang']); ?>
                            </h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($row['deskripsi_barang']); ?></p>
                            
                            <div class="mb-3 mt-auto">
                                <span class="text-secondary small d-block">Harga Awal:</span>
                                <span class="fw-semibold text-dark fs-5">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span>
                            </div>

                            <form action="" method="POST">
                                <input type="hidden" name="id_lelang" value="<?= $row['id_lelang']; ?>">
                                <input type="hidden" name="id_barang" value="<?= $row['id_barang']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">Tawar Harga (Rp)</label>
                                    <input type="number" class="form-control form-control-sm" name="penawaran_harga" placeholder="Masukkan nominal" required min="<?= $row['harga_awal'] + 1; ?>">
                                </div>
                                <button type="submit" name="tawar" class="btn btn-primary w-100 btn-sm rounded-pill py-2 shadow-sm">
                                    <i class="fas fa-paper-plane me-1"></i> Kirim Penawaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="card card-custom text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-box-open fa-3x text-secondary mb-3 opacity-50"></i>
                        <p class="text-muted mb-0 fw-semibold">Belum ada barang lelang yang dibuka saat ini oleh petugas.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>