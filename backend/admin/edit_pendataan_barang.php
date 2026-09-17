<?php
session_start();
include 'connection.php';

// Validasi Login
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level'];
$error    = '';

// Validasi parameter ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header("Location: pendataan_barang.php");
    exit;
}

// Ambil data lama
$stmt = mysqli_prepare($conn, "SELECT * FROM tb_barang WHERE id_barang = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$barang) {
    header("Location: pendataan_barang.php");
    exit;
}

$fotoLama = $barang['foto'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama      = trim($_POST['nama_barang']);
    $tgl       = $_POST['tgl'] ?: date('Y-m-d');
    $harga     = (int) preg_replace('/[^0-9]/', '', $_POST['harga_awal']);
    $deskripsi = trim($_POST['deskripsi_barang']);
    $namaFoto  = $fotoLama; // default: pertahankan foto lama

    // ---------- GANTI FOTO (opsional) ----------
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {

        $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $error = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 2 MB.';
        } elseif (!@getimagesize($_FILES['foto']['tmp_name'])) {
            $error = 'File yang diunggah bukan gambar yang valid.';
        } else {
            if (!is_dir('img')) {
                mkdir('img', 0775, true);
            }
            $namaBaru = 'brg_' . time() . '_' . rand(100, 999) . '.' . $ext;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], 'img/' . $namaBaru)) {
                // Hapus foto lama supaya folder img/ tidak menumpuk file sampah
                if ($fotoLama !== '' && file_exists('img/' . $fotoLama)) {
                    unlink('img/' . $fotoLama);
                }
                $namaFoto = $namaBaru;
            } else {
                $error = 'Gagal memindahkan file foto ke folder img/.';
            }
        }
    }

    // ---------- UPDATE DATABASE ----------
    if ($error === '' && $nama !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE tb_barang
             SET nama_barang = ?, tgl = ?, harga_awal = ?, deskripsi_barang = ?, foto = ?
             WHERE id_barang = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ssissi', $nama, $tgl, $harga, $deskripsi, $namaFoto, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: pendataan_barang.php?status=edit_sukses");
            exit;
        }
        $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
    } elseif ($error === '') {
        $error = 'Nama barang wajib diisi.';
    }

    // Supaya form tetap menampilkan input terakhir milik user
    $barang['nama_barang']      = $nama;
    $barang['tgl']              = $tgl;
    $barang['harga_awal']       = $harga;
    $barang['deskripsi_barang'] = $deskripsi;
    $barang['foto']             = $namaFoto;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - E-Lelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            position: fixed;
            width: 260px;
            top: 0;
            left: 0;
            padding-top: 25px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar h4 { letter-spacing: 1px; }
        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 22px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin: 4px 12px;
            border-radius: 8px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background: #ffffff;
        }
        .preview-box {
            width: 140px;
            height: 140px;
            border: 2px dashed #ced4da;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
            color: #adb5bd;
        }
        .preview-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>

    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="pendataan_barang.php" class="active"><i class="fas fa-box me-3 fa-fw"></i> Pendataan Barang</a>
        <a href="kelola_lelang.php"><i class="fas fa-balance-scale me-3 fa-fw"></i> Kelola Lelang</a>
        <a href="history_lelang.php"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
        <a href="laporan.php"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
        <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

        <?php if ($id_level == 1) : ?>
            <hr class="text-white-50 mx-4 my-3">
            <small class="text-warning px-4 fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">MENU ADMIN</small>
            <a href="registrasi_petugas.php"><i class="fas fa-user-shield me-3 fa-fw"></i> Registrasi Petugas</a>
        <?php endif; ?>
    </div>

    <div class="p-3 mb-2">
        <a href="logout.php" class="btn btn-danger w-100 text-white shadow-sm py-2 rounded-pill"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Edit Barang Lelang</h5>
            <small class="text-muted">Kosongkan kolom foto bila tidak ingin mengganti gambar</small>
        </div>
        <a href="pendataan_barang.php" class="btn btn-light border rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <div class="card card-custom p-4">
        <?php if ($error !== '') : ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Foto Barang</label>
                    <div class="preview-box mb-2" id="previewBox">
                        <?php if (!empty($barang['foto']) && file_exists("img/" . $barang['foto'])) : ?>
                            <img src="img/<?= htmlspecialchars($barang['foto']); ?>" alt="Foto Barang">
                        <?php else : ?>
                            <i class="fas fa-image fa-2x"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="foto" id="inputFoto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">Biarkan kosong untuk mempertahankan foto lama.</div>
                </div>

                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" required
                               value="<?= htmlspecialchars($barang['nama_barang']); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tanggal Input</label>
                            <input type="date" name="tgl" class="form-control"
                                   value="<?= htmlspecialchars($barang['tgl']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Harga Awal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga_awal" class="form-control" min="0" required
                                       value="<?= htmlspecialchars($barang['harga_awal']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Barang</label>
                        <textarea name="deskripsi_barang" class="form-control" rows="4"><?= htmlspecialchars($barang['deskripsi_barang']); ?></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-save me-2"></i> Update Barang
            </button>
            <a href="pendataan_barang.php" class="btn btn-light border rounded-pill px-4">Batal</a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('inputFoto').addEventListener('change', function () {
        const box = document.getElementById('previewBox');
        if (this.files && this.files[0]) {
            const url = URL.createObjectURL(this.files[0]);
            box.innerHTML = '<img src="' + url + '" alt="preview">';
        }
    });
</script>
</body>
</html>