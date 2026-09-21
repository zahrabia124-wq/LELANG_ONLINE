<?php
// Hitung jumlah lelang yang statusnya "dibuka"
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_lelang WHERE status = 'dibuka'");
$jumlah_dibuka = mysqli_fetch_assoc($q)['total'];
?>
<section id="hero" class="hero section dark-background">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7 text-center" data-aos="fade-up" data-aos-delay="100">
        <h2><span>Selamat Datang di </span><span class="underlight">Lelang Online</span></h2>
        <p>
          Temukan barang incaranmu dan ajukan penawaran terbaik.
          Saat ini ada <strong><?= $jumlah_dibuka ?> barang</strong> yang sedang dilelang.
        </p>
        <a href="#services" class="btn-get-started">Lihat Produk Lelang</a>

        <!-- Tombol akun: beda tampilan tergantung sudah login atau belum -->
        <div class="mt-4">
          <?php if ($role == '') : ?>
            <a href="backend/admin/index.php" class="btn btn-light rounded-pill px-4">Login</a>
            <a href="backend/admin/register_masyarakat.php" class="btn btn-outline-light rounded-pill px-4">Daftar</a>
          <?php else : ?>
            <span class="me-2">Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
            <a href="backend/admin/dashboard.php" class="btn btn-light rounded-pill px-4">Dashboard</a>
            <a href="backend/admin/logout.php" class="btn btn-outline-light rounded-pill px-4">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>