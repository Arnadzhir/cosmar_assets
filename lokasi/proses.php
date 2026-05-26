<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';
// =========================
// HELPER FUNCTION
// =========================
function upper($text) {
    return strtoupper(trim($text));
}
/* ==================
   TAMBAH DATA
================== */
if (isset($_POST['tambah'])) {

    mysqli_query($conn, "INSERT INTO tbl_lokasi VALUES (
        '".upper($_POST['lokasi_id'])."',
        '".upper($_POST['lokasi_name'])."',
        '".upper($_POST['lokasi_lantai'])."'
    )");

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data assets berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    mysqli_query($conn, "UPDATE tbl_lokasi SET
        lokasi_name       = '".upper($_POST['lokasi_name'])."',
        lokasi_lantai     = '".upper($_POST['lokasi_lantai'])."'
        WHERE lokasi_id   = '".$_POST['lokasi_id']."'
    ");


    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data assets berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    mysqli_query($conn, "DELETE FROM tbl_lokasi WHERE lokasi_id='$_GET[id]'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data assets berhasil dihapus'
    ];

    header("Location: index.php");
    exit;
}
