<?php
// ====================================================================
// admin.php: PANEL PETUGAS / ADMINISTRATOR di dalam tema frontend.
//
// Sama seperti masyarakat (services.php & portfolio.php), semua fitur
// petugas sekarang tampil DI index.php, bukan pindah ke backend/admin/.
// File ini dipanggil oleh partials/head.php HANYA kalau yang login petugas,
// dan menggantikan isi <section id="services">.
//
// Pilihan menu dibaca dari URL:  index.php?menu=barang#panel-barang
//   menu = barang | lelang | history | laporan | masyarakat | petugas
//   sub  = tambah | edit | buka   (untuk halaman form)
//
// Aturan hak akses SAMA dengan backend:
//   - Level 1 (Administrator): hanya memantau lelang, boleh registrasi petugas
//   - Level 2 (Petugas)      : boleh buka & tutup lelang
// Semua aksi (simpan/hapus/buka/tutup) memakai POST + token CSRF.
// Data & foto tetap memakai database dan folder backend/admin/img/ yang sama.
// ====================================================================

if (($_SESSION['role'] ?? '') !== 'petugas') {
    return;
}

$id_level = (int) ($_SESSION['id_level'] ?? 0);   // 1 = Administrator, 2 = Petugas
$is_admin = ($id_level === 1);

$img_dir          = __DIR__ . '/../../backend/admin/img/';
$foto_placeholder = 'frontend/template/assets/img/portfolio/product-1.jpg';


// ====================================================================
// FUNGSI BANTU
// ====================================================================

// Amankan teks sebelum dicetak ke HTML
function adm_h($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Format rupiah
function adm_rp($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

// Alamat halaman panel.
// - Halaman form (ada 'sub'): panel daftar tidak tampil, jadi pakai #services.
// - Halaman daftar: langsung ke panel menu itu sendiri (#panel-xxx).
function adm_url($menu, $tambahan = array())
{
    $param  = array('menu' => $menu) + $tambahan;
    $anchor = isset($tambahan['sub']) ? '#services' : '#panel-' . $menu;
    return 'index.php?' . http_build_query($param) . $anchor;
}

// Simpan pesan sukses/gagal lalu pindah halaman (pola Post-Redirect-Get)
function adm_redirect($menu, $pesan, $tipe = 'success', $tambahan = array())
{
    $_SESSION['adm_flash'] = array('tipe' => $tipe, 'pesan' => $pesan);
    header('Location: ' . adm_url($menu, $tambahan));
    exit;
}

// Input tersembunyi berisi token CSRF (dipasang di setiap form)
function adm_csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . adm_h($_SESSION['adm_csrf']) . '">';
}

// Alamat foto barang untuk ditampilkan; kosong kalau fotonya tidak ada
function adm_foto_url($foto)
{
    $foto = basename((string) $foto);
    if ($foto !== '' && file_exists(__DIR__ . '/../../backend/admin/img/' . $foto)) {
        return 'backend/admin/img/' . rawurlencode($foto);
    }
    return '';
}

// Hapus file foto dari folder img (aman dari path aneh)
function adm_hapus_file($dir, $nama_file)
{
    $nama_file = basename((string) $nama_file);
    if ($nama_file !== '' && is_file($dir . $nama_file)) {
        @unlink($dir . $nama_file);
    }
}

// Ambil banyak baris (SELECT) memakai prepared statement -> array
function adm_ambil($conn, $sql, $tipe = '', $params = array())
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($tipe !== '') {
        mysqli_stmt_bind_param($stmt, $tipe, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $baris = array();
    if ($hasil) {
        while ($r = mysqli_fetch_assoc($hasil)) {
            $baris[] = $r;
        }
    }
    mysqli_stmt_close($stmt);
    return $baris;
}

// Jalankan INSERT / UPDATE / DELETE. Mengembalikan jumlah baris yang berubah,
// atau -1 kalau gagal.
function adm_jalankan($conn, $sql, $tipe = '', $params = array())
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($tipe !== '') {
        mysqli_stmt_bind_param($stmt, $tipe, ...$params);
    }
    $ok    = mysqli_stmt_execute($stmt);
    $baris = $ok ? mysqli_stmt_affected_rows($stmt) : -1;
    mysqli_stmt_close($stmt);
    return $baris;
}

// Username tidak boleh kembar di tabel petugas MAUPUN masyarakat,
// karena saat login sistem mengecek tb_petugas dulu baru tb_masyarakat.
function adm_username_dipakai($conn, $username)
{
    $a = adm_ambil($conn, "SELECT username FROM tb_petugas WHERE username = ?", 's', array($username));
    $b = adm_ambil($conn, "SELECT username FROM tb_masyarakat WHERE username = ?", 's', array($username));
    return count($a) > 0 || count($b) > 0;
}

// Proses upload foto barang. Mengembalikan nama file baru, '' kalau tidak ada
// file dipilih atau gagal (alasannya diisi ke $error).
function adm_upload_foto($file, $img_dir, &$error)
{
    if (!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Ukuran file melebihi batas upload di php.ini.';
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload foto gagal (kode ' . (int) $file['error'] . ').';
        return '';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'), true)) {
        $error = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
        return '';
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Ukuran foto maksimal 2 MB.';
        return '';
    }
    if (!@getimagesize($file['tmp_name'])) {
        $error = 'File yang diunggah bukan gambar yang valid.';
        return '';
    }
    if (!is_dir($img_dir)) {
        mkdir($img_dir, 0775, true);
    }

    $nama_baru = 'brg_' . time() . '_' . rand(100, 999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $img_dir . $nama_baru)) {
        $error = 'Gagal memindahkan file foto ke folder backend/admin/img/.';
        return '';
    }
    return $nama_baru;
}

// Riwayat penawaran dikelompokkan per id_lelang (tertinggi paling atas)
function adm_riwayat_per_lelang($conn)
{
    $hasil = array();
    $baris = adm_ambil(
        $conn,
        "SELECT history_lelang.id_lelang, history_lelang.penawaran_harga, tb_masyarakat.nama_lengkap
         FROM history_lelang
         LEFT JOIN tb_masyarakat ON history_lelang.id_user = tb_masyarakat.id_user
         ORDER BY history_lelang.penawaran_harga DESC"
    );
    foreach ($baris as $b) {
        $hasil[$b['id_lelang']][] = $b;
    }
    return $hasil;
}

// Lencana status lelang
function adm_badge_status($status)
{
    if ($status === 'dibuka') {
        return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">Dibuka</span>';
    }
    return '<span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">Ditutup</span>';
}

// Judul + keterangan + (opsional) tombol di atas setiap halaman
function adm_judul($judul, $keterangan, $tombol = '')
{
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">';
    echo '<div><h4 class="fw-bold mb-1" style="color: var(--heading-color);">' . adm_h($judul) . '</h4>';
    echo '<div class="text-muted small">' . $keterangan . '</div></div>';
    echo $tombol;
    echo '</div>';
}

// Modal detail satu sesi lelang + riwayat penawarannya (dipakai Kelola Lelang & Laporan)
function adm_modal_lelang($row, $riwayat, $foto_placeholder)
{
    $id   = (int) $row['id_lelang'];
    $foto = adm_foto_url($row['foto'] ?? '');
    if ($foto === '') {
        $foto = $foto_placeholder;
    }
    ?>
    <div class="modal fade" id="admModalLelang<?= $id ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
          <div class="modal-header">
            <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i><?= adm_h($row['nama_barang']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="row g-4 mb-3">
              <div class="col-md-5">
                <img src="<?= adm_h($foto) ?>" class="adm-modal-img" alt="<?= adm_h($row['nama_barang']) ?>">
              </div>
              <div class="col-md-7">
                <p class="text-muted"><?= nl2br(adm_h($row['deskripsi_barang'] ?? '')) ?></p>
                <div class="row gy-3">
                  <div class="col-6"><div class="adm-label">Harga Awal</div><div class="fw-bold"><?= adm_rp($row['harga_awal']) ?></div></div>
                  <div class="col-6"><div class="adm-label">Harga Akhir</div><div class="fw-bold text-success"><?= $row['harga_akhir'] ? adm_rp($row['harga_akhir']) : '-' ?></div></div>
                  <div class="col-6"><div class="adm-label">Tgl Lelang</div><div class="fw-semibold"><?= adm_h($row['tgl_lelang']) ?></div></div>
                  <div class="col-6"><div class="adm-label">Status</div><div><?= adm_badge_status($row['status']) ?></div></div>
                </div>
                <div class="mt-3">
                  <?php if (!empty($row['nama_lengkap'])) : ?>
                    <span class="fw-semibold"><i class="bi bi-trophy text-warning me-1"></i> Pemenang: <?= adm_h($row['nama_lengkap']) ?></span>
                  <?php else : ?>
                    <span class="text-muted fst-italic">Belum ada pemenang</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-list-ul me-2"></i>Riwayat Penawaran</h6>
            <?php if (count($riwayat) > 0) : ?>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead><tr><th>Penawar</th><th class="text-end">Harga Ditawar</th></tr></thead>
                  <tbody>
                    <?php foreach ($riwayat as $i => $h) : ?>
                      <tr>
                        <td>
                          <?= adm_h($h['nama_lengkap'] ?? 'Masyarakat') ?>
                          <?php if ($i === 0) : ?><span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Tertinggi</span><?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold text-success"><?= adm_rp($h['penawaran_harga']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else : ?>
              <p class="text-muted text-center py-3 mb-0"><i class="bi bi-inbox me-2"></i>Belum ada penawaran untuk barang ini.</p>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>
    <?php
}


// ====================================================================
// TOKEN CSRF + DAFTAR MENU
// ====================================================================
if (empty($_SESSION['adm_csrf'])) {
    $_SESSION['adm_csrf'] = bin2hex(random_bytes(32));
}

$menu_daftar = array(
    'barang'     => array('Pendataan Barang', 'bi-box-seam'),
    'lelang'     => array('Kelola Lelang',    'bi-hammer'),
    'history'    => array('History Lelang',   'bi-clock-history'),
    'laporan'    => array('Laporan',          'bi-file-earmark-text'),
    'masyarakat' => array('Data Masyarakat',  'bi-people'),
);
if ($is_admin) {
    $menu_daftar['petugas'] = array('Registrasi Petugas', 'bi-person-plus');
}

$menu    = is_string($_GET['menu'] ?? null) ? $_GET['menu'] : 'barang';
$sub     = is_string($_GET['sub'] ?? null) ? $_GET['sub'] : '';
$id_edit = (int) ($_GET['id'] ?? 0);

$pesan_error      = '';          // pesan gagal (ditampilkan di atas panel)
$form_lama        = array();     // isi form terakhir supaya tidak hilang saat gagal
$foto_baru_upload = '';          // foto yang baru diupload di request ini


// ====================================================================
// PROSES AKSI (semua lewat POST + token CSRF)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    $aksi = $_POST['aksi'];

    if (!hash_equals((string) $_SESSION['adm_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $pesan_error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        try {
            switch ($aksi) {

                // ---------------- BARANG: TAMBAH ----------------
                case 'tambah_barang':
                    $menu = 'barang';
                    $sub  = 'tambah';

                    $nama      = trim($_POST['nama_barang'] ?? '');
                    $tgl       = trim($_POST['tgl'] ?? '');
                    $harga     = (int) preg_replace('/[^0-9]/', '', (string) ($_POST['harga_awal'] ?? ''));
                    $deskripsi = trim($_POST['deskripsi_barang'] ?? '');
                    if ($tgl === '') {
                        $tgl = date('Y-m-d');
                    }
                    $form_lama = array('nama_barang' => $nama, 'tgl' => $tgl, 'harga_awal' => $harga, 'deskripsi_barang' => $deskripsi);

                    $foto_baru_upload = adm_upload_foto($_FILES['foto'] ?? null, $img_dir, $pesan_error);
                    if ($pesan_error === '' && $nama === '') {
                        $pesan_error = 'Nama barang wajib diisi.';
                    }

                    if ($pesan_error === '') {
                        $hasil = adm_jalankan(
                            $conn,
                            "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, foto) VALUES (?, ?, ?, ?, ?)",
                            'ssiss',
                            array($nama, $tgl, $harga, $deskripsi, $foto_baru_upload)
                        );
                        if ($hasil >= 0) {
                            adm_redirect('barang', 'Barang baru berhasil ditambahkan.');
                        }
                        $pesan_error = 'Gagal menyimpan data barang.';
                    }
                    break;

                // ---------------- BARANG: EDIT ----------------
                case 'edit_barang':
                    $menu    = 'barang';
                    $sub     = 'edit';
                    $id_edit = (int) ($_POST['id_barang'] ?? 0);

                    $lama = adm_ambil($conn, "SELECT * FROM tb_barang WHERE id_barang = ?", 'i', array($id_edit));
                    if (!$lama) {
                        adm_redirect('barang', 'Data barang tidak ditemukan.', 'danger');
                    }
                    $lama = $lama[0];

                    $nama      = trim($_POST['nama_barang'] ?? '');
                    $tgl       = trim($_POST['tgl'] ?? '');
                    $harga     = (int) preg_replace('/[^0-9]/', '', (string) ($_POST['harga_awal'] ?? ''));
                    $deskripsi = trim($_POST['deskripsi_barang'] ?? '');
                    if ($tgl === '') {
                        $tgl = date('Y-m-d');
                    }
                    $form_lama = array('nama_barang' => $nama, 'tgl' => $tgl, 'harga_awal' => $harga, 'deskripsi_barang' => $deskripsi);

                    $foto_baru_upload = adm_upload_foto($_FILES['foto'] ?? null, $img_dir, $pesan_error);
                    if ($pesan_error === '' && $nama === '') {
                        $pesan_error = 'Nama barang wajib diisi.';
                    }

                    if ($pesan_error === '') {
                        $nama_foto = ($foto_baru_upload !== '') ? $foto_baru_upload : (string) $lama['foto'];
                        $hasil = adm_jalankan(
                            $conn,
                            "UPDATE tb_barang SET nama_barang = ?, tgl = ?, harga_awal = ?, deskripsi_barang = ?, foto = ? WHERE id_barang = ?",
                            'ssissi',
                            array($nama, $tgl, $harga, $deskripsi, $nama_foto, $id_edit)
                        );
                        if ($hasil >= 0) {
                            // Foto lama dibuang HANYA setelah database berhasil diperbarui
                            if ($foto_baru_upload !== '') {
                                adm_hapus_file($img_dir, $lama['foto']);
                            }
                            adm_redirect('barang', 'Data barang berhasil diperbarui.');
                        }
                        $pesan_error = 'Gagal memperbarui data barang.';
                    }
                    break;

                // ---------------- BARANG: HAPUS ----------------
                case 'hapus_barang':
                    $menu      = 'barang';
                    $id_barang = (int) ($_POST['id_barang'] ?? 0);

                    // Barang yang lelangnya masih berjalan tidak boleh dihapus
                    $aktif = adm_ambil(
                        $conn,
                        "SELECT id_lelang FROM tb_lelang WHERE id_barang = ? AND status = 'dibuka'",
                        'i',
                        array($id_barang)
                    );
                    if ($aktif) {
                        adm_redirect('barang', 'Barang sedang dilelang. Tutup lelangnya dulu sebelum menghapus.', 'danger');
                    }

                    $lama  = adm_ambil($conn, "SELECT foto FROM tb_barang WHERE id_barang = ?", 'i', array($id_barang));
                    $hasil = adm_jalankan($conn, "DELETE FROM tb_barang WHERE id_barang = ?", 'i', array($id_barang));
                    if ($hasil > 0) {
                        if ($lama) {
                            adm_hapus_file($img_dir, $lama[0]['foto']);
                        }
                        adm_redirect('barang', 'Barang berhasil dihapus.');
                    }
                    adm_redirect('barang', 'Barang gagal dihapus.', 'danger');
                    break;

                // ---------------- LELANG: BUKA (khusus Petugas) ----------------
                case 'buka_lelang':
                    $menu = 'lelang';
                    if ($is_admin) {
                        $pesan_error = 'Administrator tidak memiliki hak akses untuk membuka lelang!';
                        break;
                    }
                    $id_barang = (int) ($_POST['id_barang'] ?? 0);
                    $sub       = 'buka';

                    $ada_barang = adm_ambil($conn, "SELECT id_barang FROM tb_barang WHERE id_barang = ?", 'i', array($id_barang));
                    $sudah      = adm_ambil($conn, "SELECT id_lelang FROM tb_lelang WHERE id_barang = ?", 'i', array($id_barang));
                    if (!$ada_barang) {
                        $pesan_error = 'Barang tidak ditemukan.';
                    } elseif ($sudah) {
                        $pesan_error = 'Barang ini sudah terdaftar dalam sesi lelang!';
                    } else {
                        // PERBAIKAN: kolom tb_lelang.foto wajib terisi (NOT NULL, tanpa default),
                        // jadi fotonya disalin dari tb_barang saat lelang dibuka.
                        $hasil = adm_jalankan(
                            $conn,
                            "INSERT INTO tb_lelang (id_barang, tgl_lelang, id_petugas, status, foto)
                             SELECT id_barang, ?, ?, 'dibuka', COALESCE(foto, '')
                             FROM tb_barang WHERE id_barang = ?",
                            'sii',
                            array(date('Y-m-d'), (int) ($_SESSION['id_petugas'] ?? 0), $id_barang)
                        );
                        if ($hasil >= 0) {
                            adm_redirect('lelang', 'Sesi lelang berhasil dibuka.');
                        }
                        $pesan_error = 'Gagal membuka sesi lelang.';
                    }
                    break;

                // ---------------- LELANG: TUTUP (khusus Petugas) ----------------
                case 'tutup_lelang':
                    $menu = 'lelang';
                    if ($is_admin) {
                        $pesan_error = 'Administrator tidak memiliki hak akses untuk menutup lelang!';
                        break;
                    }
                    $id_lelang = (int) ($_POST['id_lelang'] ?? 0);

                    $lelang = adm_ambil($conn, "SELECT status FROM tb_lelang WHERE id_lelang = ?", 'i', array($id_lelang));
                    if (!$lelang || $lelang[0]['status'] !== 'dibuka') {
                        $pesan_error = 'Sesi lelang tidak ditemukan atau sudah ditutup.';
                        break;
                    }

                    // Penawaran tertinggi otomatis jadi pemenang
                    $tertinggi = adm_ambil(
                        $conn,
                        "SELECT penawaran_harga, id_user FROM history_lelang WHERE id_lelang = ? ORDER BY penawaran_harga DESC LIMIT 1",
                        'i',
                        array($id_lelang)
                    );
                    if ($tertinggi) {
                        $hasil = adm_jalankan(
                            $conn,
                            "UPDATE tb_lelang SET harga_akhir = ?, id_user = ?, status = 'ditutup' WHERE id_lelang = ?",
                            'dii',
                            array((float) $tertinggi[0]['penawaran_harga'], (int) $tertinggi[0]['id_user'], $id_lelang)
                        );
                    } else {
                        // Tidak ada yang menawar: tutup tanpa pemenang
                        $hasil = adm_jalankan($conn, "UPDATE tb_lelang SET status = 'ditutup' WHERE id_lelang = ?", 'i', array($id_lelang));
                    }
                    if ($hasil >= 0) {
                        adm_redirect('lelang', 'Sesi lelang berhasil ditutup.');
                    }
                    $pesan_error = 'Gagal menutup sesi lelang.';
                    break;

                // ---------------- MASYARAKAT: TAMBAH ----------------
                case 'tambah_masyarakat':
                    $menu = 'masyarakat';
                    $sub  = 'tambah';

                    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
                    $username     = trim($_POST['username'] ?? '');
                    $password     = (string) ($_POST['password'] ?? '');
                    $telp         = trim($_POST['telp'] ?? '');
                    $form_lama    = array('nama_lengkap' => $nama_lengkap, 'username' => $username, 'telp' => $telp);

                    if ($nama_lengkap === '' || $username === '' || $password === '') {
                        $pesan_error = 'Nama lengkap, username, dan password wajib diisi.';
                    } elseif (adm_username_dipakai($conn, $username)) {
                        $pesan_error = 'Username sudah digunakan! Pakai username lain.';
                    } else {
                        $hasil = adm_jalankan(
                            $conn,
                            "INSERT INTO tb_masyarakat (nama_lengkap, username, password, telp) VALUES (?, ?, ?, ?)",
                            'ssss',
                            array($nama_lengkap, $username, password_hash($password, PASSWORD_DEFAULT), $telp)
                        );
                        if ($hasil >= 0) {
                            adm_redirect('masyarakat', 'Akun masyarakat baru berhasil didaftarkan!');
                        }
                        $pesan_error = 'Gagal mendaftarkan masyarakat.';
                    }
                    break;

                // ---------------- PETUGAS: TAMBAH (khusus Administrator) ----------------
                case 'tambah_petugas':
                    $menu = 'petugas';
                    if (!$is_admin) {
                        $pesan_error = 'Akses ditolak! Fitur ini khusus Administrator.';
                        break;
                    }
                    $sub = 'tambah';

                    $nama_petugas = trim($_POST['nama_petugas'] ?? '');
                    $username     = trim($_POST['username'] ?? '');
                    $password     = (string) ($_POST['password'] ?? '');
                    $level_baru   = (int) ($_POST['id_level'] ?? 0);
                    $form_lama    = array('nama_petugas' => $nama_petugas, 'username' => $username, 'id_level' => $level_baru);

                    if ($nama_petugas === '' || $username === '' || $password === '') {
                        $pesan_error = 'Nama, username, dan password wajib diisi.';
                    } elseif ($level_baru !== 1 && $level_baru !== 2) {
                        $pesan_error = 'Level akses tidak valid.';
                    } elseif (adm_username_dipakai($conn, $username)) {
                        $pesan_error = 'Username sudah digunakan!';
                    } else {
                        $hasil = adm_jalankan(
                            $conn,
                            "INSERT INTO tb_petugas (nama_petugas, username, password, id_level) VALUES (?, ?, ?, ?)",
                            'sssi',
                            array($nama_petugas, $username, password_hash($password, PASSWORD_DEFAULT), $level_baru)
                        );
                        if ($hasil >= 0) {
                            adm_redirect('petugas', 'Petugas baru berhasil ditambahkan!');
                        }
                        $pesan_error = 'Gagal mendaftarkan petugas.';
                    }
                    break;
            }
        } catch (Throwable $e) {
            $pesan_error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }

    // Kalau gagal, foto yang terlanjur diupload dibuang supaya folder img tidak menumpuk
    if ($pesan_error !== '' && $foto_baru_upload !== '') {
        adm_hapus_file($img_dir, $foto_baru_upload);
    }
}

// Menu yang tidak dikenal / bukan hak akses -> kembali ke Pendataan Barang
if (!isset($menu_daftar[$menu])) {
    $menu = 'barang';
    $sub  = '';
}
// Administrator tidak boleh membuka form "Buka Lelang"
if ($menu === 'lelang' && $sub === 'buka' && $is_admin) {
    adm_redirect('lelang', 'Administrator tidak memiliki hak akses untuk membuka lelang!', 'danger');
}

// Pesan sukses dari redirect sebelumnya (tampil sekali saja)
$flash = $_SESSION['adm_flash'] ?? null;
unset($_SESSION['adm_flash']);


// ====================================================================
// SIAPKAN DATA UNTUK MENU YANG SEDANG DIBUKA
// ====================================================================
$form               = array();
$riwayat_per_lelang = array();
$barang_tersedia    = array();

// Data daftar untuk SEMUA panel, diambil sekaligus supaya seluruh panel bisa
// ditampilkan bersamaan di satu halaman tanpa perlu klik tab dulu.
$data_barang     = array();
$data_lelang     = array();
$data_history    = array();
$data_masyarakat = array();
$data_petugas    = array();

// Sedang membuka salah satu form (tambah/edit/buka)? Kalau ya, cuma form itu
// yang disiapkan datanya; kalau tidak, semua panel daftar disiapkan sekaligus.
$sedang_form = in_array($sub, array('tambah', 'edit', 'buka'), true);

try {
    if ($sedang_form && $menu === 'barang' && ($sub === 'tambah' || $sub === 'edit')) {
        $form = array('id_barang' => 0, 'nama_barang' => '', 'tgl' => date('Y-m-d'), 'harga_awal' => '', 'deskripsi_barang' => '', 'foto' => '');
        if ($sub === 'edit') {
            $baris = adm_ambil($conn, "SELECT * FROM tb_barang WHERE id_barang = ?", 'i', array($id_edit));
            if (!$baris) {
                adm_redirect('barang', 'Data barang tidak ditemukan.', 'danger');
            }
            $form = $baris[0];
        }
        $form = array_merge($form, $form_lama);
    } elseif ($sedang_form && $menu === 'lelang' && $sub === 'buka') {
        $barang_tersedia = adm_ambil(
            $conn,
            "SELECT id_barang, nama_barang, harga_awal FROM tb_barang
             WHERE id_barang NOT IN (SELECT id_barang FROM tb_lelang) ORDER BY nama_barang"
        );
    }

    // Barang yang sudah laku (lelang ditutup + ada pemenang) tidak ditampilkan lagi,
    // tetap tersimpan untuk History & Laporan.
    $data_barang = adm_ambil(
        $conn,
        "SELECT tb_barang.* FROM tb_barang
         WHERE tb_barang.id_barang NOT IN (
             SELECT tb_lelang.id_barang FROM tb_lelang
             WHERE tb_lelang.status = 'ditutup' AND tb_lelang.harga_akhir IS NOT NULL
         )
         ORDER BY tb_barang.id_barang DESC"
    );

    $data_lelang = adm_ambil(
        $conn,
        "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, tb_barang.foto,
                tb_barang.deskripsi_barang, tb_masyarakat.nama_lengkap
         FROM tb_lelang
         JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
         LEFT JOIN tb_masyarakat ON tb_lelang.id_user = tb_masyarakat.id_user
         ORDER BY tb_lelang.id_lelang DESC"
    );
    $riwayat_per_lelang = adm_riwayat_per_lelang($conn);

    $data_history = adm_ambil(
        $conn,
        "SELECT history_lelang.*, tb_barang.nama_barang, tb_masyarakat.nama_lengkap
         FROM history_lelang
         JOIN tb_lelang ON history_lelang.id_lelang = tb_lelang.id_lelang
         JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang
         LEFT JOIN tb_masyarakat ON history_lelang.id_user = tb_masyarakat.id_user
         ORDER BY history_lelang.id_history DESC"
    );

    $data_masyarakat = adm_ambil($conn, "SELECT * FROM tb_masyarakat ORDER BY id_user DESC");

    if ($is_admin) {
        $data_petugas = adm_ambil(
            $conn,
            "SELECT id_petugas, nama_petugas, username, id_level FROM tb_petugas
             WHERE id_petugas != ? ORDER BY id_petugas DESC",
            'i',
            array((int) ($_SESSION['id_petugas'] ?? 0))
        );
    }
} catch (Throwable $e) {
    $pesan_error = 'Gagal mengambil data: ' . $e->getMessage();
}

$badge_level = $is_admin
    ? '<span class="badge bg-danger">Administrator</span>'
    : '<span class="badge bg-success">Petugas</span>';
?>
<style>
  .adm-card { background: var(--surface-color); border-radius: 14px; box-shadow: 0 0 25px rgba(0,0,0,.08); padding: 24px; }
  .adm-tabs { gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
  .adm-tabs .nav-link { border-radius: 50px; padding: 8px 18px; font-weight: 600; font-size: 14px;
                        color: var(--heading-color); background: var(--surface-color); box-shadow: 0 0 12px rgba(0,0,0,.06); }
  .adm-tabs .nav-link.active { background: var(--accent-color); color: var(--contrast-color); }
  .adm-table th { font-weight: 600; color: var(--heading-color); white-space: nowrap;
                  background: color-mix(in srgb, var(--accent-color), transparent 94%) !important; }
  .adm-table td { vertical-align: middle; }
  .adm-thumb { width: 55px; height: 55px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,.1); }
  .adm-preview { width: 140px; height: 140px; border: 2px dashed #ced4da; border-radius: 10px; display: flex;
                 align-items: center; justify-content: center; overflow: hidden; background: #f8f9fa; color: #adb5bd; font-size: 28px; }
  .adm-preview img { width: 100%; height: 100%; object-fit: cover; }
  .adm-modal-img { width: 100%; max-height: 300px; object-fit: cover; border-radius: 10px; }
  .adm-label { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; }

  /* Saat menu diklik, judul panel berhenti tepat di bawah navbar (tidak tertutup) */
  [id^="panel-"] { scroll-margin-top: 140px; }

  /* Pesan sukses/gagal melayang di bawah navbar, jadi tetap terlihat walau halaman
     langsung loncat ke panel di bawahnya */
  .adm-flash { position: fixed; top: 135px; left: 50%; transform: translateX(-50%);
               width: min(640px, 92%); z-index: 1050; }

  @media print {
    body * { visibility: hidden !important; }
    #adm-cetak, #adm-cetak * { visibility: visible !important; }
    #adm-cetak { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; }
    .adm-noprint { display: none !important; }
  }
</style>

<section id="services" class="services section">

  <div class="container section-title" data-aos="fade-up">
    <h2>Panel <?= $is_admin ? 'Administrator' : 'Petugas' ?></h2>
    <p>Kelola barang, lelang, dan data pengguna langsung dari sini</p>
  </div>

  <div class="container">

    <!-- MENU PANEL: semua panel sudah tampil sekaligus di bawah, tab ini cuma lompat cepat -->
    <ul class="nav nav-pills adm-tabs adm-noprint">
      <?php foreach ($menu_daftar as $kunci => $info) : ?>
        <li class="nav-item">
          <a class="nav-link" href="#panel-<?= adm_h($kunci) ?>">
            <i class="bi <?= adm_h($info[1]) ?> me-1"></i> <?= adm_h($info[0]) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- PESAN (melayang di bawah navbar) -->
    <?php if ($flash || $pesan_error !== '') : ?>
      <div class="adm-flash adm-noprint">
        <?php if ($flash) : ?>
          <div class="alert alert-<?= adm_h($flash['tipe']) ?> alert-dismissible fade show rounded-4 shadow" role="alert">
            <?= adm_h($flash['pesan']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
          </div>
        <?php endif; ?>
        <?php if ($pesan_error !== '') : ?>
          <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow" role="alert">
            <?= adm_h($pesan_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
          </div>
        <?php endif; ?>
      </div>
      <script>
        // Pesan hilang sendiri setelah 6 detik
        setTimeout(function () {
          if (!window.bootstrap) return;
          document.querySelectorAll('.adm-flash .alert').forEach(function (el) {
            bootstrap.Alert.getOrCreateInstance(el).close();
          });
        }, 6000);
      </script>
    <?php endif; ?>


    <?php /* =================== PENDATAAN BARANG: FORM TAMBAH / EDIT =================== */ ?>
    <?php if ($sedang_form && $menu === 'barang' && ($sub === 'tambah' || $sub === 'edit')) : ?>

      <?php
        $judul_form = ($sub === 'edit') ? 'Edit Barang Lelang' : 'Tambah Barang Lelang';
        $foto_form  = adm_foto_url($form['foto'] ?? '');
        adm_judul(
            $judul_form,
            ($sub === 'edit') ? 'Kosongkan kolom foto bila tidak ingin mengganti gambar' : 'Lengkapi data barang beserta fotonya',
            '<a href="' . adm_h(adm_url('barang')) . '" class="btn btn-outline-accent rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Kembali</a>'
        );
      ?>

      <div class="adm-card">
        <form method="POST" action="<?= adm_h(adm_url('barang')) ?>" enctype="multipart/form-data">
          <?= adm_csrf_field() ?>
          <input type="hidden" name="aksi" value="<?= $sub === 'edit' ? 'edit_barang' : 'tambah_barang' ?>">
          <?php if ($sub === 'edit') : ?>
            <input type="hidden" name="id_barang" value="<?= (int) $form['id_barang'] ?>">
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Foto Barang</label>
              <div class="adm-preview mb-2" id="admPreview">
                <?php if ($foto_form !== '') : ?>
                  <img src="<?= adm_h($foto_form) ?>" alt="Foto barang">
                <?php else : ?>
                  <i class="bi bi-image"></i>
                <?php endif; ?>
              </div>
              <input type="file" name="foto" id="admInputFoto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
              <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            </div>

            <div class="col-md-8">
              <div class="mb-3">
                <label class="form-label fw-semibold">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" required value="<?= adm_h($form['nama_barang']) ?>">
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Tanggal Input</label>
                  <input type="date" name="tgl" class="form-control" value="<?= adm_h($form['tgl']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Harga Awal</label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="harga_awal" class="form-control" min="0" required value="<?= adm_h($form['harga_awal']) ?>">
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Deskripsi Barang</label>
                <textarea name="deskripsi_barang" class="form-control" rows="4"><?= adm_h($form['deskripsi_barang']) ?></textarea>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <button type="submit" class="btn btn-accent rounded-pill px-4"><i class="bi bi-save me-2"></i><?= $sub === 'edit' ? 'Update Barang' : 'Simpan Barang' ?></button>
          <a href="<?= adm_h(adm_url('barang')) ?>" class="btn btn-outline-accent rounded-pill px-4">Batal</a>
        </form>
      </div>

      <script>
        document.getElementById('admInputFoto').addEventListener('change', function () {
          var kotak = document.getElementById('admPreview');
          if (this.files && this.files[0]) {
            var img = document.createElement('img');
            img.src = URL.createObjectURL(this.files[0]);
            kotak.innerHTML = '';
            kotak.appendChild(img);
          }
        });
      </script>

    <?php endif; ?>


    <?php /* =================== KELOLA LELANG: FORM BUKA LELANG =================== */ ?>
    <?php if ($sedang_form && $menu === 'lelang' && $sub === 'buka') : ?>

      <?php
        adm_judul(
            'Buka Sesi Lelang Baru',
            'Pilih barang yang akan dilelang',
            '<a href="' . adm_h(adm_url('lelang')) . '" class="btn btn-outline-accent rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Kembali</a>'
        );
      ?>

      <div class="adm-card">
        <?php if (count($barang_tersedia) === 0) : ?>
          <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <p class="mt-3 mb-3 fw-semibold">Semua barang sudah masuk sesi lelang. Tambahkan barang baru dulu.</p>
            <a href="<?= adm_h(adm_url('barang', array('sub' => 'tambah'))) ?>" class="btn btn-accent rounded-pill px-4">Tambah Barang</a>
          </div>
        <?php else : ?>
          <form method="POST" action="<?= adm_h(adm_url('lelang')) ?>">
            <?= adm_csrf_field() ?>
            <input type="hidden" name="aksi" value="buka_lelang">
            <div class="mb-3">
              <label class="form-label fw-semibold">Pilih Barang</label>
              <select name="id_barang" class="form-select" required>
                <option value="">-- Pilih Barang yang Akan Dilelang --</option>
                <?php foreach ($barang_tersedia as $b) : ?>
                  <option value="<?= (int) $b['id_barang'] ?>"><?= adm_h($b['nama_barang']) ?> (Harga Awal: <?= adm_rp($b['harga_awal']) ?>)</option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Hanya menampilkan barang yang belum pernah masuk sesi lelang.</div>
            </div>
            <button type="submit" class="btn btn-accent rounded-pill px-4"><i class="bi bi-hammer me-2"></i>Buka Lelang</button>
            <a href="<?= adm_h(adm_url('lelang')) ?>" class="btn btn-outline-accent rounded-pill px-4">Batal</a>
          </form>
        <?php endif; ?>
      </div>

    <?php endif; ?>


    <?php /* =================== DATA MASYARAKAT: FORM TAMBAH =================== */ ?>
    <?php if ($sedang_form && $menu === 'masyarakat' && $sub === 'tambah') : ?>

      <?php
        adm_judul(
            'Tambah Masyarakat',
            'Daftarkan akun masyarakat baru',
            '<a href="' . adm_h(adm_url('masyarakat')) . '" class="btn btn-outline-accent rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Kembali</a>'
        );
      ?>

      <div class="adm-card">
        <form method="POST" action="<?= adm_h(adm_url('masyarakat')) ?>">
          <?= adm_csrf_field() ?>
          <input type="hidden" name="aksi" value="tambah_masyarakat">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Lengkap</label>
              <input type="text" name="nama_lengkap" class="form-control" required value="<?= adm_h($form_lama['nama_lengkap'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Username</label>
              <input type="text" name="username" class="form-control" required autocomplete="off" value="<?= adm_h($form_lama['username'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Password</label>
              <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">No. Telepon</label>
              <input type="text" name="telp" class="form-control" required value="<?= adm_h($form_lama['telp'] ?? '') ?>">
            </div>
          </div>
          <hr class="my-4">
          <button type="submit" class="btn btn-accent rounded-pill px-4"><i class="bi bi-person-plus me-2"></i>Simpan</button>
          <a href="<?= adm_h(adm_url('masyarakat')) ?>" class="btn btn-outline-accent rounded-pill px-4">Batal</a>
        </form>
      </div>

    <?php endif; ?>


    <?php /* =================== REGISTRASI PETUGAS (ADMIN): FORM TAMBAH =================== */ ?>
    <?php if ($sedang_form && $menu === 'petugas' && $sub === 'tambah') : ?>

      <?php
        adm_judul(
            'Tambah Petugas Baru',
            'Status login: ' . $badge_level . ' (full akses sistem)',
            '<a href="' . adm_h(adm_url('petugas')) . '" class="btn btn-outline-accent rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Kembali</a>'
        );
        $level_form = (int) ($form_lama['id_level'] ?? 0);
      ?>

      <div class="adm-card">
        <form method="POST" action="<?= adm_h(adm_url('petugas')) ?>">
          <?= adm_csrf_field() ?>
          <input type="hidden" name="aksi" value="tambah_petugas">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Lengkap Petugas</label>
              <input type="text" name="nama_petugas" class="form-control" required placeholder="Contoh: Budi Santoso" value="<?= adm_h($form_lama['nama_petugas'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Username</label>
              <input type="text" name="username" class="form-control" required autocomplete="off" placeholder="Contoh: budi_petugas" value="<?= adm_h($form_lama['username'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Password</label>
              <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Level Akses</label>
              <select name="id_level" class="form-select" required>
                <option value="" disabled <?= $level_form === 0 ? 'selected' : '' ?>>Pilih Level Akses</option>
                <option value="1" <?= $level_form === 1 ? 'selected' : '' ?>>Administrator (Full Akses)</option>
                <option value="2" <?= $level_form === 2 ? 'selected' : '' ?>>Petugas (Akses Standar)</option>
              </select>
            </div>
          </div>
          <hr class="my-4">
          <button type="submit" class="btn btn-accent rounded-pill px-4"><i class="bi bi-person-plus me-2"></i>Simpan Petugas</button>
          <a href="<?= adm_h(adm_url('petugas')) ?>" class="btn btn-outline-accent rounded-pill px-4">Batal</a>
        </form>
      </div>

    <?php endif; ?>


    <?php /* =================== SEMUA PANEL TAMPIL SEKALIGUS (tidak perlu klik tab) =================== */ ?>
    <?php if (!$sedang_form) : ?>

      <?php /* ---------- PENDATAAN BARANG ---------- */ ?>
      <div id="panel-barang" class="mb-5">
      <?php
        adm_judul(
            'Pendataan Barang Lelang',
            'Status login: ' . $badge_level . ' &middot; Barang yang sudah laku terjual otomatis tidak ditampilkan di sini',
            '<a href="' . adm_h(adm_url('barang', array('sub' => 'tambah'))) . '" class="btn btn-accent rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Tambah Barang</a>'
        );
      ?>

      <div class="adm-card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th class="text-center">Foto</th>
                <th>Nama Barang</th>
                <th>Tanggal Input</th>
                <th>Harga Awal</th>
                <th>Deskripsi</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_barang) > 0) : ?>
                <?php foreach ($data_barang as $no => $row) : ?>
                  <?php $foto = adm_foto_url($row['foto']); ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="text-center">
                      <?php if ($foto !== '') : ?>
                        <img src="<?= adm_h($foto) ?>" alt="<?= adm_h($row['nama_barang']) ?>" class="adm-thumb">
                      <?php else : ?>
                        <span class="badge bg-secondary">No Image</span>
                      <?php endif; ?>
                    </td>
                    <td class="fw-bold"><?= adm_h($row['nama_barang']) ?></td>
                    <td><?= adm_h($row['tgl']) ?></td>
                    <td><span class="fw-semibold text-success"><?= adm_rp($row['harga_awal']) ?></span></td>
                    <td class="text-muted text-truncate" style="max-width: 180px;"><?= adm_h($row['deskripsi_barang']) ?></td>
                    <td class="text-center text-nowrap">
                      <button type="button" class="btn btn-info btn-sm text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#admModalBarang<?= (int) $row['id_barang'] ?>">
                        <i class="bi bi-eye me-1"></i> Detail
                      </button>
                      <a href="<?= adm_h(adm_url('barang', array('sub' => 'edit', 'id' => (int) $row['id_barang']))) ?>" class="btn btn-warning btn-sm rounded-pill px-3">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                      </a>
                      <form method="POST" action="<?= adm_h(adm_url('barang')) ?>" class="d-inline" onsubmit="return confirm('Hapus barang ini? Foto barang juga akan dihapus.');">
                        <?= adm_csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus_barang">
                        <input type="hidden" name="id_barang" value="<?= (int) $row['id_barang'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3"><i class="bi bi-trash me-1"></i> Hapus</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="bi bi-box-seam d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada data barang yang tersedia.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-barang -->


      <?php /* ---------- KELOLA LELANG ---------- */ ?>
      <div id="panel-lelang" class="mb-5">
      <?php
        adm_judul(
            'Kelola Sesi Lelang',
            'Status login: ' . $badge_level . ($is_admin ? ' (hanya memantau)' : ' (buka atau tutup sesi lelang)'),
            $is_admin ? '' : '<a href="' . adm_h(adm_url('lelang', array('sub' => 'buka'))) . '" class="btn btn-accent rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Buka Lelang Baru</a>'
        );
      ?>

      <div class="adm-card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th class="text-center">Foto</th>
                <th>Nama Barang</th>
                <th>Tgl Lelang</th>
                <th>Harga Awal</th>
                <th>Harga Akhir</th>
                <th>Pemenang</th>
                <th class="text-center">Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_lelang) > 0) : ?>
                <?php foreach ($data_lelang as $no => $row) : ?>
                  <?php $foto = adm_foto_url($row['foto']); ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="text-center">
                      <?php if ($foto !== '') : ?>
                        <img src="<?= adm_h($foto) ?>" alt="<?= adm_h($row['nama_barang']) ?>" class="adm-thumb">
                      <?php else : ?>
                        <span class="badge bg-secondary">No Image</span>
                      <?php endif; ?>
                    </td>
                    <td class="fw-bold"><?= adm_h($row['nama_barang']) ?></td>
                    <td><?= adm_h($row['tgl_lelang']) ?></td>
                    <td><?= adm_rp($row['harga_awal']) ?></td>
                    <td class="fw-semibold text-success"><?= $row['harga_akhir'] ? adm_rp($row['harga_akhir']) : '-' ?></td>
                    <td>
                      <?php if (!empty($row['nama_lengkap'])) : ?>
                        <i class="bi bi-trophy text-warning me-1"></i><?= adm_h($row['nama_lengkap']) ?>
                      <?php else : ?>
                        <span class="text-muted fst-italic">Belum ada</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><?= adm_badge_status($row['status']) ?></td>
                    <td class="text-center text-nowrap">
                      <button type="button" class="btn btn-info btn-sm text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#admModalLelang<?= (int) $row['id_lelang'] ?>">
                        <i class="bi bi-eye me-1"></i> Detail
                      </button>
                      <?php if (!$is_admin && $row['status'] === 'dibuka') : ?>
                        <form method="POST" action="<?= adm_h(adm_url('lelang')) ?>" class="d-inline" onsubmit="return confirm('Tutup sesi lelang ini dan tentukan pemenang?');">
                          <?= adm_csrf_field() ?>
                          <input type="hidden" name="aksi" value="tutup_lelang">
                          <input type="hidden" name="id_lelang" value="<?= (int) $row['id_lelang'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3"><i class="bi bi-lock me-1"></i> Tutup</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-hammer d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada sesi lelang yang dibuka.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-lelang -->


      <?php /* ---------- HISTORY LELANG ---------- */ ?>
      <div id="panel-history" class="mb-5">
      <?php adm_judul('History Penawaran Lelang', 'Daftar riwayat harga yang ditawarkan oleh masyarakat'); ?>

      <div class="adm-card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th>Nama Barang</th>
                <th>Nama Penawar</th>
                <th>Harga Ditawar</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_history) > 0) : ?>
                <?php foreach ($data_history as $no => $row) : ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="fw-bold"><?= adm_h($row['nama_barang']) ?></td>
                    <td><?= adm_h($row['nama_lengkap'] ?? 'Masyarakat') ?></td>
                    <td class="fw-semibold text-success"><?= adm_rp($row['penawaran_harga']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-5">
                    <i class="bi bi-clock-history d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada riwayat penawaran lelang.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-history -->


      <?php /* ---------- LAPORAN ---------- */ ?>
      <div id="panel-laporan" class="mb-5">
      <?php
        adm_judul(
            'Laporan Hasil Lelang',
            'Status login: ' . $badge_level . ' &middot; Rekapitulasi data seluruh pelaksanaan lelang',
            '<button type="button" onclick="window.print()" class="btn btn-accent rounded-pill px-4 adm-noprint"><i class="bi bi-printer me-2"></i>Cetak Laporan</button>'
        );
      ?>

      <div class="adm-card" id="adm-cetak">
        <h5 class="fw-bold mb-1 d-none d-print-block">Laporan Hasil Lelang - Lelang Online</h5>
        <div class="text-muted small mb-3 d-none d-print-block">Dicetak pada <?= date('d-m-Y') ?></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th>Nama Barang</th>
                <th>Tgl Lelang</th>
                <th>Harga Awal</th>
                <th>Harga Akhir</th>
                <th>Pemenang</th>
                <th class="text-center">Status</th>
                <th class="text-center adm-noprint">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_lelang) > 0) : ?>
                <?php foreach ($data_lelang as $no => $row) : ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="fw-bold"><?= adm_h($row['nama_barang']) ?></td>
                    <td><?= adm_h($row['tgl_lelang']) ?></td>
                    <td><?= adm_rp($row['harga_awal']) ?></td>
                    <td class="fw-semibold text-success"><?= $row['harga_akhir'] ? adm_rp($row['harga_akhir']) : '-' ?></td>
                    <td><?= !empty($row['nama_lengkap']) ? adm_h($row['nama_lengkap']) : '<span class="text-muted fst-italic">Belum ada</span>' ?></td>
                    <td class="text-center"><?= adm_badge_status($row['status']) ?></td>
                    <td class="text-center adm-noprint">
                      <button type="button" class="btn btn-info btn-sm text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#admModalLelang<?= (int) $row['id_lelang'] ?>">
                        <i class="bi bi-clock-history me-1"></i> Detail
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-5">
                    <i class="bi bi-file-earmark-text d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada data laporan lelang yang tersedia.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-laporan -->


      <?php /* ---------- DATA MASYARAKAT ---------- */ ?>
      <div id="panel-masyarakat" class="mb-5">
      <?php
        adm_judul(
            'Daftar Masyarakat Terdaftar',
            'Kelola data akun masyarakat yang berpartisipasi',
            '<a href="' . adm_h(adm_url('masyarakat', array('sub' => 'tambah'))) . '" class="btn btn-accent rounded-pill px-4"><i class="bi bi-person-plus me-2"></i>Tambah Masyarakat</a>'
        );
      ?>

      <div class="adm-card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>No. Telepon</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_masyarakat) > 0) : ?>
                <?php foreach ($data_masyarakat as $no => $row) : ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="fw-bold"><i class="bi bi-person-circle me-2" style="color: var(--accent-color);"></i><?= adm_h($row['nama_lengkap']) ?></td>
                    <td class="text-muted">@<?= adm_h($row['username']) ?></td>
                    <td><?= adm_h($row['telp']) ?></td>
                    <td class="text-center">
                      <button type="button" class="btn btn-info btn-sm text-white rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#admModalMasy<?= (int) $row['id_user'] ?>">
                        <i class="bi bi-eye me-1"></i> Detail
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-5">
                    <i class="bi bi-people d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada masyarakat yang terdaftar.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-masyarakat -->


      <?php if ($is_admin) : ?>
      <?php /* ---------- REGISTRASI PETUGAS (khusus Administrator) ---------- */ ?>
      <div id="panel-petugas" class="mb-5">
      <?php
        adm_judul(
            'Registrasi Petugas',
            'Status login: ' . $badge_level . ' (full akses sistem)',
            '<a href="' . adm_h(adm_url('petugas', array('sub' => 'tambah'))) . '" class="btn btn-accent rounded-pill px-4"><i class="bi bi-person-plus me-2"></i>Tambah Petugas</a>'
        );
      ?>

      <div class="adm-card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 adm-table">
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th>Nama Petugas</th>
                <th>Username</th>
                <th class="text-center">Level Akses</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_petugas) > 0) : ?>
                <?php foreach ($data_petugas as $no => $row) : ?>
                  <tr>
                    <td class="text-center text-muted"><?= $no + 1 ?></td>
                    <td class="fw-bold"><i class="bi bi-person-badge me-2" style="color: var(--accent-color);"></i><?= adm_h($row['nama_petugas']) ?></td>
                    <td class="text-muted">@<?= adm_h($row['username']) ?></td>
                    <td class="text-center">
                      <?php if ((int) $row['id_level'] === 1) : ?>
                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Administrator</span>
                      <?php else : ?>
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">Petugas</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-5">
                    <i class="bi bi-person-badge d-block mb-2" style="font-size: 2.5rem; opacity: .5;"></i>
                    Belum ada data petugas lain.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div><!-- /#panel-petugas -->
      <?php endif; ?>

    <?php endif; ?>

  </div>

</section>

<script>
// Scroll ke panel diatur di sini sendiri (tidak bergantung pada main.js template),
// dengan memperhitungkan tinggi navbar yang menempel di atas.
(function () {
  function keLuar() {
    var h = document.getElementById('header');
    return (h ? h.offsetHeight : 120) + 20;
  }
  function kePanel(el) {
    var y = el.getBoundingClientRect().top + window.pageYOffset - keLuar();
    window.scrollTo({ top: y, behavior: 'smooth' });
  }

  // Klik link "#panel-xxx" (navbar & tab): scroll sendiri, matikan handler template
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[href^="#panel-"]') : null;
    if (!a) return;
    var el = document.getElementById(a.getAttribute('href').slice(1));
    if (!el) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    kePanel(el);
    history.replaceState(null, '', a.getAttribute('href'));
    // Tutup menu mobile kalau sedang terbuka
    if (document.body.classList.contains('mobile-nav-active')) {
      var t = document.querySelector('.mobile-nav-toggle');
      if (t) t.click();
    }
  }, true);

  // Datang dari redirect (misal setelah simpan/tutup lelang) dengan #panel-xxx di URL
  window.addEventListener('load', function () {
    if (location.hash.indexOf('#panel-') !== 0) return;
    var el = document.getElementById(location.hash.slice(1));
    if (el) setTimeout(function () { kePanel(el); }, 500);
  });
})();
</script>

<?php
// ====================================================================
// MODAL DETAIL (ditaruh SETELAH section supaya tidak terpotong layout tema)
// ====================================================================

// Detail barang + tombol Edit & Hapus
if (!$sedang_form) :
    foreach ($data_barang as $row) :
        $foto = adm_foto_url($row['foto']);
        if ($foto === '') {
            $foto = $foto_placeholder;
        }
        ?>
        <div class="modal fade" id="admModalBarang<?= (int) $row['id_barang'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
              <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Detail Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
              </div>
              <div class="modal-body">
                <div class="row g-4">
                  <div class="col-md-5">
                    <img src="<?= adm_h($foto) ?>" class="adm-modal-img" alt="<?= adm_h($row['nama_barang']) ?>">
                  </div>
                  <div class="col-md-7">
                    <h4 class="fw-bold mb-3"><?= adm_h($row['nama_barang']) ?></h4>
                    <div class="adm-label">Tanggal Input</div>
                    <div class="mb-3"><?= adm_h($row['tgl']) ?></div>
                    <div class="adm-label">Harga Awal</div>
                    <div class="mb-3 fw-bold text-success fs-5"><?= adm_rp($row['harga_awal']) ?></div>
                    <div class="adm-label">Deskripsi</div>
                    <div class="text-muted"><?= nl2br(adm_h($row['deskripsi_barang'])) ?></div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <a href="<?= adm_h(adm_url('barang', array('sub' => 'edit', 'id' => (int) $row['id_barang']))) ?>" class="btn btn-warning rounded-pill px-4">
                  <i class="bi bi-pencil-square me-1"></i> Edit
                </a>
                <form method="POST" action="<?= adm_h(adm_url('barang')) ?>" class="d-inline" onsubmit="return confirm('Hapus barang ini? Foto barang juga akan dihapus.');">
                  <?= adm_csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus_barang">
                  <input type="hidden" name="id_barang" value="<?= (int) $row['id_barang'] ?>">
                  <button type="submit" class="btn btn-danger rounded-pill px-4"><i class="bi bi-trash me-1"></i> Hapus</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php
    endforeach;
endif;

// Detail sesi lelang + riwayat penawaran (dipakai bersama oleh panel Kelola Lelang & Laporan)
if (!$sedang_form) :
    foreach ($data_lelang as $row) :
        adm_modal_lelang($row, $riwayat_per_lelang[$row['id_lelang']] ?? array(), $foto_placeholder);
    endforeach;
endif;

// Detail masyarakat
if (!$sedang_form) :
    foreach ($data_masyarakat as $row) :
        ?>
        <div class="modal fade" id="admModalMasy<?= (int) $row['id_user'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
              <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-vcard me-2"></i>Detail Masyarakat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
              </div>
              <div class="modal-body">
                <h5 class="fw-bold mb-0"><?= adm_h($row['nama_lengkap']) ?></h5>
                <div class="text-muted mb-3">@<?= adm_h($row['username']) ?></div>
                <div class="d-flex justify-content-between py-2 border-top"><span class="text-secondary"><i class="bi bi-telephone me-2"></i>No. Telepon</span><span class="fw-semibold"><?= adm_h($row['telp']) ?></span></div>
                <?php if (!empty($row['email'])) : ?>
                  <div class="d-flex justify-content-between py-2 border-top"><span class="text-secondary"><i class="bi bi-envelope me-2"></i>Email</span><span class="fw-semibold"><?= adm_h($row['email']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($row['alamat'])) : ?>
                  <div class="d-flex justify-content-between py-2 border-top"><span class="text-secondary"><i class="bi bi-geo-alt me-2"></i>Alamat</span><span class="fw-semibold text-end" style="max-width: 60%;"><?= adm_h($row['alamat']) ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between py-2 border-top"><span class="text-secondary"><i class="bi bi-hash me-2"></i>ID Pengguna</span><span class="fw-semibold">#<?= (int) $row['id_user'] ?></span></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>
        <?php
    endforeach;
endif;