<?php
$role = $_SESSION['role'] ?? '';

// Menu petugas yang sedang dibuka (dari ?menu=... di URL), dipakai untuk menandai link yang aktif.
// Kosong = sedang di halaman awal (Home).
$menu_aktif = '';
if ($role === 'petugas' && is_string($_GET['menu'] ?? null)
    && in_array($_GET['menu'], array('barang', 'lelang', 'history', 'laporan', 'masyarakat', 'petugas'), true)) {
    $menu_aktif = $_GET['menu'];
}
?>
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container position-relative d-flex align-items-center justify-content-between">

      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">LELANG ONLINE</h1>
      </a>

      <nav id="navmenu" class="navmenu nav-spy">
        <ul>
          <?php if ($role === 'petugas') : ?>
            <!-- MENU PETUGAS/ADMIN (tanpa "Tentang").
                 Alamat lengkap (?menu=...#panel-...) selalu ada, jadi kalau panelnya belum ada di halaman
                 (misal sedang di Home) browser pindah halaman. Kalau panelnya sudah ada, script di bawah
                 langsung scroll tanpa reload. -->
            <li><a href="index.php#hero" data-home="1" class="<?= $menu_aktif === '' ? 'active' : '' ?>">Home</a></li>
            <li><a href="index.php?menu=barang#panel-barang" data-panel="barang" class="<?= $menu_aktif === 'barang' ? 'active' : '' ?>">Barang</a></li>
            <li><a href="index.php?menu=lelang#panel-lelang" data-panel="lelang" class="<?= $menu_aktif === 'lelang' ? 'active' : '' ?>">Lelang</a></li>
            <li><a href="index.php?menu=history#panel-history" data-panel="history" class="<?= $menu_aktif === 'history' ? 'active' : '' ?>">History</a></li>
            <li><a href="index.php?menu=laporan#panel-laporan" data-panel="laporan" class="<?= $menu_aktif === 'laporan' ? 'active' : '' ?>">Laporan</a></li>
            <li><a href="index.php?menu=masyarakat#panel-masyarakat" data-panel="masyarakat" class="<?= $menu_aktif === 'masyarakat' ? 'active' : '' ?>">Masyarakat</a></li>
            <?php if (($_SESSION['id_level'] ?? null) == 1) : ?>
              <li><a href="index.php?menu=petugas#panel-petugas" data-panel="petugas" class="<?= $menu_aktif === 'petugas' ? 'active' : '' ?>">Petugas</a></li>
            <?php endif; ?>
          <?php else : ?>
            <!-- MENU DEFAULT: pengunjung umum & masyarakat -->
            <li><a href="#hero" class="active">Home</a></li>
            <li><a href="#about">Tentang</a></li>
            <li><a href="#services">Produk Lelang</a></li>
            <li><a href="#portfolio">Penawaran Saya</a></li>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <?php if ($role === '') : ?>
        <!-- Belum login: tombol Login/Daftar tampil di navbar -->
        <div class="d-flex align-items-center gap-2 ms-3">
          <a href="index.php?form=login#hero" class="btn btn-light rounded-pill px-3">Login</a>
          <a href="index.php?form=daftar#hero" class="btn btn-outline-light rounded-pill px-3">Daftar</a>
        </div>

      <?php elseif ($role === 'masyarakat') : ?>
        <!-- Sudah login (masyarakat): Penawaran Saya & Logout, gayanya sama dengan Login/Daftar -->
        <div class="d-flex align-items-center gap-2 ms-3">
          <a href="#portfolio" class="btn btn-light rounded-pill px-3">Penawaran Saya</a>
          <a href="index.php?logout=1" class="btn btn-outline-light rounded-pill px-3">Logout</a>
        </div>

      <?php elseif ($role === 'petugas') : ?>
        <!-- Sudah login (petugas/admin): Logout -->
        <div class="d-flex align-items-center gap-2 ms-3">
          <a href="index.php?logout=1" class="btn btn-outline-light rounded-pill px-3">Logout</a>
        </div>
      <?php endif; ?>

    </div>
  </header>

<style>
  /* Menu navbar: yang sedang dibuka (sesuai posisi scroll) putih terang, sisanya redup */
  @media (min-width: 1200px) {
    #header .nav-spy a { color: rgba(255,255,255,.62); }
    #header .nav-spy a:hover { color: #fff; }
    #header .nav-spy a.active,
    #header .nav-spy a.active:focus {
      color: #fff; font-weight: 600;
      text-decoration: underline; text-decoration-thickness: 2px; text-underline-offset: 8px;
    }
  }
</style>
<script>
// Navigasi menu + penanda menu aktif (tidak bergantung pada main.js template)
(function () {
  function jarakAtas() {
    var h = document.getElementById('header');
    return (h ? h.offsetHeight : 120) + 20;
  }
  function keElemen(el, langsung) {
    var y = el.getBoundingClientRect().top + window.pageYOffset - jarakAtas();
    window.scrollTo({ top: y, behavior: langsung ? 'instant' : 'smooth' });
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('#navmenu a[data-panel], #navmenu a[data-home]') : null;
    if (!a) return;

    var target = a.hasAttribute('data-home')
      ? document.getElementById('hero')
      : document.getElementById('panel-' + a.getAttribute('data-panel'));

    // Panel/hero belum ada di halaman ini -> biarkan browser pindah halaman seperti biasa
    if (!target) return;

    e.preventDefault();
    e.stopImmediatePropagation();
    if (a.hasAttribute('data-home')) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      keElemen(target, false);
    }
    history.replaceState(null, '', a.getAttribute('href'));

    if (document.body.classList.contains('mobile-nav-active')) {
      var t = document.querySelector('.mobile-nav-toggle');
      if (t) t.click();
    }
  }, true);

  // Baru sampai lewat alamat ...#panel-xxx (klik dari Home, atau setelah simpan/tutup lelang):
  // ulangi scroll beberapa kali supaya tidak "dibatalkan" oleh script template / gambar yang baru termuat
  window.addEventListener('load', function () {
    if (location.hash.indexOf('#panel-') !== 0) return;
    var el = document.getElementById(location.hash.slice(1));
    if (!el) return;
    [150, 600, 1300].forEach(function (ms) {
      setTimeout(function () { keElemen(el, true); }, ms);
    });
  });

  // ---- Penanda menu aktif: mengikuti posisi scroll ----
  var petaMenu = [];
  document.querySelectorAll('#navmenu a').forEach(function (a) {
    var id = '';
    if (a.hasAttribute('data-home')) id = 'hero';
    else if (a.hasAttribute('data-panel')) id = 'panel-' + a.getAttribute('data-panel');
    else if ((a.getAttribute('href') || '').charAt(0) === '#') id = a.getAttribute('href').slice(1);
    if (id) petaMenu.push({ link: a, id: id });
  });

  function perbaruiAktif() {
    var batas = window.pageYOffset + jarakAtas() + 30;
    var terpilih = null;

    petaMenu.forEach(function (m) {
      var el = document.getElementById(m.id);
      if (!el) return;
      var atas = el.getBoundingClientRect().top + window.pageYOffset;
      if (atas <= batas) terpilih = m;
    });

    // Sudah mentok di dasar halaman -> menu paling bawah yang ada
    if (window.innerHeight + window.pageYOffset >= document.documentElement.scrollHeight - 4) {
      for (var i = petaMenu.length - 1; i >= 0; i--) {
        if (document.getElementById(petaMenu[i].id)) { terpilih = petaMenu[i]; break; }
      }
    }
    if (!terpilih) return;
    petaMenu.forEach(function (m) { m.link.classList.toggle('active', m === terpilih); });
  }

  // requestAnimationFrame jalan SETELAH script scroll-spy bawaan template, jadi hasil kita yang dipakai
  var menunggu = false;
  window.addEventListener('scroll', function () {
    if (menunggu) return;
    menunggu = true;
    requestAnimationFrame(function () { menunggu = false; perbaruiAktif(); });
  }, { passive: true });
  window.addEventListener('load', function () { setTimeout(perbaruiAktif, 300); });
  perbaruiAktif();
})();
</script>