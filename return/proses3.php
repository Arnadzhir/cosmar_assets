<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Cek role (admin, operator, user - semua bisa akses)
if (!in_array($_SESSION['user_level'], [1, 2, 3])) {
    header("Location: ../dashboard.php");
    exit;
}

/* ======================
   PROSES KEMBALIKAN ASSETS (HAPUS KARYAWAN_ID DAN DEP_ID)
====================== */
if (isset($_POST['setujui'])) {
    
    // Cek apakah ada primary_ids yang dipilih
    if (!isset($_POST['primary_ids']) || empty($_POST['primary_ids'])) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Pilih minimal 1 asset yang akan dikembalikan'
        ];
        header("Location: index3.php");
        exit;
    }
    
    $primary_ids = $_POST['primary_ids'];
    $id_list = implode(',', array_map('intval', $primary_ids));
    
    // Cek apakah asset yang dipilih benar-benar berstatus approved (return_status = 2)
    $check_query = "
        SELECT COUNT(*) as total 
        FROM tbl_primary 
        WHERE primary_id IN ($id_list) 
        AND return_status = 2
    ";
    
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error query: ' . mysqli_error($conn)
        ];
        header("Location: index3.php");
        exit;
    }
    
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] != count($primary_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Beberapa asset tidak valid atau sudah diproses'
        ];
        header("Location: index3.php");
        exit;
    }
    
    // Ambil data gambar untuk cek apakah gambar masih digunakan
    $qGambar = mysqli_query($conn, "
        SELECT primary_image 
        FROM tbl_primary 
        WHERE primary_id IN ($id_list) 
        AND primary_image IS NOT NULL 
        AND primary_image != ''
    ");
    
    $gambar_dihapus = [];
    while ($row = mysqli_fetch_assoc($qGambar)) {
        $gambar_dihapus[] = $row['primary_image'];
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // PERBAIKAN: Update status return menjadi NULL, hapus karyawan_id dan dep_id
        $update = mysqli_query($conn, "
            UPDATE tbl_primary 
            SET return_status = NULL, 
                karyawan_id = NULL,
                dep_id = NULL
            WHERE primary_id IN ($id_list)
        ");
        
        if (!$update) {
            throw new Exception("Gagal mengupdate status: " . mysqli_error($conn));
        }
        
        // Cek dan hapus gambar yang tidak digunakan oleh primary lain
        $folder = "../master/img/assets/";
        foreach ($gambar_dihapus as $gambar) {
            if (!empty($gambar)) {
                $gambar_escape = mysqli_real_escape_string($conn, $gambar);
                $qCek = mysqli_query($conn, "
                    SELECT COUNT(*) as jumlah 
                    FROM tbl_primary 
                    WHERE primary_image = '$gambar_escape' 
                    AND primary_id NOT IN ($id_list)
                ");
                $cek = mysqli_fetch_assoc($qCek);
                
                if ($cek && $cek['jumlah'] == 0 && file_exists($folder . $gambar)) {
                    unlink($folder . $gambar);
                }
            }
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => count($primary_ids) . ' asset berhasil dikembalikan ke pool perusahaan.'
        ];
        
        header("Location: index3.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
        header("Location: index3.php");
        exit;
    }
}

// Jika tidak ada aksi yang sesuai
header("Location: index3.php");
exit;
?>