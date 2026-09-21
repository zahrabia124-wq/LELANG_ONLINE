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
?>
<section id="about" class="about section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Tentang</h2>
    <p>Cara mudah dan transparan untuk mendapatkan barang lewat lelang</p>
  </div>

  <div class="container">
    <div class="row gy-5">

      <!-- Kiri: cara ikut lelang -->
      <div class="content col-xl-5 d-flex flex-column" data-aos="fade-up" data-aos-delay="100">
        <h3>Bagaimana cara ikut lelang?</h3>
        <p>
          1. Daftar akun masyarakat lalu login.<br>
          2. Pilih barang yang sedang dibuka di Produk Lelang.<br>
          3. Ajukan penawaran harga terbaikmu.<br>
          4. Saat lelang ditutup petugas, penawar tertinggi jadi pemenang.
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