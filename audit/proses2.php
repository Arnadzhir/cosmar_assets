<?php
include '../auth/auth.php';
allowRole([1,2]); // Hanya level 1 dan 2

include '../config/koneksi.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID audit tidak ditemukan!'];
    header("Location: index2.php");
    exit;
}

$audit_id = (int)$_GET['id'];

// Cek apakah data audit dengan ID tersebut ada dan statusnya 2 (Done)
$check = mysqli_query($conn, "SELECT status FROM tbl_audit WHERE audit_id = '$audit_id'");
if (!$check || mysqli_num_rows($check) == 0) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data audit tidak ditemukan!'];
    header("Location: index2.php");
    exit;
}

$row = mysqli_fetch_assoc($check);
if ($row['status'] != 2) {
    $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Data audit tidak dalam status Done, tidak dapat diubah menjadi Pending.'];
    header("Location: index2.php");
    exit;
}

// Update status dari 2 menjadi 1 (Pending)
$update = mysqli_query($conn, "UPDATE tbl_audit SET status = 1 WHERE audit_id = '$audit_id'");
if ($update) {
    $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Status audit berhasil diubah menjadi Pending.'];
} else {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal mengubah status audit: ' . mysqli_error($conn)];
}

header("Location: index2.php");
exit;
?>