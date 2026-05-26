<?php
// Set timezone ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "cosmar_assets";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Optional: Set timezone untuk MySQL juga (agar konsisten)
mysqli_query($conn, "SET time_zone = '+07:00'");
?>