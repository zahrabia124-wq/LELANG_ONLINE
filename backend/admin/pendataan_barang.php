<?php
session_start();
include 'connection.php';

// Validasi Login
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level'];

// Ambil data barang dari database
// Barang yang SUDAH LAKU (sesi lelangnya berstatus 'ditutup' DAN sudah ada pemenang/harga_akhir)
// tidak ditampilkan lagi di sini, karena sudah selesai transaksinya.
// Data barangnya tetap aman tersimpan di database untuk keperluan History & Laporan.
$query  = "SELECT tb_barang.* FROM tb_barang
           WHERE tb_barang.id_barang NOT IN (
               SELECT tb_lelang.id_barang FROM tb_lelang
               WHERE tb_lelang.status = 'ditutup' AND tb_lelang.harga_akhir IS NOT NULL
           )
           ORDER BY tb_barang.id_barang DESC";
$result = mysqli_query($conn, $query);

// Simpan semua baris ke array supaya bisa dipakai dua kali:
// sekali untuk tabel, sekali lagi untuk modal Detail.
$data_barang = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Pesan notifikasi
$notif = '';
$tipe  = 'success';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'tambah_sukses':
            $notif = 'Barang baru berhasil ditambahkan.';
            break;
        case 'edit_sukses':
            $notif = 'Data barang berhasil diperbarui.';
            break;
        case 'hapus_sukses':
            $notif = 'Barang berhasil dihapus.';
            break;
        case 'hapus_gagal':
            $notif = 'Barang gagal dihapus.';
            $tipe = 'danger';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendataan Barang - E-Lelang</title>
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
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar h4 {
            letter-spacing: 1px;
        }

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

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background: #ffffff;
        }

        .table th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            vertical-align: middle;
            color: #212529;
        }

        .btn-action {
            padding: 0.35rem 0.65rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }

        .img-barang {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Modal detail */
        .img-detail {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }

        .img-detail-kosong {
            width: 100%;
            height: 220px;
            border-radius: 10px;
            background: #f1f3f5;
            color: #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .label-detail {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
        }
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
                <h5 class="mb-0 fw-bold text-dark">Pendataan Barang Lelang</h5>
                <small class="text-muted">
                    <?php if ($id_level == 1) : ?>
                        Status Login: <span class="badge bg-danger">Administrator</span> (Akses Pendataan Barang)
                    <?php else : ?>
                        Status Login: <span class="badge bg-success">Petugas</span> (Akses Pendataan Barang)
                    <?php endif; ?>
                    <span class="text-muted ms-2"><i class="fas fa-circle-info me-1"></i>Barang yang sudah laku terjual otomatis tidak ditampilkan di sini</span>
                </small>
            </div>

            <a href="tambah_pendataan_barang.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus me-2"></i> Tambah Barang
            </a>
        </div>

        <?php if ($notif !== '') : ?>
            <div class="alert alert-<?= $tipe; ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                <i class="fas fa-circle-check me-2"></i> <?= htmlspecialchars($notif); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Data Barang -->
        <div class="card card-custom p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 text-center" style="width: 5%;">No</th>
                            <th class="py-3 text-center" style="width: 8%;">Foto</th>
                            <th class="py-3" style="width: 18%;">Nama Barang</th>
                            <th class="py-3" style="width: 14%;">Tanggal Input</th>
                            <th class="py-3" style="width: 15%;">Harga Awal</th>
                            <th class="py-3" style="width: 16%;">Deskripsi</th>
                            <th class="py-3 text-center" style="width: 24%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_barang) > 0) : ?>
                            <?php foreach ($data_barang as $no => $row) : ?>
                                <tr>
                                    <td class="text-center fw-semibold text-secondary"><?= $no + 1; ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto']) && file_exists("img/" . $row['foto'])) : ?>
                                            <img src="img/<?= htmlspecialchars($row['foto']); ?>"
                                                alt="<?= htmlspecialchars($row['nama_barang']); ?>"
                                                class="img-barang">
                                        <?php else : ?>
                                            <span class="badge bg-secondary">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                    <td><span class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($row['tgl']); ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-semibold">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span></td>
                                    <td class="text-muted text-truncate" style="max-width: 180px;"><?= htmlspecialchars($row['deskripsi_barang']); ?></td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="button"
                                                class="btn btn-info btn-action text-white shadow-sm" title="Detail Barang"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalBarang<?= (int) $row['id_barang']; ?>">
                                                <i class="fa-solid fa-eye"></i> Detail
                                            </button>
                                            <a href="edit_pendataan_barang.php?id=<?= (int) $row['id_barang']; ?>"
                                                class="btn btn-warning btn-action text-dark shadow-sm" title="Edit Barang">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                            <a href="hapus_pendataan_barang.php?id=<?= (int) $row['id_barang']; ?>"
                                                class="btn btn-danger btn-action text-white shadow-sm" title="Hapus Barang"
                                                onclick="return confirm('Hapus barang ini? Foto barang juga akan dihapus.');">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="py-3">
                                        <i class="fas fa-box-open fa-3x text-secondary mb-3 opacity-50"></i>
                                        <p class="mb-0">Belum ada data barang yang tersedia.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail Barang (satu modal per barang, ditaruh di luar tabel) -->
    <?php foreach ($data_barang as $row) : ?>
        <?php $ada_foto = !empty($row['foto']) && file_exists("img/" . $row['foto']); ?>
        <div class="modal fade" id="modalBarang<?= (int) $row['id_barang']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fas fa-box me-2"></i>Detail Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-5">
                                <?php if ($ada_foto) : ?>
                                    <img src="img/<?= htmlspecialchars($row['foto']); ?>"
                                        alt="<?= htmlspecialchars($row['nama_barang']); ?>"
                                        class="img-detail">
                                <?php else : ?>
                                    <div class="img-detail-kosong"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <h4 class="fw-bold mb-3"><?= htmlspecialchars($row['nama_barang']); ?></h4>

                                <div class="label-detail">Tanggal Input</div>
                                <div class="mb-3"><?= htmlspecialchars($row['tgl']); ?></div>

                                <div class="label-detail">Harga Awal</div>
                                <div class="mb-3 fw-bold text-success fs-5">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></div>

                                <div class="label-detail">Deskripsi</div>
                                <div class="text-muted"><?= nl2br(htmlspecialchars($row['deskripsi_barang'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        <a href="edit_pendataan_barang.php?id=<?= (int) $row['id_barang']; ?>" class="btn btn-warning rounded-pill px-4">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </a>
                        <a href="hapus_pendataan_barang.php?id=<?= (int) $row['id_barang']; ?>" class="btn btn-danger rounded-pill px-4"
                            onclick="return confirm('Hapus barang ini? Foto barang juga akan dihapus.');">
                            <i class="fa-solid fa-trash me-1"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>