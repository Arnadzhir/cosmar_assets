<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Cek apakah ada data di session
if (isset($_SESSION['return_print_data'])) {
    // Hapus data session setelah print
    unset($_SESSION['return_print_data']);
    unset($_SESSION['return_print_date']);
    
    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Berita Acara Pengembalian telah dicetak. Asset berhasil dikembalikan ke perusahaan.'
    ];
} else {
    $_SESSION['alert'] = [
        'type' => 'warning',
        'msg'  => 'Tidak ada data pengembalian yang diproses.'
    ];
}

header("Location: index2.php");
exit;
?>