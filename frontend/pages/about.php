<?php
// Ambil 4 angka statistik dari database
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_lelang WHERE status = 'dibuka'");
$lelang_dibuka = mysqli_fetch_assoc($q)['total'];

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_barang");
$total_barang = mysqli_fetch_assoc($q)['total'];

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_lelang WHERE status = 'ditutup'");
$lelang_selesai = mysqli_fetch_assoc($q)['total'];

$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_masyarakat");
$total_peserta = mysqli_fetch_assoc($q)['total'];

// Section "Tentang" cuma untuk pengunjung & masyarakat, bukan petugas/admin
if (($_SESSION['role'] ?? '') === 'petugas') {
    return;
}
?>
<section id="about" class="about section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Tentang</h2>
    <p>Mengenal apa itu Kelola Lelang</p>
  </div>

  <div class="container">
    <div class="row gy-5">

      <!-- Kiri: pengertian kelola lelang, bahasa sederhana -->
      <div class="content col-xl-5 d-flex flex-column" data-aos="fade-up" data-aos-delay="100">
        <h3>Apa itu Kelola Lelang?</h3>
        <p>
          Kelola Lelang adalah cara petugas mengatur barang mana yang boleh dilelang, kapan lelangnya dibuka,
          dan kapan lelangnya ditutup.
        </p>
        <p>
          Sederhananya begini: petugas mendata barang yang mau dijual, lalu membuka lelangnya di sistem ini.
          Setelah dibuka, barang itu muncul di halaman Produk Lelang dan siapa saja yang sudah punya akun
          masyarakat bisa mengajukan tawaran harga. Saat petugas menutup lelang, tawaran tertinggi otomatis
          jadi pemenang.
        </p>
        <p class="mb-0">
          Jadi, kalau kamu lihat status barang <strong>"Dibuka"</strong>, artinya lelangnya masih berjalan dan
          kamu masih bisa menawar. Kalau statusnya <strong>"Ditutup"</strong>, artinya lelang sudah selesai dan
          sudah ada pemenangnya.
        </p>
      </div>

      <!-- Kanan: statistik dari database -->
      <div class="col-xl-7" data-aos="fade-up" data-aos-delay="200">
        <div class="row gy-4">

          <div class="col-md-6 icon-box position-relative">
            <i class="bi bi-broadcast"></i>
            <h4><?= $lelang_dibuka ?> Lelang Berlangsung</h4>
            <p>Barang yang bisa kamu tawar saat ini</p>
          </div>

          <div class="col-md-6 icon-box position-relative">
            <i class="bi bi-briefcase"></i>
            <h4><?= $total_barang ?> Total Barang</h4>
            <p>Barang yang sudah terdata di sistem</p>
          </div>

          <div class="col-md-6 icon-box position-relative">
            <i class="bi bi-gem"></i>
            <h4><?= $lelang_selesai ?> Lelang Selesai</h4>
            <p>Lelang yang sudah ditutup oleh petugas</p>
          </div>

          <div class="col-md-6 icon-box position-relative">
            <i class="bi bi-people"></i>
            <h4><?= $total_peserta ?> Peserta Terdaftar</h4>
            <p>Masyarakat yang sudah bergabung</p>
          </div>

        </div>
      </div>

    </div>
  </div>

</section>