<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level']; 
$query = "SELECT * FROM tb_masyarakat ORDER BY id_user DESC"; 
$result = mysqli_query($conn, $query);

// Simpan ke array supaya bisa dipakai 2x: buat baris tabel & buat generate modal detail
$masyarakat_data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $masyarakat_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Masyarakat - E-Lelang</title>
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
            overflow-y: auto;
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
        .table td { vertical-align: middle; color: #212529; }
        .btn-action { padding: 0.35rem 0.65rem; font-size: 0.813rem; border-radius: 6px; }
        .detail-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #e7f1ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .detail-row {
            border-bottom: 1px solid #f0f2f5;
            padding: 10px 0;
        }
        .detail-row:last-child { border-bottom: none; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="pendataan_barang.php"><i class="fas fa-box me-3 fa-fw"></i> Pendataan Barang</a>
        <a href="kelola_lelang.php"><i class="fas fa-balance-scale me-3 fa-fw"></i> Kelola Lelang</a>
        <a href="history_lelang.php"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
        <a href="laporan.php"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
        <a href="pendataan_masyarakat.php" class="active"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

        <?php if ($id_level == 1) : ?>
            <hr class="text-white-50 mx-4 my-3">
            <small class="text-warning px-4 fw-bold" style="font-size: 0.75rem;">MENU ADMIN</small>
            <a href="registrasi_petugas.php"><i class="fas fa-user-shield me-3 fa-fw"></i> Registrasi Petugas</a>
        <?php endif; ?>
    </div>
    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Daftar Masyarakat Terdaftar</h5>
            <small class="text-muted">Kelola data akun masyarakat yang berpartisipasi</small>
        </div>
        <!-- Tombol mengarah ke halaman form terpisah -->
        <a href="tambah_masyarakat.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-user-plus me-2"></i> Tambah Masyarakat
        </a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses') : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> Akun masyarakat baru berhasil didaftarkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 text-center" style="width: 5%;">No</th>
                        <th class="py-3" style="width: 30%;">Nama Lengkap</th>
                        <th class="py-3" style="width: 25%;">Username</th>
                        <th class="py-3" style="width: 25%;">No. Telepon</th>
                        <th class="py-3 text-center" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($masyarakat_data) > 0) : ?>
                        <?php $no = 1; foreach ($masyarakat_data as $row) : ?>
                        <tr>
                            <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-bold text-dark"><i class="fas fa-user text-primary me-2"></i> <?= htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td><span class="text-muted">@<?= htmlspecialchars($row['username']); ?></span></td>
                            <td><?= htmlspecialchars($row['telp']); ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-info btn-action text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_user']; ?>" title="Lihat Detail Masyarakat">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-users-slash fa-3x text-secondary mb-3 opacity-50"></i>
                                <p class="mb-0">Belum ada masyarakat yang terdaftar.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail per masyarakat -->
<?php foreach ($masyarakat_data as $row) : ?>
    <div class="modal fade" id="modalDetail<?= $row['id_user']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 14px; border: none;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fas fa-id-card text-primary me-2"></i>Detail Masyarakat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="detail-avatar me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['nama_lengkap']); ?></h5>
                            <span class="text-muted">@<?= htmlspecialchars($row['username']); ?></span>
                        </div>
                    </div>

                    <div class="detail-row d-flex justify-content-between">
                        <span class="text-secondary"><i class="fas fa-phone me-2"></i>No. Telepon</span>
                        <span class="fw-semibold text-dark"><?= htmlspecialchars($row['telp']); ?></span>
                    </div>

                    <?php if (!empty($row['email'])) : ?>
                        <div class="detail-row d-flex justify-content-between">
                            <span class="text-secondary"><i class="fas fa-envelope me-2"></i>Email</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($row['email']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row['alamat'])) : ?>
                        <div class="detail-row d-flex justify-content-between">
                            <span class="text-secondary"><i class="fas fa-map-marker-alt me-2"></i>Alamat</span>
                            <span class="fw-semibold text-dark text-end" style="max-width: 60%;"><?= htmlspecialchars($row['alamat']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="detail-row d-flex justify-content-between">
                        <span class="text-secondary"><i class="fas fa-hashtag me-2"></i>ID Pengguna</span>
                        <span class="fw-semibold text-dark">#<?= $row['id_user']; ?></span>
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