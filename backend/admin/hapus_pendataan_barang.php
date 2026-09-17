<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['id_petugas'])) {
    header("Location: index.php");
    exit;
}

$id_barang = $_GET['id'] ?? null;

if ($id_barang) {
    $stmt = mysqli_prepare($conn, "DELETE FROM tb_barang WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: pendataan_barang.php");
exit;
?>