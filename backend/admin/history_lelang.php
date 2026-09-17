<?php
session_start();
include 'connection.php';

// Validasi Login
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level']; 

// Ambil data history lelang digabung dengan barang dan masyarakat
$query = "SELECT history_lelang.*, tb_barang.nama_barang, tb_masyarakat.nama_lengkap 
          FROM history_lelang 
          JOIN tb_lelang ON history_lelang.id_lelang = tb_lelang.id_lelang
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
          LEFT JOIN tb_masyarakat ON history_lelang.id_user = tb_masyarakat.id_user
          ORDER BY history_lelang.id_history DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Lelang - E-Lelang</title>
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
        <a href="history_lelang.php" class="active"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
        <a href="laporan.php"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
        <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

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
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4">
        <h5 class="mb-0 fw-bold text-dark">History Penawaran Lelang</h5>
        <small class="text-muted">Daftar riwayat harga yang ditawarkan oleh masyarakat</small>
    </div>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 text-center" style="width: 5%;">No</th>
                        <th class="py-3" style="width: 25%;">Nama Barang</th>
                        <th class="py-3" style="width: 25%;">Nama Penawar</th>
                        <th class="py-3" style="width: 25%;">Harga Ditawar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']); ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? 'Masyarakat'); ?></td>
                            <td><span class="text-success fw-semibold">Rp <?= number_format($row['penawaran_harga'], 0, ',', '.'); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <div class="py-3">
                                    <i class="fas fa-history fa-3x text-secondary mb-3 opacity-50"></i>
                                    <p class="mb-0">Belum ada riwayat penawaran lelang.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>