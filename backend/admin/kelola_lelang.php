<?php
session_start();
include 'connection.php';

// Validasi Login
if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_level = $_SESSION['id_level']; 
// Berdasarkan UKK RPL: 
// Level 1 = Administrator
// Level 2 = Petugas (atau tingkat selain admin yang berhak membuka/menutup lelang)

// Proses Buka Lelang Baru (Hanya Petugas yang bisa memproses)
if (isset($_POST['buka_lelang'])) {
    if ($id_level == 1) {
        $error = "Administrator tidak memiliki hak akses untuk membuka lelang!";
    } else {
        $id_barang = $_POST['id_barang'];
        $tgl_lelang = date('Y-m-d');
        $id_petugas = $_SESSION['id_petugas'];
        $status = 'dibuka';

        // Cek apakah barang sudah pernah dilelang sebelumnya
        $cek = mysqli_query($conn, "SELECT * FROM tb_lelang WHERE id_barang = '$id_barang'");
        if (mysqli_num_rows($cek) == 0) {
            $stmt = mysqli_prepare($conn, "INSERT INTO tb_lelang (id_barang, tgl_lelang, id_petugas, status) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isis", $id_barang, $tgl_lelang, $id_petugas, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: kelola_lelang.php?status=dibuka");
            exit;
        } else {
            $error = "Barang ini sudah terdaftar dalam sesi lelang!";
        }
    }
}

// Proses Tutup Lelang (Hanya Petugas yang bisa memproses)
if (isset($_GET['tutup'])) {
    if ($id_level == 1) {
        header("Location: kelola_lelang.php");
        exit;
    }
    
    $id_lelang = $_GET['tutup'];
    $status = 'ditutup';

    // Cari penawaran tertinggi menggunakan history_lelang
    $q_tawaran = mysqli_query($conn, "SELECT * FROM history_lelang WHERE id_lelang = '$id_lelang' ORDER BY penawaran_harga DESC LIMIT 1");
    
    if ($q_tawaran && mysqli_num_rows($q_tawaran) > 0) {
        $data_tawaran = mysqli_fetch_assoc($q_tawaran);
        $harga_akhir = $data_tawaran['penawaran_harga'];
        
        $id_penawar = isset($data_tawaran['id_user']) ? $data_tawaran['id_user'] : (isset($data_tawaran['id_masyarakat']) ? $data_tawaran['id_masyarakat'] : null);

        // Update status lelang menjadi ditutup, catat harga akhir dan pemenangnya
        $stmt = mysqli_prepare($conn, "UPDATE tb_lelang SET harga_akhir = ?, id_user = ?, status = ? WHERE id_lelang = ?");
        mysqli_stmt_bind_param($stmt, "disi", $harga_akhir, $id_penawar, $status, $id_lelang);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Jika tidak ada yang menawar, tutup lelang tanpa pemenang
        $stmt = mysqli_prepare($conn, "UPDATE tb_lelang SET status = ? WHERE id_lelang = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $id_lelang);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: kelola_lelang.php?status=ditutup");
    exit;
}

// Ambil data lelang digabung dengan tb_barang dan tb_masyarakat
// Termasuk foto, tanggal input barang, dan deskripsi barang (untuk ditampilkan di modal Detail)
$query = "SELECT tb_lelang.*, tb_barang.nama_barang, tb_barang.harga_awal, tb_barang.foto,
                 tb_barang.tgl AS tgl_input_barang, tb_barang.deskripsi_barang,
                 tb_masyarakat.nama_lengkap 
          FROM tb_lelang 
          JOIN tb_barang ON tb_lelang.id_barang = tb_barang.id_barang 
          LEFT JOIN tb_masyarakat ON tb_lelang.id_user = tb_masyarakat.id_user 
          ORDER BY tb_lelang.id_lelang DESC";
$result = mysqli_query($conn, $query);

// Simpan semua baris lelang ke array (supaya bisa dipakai 2x: bikin baris tabel & bikin modal)
$data_lelang = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_lelang[] = $row;
}

// Ambil semua riwayat penawaran sekaligus, lalu kelompokkan per id_lelang
// supaya tidak perlu query berulang-ulang di dalam loop
$history_by_lelang = [];
$q_history = mysqli_query($conn, "SELECT history_lelang.id_lelang, history_lelang.penawaran_harga, tb_masyarakat.nama_lengkap
                                   FROM history_lelang
                                   LEFT JOIN tb_masyarakat ON history_lelang.id_user = tb_masyarakat.id_user
                                   ORDER BY history_lelang.penawaran_harga DESC");
if ($q_history) {
    while ($h = mysqli_fetch_assoc($q_history)) {
        $history_by_lelang[$h['id_lelang']][] = $h;
    }
}

// Ambil data barang yang BELUM dilelang untuk pilihan di modal/form buka lelang
$q_barang = mysqli_query($conn, "SELECT * FROM tb_barang WHERE id_barang NOT IN (SELECT id_barang FROM tb_lelang)");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lelang - E-Lelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%); color: white; position: fixed; width: 260px; top: 0; left: 0; padding-top: 25px; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar h4 { letter-spacing: 1px; }
        .sidebar a { color: rgba(255, 255, 255, 0.85); text-decoration: none; display: flex; align-items: center; padding: 12px 22px; font-size: 0.95rem; transition: all 0.3s ease; margin: 4px 12px; border-radius: 8px; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(255, 255, 255, 0.15); color: white; transform: translateX(4px); }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); background: #ffffff; }
        .table th { font-weight: 600; color: #495057; background-color: #f8f9fa !important; border-bottom: 2px solid #dee2e6; }
        .table td { vertical-align: middle; color: #212529; }
        .btn-action { padding: 0.35rem 0.75rem; font-size: 0.875rem; border-radius: 6px; }
        .form-control, .form-select { padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #dee2e6; }
        .img-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .img-modal-detail { width: 100%; max-height: 240px; object-fit: cover; border-radius: 12px; margin-bottom: 16px; background-color: #e9ecef; }
        .detail-label { font-size: 0.8rem; color: #6c757d; margin-bottom: 2px; }
        .detail-value { font-weight: 600; color: #212529; margin-bottom: 14px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column">
    <h4 class="text-center fw-bold mb-4"><i class="fas fa-gavel me-2"></i>E-LELANG</h4>
    
    <div class="flex-grow-1">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-3 fa-fw"></i> Dashboard</a>
        <a href="pendataan_barang.php"><i class="fas fa-box me-3 fa-fw"></i> Pendataan Barang</a>
        <a href="kelola_lelang.php" class="active"><i class="fas fa-balance-scale me-3 fa-fw"></i> Kelola Lelang</a>
        <a href="history_lelang.php"><i class="fas fa-history me-3 fa-fw"></i> History Lelang</a>
        <a href="laporan.php"><i class="fas fa-file-alt me-3 fa-fw"></i> Generate Laporan</a>
        <a href="pendataan_masyarakat.php"><i class="fas fa-users me-3 fa-fw"></i> Data Masyarakat</a>

        <!-- MENU KHUSUS ADMIN -->
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
    <!-- Header Section -->
    <div class="bg-white p-3 px-4 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Kelola Sesi Lelang</h5>
            <small class="text-muted">
                <?php if ($id_level == 1) : ?>
                    Status Login: <span class="badge bg-danger">Administrator</span> (Hanya Memantau)
                <?php else : ?>
                    Status Login: <span class="badge bg-success">Petugas</span> (Buka atau tutup sesi lelang)
                <?php endif; ?>
            </small>
        </div>
        
        <!-- Tombol Buka Lelang Baru HANYA MUNCUL untuk PETUGAS (Level != 1) -->
        <?php if ($id_level != 1) : ?>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBukaLelang">
                <i class="fas fa-plus me-2"></i> Buka Lelang Baru
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabel Kelola Lelang -->
    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 text-center" style="width: 5%;">No</th>
                        <th class="py-3 text-center" style="width: 8%;">Foto</th>
                        <th class="py-3" style="width: 15%;">Nama Barang</th>
                        <th class="py-3" style="width: 10%;">Tgl Lelang</th>
                        <th class="py-3" style="width: 13%;">Harga Awal</th>
                        <th class="py-3" style="width: 13%;">Harga Akhir</th>
                        <th class="py-3" style="width: 13%;">Pemenang</th>
                        <th class="py-3 text-center" style="width: 10%;">Status</th>
                        <th class="py-3 text-center" style="width: 13%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data_lelang) > 0) : ?>
                        <?php $no = 1; foreach ($data_lelang as $row) : ?>
                        <?php
                            $fotoPath = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
                                ? "img/" . htmlspecialchars($row['foto'])
                                : null;
                        ?>
                        <tr>
                            <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="text-center">
                                <?php if ($fotoPath) : ?>
                                    <img src="<?= $fotoPath; ?>" alt="<?= htmlspecialchars($row['nama_barang']); ?>" class="img-thumb">
                                <?php else : ?>
                                    <span class="badge bg-secondary">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']); ?></td>
                            <td><span class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= $row['tgl_lelang']; ?></span></td>
                            <td><span class="text-secondary fw-semibold">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></span></td>
                            <td><span class="text-success fw-semibold"><?= $row['harga_akhir'] ? 'Rp ' . number_format($row['harga_akhir'], 0, ',', '.') : '-'; ?></span></td>
                            <td><?= isset($row['nama_lengkap']) && $row['nama_lengkap'] ? '<span class="fw-semibold text-dark"><i class="fas fa-user-check text-success me-1"></i> ' . htmlspecialchars($row['nama_lengkap']) . '</span>' : '<span class="text-muted fst-italic">Belum ada</span>'; ?></td>
                            <td class="text-center">
                                <?php if ($row['status'] == 'dibuka') : ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Dibuka</span>
                                <?php else : ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">Ditutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <!-- Kontrol Aksi Berdasarkan Hak Akses UKK -->
                                <?php if ($id_level == 1) : ?>
                                    <!-- Administrator hanya bisa melihat detail (modal) -->
                                    <button type="button" class="btn btn-info btn-action text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_lelang']; ?>" title="Lihat Detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                <?php else : ?>
                                    <!-- Petugas bisa menutup lelang jika status dibuka, atau lihat detail jika sudah ditutup -->
                                    <?php if ($row['status'] == 'dibuka') : ?>
                                        <a href="kelola_lelang.php?tutup=<?= $row['id_lelang']; ?>" class="btn btn-danger btn-action shadow-sm" onclick="return confirm('Tutup sesi lelang ini dan tentukan pemenang?')">
                                            <i class="fas fa-lock me-1"></i> Tutup
                                        </a>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-info btn-action text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id_lelang']; ?>" title="Lihat Detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <div class="py-3">
                                    <i class="fas fa-balance-scale fa-3x text-secondary mb-3 opacity-50"></i>
                                    <p class="mb-0">Belum ada sesi lelang yang dibuka.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Buka Lelang (Hanya dimuat untuk Petugas) -->
<?php if ($id_level != 1) : ?>
<div class="modal fade" id="modalBukaLelang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="" method="POST">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">Buka Sesi Lelang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Pilih Barang</label>
                        <select name="id_barang" class="form-select" required>
                            <option value="">-- Pilih Barang yang Akan Dilelang --</option>
                            <?php while ($b = mysqli_fetch_assoc($q_barang)) : ?>
                                <option value="<?= $b['id_barang']; ?>">
                                    <?= htmlspecialchars($b['nama_barang']); ?> (Harga Awal: Rp <?= number_format($b['harga_awal'], 0, ',', '.'); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted mt-1 d-block">Hanya menampilkan barang yang belum pernah masuk sesi lelang.</small>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light px-4 rounded-pill border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="buka_lelang" class="btn btn-primary px-4 rounded-pill shadow-sm">Buka Lelang</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Detail per Barang (satu modal untuk tiap baris lelang) -->
<?php foreach ($data_lelang as $row) : ?>
    <?php
        $fotoModal = (!empty($row['foto']) && file_exists("img/" . $row['foto']))
            ? "img/" . htmlspecialchars($row['foto'])
            : "https://via.placeholder.com/500x300?text=No+Image";
        $riwayat = $history_by_lelang[$row['id_lelang']] ?? [];
    ?>
    <div class="modal fade" id="modalDetail<?= $row['id_lelang']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">Detail Barang & Riwayat Penawaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5">
                            <img src="<?= $fotoModal; ?>" class="img-modal-detail" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                        </div>
                        <div class="col-md-7">
                            <div class="detail-label">Nama Barang</div>
                            <div class="detail-value fs-5"><?= htmlspecialchars($row['nama_barang']); ?></div>

                            <div class="detail-label">Tanggal Input Barang</div>
                            <div class="detail-value"><i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($row['tgl_input_barang']); ?></div>

                            <div class="detail-label">Deskripsi</div>
                            <div class="detail-value fw-normal text-muted" style="font-weight: 400 !important;"><?= nl2br(htmlspecialchars($row['deskripsi_barang'])); ?></div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="detail-label">Harga Awal</div>
                                    <div class="detail-value">Rp <?= number_format($row['harga_awal'], 0, ',', '.'); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="detail-label">Harga Akhir</div>
                                    <div class="detail-value text-success"><?= $row['harga_akhir'] ? 'Rp ' . number_format($row['harga_akhir'], 0, ',', '.') : '-'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history me-2"></i>Riwayat Penawaran</h6>
                    <?php if (count($riwayat) > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Penawar</th>
                                        <th class="text-end">Harga Ditawar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $i => $h) : ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($h['nama_lengkap'] ?? 'Masyarakat'); ?>
                                                <?php if ($i === 0) : ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Tertinggi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-semibold text-success">Rp <?= number_format($h['penawaran_harga'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="text-muted text-center py-3 mb-0"><i class="fas fa-inbox me-2"></i>Belum ada penawaran untuk barang ini.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light px-4 rounded-pill border" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>