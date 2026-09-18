<?php
session_start();
include 'connection.php';

// Validasi Login
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level']; 

// Ambil data laporan lelang digabung dengan tb_barang dan tb_masyarakat
// (foto & deskripsi_barang ditambahkan supaya bisa ditampilkan di modal detail)
$query = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, 
                 tb_barang.deskripsi_barang, tb_barang.foto, tb_masyarakat.nama_lengkap 
          FROM tb_lelang 
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
          LEFT JOIN tb_masyarakat ON tb_lelang.id_user = tb_masyarakat.id_user 
          ORDER BY tb_lelang.id_lelang DESC";
$result = mysqli_query($conn, $query);

// Simpan semua baris ke array supaya bisa dipakai dua kali:
// 1) untuk isi tabel, 2) untuk generate modal detail masing-masing barang
$laporan_data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $laporan_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Lelang - E-Lelang</title>
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
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background: #ffffff;
        }
        .table th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            vertical-align: middle;
            color: #212529;
        }
        .btn-action {
            padding: 0.35rem 0.65rem;
            font-size: 0.813rem;
            border-radius: 6px;
        }
        .foto-detail-modal {
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 12px;
            background-color: #e9ecef;
        }
        .img-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Styling khusus saat halaman dicetak (Print) */
        @media print {
            .sidebar, .btn-print-hide, .col-aksi {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .card-custom {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column btn-print-hide">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    
    <div class="flex-grow-1">
        <!-- MENU UTAMA -->
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="pendataan_barang.php"><i class="fas fa-box me-3 fa-fw"></i> Pendataan Barang</a>
        <a href="kelola_lelang.php"><i class="fas fa-balance-scale me-3 fa-fw"></i> Kelola Lelang</a>
        <a href="history_lelang.php"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
        <a href="laporan.php" class="active"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
        <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

        <!-- MENU KHUSUS ADMIN -->
        <?php if ($id_level == 1) : ?>
            <hr class="text-white-50 mx-4 my-3">
            <small class="text-warning px-4 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">MENU ADMIN</small>
            <a href="registrasi_petugas.php"><i class="fas fa-user-shield me-3 fa-fw"></i> Registrasi Petugas</a>
        <?php endif; ?>
    </div>
    
    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Header Section -->
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Laporan Hasil Lelang</h5>
            <small class="text-muted">
                <?php if ($id_level == 1) : ?>
                    Status Login: <span class="badge bg-danger">Administrator</span>
                <?php else : ?>
                    Status Login: <span class="badge bg-success">Petugas</span>
                <?php endif; ?>
                &bull; Rekapitulasi data seluruh pelaksanaan lelang
            </small>
        </div>
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 shadow-sm btn-print-hide">
            <i class="fas fa-print me-2"></i> Cetak Laporan
        </button>
    </div>

    <!-- Tabel Laporan -->
    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 text-center" style="width: 5%;">No</th>
                        <th class="py-3" style="width: 20%;">Nama Barang</th>
                        <th class="py-3" style="width: 13%;">Tgl Lelang</th>
                        <th class="py-3" style="width: 14%;">Harga Awal</th>
                        <th class="py-3" style="width: 14%;">Harga Akhir</th>
                        <th class="py-3" style="width: 16%;">Pemenang</th>
                        <th class="py-3 text-center" style="width: 9%;">Status</th>
                        <th class="py-3 text-center col-aksi" style="width: 9%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($laporan_data) > 0) : ?>
                        <?php $no = 1; foreach ($laporan_data as $row) : ?>
                        <tr>
                            <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']); ?></td>
                            <td><span class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= $row['tgl_lelang']; ?></span></td>
                            <td><span class="text-secondary fw-semibold">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span></td>
                            <td><span class="text-success fw-semibold"><?= $row['harga_akhir'] ? 'Rp ' . number_format($row['harga_akhir'], 0, ',', '.') : '-'; ?></span></td>
                            <td><?= isset($row['nama_lengkap']) && $row['nama_lengkap'] ? '<span class="fw-semibold text-dark"><i class="fas fa-user-check text-success me-1"></i> ' . htmlspecialchars($row['nama_lengkap']) . '</span>' : '<span class="text-muted fst-italic">Belum ada</span>'; ?></td>
                            <td class="text-center">
                                <?php if ($row['status'] == 'dibuka') : ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Dibuka</span>
                                <?php else : ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">Ditutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center col-aksi">
                                <!-- Tombol Detail sekarang membuka modal, tidak pindah halaman -->
                                <button type="button" class="btn btn-info btn-action text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_lelang']; ?>">
                                    <i class="fas fa-history"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <div class="py-3">
                                    <i class="fas fa-file-alt fa-3x text-secondary mb-3 opacity-50"></i>
                                    <p class="mb-0">Belum ada data laporan lelang yang tersedia.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail per barang -->
<?php foreach ($laporan_data as $row) : ?>
    <?php
        $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
            ? "img/" . htmlspecialchars($row['foto'])
            : "https://via.placeholder.com/500x350?text=No+Image";

        // Ambil riwayat penawaran untuk barang/sesi lelang ini
        $query_hist = "SELECT history_lelang.*, tb_masyarakat.nama_lengkap 
                        FROM history_lelang 
                        LEFT JOIN tb_masyarakat ON history_lelang.id_user = tb_masyarakat.id_user
                        WHERE history_lelang.id_lelang = ?
                        ORDER BY history_lelang.penawaran_harga DESC";
        $stmt_hist = mysqli_prepare($conn, $query_hist);
        mysqli_stmt_bind_param($stmt_hist, "i", $row['id_lelang']);
        mysqli_stmt_execute($stmt_hist);
        $result_hist = mysqli_stmt_get_result($stmt_hist);
    ?>
    <div class="modal fade" id="modalDetail<?= $row['id_lelang']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 14px; border: none;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fas fa-box text-primary me-2"></i><?= htmlspecialchars($row['nama_barang']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4 mb-4">
                        <div class="col-md-5">
                            <img src="<?= $fotoPath; ?>" class="foto-detail-modal" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                        </div>
                        <div class="col-md-7">
                            <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($row['deskripsi_barang'])); ?></p>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <span class="text-secondary small d-block">Harga Awal</span>
                                    <span class="fw-bold text-dark">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="col-6 mb-3">
                                    <span class="text-secondary small d-block">Harga Akhir</span>
                                    <span class="fw-bold text-success"><?= $row['harga_akhir'] ? 'Rp ' . number_format($row['harga_akhir'], 0, ',', '.') : '-'; ?></span>
                                </div>
                                <div class="col-6 mb-3">
                                    <span class="text-secondary small d-block">Tgl Lelang</span>
                                    <span class="fw-semibold text-dark"><i class="far fa-calendar-alt me-1"></i> <?= $row['tgl_lelang']; ?></span>
                                </div>
                                <div class="col-6 mb-3">
                                    <span class="text-secondary small d-block">Status</span>
                                    <?php if ($row['status'] == 'dibuka') : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Dibuka</span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">Ditutup</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (isset($row['nama_lengkap']) && $row['nama_lengkap']) : ?>
                                <span class="fw-semibold text-dark"><i class="fas fa-user-check text-success me-1"></i> Pemenang: <?= htmlspecialchars($row['nama_lengkap']); ?></span>
                            <?php else : ?>
                                <span class="text-muted fst-italic">Belum ada pemenang</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-list me-2"></i>Riwayat Penawaran</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="py-2">Penawar</th>
                                    <th class="py-2">Harga Ditawar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_hist && mysqli_num_rows($result_hist) > 0) : ?>
                                    <?php while ($h = mysqli_fetch_assoc($result_hist)) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($h['nama_lengkap'] ?? 'Masyarakat'); ?></td>
                                            <td><span class="text-success fw-semibold">Rp <?= number_format($h['penawaran_harga'], 0, ',', '.'); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Belum ada penawaran untuk barang ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>