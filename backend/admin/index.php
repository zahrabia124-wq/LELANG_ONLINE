<?php
session_start();
include 'connection.php';

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 1. Cek dulu ke tabel tb_petugas (untuk Administrator & Petugas)
    $stmt = mysqli_prepare($conn, "SELECT * FROM tb_petugas WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) { 
        // Jika ditemukan di tb_petugas
        if (password_verify($password, $row['password']) || $password === $row['password'] || md5($password) === $row['password']) {
            session_regenerate_id(true);
            $_SESSION['id_petugas'] = $row['id_petugas'];
            $_SESSION['nama']       = $row['nama_petugas']; // Disamakan agar sesuai dashboard
            $_SESSION['id_level']   = $row['id_level'];     // 1: Admin, 2: Petugas
            $_SESSION['role']       = 'petugas';            // Penanda role utama

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        // 2. Jika tidak ada di tb_petugas, cek ke tabel tb_masyarakat
        $stmt_masyarakat = mysqli_prepare($conn, "SELECT * FROM tb_masyarakat WHERE username = ?");
        mysqli_stmt_bind_param($stmt_masyarakat, "s", $username);
        mysqli_stmt_execute($stmt_masyarakat);
        $result_masyarakat = mysqli_stmt_get_result($stmt_masyarakat);
        $row_m = mysqli_fetch_assoc($result_masyarakat);
        mysqli_stmt_close($stmt_masyarakat);

        if ($row_m) {
            // Jika ditemukan di tb_masyarakat (Sesuaikan nama kolom password jika di database pakai 'password')
            if (password_verify($password, $row_m['password']) || $password === $row_m['password'] || md5($password) === $row_m['password']) {
                session_regenerate_id(true);
                // Sesuaikan 'id_ masyarakat' atau 'id_user' dengan kolom primary key tabel masyarakatmu
                $_SESSION['id_user']  = $row_m['id_masyarakat'] ?? $row_m['id_user'] ?? 1; 
                $_SESSION['nama']     = $row_m['nama_lengkap'];
                $_SESSION['role']     = 'masyarakat'; // Penanda role masyarakat

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Lelang Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f8f9fa; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-card { 
            width: 100%; 
            max-width: 420px; 
            padding: 30px; 
            border-radius: 14px; 
            box-shadow: 0 6px 20px rgba(0,0,0,0.08); 
            background: #ffffff; 
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fas fa-gavel me-2"></i>E-LELANG</h3>
        <p class="text-muted small">Silakan login untuk masuk ke sistem</p>
    </div>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger py-2 small"><?= $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" class="form-control" name="username" required autocomplete="off" placeholder="Masukkan username">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" class="form-control" name="password" required placeholder="Masukkan password">
        </div>
        <div class="d-grid mb-3">
            <button type="submit" name="login" class="btn btn-primary py-2 fw-semibold">Masuk</button>
        </div>
        
        <div class="text-center">
            <small class="text-muted">Belum punya akun masyarakat? <a href="register_masyarakat.php" class="text-decoration-none fw-semibold">Daftar di sini</a></small>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>