<?php
session_start();
include 'connection.php';

// Validasi Login & Hak Akses (Hanya Admin dengan id_level = 1 yang boleh akses)
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['id_level'] != 1) {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Administrator.'); window.location='dashboard.php';</script>";
    exit;
}

$id_level = $_SESSION['id_level']; 
$id_petugas_sedang_login = $_SESSION['id_petugas']; 
$error = '';
$success = '';

// Proses Tambah Petugas Baru
if (isset($_POST['tambah_petugas'])) {
    $nama_petugas  = mysqli_real_escape_string($conn, $_POST['nama_petugas']);
    $username      = mysqli_real_escape_string($conn, $_POST['username']);
    $password      = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $id_level_baru = mysqli_real_escape_string($conn, $_POST['id_level']);

    // Cek apakah username sudah terdaftar
    $cek_user = mysqli_query($conn, "SELECT * FROM tb_petugas WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $error = "Username sudah digunakan oleh petugas lain!";
    } else {
        $query_insert = "INSERT INTO tb_petugas (nama_petugas, username, password, id_level) VALUES ('$nama_petugas', '$username', '$password', '$id_level_baru')";
        if (mysqli_query($conn, $query_insert)) {
            $success = "Petugas baru berhasil ditambahkan!";
        } else {
            $error = "Gagal mendaftarkan petugas: " . mysqli_error($conn);
        }
    }
}

// Ambil daftar petugas KECUALI admin yang sedang login saat ini
$query_petugas = "SELECT tb_petugas.*, tb_level.level FROM tb_petugas JOIN tb_level ON tb_petugas.id_level = tb_level.id_level WHERE tb_petugas.id_petugas != '$id_petugas_sedang_login' ORDER BY tb_petugas.id_petugas DESC";
$result_petugas = mysqli_query($conn, $query_petugas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Petugas - E-Lelang</title>
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
        <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

        <!-- MENU KHUSUS ADMIN -->
        <?php if ($id_level == 1) : ?>
            <hr class="text-white-50 mx-4 my-3">
            <small class="text-warning px-4 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">MENU ADMIN</small>
            <a href="registrasi_petugas.php" class="active"><i class="fas fa-user-shield me-3 fa-fw"></i> Registrasi Petugas</a>
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
            <h5 class="mb-0 fw-bold text-dark">Registrasi Petugas Baru</h5>
            <small class="text-muted">Status Login: <span class="badge bg-danger">Administrator</span> (Full Akses Sistem)</small>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-user-plus me-2"></i> Tambah Petugas
        </button>
    </div>

    <!-- Alert Notifikasi -->
    <?php if ($error) : ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($success) : ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Petugas -->
    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 text-center" style="width: 5%;">No</th>
                        <th class="py-3" style="width: 35%;">Nama Petugas</th>
                        <th class="py-3" style="width: 30%;">Username</th>
                        <th class="py-3 text-center" style="width: 30%;">Level Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_petugas) > 0) : ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result_petugas)) : ?>
                        <tr>
                            <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-bold text-dark"><i class="fas fa-user-circle text-primary me-2"></i> <?= htmlspecialchars($row['nama_petugas']); ?></td>
                            <td><span class="text-muted">@<?= htmlspecialchars($row['username']); ?></span></td>
                            <td class="text-center">
                                <?php if ($row['id_level'] == 1) : ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Administrator</span>
                                <?php else : ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">Petugas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <div class="py-3">
                                    <i class="fas fa-users-slash fa-3x text-secondary mb-3 opacity-50"></i>
                                    <p class="mb-0">Belum ada data petugas lain.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Petugas -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="" method="POST">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalTambahLabel"><i class="fas fa-user-plus me-2 text-primary"></i>Tambah Petugas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-3">
                        <label for="nama_petugas" class="form-label fw-semibold text-secondary">Nama Lengkap Petugas</label>
                        <input type="text" class="form-control py-2" id="nama_petugas" name="nama_petugas" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold text-secondary">Username</label>
                        <input type="text" class="form-control py-2" id="username" name="username" required placeholder="Contoh: budi_petugas">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                        <input type="password" class="form-control py-2" id="password" name="password" required placeholder="Masukkan password akun">
                    </div>
                    <div class="mb-3">
                        <label for="id_level" class="form-label fw-semibold text-secondary">Level Akses</label>
                        <select class="form-select py-2" id="id_level" name="id_level" required>
                            <option value="" disabled selected>Pilih Level Akses</option>
                            <option value="1">Administrator (Full Akses)</option>
                            <option value="2">Petugas (Akses Standar)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_petugas" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Petugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>