<?php
include '../auth/auth.php';
allowRole([1,2]);
include '../config/koneksi.php';

// =====================
// 1. KOSONGKAN FOLDER AUDIT
// =====================
$audit_dir = "../master/img/audit/";
if (!is_dir($audit_dir)) {
    mkdir($audit_dir, 0777, true);
} else {
    // Hapus semua file di folder audit
    $files = glob($audit_dir . "*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// =====================
// 2. NONAKTIFKAN FOREIGN KEY CHECK SEMENTARA
// =====================
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// =====================
// 3. KOSONGKAN TABEL AUDIT & RESET AUTO_INCREMENT
// =====================
mysqli_query($conn, "DELETE FROM tbl_audit");
mysqli_query($conn, "ALTER TABLE tbl_audit AUTO_INCREMENT = 1");

// =====================
// 4. COPY DATA DARI TBL_PRIMARY KE TBL_AUDIT (MENGGUNAKAN karyawan_id)
// =====================
$query = "SELECT 
            assets_id, 
            primary_qty, 
            primary_image, 
            kondisi_id, 
            karyawan_id, 
            lokasi_id 
          FROM tbl_primary";
$result = mysqli_query($conn, $query);
$count = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $assets_id   = $row['assets_id'];
    $audit_qty   = $row['primary_qty'];
    $audit_image = $row['primary_image'];
    $kondisi_id  = $row['kondisi_id'];
    $karyawan_id = $row['karyawan_id'];  // PERUBAHAN: user_id -> karyawan_id
    $lokasi_id   = $row['lokasi_id'];
    
    // PERUBAHAN: Validasi karyawan_id (menggunakan tbl_karyawan)
    if (!empty($karyawan_id)) {
        $check_karyawan = mysqli_query($conn, "SELECT karyawan_id FROM tbl_karyawan WHERE karyawan_id = '$karyawan_id'");
        if (mysqli_num_rows($check_karyawan) == 0) {
            $karyawan_id = "NULL";
        } else {
            $karyawan_id = "'$karyawan_id'";
        }
    } else {
        $karyawan_id = "NULL";
    }
    
    // Validasi lokasi_id
    if (!empty($lokasi_id)) {
        $lokasi_id = "'$lokasi_id'";
    } else {
        $lokasi_id = "NULL";
    }
    
    // Validasi kondisi_id
    if (empty($kondisi_id)) {
        $kondisi_id = "NULL";
    }
    
    // Insert ke tbl_audit (audit_status sementara 1, status = 1 = Pending)
    $insert = "INSERT INTO tbl_audit (
                   assets_id, 
                   audit_qty, 
                   audit_image, 
                   kondisi_id, 
                   user_id, 
                   lokasi_id, 
                   auditor, 
                   audit_status, 
                   status, 
                   timestamp
               ) VALUES (
                   '$assets_id', 
                   '$audit_qty', 
                   " . ($audit_image ? "'$audit_image'" : "NULL") . ", 
                   $kondisi_id, 
                   $karyawan_id, 
                   $lokasi_id, 
                   NULL, 
                   1, 
                   1, 
                   NOW()
               )";
    
    if (mysqli_query($conn, $insert)) {
        $count++;
        // Copy gambar dari folder assets ke audit
        if (!empty($audit_image)) {
            $src = "../master/img/assets/" . $audit_image;
            $dst = $audit_dir . $audit_image;
            if (file_exists($src) && !file_exists($dst)) {
                copy($src, $dst);
            }
        }
    } else {
        $skipped++;
        error_log("Gagal insert audit untuk assets_id $assets_id: " . mysqli_error($conn));
    }
}

// =====================
// 5. HITUNG ULANG AUDIT_STATUS BERDASARKAN KESESUAIAN QTY
// =====================
$asset_ids = mysqli_query($conn, "SELECT DISTINCT assets_id FROM tbl_audit");
while ($asset = mysqli_fetch_assoc($asset_ids)) {
    $assets_id = $asset['assets_id'];
    
    // Total audit_qty untuk asset ini
    $total_qty_res = mysqli_query($conn, "SELECT SUM(audit_qty) as total FROM tbl_audit WHERE assets_id = '$assets_id'");
    $total_qty_row = mysqli_fetch_assoc($total_qty_res);
    $total_audit_qty = $total_qty_row['total'] ?? 0;
    
    // Qty master dari tbl_assets
    $master_res = mysqli_query($conn, "SELECT assets_qty FROM tbl_assets WHERE assets_id = '$assets_id'");
    $master_row = mysqli_fetch_assoc($master_res);
    $master_qty = $master_row['assets_qty'] ?? 0;
    
    // Tentukan audit_status
    if ($total_audit_qty == 0) {
        $new_audit_status = 0;  // Belum
    } elseif ($total_audit_qty < $master_qty) {
        $new_audit_status = 1;  // Kurang
    } elseif ($total_audit_qty > $master_qty) {
        $new_audit_status = 2;  // Lebih
    } else {
        $new_audit_status = 3;  // Selesai
    }
    
    // Update semua baris audit untuk asset ini
    mysqli_query($conn, "UPDATE tbl_audit SET audit_status = $new_audit_status WHERE assets_id = '$assets_id'");
}

// =====================
// 6. AKTIFKAN KEMBALI FOREIGN KEY CHECK
// =====================
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// =====================
// 7. PESAN SESSION
// =====================
$_SESSION['alert'] = [
    'type' => 'success',
    'msg'  => "Transfer selesai: $count data berhasil disalin ke audit. ($skipped data dilewati). Audit status telah disesuaikan."
];

header("Location: index.php");
exit;
?>