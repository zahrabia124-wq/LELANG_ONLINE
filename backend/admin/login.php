<?php
session_start();
include 'connection.php';

$error = '';

// Buat CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Validasi keamanan gagal!";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        $login_berhasil = false;

        // 1. Cek dulu ke tabel petugas (tb_petugas)
        $stmt = mysqli_prepare($conn, "SELECT * FROM tb_petugas WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Jika ketemu di tb_petugas, cek passwordnya
            if ($password === $row['password'] || md5($password) === $row['password'] || password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_petugas'] = $row['id_petugas'];
                $_SESSION['nama']       = $row['nama_petugas']; // Nama untuk sapaan di dashboard
                $_SESSION['username']   = $row['username'];
                $_SESSION['id_level']   = $row['id_level'];     // 1 = Administrator, 2 = Petugas
                $_SESSION['role']       = 'petugas';            // PENANDA ROLE UTAMA

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Password untuk akun Petugas/Admin salah!";
                $login_berhasil = true; // Username ditemukan tapi password salah
            }
        }
        mysqli_stmt_close($stmt);

        // 2. Jika belum berhasil, cek ke tabel masyarakat (tb_masyarakat)
        if (!$login_berhasil && empty($error)) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM tb_masyarakat WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // Jika ketemu di tb_masyarakat, cek passwordnya
                if ($password === $row['password'] || md5($password) === $row['password'] || password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['id_user']  = $row['id_user'];
                    $_SESSION['nama']     = $row['nama_lengkap']; // Nama untuk sapaan di dashboard
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role']     = 'masyarakat';         // PENANDA ROLE UTAMA

                    header("Location: dashboard.php"); 
                    exit;
                } else {
                    $error = "Password untuk akun Masyarakat salah!";
                    $login_berhasil = true;
                }
            }
            mysqli_stmt_close($stmt);
        }

        // 3. Jika di kedua tabel tidak ditemukan sama sekali
        if (empty($error)) {
            $error = "Username tidak terdaftar di sistem!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login E-Lelang (UKK RPL)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f4f7fc; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
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
        <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
            <i class="fas fa-gavel fa-2x"></i>
        </div>
        <h4 class="fw-bold text-primary mb-1">LOGIN E-LELANG</h4>
        <p class="text-muted small">Sistem mendeteksi akun Anda secara otomatis</p>
    </div>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
            <?= htmlspecialchars($error); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <!-- Token keamanan CSRF -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="Masukkan username Anda..." required autocomplete="off">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Masukkan password..." required>
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" name="login" class="btn btn-primary py-2 fw-semibold">Masuk ke Sistem</button>
        </div>

        <div class="text-center">
            <small class="text-muted">Belum punya akun masyarakat? <a href="register_masyarakat.php" class="text-primary text-decoration-none fw-semibold">Registrasi di sini</a></small>
        </div>
    </form>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>