<?php
include '../auth/auth.php';
allowRole([1,2]);
include '../config/koneksi.php';

// Cek apakah semua data audit memenuhi syarat: status = 2 (Done) dan audit_status = 3 (Sesuai)
$checkQuery = "SELECT COUNT(*) as total, 
                      SUM(CASE WHEN status = 2 AND audit_status = 3 THEN 1 ELSE 0 END) as eligible
               FROM tbl_audit";
$result = mysqli_query($conn, $checkQuery);
$row = mysqli_fetch_assoc($result);
$total = $row['total'];
$eligible = $row['eligible'];

if ($total == 0) {
    $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Tidak ada data audit untuk ditransfer.'];
    header("Location: index2.php");
    exit;
}

if ($total != $eligible) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Tidak semua data audit memiliki status Done dan Sesuai. Transfer dibatalkan.'];
    header("Location: index2.php");
    exit;
}

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
    // 1. Hapus semua data dari tbl_primary
    mysqli_query($conn, "DELETE FROM tbl_primary");
    
    // 2. Salin data dari tbl_audit ke tbl_primary
    $copyQuery = "INSERT INTO tbl_primary (assets_id, primary_qty, primary_image, kondisi_id, karyawan_id, lokasi_id, timestamp)
                  SELECT assets_id, audit_qty, audit_image, kondisi_id, karyawan_id, lokasi_id, NOW()
                  FROM tbl_audit";
    mysqli_query($conn, $copyQuery);
    
    // 3. (Opsional) Update status audit menjadi "archived" atau biarkan saja
    // mysqli_query($conn, "UPDATE tbl_audit SET status = 3 WHERE status = 2");
    
    mysqli_commit($conn);
    
    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => "Transfer data dari audit ke primary berhasil dilakukan. Semua data primary telah diganti dengan data audit."
    ];
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Gagal transfer data: ' . $e->getMessage()
    ];
}

header("Location: index2.php");
exit;
?>