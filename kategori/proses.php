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

    $kategori_id    = $_POST['kategori_id'];
    $kategori_name  = upper($_POST['kategori_name']);
    $kategori_line  = upper($_POST['kategori_line']);
    $kategori_code  = upper($_POST['kategori_code']);
    $kategori_final = $kategori_name . ' - ' . $kategori_line;

    // CEK DUPLIKAT
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_kategori WHERE kategori_final='$kategori_final'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'kategori sudah terdaftar!'
        ];
        header("Location: tambah.php");
        exit;
    }

    mysqli_query($conn,
        "INSERT INTO tbl_kategori (kategori_id, kategori_name, kategori_line, kategori_code, kategori_final)
         VALUES ('$kategori_id', '$kategori_name', '$kategori_line', '$kategori_code', '$kategori_final')"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data kategori berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    $kategori_id    = $_POST['kategori_id'];
    $kategori_name  = upper($_POST['kategori_name']);
    $kategori_line  = upper($_POST['kategori_line']);
    $kategori_code  = upper($_POST['kategori_code']);
    $kategori_final = $kategori_name . ' - ' . $kategori_line;


    // CEK DUPLIKAT (KECUALI DIRINYA SENDIRI)
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_kategori 
         WHERE kategori_final='$kategori_final'
         AND kategori_id != '$kategori_id'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'kategori sudah digunakan oleh data lain!'
        ];
        header("Location: edit.php?id=$kategori_id");
        exit;
    }

    mysqli_query($conn,
        "UPDATE tbl_kategori SET
            kategori_name = '$kategori_name',
            kategori_line = '$kategori_line',
            kategori_code = '$kategori_code',
            kategori_final = '$kategori_final'
         WHERE kategori_id = '$kategori_id'"
    );

    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data kategori berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    mysqli_query($conn, "DELETE FROM tbl_kategori WHERE kategori_id='$_GET[id]'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data assets berhasil dihapus'
    ];

    header("Location: index.php");
    exit;
}
