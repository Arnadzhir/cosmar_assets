<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Cek role (hanya admin dan operator)
if (!in_array($_SESSION['user_level'], [1, 2])) {
    header("Location: ../dashboard.php");
    exit;
}

/* ======================
   SETUJUI PENGEMBALIAN
====================== */
if (isset($_POST['setujui'])) {
    
    // Cek apakah ada primary_ids yang dipilih
    if (!isset($_POST['primary_ids']) || empty($_POST['primary_ids'])) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Pilih minimal 1 asset yang akan disetujui'
        ];
        header("Location: index2.php");
        exit;
    }
    
    $primary_ids = $_POST['primary_ids'];
    $id_list = implode(',', array_map('intval', $primary_ids));
    
    // Cek apakah asset yang dipilih benar-benar berstatus request (return_status = 1)
    $check_query = "
        SELECT COUNT(*) as total 
        FROM tbl_primary 
        WHERE primary_id IN ($id_list) 
        AND return_status = 1
    ";
    
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error query: ' . mysqli_error($conn)
        ];
        header("Location: index2.php");
        exit;
    }
    
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] != count($primary_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Beberapa asset tidak valid atau sudah diproses'
        ];
        header("Location: index2.php");
        exit;
    }
    
    // PERBAIKAN: Ambil data untuk dicetak menggunakan tbl_karyawan
    $query_data = mysqli_query($conn, "
        SELECT 
            p.primary_id,
            p.primary_image,
            a.assets_kode,
            a.assets_name,
            a.assets_model,
            a.assets_spec,
            a.assets_cap,
            a.assets_uom,
            a.assets_price,
            a.assets_date,
            l.lokasi_name,
            l.lokasi_lantai,
            kond.kondisi_name,
            kar.karyawan_id,
            kar.karyawan_name,
            kar.karyawan_no,
            d.dep_id,
            d.dep_name,
            d.dep_code,
            a.assets_id
        FROM tbl_primary p
        INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
        INNER JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
        INNER JOIN tbl_dep d ON kar.dep_id = d.dep_id
        LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
        LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
        WHERE p.primary_id IN ($id_list)
    ");
    
    if (!$query_data) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error query data: ' . mysqli_error($conn)
        ];
        header("Location: index2.php");
        exit;
    }
    
    $data_cetak = [];
    while ($row = mysqli_fetch_assoc($query_data)) {
        $data_cetak[] = $row;
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // PERBAIKAN: Update status return menjadi 2 (approved)
        // JANGAN HAPUS karyawan_id, cukup update status
        $update = mysqli_query($conn, "
            UPDATE tbl_primary 
            SET return_status = 2
            WHERE primary_id IN ($id_list)
        ");
        
        if (!$update) {
            throw new Exception("Gagal mengupdate status: " . mysqli_error($conn));
        }
        
        // Update total qty di tbl_assets (tidak ada perubahan qty karena status hanya berubah)
        $assets_ids = [];
        foreach ($data_cetak as $item) {
            if (isset($item['assets_id'])) {
                $assets_ids[] = $item['assets_id'];
            }
        }
        
        $assets_ids_unique = array_unique($assets_ids);
        foreach ($assets_ids_unique as $assets_id) {
            $qTotal = mysqli_query($conn, "
                SELECT COUNT(*) as total 
                FROM tbl_primary 
                WHERE assets_id = $assets_id
            ");
            
            if ($qTotal) {
                $total = mysqli_fetch_assoc($qTotal);
                mysqli_query($conn, "
                    UPDATE tbl_assets 
                    SET assets_qty = {$total['total']} 
                    WHERE assets_id = $assets_id
                ");
            }
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        // Simpan data ke session untuk dicetak
        $_SESSION['return_print_data'] = $data_cetak;
        $_SESSION['return_print_date'] = date('Y-m-d H:i:s');
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => count($primary_ids) . ' Asset berhasil disetujui'
        ];
        
        // Redirect ke halaman print
        header("Location: index3.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
        header("Location: index2.php");
        exit;
    }
}

/* ======================
   TOLAK PENGEMBALIAN
====================== */
if (isset($_POST['tolak'])) {
    
    // Cek apakah ada primary_ids yang dipilih
    if (!isset($_POST['primary_ids']) || empty($_POST['primary_ids'])) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Pilih minimal 1 asset yang akan ditolak'
        ];
        header("Location: index2.php");
        exit;
    }
    
    $primary_ids = $_POST['primary_ids'];
    $id_list = implode(',', array_map('intval', $primary_ids));
    
    // Cek apakah asset yang dipilih benar-benar berstatus request (return_status = 1)
    $check_query = "
        SELECT COUNT(*) as total 
        FROM tbl_primary 
        WHERE primary_id IN ($id_list) 
        AND return_status = 1
    ";
    
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error query: ' . mysqli_error($conn)
        ];
        header("Location: index2.php");
        exit;
    }
    
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] != count($primary_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Beberapa asset tidak valid atau sudah diproses'
        ];
        header("Location: index2.php");
        exit;
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Update status return menjadi 0 (kembalikan ke aktif) tanpa menghapus karyawan_id
        $update = mysqli_query($conn, "
            UPDATE tbl_primary 
            SET return_status = 0 
            WHERE primary_id IN ($id_list)
        ");
        
        if (!$update) {
            throw new Exception("Gagal mengupdate status: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => count($primary_ids) . ' asset berhasil ditolak. Status dikembalikan ke aktif.'
        ];
        
        header("Location: index2.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
        header("Location: index2.php");
        exit;
    }
}

// Jika tidak ada aksi yang sesuai
header("Location: index2.php");
exit;
?>