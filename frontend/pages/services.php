<?php
// ====================================================================
// services.php: Produk Lelang.
// - Biasanya menampilkan daftar barang (grid).
// - Kalau ada ?id_lelang=... di URL, menampilkan DETAIL + FORM TAWAR
//   untuk satu barang itu saja, tetap di index.php (tidak pindah ke backend).
// - Kalau ada ?semua=1, menampilkan SEMUA barang (tanpa batas 9).
// ====================================================================

$id_lelang_dipilih = isset($_GET['id_lelang']) ? (int) $_GET['id_lelang'] : 0;
$tampilkan_semua   = isset($_GET['semua']);
$pesan_tawar       = '';

// --------- PROSES KIRIM TAWARAN ---------
if (isset($_POST['tawar']) && $role == 'masyarakat') {
    $id_lelang_tawar = (int) $_POST['id_lelang'];
    $id_barang_tawar = (int) $_POST['id_barang'];
    $harga_tawar     = (float) $_POST['penawaran_harga'];
    $id_user         = (int) $_SESSION['id_user'];

    $stmt = mysqli_prepare($conn, "INSERT INTO history_lelang (id_lelang, id_barang, id_user, penawaran_harga) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiid", $id_lelang_tawar, $id_barang_tawar, $id_user, $harga_tawar);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $pesan_tawar       = 'Penawaranmu berhasil dikirim!';
    $id_lelang_dipilih = $id_lelang_tawar; // tetap di halaman detail barang ini
}
?>
<section id="services" class="services section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Produk Lelang</h2>
    <p>Barang yang sedang dilelang dan siap kamu tawar</p>
  </div>

  <div class="container">

    <?php if ($id_lelang_dipilih > 0) { ?>

      <?php
        // ---------- MODE DETAIL + TAWAR (satu barang saja) ----------
        $query = "SELECT tb_lelang.id_lelang, tb_lelang.id_barang, tb_barang.nama_barang, tb_barang.harga_awal, tb_barang.deskripsi_barang, tb_barang.foto FROM tb_lelang JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang WHERE tb_lelang.id_lelang = ? AND tb_lelang.status = 'dibuka'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_lelang_dipilih);
        mysqli_stmt_execute($stmt);
        $barang = mysqli_stmt_get_result($stmt)->fetch_assoc();
      ?>

      <?php if (!$barang) { ?>
        <div class="empty-state">
          <i class="bi bi-emoji-frown"></i>
          <p class="mt-3 mb-3 fw-semibold">Barang tidak ditemukan atau lelangnya sudah ditutup.</p>
          <a href="index.php#services" class="btn btn-accent rounded-pill px-4">Kembali ke Produk Lelang</a>
        </div>

      <?php } else { ?>

        <a href="index.php#services" class="text-decoration-none small mb-4 d-inline-block">&larr; Kembali ke Produk Lelang</a>

        <?php if ($pesan_tawar) { ?>
          <div class="alert alert-success rounded-4"><?= htmlspecialchars($pesan_tawar) ?></div>
        <?php } ?>

        <?php
          $id_lelang_sql = (int) $id_lelang_dipilih;
          $q = mysqli_query($conn, "SELECT MAX(penawaran_harga) AS tertinggi, COUNT(*) AS jumlah FROM history_lelang WHERE id_lelang = $id_lelang_sql");
          $tawaran     = mysqli_fetch_assoc($q);
          $harga_dasar = ($tawaran['tertinggi'] > 0) ? $tawaran['tertinggi'] : $barang['harga_awal'];

          $foto = 'frontend/template/assets/img/portfolio/product-1.jpg';
          if (!empty($barang['foto']) && file_exists(__DIR__ . '/../../backend/admin/img/' . $barang['foto'])) {
              $foto = 'backend/admin/img/' . $barang['foto'];
          }
        ?>

        <div class="row g-5 align-items-start">
          <div class="col-md-6">
            <img src="<?= htmlspecialchars($foto) ?>" class="w-100 rounded-4" style="max-height:420px;object-fit:cover;" alt="<?= htmlspecialchars($barang['nama_barang']) ?>">
          </div>

          <div class="col-md-6">
            <h3 class="fw-bold mb-3"><?= htmlspecialchars($barang['nama_barang']) ?></h3>
            <p class="text-muted"><?= nl2br(htmlspecialchars($barang['deskripsi_barang'])) ?></p>

            <div class="d-flex gap-4 my-4">
              <div>
                <div class="small text-uppercase text-muted">Harga Awal</div>
                <div class="fs-5 fw-bold">Rp <?= number_format($barang['harga_awal'], 0, ',', '.') ?></div>
              </div>
              <div>
                <div class="small text-uppercase text-muted">Tawaran Tertinggi</div>
                <div class="fs-5 fw-bold" style="color: var(--accent-color);">Rp <?= number_format($harga_dasar, 0, ',', '.') ?></div>
                <div class="small text-muted"><?= (int) $tawaran['jumlah'] ?> tawaran</div>
              </div>
            </div>

            <?php if ($role == 'masyarakat') { ?>
              <form method="POST" action="index.php#services" class="p-4 rounded-4" style="background: var(--surface-color); box-shadow: 0 0 20px rgba(0,0,0,.06);">
                <input type="hidden" name="id_lelang" value="<?= (int) $barang['id_lelang'] ?>">
                <input type="hidden" name="id_barang" value="<?= (int) $barang['id_barang'] ?>">

                <label class="form-label fw-semibold">Nominal Penawaranmu (Rp)</label>
                <input type="number" name="penawaran_harga" class="form-control form-control-lg mb-3"
                       min="<?= $harga_dasar + 1 ?>" required placeholder="Lebih tinggi dari tawaran saat ini">

                <button type="submit" name="tawar" class="btn w-100 py-2 rounded-pill fw-semibold" style="background: var(--accent-color); color:#fff;">
                  Kirim Penawaran
                </button>
              </form>
            <?php } else { ?>
              <a href="index.php?form=login#hero" class="btn btn-accent rounded-pill w-100 py-2">Login untuk Menawar</a>
            <?php } ?>
          </div>
        </div>

      <?php } ?>

    <?php } else { ?>

      <?php
        // ---------- MODE DAFTAR (grid barang) ----------
        $batas = $tampilkan_semua ? 999 : 9;
        $query = "SELECT tb_lelang.id_lelang, tb_barang.nama_barang, tb_barang.harga_awal, tb_barang.deskripsi_barang, tb_barang.foto FROM tb_lelang JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang WHERE tb_lelang.status = 'dibuka' ORDER BY tb_lelang.id_lelang DESC LIMIT $batas";
        $hasil = mysqli_query($conn, $query);
      ?>

      <?php if (mysqli_num_rows($hasil) == 0) { ?>
        <div class="empty-state">
          <i class="bi bi-box-seam"></i>
          <p class="mt-3 mb-0 fw-semibold">Belum ada barang lelang yang dibuka.</p>
        </div>

      <?php } else { ?>

        <div class="row gy-4">
          <?php while ($row = mysqli_fetch_assoc($hasil)) { ?>
            <?php
              $id_lelang = (int) $row['id_lelang'];

              $q = mysqli_query($conn, "SELECT MAX(penawaran_harga) AS tertinggi, COUNT(*) AS jumlah FROM history_lelang WHERE id_lelang = $id_lelang");
              $tawaran = mysqli_fetch_assoc($q);

              if ($tawaran['tertinggi'] > 0) {
                  $judul_harga = 'Tawaran tertinggi';
                  $harga = $tawaran['tertinggi'];
              } else {
                  $judul_harga = 'Harga awal';
                  $harga = $row['harga_awal'];
              }

              $foto = 'frontend/template/assets/img/portfolio/product-1.jpg';
              if (!empty($row['foto']) && file_exists(__DIR__ . '/../../backend/admin/img/' . $row['foto'])) {
                  $foto = 'backend/admin/img/' . $row['foto'];
              }
            ?>

            <div class="col-lg-4 col-md-6" data-aos="fade-up">
              <div class="lelang-card">
                <img class="thumb" src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>">

                <div class="body">
                  <h4><?= htmlspecialchars($row['nama_barang']) ?></h4>
                  <p class="desc"><?= htmlspecialchars($row['deskripsi_barang']) ?></p>

                  <div class="mt-auto mb-3">
                    <div class="label"><?= $judul_harga ?></div>
                    <div class="price">Rp <?= number_format($harga, 0, ',', '.') ?></div>
                    <small class="text-muted"><?= (int) $tawaran['jumlah'] ?> tawaran</small>
                  </div>

                  <?php if ($role == 'masyarakat') { ?>
                    <a href="index.php?id_lelang=<?= $id_lelang ?>#services" class="btn btn-accent rounded-pill w-100">Lihat &amp; Tawar</a>
                  <?php } elseif ($role == 'petugas') { ?>
                    <a href="backend/admin/kelola_lelang.php" class="btn btn-outline-accent rounded-pill w-100">Kelola Lelang</a>
                  <?php } else { ?>
                    <a href="index.php?form=login#hero" class="btn btn-outline-accent rounded-pill w-100">Lihat & Tawar</a>
                  <?php } ?>
                </div>
              </div>
            </div>

          <?php } ?>
        </div>

        <?php if (!$tampilkan_semua) { ?>
          <div class="text-center mt-5">
            <a href="index.php?semua=1#services" class="btn btn-accent rounded-pill px-4">Lihat semua barang lelang</a>
          </div>
        <?php } ?>

      <?php } ?>

    <?php } ?>

  </div>

</section>