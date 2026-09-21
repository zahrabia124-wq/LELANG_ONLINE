<?php
// Ambil barang lelang yang statusnya "dibuka" (maksimal 6)
$query = "SELECT tb_lelang.id_lelang, tb_barang.nama_barang, tb_barang.harga_awal,
                 tb_barang.deskripsi_barang, tb_barang.foto
          FROM tb_lelang
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
          WHERE tb_lelang.status = 'dibuka'
          ORDER BY tb_lelang.id_lelang DESC
          LIMIT 6";
$hasil = mysqli_query($conn, $query);
?>
<section id="services" class="services section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Produk Lelang</h2>
    <p>Barang yang sedang dilelang dan siap kamu tawar</p>
  </div>

  <div class="container">

    <?php if (mysqli_num_rows($hasil) == 0) : ?>

      <!-- Kalau belum ada barang -->
      <div class="empty-state">
        <i class="bi bi-box-seam"></i>
        <p class="mt-3 mb-0 fw-semibold">Belum ada barang lelang yang dibuka.</p>
      </div>

    <?php else : ?>

      <div class="row gy-4">
        <?php while ($row = mysqli_fetch_assoc($hasil)) : ?>
          <?php
            $id_lelang = (int) $row['id_lelang'];

            // Cari tawaran tertinggi + jumlah tawaran untuk barang ini
            $q = mysqli_query($conn, "SELECT MAX(penawaran_harga) AS tertinggi, COUNT(*) AS jumlah
                                      FROM history_lelang WHERE id_lelang = $id_lelang");
            $tawaran = mysqli_fetch_assoc($q);

            // Kalau belum ada yang menawar, tampilkan harga awal
            if ($tawaran['tertinggi'] > 0) {
                $judul_harga = 'Tawaran tertinggi';
                $harga = $tawaran['tertinggi'];
            } else {
                $judul_harga = 'Harga awal';
                $harga = $row['harga_awal'];
            }

            // Foto barang (kalau file tidak ada, pakai gambar cadangan)
            $foto = 'frontend/template/assets/img/portfolio/product-1.jpg';
            if (!empty($row['foto']) && file_exists(__DIR__ . '/../../backend/admin/img/' . $row['foto'])) {
                $foto = 'backend/admin/img/' . $row['foto'];
            }
          ?>

          <div class="col-lg-4 col-md-6" data-aos="fade-up">
            <div class="lelang-card">
              <img class="thumb" src="<?= $foto ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>">

              <div class="body">
                <h4><?= htmlspecialchars($row['nama_barang']) ?></h4>
                <p class="desc"><?= htmlspecialchars($row['deskripsi_barang']) ?></p>

                <div class="mt-auto mb-3">
                  <div class="label"><?= $judul_harga ?></div>
                  <div class="price">Rp <?= number_format($harga, 0, ',', '.') ?></div>
                  <small class="text-muted"><?= $tawaran['jumlah'] ?> tawaran</small>
                </div>

                <!-- Tombol berbeda tergantung siapa yang login -->
                <?php if ($role == 'masyarakat') : ?>
                  <a href="backend/admin/detail_barang.php?id_lelang=<?= $id_lelang ?>" class="btn btn-accent rounded-pill w-100">Lihat &amp; Tawar</a>
                <?php elseif ($role == 'petugas') : ?>
                  <a href="backend/admin/kelola_lelang.php" class="btn btn-outline-accent rounded-pill w-100">Kelola Lelang</a>
                <?php else : ?>
                  <a href="backend/admin/index.php" class="btn btn-outline-accent rounded-pill w-100">Login untuk Menawar</a>
                <?php endif; ?>
              </div>
            </div>
          </div>

        <?php endwhile; ?>
      </div>

      <?php if ($role == 'masyarakat') : ?>
        <div class="text-center mt-5">
          <a href="backend/admin/daftar_barang.php" class="btn btn-accent rounded-pill px-4">Lihat semua barang lelang</a>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>

</section>