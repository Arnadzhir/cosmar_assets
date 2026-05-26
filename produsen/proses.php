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

    $produsen_id     = $_POST['produsen_id'];
    $produsen_code   = upper($_POST['produsen_code']);
    $produsen_region = upper($_POST['produsen_region']);

    // CEK DUPLIKAT
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_produsen WHERE produsen_code='$produsen_code' AND produsen_region='$produsen_region'" 
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'produsen sudah terdaftar!'
        ];
        header("Location: tambah.php");
        exit;
    }

    mysqli_query($conn,
        "INSERT INTO tbl_produsen (produsen_id, produsen_code, produsen_region)
         VALUES ('$produsen_id', '$produsen_code', '$produsen_region')"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data produsen berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    $produsen_id     = $_POST['produsen_id'];
    $produsen_code   = upper($_POST['produsen_code']);
    $produsen_region = upper($_POST['produsen_region']);

    // CEK DUPLIKAT (KECUALI DIRINYA SENDIRI)
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_produsen 
         WHERE produsen_code='$produsen_code'
         AND produsen_region='$produsen_region'
         AND produsen_id != '$produsen_id'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'produsen sudah digunakan oleh data lain!'
        ];
        header("Location: edit.php?id=$produsen_id");
        exit;
    }

    mysqli_query($conn,
        "UPDATE tbl_produsen SET
            produsen_code = '$produsen_code',
            produsen_region = '$produsen_region'
         WHERE produsen_id = '$produsen_id'"
    );

    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data produsen berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    mysqli_query($conn, "DELETE FROM tbl_produsen WHERE produsen_id='$_GET[id]'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data assets berhasil dihapus'
    ];

    header("Location: index.php");
    exit;
}
