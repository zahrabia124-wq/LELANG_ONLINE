<?php
session_start();
include 'connection.php';

if (isset($_POST['daftar'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    // Menggunakan password_hash untuk keamanan data password
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telp         = mysqli_real_escape_string($conn, $_POST['telp']);

    // Cek apakah username sudah dipakai orang lain
    $cek_db = mysqli_query($conn, "SELECT * FROM tb_masyarakat WHERE username = '$username'");
    if (mysqli_num_rows($cek_db) > 0) {
        echo "<script>alert('Username sudah terdaftar! Silakan gunakan username lain.'); window.location='register.php';</script>";
        exit;
    }

    // Masukkan data ke tabel tb_masyarakat (sesuaikan nama kolom jika berbeda di database kamu)
    $query = "INSERT INTO tb_masyarakat (nama_lengkap, username, password, telp) VALUES ('$nama_lengkap', '$username', '$password', '$telp')";
    $insert = mysqli_query($conn, $query);

    if ($insert) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Registrasi gagal, silakan coba lagi.'); window.location='register.php';</script>";
    }
} else {
    header("Location: register.php");
    exit;
}
?>