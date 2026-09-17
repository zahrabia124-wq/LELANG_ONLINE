<?php
session_start();
include 'connection.php';

// Jika sudah login, lempar ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Masyarakat - E-Lelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-register {
            border: none;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-register p-4 bg-white">
                <div class="text-center mb-4">
                    <div class="text-primary fs-1 mb-2"><i class="fas fa-gavel"></i></div>
                    <h3 class="fw-bold text-dark">Registrasi Masyarakat</h3>
                    <p class="text-muted small">Daftarkan akun Anda untuk mulai menawar barang lelang</p>
                </div>

                <form action="proses_register.php" method="POST">
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required placeholder="Masukkan nama lengkap">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-at text-muted"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required placeholder="Buat username unik">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="telp" class="form-label fw-semibold">Nomor Telepon / WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                            <input type="number" class="form-control" id="telp" name="telp" required placeholder="Contoh: 081234567890">
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" name="daftar" class="btn btn-primary py-2 fw-semibold rounded-pill">Daftar Sekarang</button>
                    </div>

                    <div class="text-center">
                        <small class="text-muted">Sudah punya akun? <a href="index.php" class="text-decoration-none fw-semibold">Login di sini</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>