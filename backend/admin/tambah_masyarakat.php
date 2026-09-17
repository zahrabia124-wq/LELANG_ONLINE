<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level']; 
$error = '';

// Proses Simpan Data Masyarakat Baru
if (isset($_POST['tambah_masyarakat'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telp         = mysqli_real_escape_string($conn, $_POST['telp']);

    $cek_user = mysqli_query($conn, "SELECT * FROM tb_masyarakat WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $error = "Username sudah digunakan oleh masyarakat lain!";
    } else {
        $query_insert = "INSERT INTO tb_masyarakat (nama_lengkap, username, password, telp) VALUES ('$nama_lengkap', '$username', '$password', '$telp')";
        if (mysqli_query($conn, $query_insert)) {
            header("Location: data_masyarakat.php?status=sukses");
            exit;
        } else {
            $error = "Gagal mendaftarkan masyarakat: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Masyarakat - E-Lelang</title>
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
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
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
        <a href="data_masyarakat.php" class="active"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

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
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4">
        <h5 class="mb-0 fw-bold text-dark">Tambah Akun Masyarakat</h5>
        <small class="text-muted">Daftarkan akun baru untuk peserta lelang</small>
    </div>

    <div class="card card-custom p-4 col-lg-8">
        <?php if ($error) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="nama_lengkap" class="form-label fw-semibold text-secondary">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required placeholder="Contoh: Siti Aminah" autocomplete="off">
            </div>
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold text-secondary">Username</label>
                <input type="text" class="form-control" id="username" name="username" required placeholder="Contoh: siti_aminah" autocomplete="off">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password akun">
            </div>
            <div class="mb-4">
                <label for="telp" class="form-label fw-semibold text-secondary">Nomor Telepon</label>
                <input type="text" class="form-control" id="telp" name="telp" required placeholder="Contoh: 081234567890" autocomplete="off">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="tambah_masyarakat" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                    <i class="fas fa-save me-2"></i> Simpan Data
                </button>
                <a href="pendataan_masyarakat.php" class="btn btn-light px-4 py-2 rounded-pill border">
                    <i class="fas fa-arrow-left me-2"></i> Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>