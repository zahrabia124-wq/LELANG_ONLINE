<?php
// ====================================================================
// hero.php: sambutan awal + LOGIN & DAFTAR (langsung di sini, tanpa file lain).
// Cara kerjanya pakai ?form=login atau ?form=daftar di URL index.php,
// jadi tetap satu halaman, tidak pernah pindah ke folder backend.
// Petugas/Admin yang sudah login TETAP di index.php (tema frontend),
// isinya mini-dashboard berisi data asli dari backend. Tombol-tombolnya
// membuka panel kelola di section #services (lihat pages/admin.php),
// jadi tidak perlu lagi pindah ke halaman backend/admin/.
// Masyarakat yang sudah login juga sama: tetap di tema frontend, dan
// tombol "Lihat Produk Lelang" & "Penawaran Saya" membuka section
// #services (menu=produk / menu=penawaran) — TIDAK pindah ke
// backend/admin/*.php.
// ====================================================================

// Hitung jumlah lelang yang sedang dibuka
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_lelang WHERE status = 'dibuka'");
$jumlah_dibuka = mysqli_fetch_assoc($q)['total'];

$error       = '';
$form_dibuka = $_GET['form'] ?? ''; // '' | 'login' | 'daftar'
$menu_aktif  = $_GET['menu'] ?? ''; // menu yang sedang dibuka (untuk highlight tombol)

// --------- PROSES LOGIN ---------
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 1. Cek ke tabel petugas
    $stmt = mysqli_prepare($conn, "SELECT * FROM tb_petugas WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    if ($row) {
        if (password_verify($password, $row['password']) || $password === $row['password'] || md5($password) === $row['password']) {
            session_regenerate_id(true);
            $_SESSION['id_petugas'] = $row['id_petugas'];
            $_SESSION['nama']       = $row['nama_petugas'];
            $_SESSION['id_level']   = $row['id_level'];
            $_SESSION['role']       = 'petugas';

            // Petugas/Admin TETAP di index.php (frontend), bukan pindah ke backend
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        // 2. Kalau tidak ada di petugas, cek ke masyarakat
        $stmt = mysqli_prepare($conn, "SELECT * FROM tb_masyarakat WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $row_m = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        if ($row_m) {
            if (password_verify($password, $row_m['password']) || $password === $row_m['password'] || md5($password) === $row_m['password']) {
                session_regenerate_id(true);
                $_SESSION['id_user'] = $row_m['id_masyarakat'] ?? $row_m['id_user'] ?? 1;
                $_SESSION['nama']    = $row_m['nama_lengkap'];
                $_SESSION['role']    = 'masyarakat';
                header("Location: index.php");
                exit;
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
    $form_dibuka = 'login';
}

// --------- PROSES DAFTAR ---------
if (isset($_POST['daftar'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telp         = mysqli_real_escape_string($conn, $_POST['telp']);

    $cek = mysqli_query($conn, "SELECT * FROM tb_masyarakat WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Username sudah terdaftar! Pakai username lain.";
        $form_dibuka = 'daftar';
    } else {
        $query = "INSERT INTO tb_masyarakat (nama_lengkap, username, password, telp) VALUES ('$nama_lengkap', '$username', '$password', '$telp')";
        if (mysqli_query($conn, $query)) {
            header("Location: index.php?form=login&daftar=sukses");
            exit;
        } else {
            $error = "Registrasi gagal, coba lagi.";
            $form_dibuka = 'daftar';
        }
    }
}

// --------- LOGOUT ---------
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// --------- DATA STATISTIK UNTUK MINI-DASHBOARD PETUGAS/ADMIN ---------
if (($_SESSION['role'] ?? '') === 'petugas') {
    $t_barang = $t_lelang = $t_user = $t_dibuka = $t_ditutup = 0;

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang");
    if ($r) $t_barang = mysqli_fetch_assoc($r)['total'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang");
    if ($r) $t_lelang = mysqli_fetch_assoc($r)['total'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_masyarakat");
    if ($r) $t_user = mysqli_fetch_assoc($r)['total'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'dibuka'");
    if ($r) $t_dibuka = mysqli_fetch_assoc($r)['total'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status = 'ditutup'");
    if ($r) $t_ditutup = mysqli_fetch_assoc($r)['total'];

    // Daftar tombol akses cepat: [kode menu, label, icon, hanya admin?]
    $menu_petugas = [
        ['barang',     'Pendataan Barang',  'bi-box-seam',           false],
        ['lelang',     'Kelola Lelang',     'bi-hammer',             false],
        ['history',    'History Lelang',    'bi-clock-history',      false],
        ['masyarakat', 'Data Masyarakat',   'bi-people',             false],
        ['petugas',    'Registrasi Petugas','bi-person-plus',        true],
        ['laporan',    'Generate Laporan',  'bi-file-earmark-text',  false],
    ];
}
?>
<section id="hero" class="hero section dark-background">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-delay="100">

        <?php if ($role == '' && $form_dibuka == '') : ?>
          <!-- TAMPILAN AWAL: belum login, belum buka form apa pun -->
          <h2><span>Selamat Datang di </span><span class="underlight">Lelang Online</span></h2>
          <p>
            Temukan barang incaranmu dan ajukan penawaran terbaik.
            Saat ini ada <strong><?= $jumlah_dibuka ?> barang</strong> yang sedang dilelang.
          </p>
          <a href="#services" class="btn-get-started">Lihat Produk Lelang</a>

        <?php elseif ($role == '' && $form_dibuka == 'login') : ?>
          <!-- FORM LOGIN, tetap di index.php, tidak pindah halaman -->
          <div class="row justify-content-center">
            <div class="col-md-6 text-start">
              <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.08);">
                <h4 class="text-center mb-3">Login</h4>

                <?php if (isset($_GET['daftar'])) : ?>
                  <div class="alert alert-success py-2 small">Registrasi berhasil! Silakan login.</div>
                <?php endif; ?>
                <?php if ($error) : ?>
                  <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php#hero">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" required autocomplete="off">
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                  </div>
                  <button type="submit" name="login" class="btn w-100 py-2 rounded-pill fw-semibold"
                          style="background: var(--accent-color); color:#fff; border:none;">Masuk</button>
                </form>

                <p class="text-center small mt-3 mb-0">
                  Belum punya akun? <a href="index.php?form=daftar#hero">Daftar di sini</a><br>
                  <a href="index.php#hero" class="text-white-50">&larr; Batal</a>
                </p>
              </div>
            </div>
          </div>

        <?php elseif ($role == '' && $form_dibuka == 'daftar') : ?>
          <!-- FORM DAFTAR, tetap di index.php -->
          <div class="row justify-content-center">
            <div class="col-md-6 text-start">
              <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.08);">
                <h4 class="text-center mb-3">Daftar Akun Masyarakat</h4>

                <?php if ($error) : ?>
                  <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">No. Telepon</label>
                    <input type="number" name="telp" class="form-control" required>
                  </div>
                  <button type="submit" name="daftar" class="btn w-100 py-2 rounded-pill fw-semibold"
                          style="background: var(--accent-color); color:#fff; border:none;">Daftar Sekarang</button>
                </form>

                <p class="text-center small mt-3 mb-0">
                  Sudah punya akun? <a href="index.php?form=login#hero">Login di sini</a><br>
                  <a href="index.php#hero" class="text-white-50">&larr; Batal</a>
                </p>
              </div>
            </div>
          </div>

        <?php elseif ($role == 'masyarakat') : ?>
          <!-- SUDAH LOGIN SEBAGAI MASYARAKAT: tetap di frontend, buka section #services -->
          <h2><span>Halo, </span><span class="underlight"><?= htmlspecialchars($_SESSION['nama']) ?></span></h2>
          <p>Ayo cari barang incaranmu dan ajukan penawaran terbaik.</p>
          <a href="index.php?menu=produk#services" class="btn-get-started">Lihat Produk Lelang</a>

        <?php elseif ($role == 'petugas') : ?>
          <!-- SUDAH LOGIN SEBAGAI PETUGAS/ADMIN: mini-dashboard bertema frontend -->
          <h2><span>Halo, </span><span class="underlight"><?= htmlspecialchars($_SESSION['nama']) ?></span></h2>
          <p>
            <?= ($_SESSION['id_level'] == 1) ? 'Panel Administrator — kelola seluruh sistem lelang.' : 'Panel Petugas — kelola barang dan lelang.' ?>
          </p>
        <?php endif; ?>

      </div>
    </div>

    <?php if ($role == 'petugas') : ?>
      <!-- STATISTIK -->
      <div class="row gy-4 mt-3" data-aos="fade-up" data-aos-delay="150">
        <div class="col-md-4">
          <div class="stat-box p-4 rounded-4 text-center" style="background: rgba(255,255,255,0.08);">
            <div class="stat-number text-white"><?= $t_barang ?></div>
            <div class="text-white-50">Total Barang</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-box p-4 rounded-4 text-center" style="background: rgba(255,255,255,0.08);">
            <div class="stat-number text-white"><?= $t_lelang ?></div>
            <div class="text-white-50">Sesi Lelang (<?= $t_dibuka ?> dibuka, <?= $t_ditutup ?> ditutup)</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-box p-4 rounded-4 text-center" style="background: rgba(255,255,255,0.08);">
            <div class="stat-number text-white"><?= $t_user ?></div>
            <div class="text-white-50">Masyarakat Terdaftar</div>
          </div>
        </div>
      </div>

      <!-- AKSES CEPAT KE PANEL KELOLA (tampil di index.php bagian #services)
           Semua tombol pakai gaya yang sama: putih-outline biar terbaca di
           background biru. Tombol yang menu-nya sedang dibuka jadi solid. -->
      <div class="row gy-3 mt-4 justify-content-center" data-aos="fade-up" data-aos-delay="200">
        <?php foreach ($menu_petugas as [$kode, $label, $icon, $hanya_admin]) : ?>
          <?php
            // Registrasi Petugas cuma untuk admin (id_level == 1)
            if ($hanya_admin && ($_SESSION['id_level'] ?? null) != 1) continue;
            $aktif = ($menu_aktif === $kode);
          ?>
          <div class="col-md-3 col-6">
            <a href="index.php?menu=<?= $kode ?>#panel-<?= $kode ?>"
               class="btn <?= $aktif ? 'btn-light text-primary fw-semibold' : 'btn-outline-light' ?> w-100 py-3 rounded-4">
              <i class="bi <?= $icon ?> d-block mb-1" style="font-size:1.4rem;"></i> <?= $label ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>