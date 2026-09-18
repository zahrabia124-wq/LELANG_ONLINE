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

// Mode halaman ini ada 2:
// 1) Ada id_lelang di URL (datang dari tombol "Tawar Barang Ini" di detail_barang.php) -> tampilkan form tawar untuk barang itu saja
// 2) Tidak ada id_lelang (diakses langsung dari menu sidebar "Penawaran Saya") -> tampilkan daftar barang yang SUDAH ditawar oleh user ini
$mode_tawar = isset($_GET['id_lelang']);

if ($mode_tawar) {
    $id_lelang_filter = (int) $_GET['id_lelang'];
    $query_lelang = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, 
                            tb_barang.deskripsi_barang, tb_barang.foto
                     FROM tb_lelang 
                     JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
                     WHERE tb_lelang.status = 'dibuka' AND tb_lelang.id_lelang = ?";
    $stmt_lelang = mysqli_prepare($conn, $query_lelang);
    mysqli_stmt_bind_param($stmt_lelang, "i", $id_lelang_filter);
    mysqli_stmt_execute($stmt_lelang);
    $result_lelang = mysqli_stmt_get_result($stmt_lelang);
} else {
    // Ambil barang-barang yang sudah pernah ditawar oleh user yang login ini,
    // beserta harga tawaran tertinggi milik dia untuk tiap barang, status lelang, dan siapa pemenangnya (tb_lelang.id_user)
    // Pakai subquery dulu supaya GROUP BY-nya cuma berisi kolom yang di-agregasi (kompatibel dengan sql_mode ONLY_FULL_GROUP_BY)
    $query_riwayat = "SELECT h.id_lelang, h.id_barang, h.penawaran_saya,
                              tb_barang.nama_barang, tb_barang.foto, tb_barang.harga_awal,
                              tb_lelang.status, tb_lelang.harga_akhir, tb_lelang.tgl_lelang,
                              tb_lelang.id_user AS id_pemenang
                       FROM (
                            SELECT id_lelang, id_barang, MAX(penawaran_harga) AS penawaran_saya
                            FROM history_lelang
                            WHERE id_user = ?
                            GROUP BY id_lelang, id_barang
                       ) h
                       JOIN tb_lelang ON h.id_lelang = tb_lelang.id_lelang
                       JOIN tb_barang ON h.id_barang = tb_barang.id_barang
                       ORDER BY h.id_lelang DESC";
    $stmt_riwayat = mysqli_prepare($conn, $query_riwayat);
    mysqli_stmt_bind_param($stmt_riwayat, "i", $id_user);
    mysqli_stmt_execute($stmt_riwayat);
    $result_riwayat = mysqli_stmt_get_result($stmt_riwayat);
}
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
        .card-img-barang { width: 100%; height: 180px; object-fit: cover; border-radius: 10px; background-color: #e9ecef; }
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
            <h5 class="mb-0 fw-bold text-dark"><?= $mode_tawar ? 'Ajukan Penawaran' : 'Penawaran Saya'; ?></h5>
            <small class="text-muted"><?= $mode_tawar ? 'Isi nominal penawaran terbaik Anda untuk barang ini' : 'Daftar barang yang sudah Anda tawar'; ?></small>
        </div>
        <span class="text-secondary small">Login sebagai: <strong class="text-dark"><?= htmlspecialchars((string)$nama_masyarakat); ?></strong></span>
    </div>

    <?php if ($mode_tawar) : ?>
        <!-- MODE TAWAR: form pengajuan penawaran untuk 1 barang, dari tombol "Tawar Barang Ini" -->
        <div class="row">
            <?php if ($result_lelang && mysqli_num_rows($result_lelang) > 0) : ?>
                <?php while ($row = mysqli_fetch_assoc($result_lelang)) : ?>
                    <?php
                        $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
                            ? "img/" . htmlspecialchars($row['foto'])
                            : "https://via.placeholder.com/400x300?text=No+Image";
                    ?>
                    <div class="col-md-4 mb-4">
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
                            <p class="text-muted mb-0 fw-semibold">Barang tidak ditemukan atau lelang sudah ditutup.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <!-- MODE RIWAYAT: hanya barang yang sudah pernah ditawar oleh user yang sedang login -->
        <div class="row">
            <?php if ($result_riwayat && mysqli_num_rows($result_riwayat) > 0) : ?>
                <?php while ($row = mysqli_fetch_assoc($result_riwayat)) : ?>
                    <?php
                        $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
                            ? "img/" . htmlspecialchars($row['foto'])
                            : "https://via.placeholder.com/400x300?text=No+Image";
                        $menang = ($row['status'] === 'ditutup' && (int)$row['id_pemenang'] === (int)$id_user);
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card card-custom h-100 p-3">
                            <img src="<?= $fotoPath; ?>" class="card-img-barang mb-3" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                            <div class="card-body d-flex flex-column p-0">
                                <h5 class="card-title fw-bold text-primary mb-2">
                                    <i class="fas fa-box me-2"></i><?= htmlspecialchars($row['nama_barang']); ?>
                                </h5>

                                <div class="mb-2">
                                    <span class="text-secondary small d-block">Harga Awal:</span>
                                    <span class="fw-semibold text-dark">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span>
                                </div>

                                <div class="mb-3">
                                    <span class="text-secondary small d-block">Penawaran Saya:</span>
                                    <span class="fw-bold text-primary fs-5">Rp <?= number_format($row['penawaran_saya'], 0, ',', '.'); ?></span>
                                </div>

                                <div class="mt-auto d-flex align-items-center gap-2 flex-wrap">
                                    <?php if ($row['status'] === 'dibuka') : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Lelang Dibuka</span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">Lelang Ditutup</span>
                                        <?php if ($menang) : ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">
                                                <i class="fas fa-trophy me-1"></i> Anda Menang
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="col-12">
                    <div class="card card-custom text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-gavel fa-3x text-secondary mb-3 opacity-50"></i>
                            <p class="text-muted mb-1 fw-semibold">Anda belum pernah mengajukan penawaran.</p>
                            <small class="text-muted">Silakan pilih barang di menu <a href="daftar_barang.php">Daftar Barang Lelang</a> untuk mulai menawar.</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>