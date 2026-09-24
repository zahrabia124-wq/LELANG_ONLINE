<?php
// ====================================================================
// head.php dimuat paling awal oleh index.php.
// Di sini kita menyambungkan FRONTEND ke BACKEND dalam 4 langkah.
// ====================================================================

// LANGKAH 1: mulai session (supaya tahu siapa yang login) + koneksi database
if (!headers_sent()) {
    session_start();
}
include_once __DIR__ . '/../../backend/admin/connection.php';   // menghasilkan variabel $conn

// Siapa yang login? isinya: 'masyarakat', 'petugas', atau '' (belum login)
$role = $_SESSION['role'] ?? '';


// LANGKAH 2: siapkan isi 4 section dari folder frontend/pages
// (ob_start + ob_get_clean = tangkap hasil file PHP menjadi teks)
ob_start();
include __DIR__ . '/../pages/hero.php';
$isi_hero = ob_get_clean();

ob_start();
include __DIR__ . '/../pages/about.php';
$isi_about = ob_get_clean();

// Section "services" dan "portfolio" isinya BEDA tergantung siapa yang login:
//   - masyarakat / pengunjung : services.php (produk lelang) + portfolio.php (penawaran saya)
//   - petugas / administrator : admin.php (panel kelola barang, lelang, dll.)
//                               portfolio dikosongkan karena khusus masyarakat
ob_start();
if ($role === 'petugas') {
    include __DIR__ . '/../pages/admin.php';
} else {
    include __DIR__ . '/../pages/services.php';
}
$isi_services = ob_get_clean();

ob_start();
if ($role !== 'petugas') {
    include __DIR__ . '/../pages/portfolio.php';
}
$isi_portfolio = ob_get_clean();


// LANGKAH 3: fungsi untuk menukar 1 section di halaman dengan isi baru.
// Cara kerja: cari tulisan <section id="..."> sampai </section>, lalu ganti.
function ganti_section($html, $id, $isi_baru)
{
    $awal = strpos($html, '<section id="' . $id . '"');
    if ($awal === false) {
        return $html;               // section tidak ditemukan, biarkan
    }

    $akhir = strpos($html, '</section>', $awal);
    if ($akhir === false) {
        return $html;
    }
    $akhir = $akhir + strlen('</section>');

    // bagian sebelum section + isi baru + bagian sesudah section
    return substr($html, 0, $awal) . $isi_baru . substr($html, $akhir);
}

// Fungsi ini dijalankan otomatis di akhir, saat halaman sudah selesai dibuat
function tukar_semua_section($html)
{
    global $isi_hero, $isi_about, $isi_services, $isi_portfolio;

    $html = ganti_section($html, 'hero', $isi_hero);
    $html = ganti_section($html, 'about', $isi_about);
    $html = ganti_section($html, 'services', $isi_services);
    $html = ganti_section($html, 'portfolio', $isi_portfolio);

    return $html;
}


// LANGKAH 4: mulai menampung halaman, lalu di akhir dijalankan fungsi di atas
ob_start('tukar_semua_section');
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Index - Anyar Bootstrap Template</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="frontend/template/assets/img/favicon.png" rel="icon">
  <link href="frontend/template/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="frontend/template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="frontend/template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="frontend/template/assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="frontend/template/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="frontend/template/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="frontend/template/assets/css/main.css" rel="stylesheet">

  <!-- Gaya tambahan untuk bagian yang terhubung ke backend -->
  <style>
    .btn-accent { background: var(--accent-color); border-color: var(--accent-color); color: var(--contrast-color); }
    .btn-accent:hover { background: color-mix(in srgb, var(--accent-color), #000 12%); border-color: transparent; color: var(--contrast-color); }
    .btn-outline-accent { border-color: var(--accent-color); color: var(--accent-color); background: transparent; }
    .btn-outline-accent:hover { background: var(--accent-color); color: var(--contrast-color); }

    .stat-box .stat-number { font-size: 34px; font-weight: 700; color: var(--heading-color); line-height: 1.1; }

    .lelang-card { background: var(--surface-color); border-radius: 14px; overflow: hidden; height: 100%;
                   box-shadow: 0 0 25px rgba(0,0,0,.08); display: flex; flex-direction: column; transition: transform .25s; }
    .lelang-card:hover { transform: translateY(-4px); }
    .lelang-card .thumb { width: 100%; height: 200px; object-fit: cover; background: #e9ecef; }
    .lelang-card .body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
    .lelang-card h4 { font-size: 19px; font-weight: 700; margin-bottom: 6px; }
    .lelang-card .desc { font-size: 14px; color: color-mix(in srgb, var(--default-color), transparent 30%);
                         display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .lelang-card .label { font-size: 12px; text-transform: uppercase; letter-spacing: .5px;
                          color: color-mix(in srgb, var(--default-color), transparent 40%); }
    .lelang-card .price { font-size: 20px; font-weight: 700; color: var(--accent-color); }
    .empty-state { text-align: center; padding: 50px 20px; background: var(--surface-color); border-radius: 14px;
                   box-shadow: 0 0 25px rgba(0,0,0,.06); }
    .empty-state i { font-size: 46px; color: color-mix(in srgb, var(--default-color), transparent 65%); }
  </style>

  <!-- =======================================================
  * Template Name: Anyar
  * Template URL: https://bootstrapmade.com/anyar-free-multipurpose-one-page-bootstrap-theme/
  * Updated: Aug 07 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>