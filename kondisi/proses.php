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

    $kondisi_id   = $_POST['kondisi_id'];
    $kondisi_name = upper($_POST['kondisi_name']);

    // CEK DUPLIKAT
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_kondisi WHERE kondisi_name='$kondisi_name'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'kondisi sudah terdaftar!'
        ];
        header("Location: tambah.php");
        exit;
    }

    mysqli_query($conn,
        "INSERT INTO tbl_kondisi (kondisi_id, kondisi_name)
         VALUES ('$kondisi_id', '$kondisi_name')"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data kondisi berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    $kondisi_id   = $_POST['kondisi_id'];
    $kondisi_name = upper($_POST['kondisi_name']);

    // CEK DUPLIKAT (KECUALI DIRINYA SENDIRI)
    $cek = mysqli_query($conn,
        "SELECT 1 FROM tbl_kondisi 
         WHERE kondisi_name='$kondisi_name'
         AND kondisi_id != '$kondisi_id'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'kondisi sudah digunakan oleh data lain!'
        ];
        header("Location: edit.php?id=$kondisi_id");
        exit;
    }

    mysqli_query($conn,
        "UPDATE tbl_kondisi SET
            kondisi_name = '$kondisi_name'
         WHERE kondisi_id = '$kondisi_id'"
    );

    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data kondisi berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    mysqli_query($conn, "DELETE FROM tbl_kondisi WHERE kondisi_id='$_GET[id]'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data assets berhasil dihapus'
    ];

    header("Location: index.php");
    exit;
}
