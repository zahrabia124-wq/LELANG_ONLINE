<section id="portfolio" class="portfolio section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Penawaran Saya</h2>
    <p>Pantau barang yang pernah kamu tawar beserta statusnya</p>
  </div>

  <div class="container">

    <?php if ($role == '') : ?>

      <!-- 1. Belum login -->
      <div class="empty-state">
        <i class="bi bi-lock"></i>
        <p class="mt-3 mb-3 fw-semibold">Login dulu untuk melihat penawaranmu.</p>
        <a href="backend/admin/index.php" class="btn btn-accent rounded-pill px-4">Login</a>
        <a href="backend/admin/register_masyarakat.php" class="btn btn-outline-accent rounded-pill px-4">Daftar</a>
      </div>

    <?php elseif ($role == 'petugas') : ?>

      <!-- 2. Login sebagai petugas -->
      <div class="empty-state">
        <i class="bi bi-person-badge"></i>
        <p class="mt-3 mb-3 fw-semibold">Bagian ini khusus akun masyarakat.</p>
        <a href="backend/admin/history_lelang.php" class="btn btn-accent rounded-pill px-4">Lihat History Lelang</a>
      </div>

    <?php else : ?>

      <?php
        // 3. Login sebagai masyarakat: ambil semua barang yang pernah ditawar
        $id_user = (int) $_SESSION['id_user'];

        $query = "SELECT history_lelang.id_lelang,
                         MAX(history_lelang.penawaran_harga) AS tawaran_saya,
                         tb_barang.nama_barang, tb_barang.foto,
                         tb_lelang.status, tb_lelang.harga_akhir,
                         tb_lelang.id_user AS id_pemenang
                  FROM history_lelang
                  JOIN tb_lelang ON history_lelang.id_lelang = tb_lelang.id_lelang
                  JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
                  WHERE history_lelang.id_user = $id_user
                  GROUP BY history_lelang.id_lelang, tb_barang.nama_barang, tb_barang.foto,
                           tb_lelang.status, tb_lelang.harga_akhir, tb_lelang.id_user
                  ORDER BY history_lelang.id_lelang DESC";
        $hasil = mysqli_query($conn, $query);
      ?>

      <?php if (mysqli_num_rows($hasil) == 0) : ?>

        <div class="empty-state">
          <i class="bi bi-hammer"></i>
          <p class="mt-3 mb-3 fw-semibold">Kamu belum pernah menawar barang.</p>
          <a href="#services" class="btn btn-accent rounded-pill px-4">Cari barang lelang</a>
        </div>

      <?php else : ?>

        <div class="row gy-4">
          <?php while ($row = mysqli_fetch_assoc($hasil)) : ?>
            <?php
              $id_lelang = (int) $row['id_lelang'];

              // Tentukan status: berlangsung / menang / kalah
              if ($row['status'] == 'dibuka') {
                  $label = 'Sedang berlangsung';
                  $warna = 'info';
              } elseif ($row['id_pemenang'] == $id_user) {
                  $label = 'Menang';
                  $warna = 'success';
              } else {
                  $label = 'Kalah';
                  $warna = 'secondary';
              }

              // Tawaran tertinggi saat ini (dari semua penawar)
              $q = mysqli_query($conn, "SELECT MAX(penawaran_harga) AS tertinggi
                                        FROM history_lelang WHERE id_lelang = $id_lelang");
              $tertinggi = mysqli_fetch_assoc($q)['tertinggi'];

              // Foto barang
              $foto = 'frontend/template/assets/img/portfolio/product-1.jpg';
              if (!empty($row['foto']) && file_exists(__DIR__ . '/../../backend/admin/img/' . $row['foto'])) {
                  $foto = 'backend/admin/img/' . $row['foto'];
              }
            ?>

            <div class="col-lg-4 col-md-6">
              <div class="lelang-card">
                <img class="thumb" src="<?= $foto ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>">

                <div class="body">
                  <h4><?= htmlspecialchars($row['nama_barang']) ?></h4>
                  <span class="badge bg-<?= $warna ?> align-self-start mb-3"><?= $label ?></span>

                  <div class="label">Tawaran tertinggimu</div>
                  <div class="price">Rp <?= number_format($row['tawaran_saya'], 0, ',', '.') ?></div>

                  <?php if ($row['status'] == 'dibuka') : ?>
                    <small class="text-muted">Tertinggi saat ini: Rp <?= number_format($tertinggi, 0, ',', '.') ?></small>
                    <a href="backend/admin/detail_barang.php?id_lelang=<?= $id_lelang ?>" class="btn btn-accent rounded-pill w-100 mt-3">Tawar Lagi</a>
                  <?php else : ?>
                    <small class="text-muted">Harga akhir: Rp <?= number_format($row['harga_akhir'], 0, ',', '.') ?></small>
                  <?php endif; ?>
                </div>
              </div>
            </div>

          <?php endwhile; ?>
        </div>

      <?php endif; ?>

    <?php endif; ?>

  </div>

</section>