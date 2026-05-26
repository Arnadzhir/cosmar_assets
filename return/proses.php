<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Cek apakah ada aksi ajukan_return
if (isset($_POST['ajukan_return']) && isset($_POST['primary_ids'])) {
    
    $primary_ids = $_POST['primary_ids'];
    
    // Validasi minimal pilih 1 asset
    if (empty($primary_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Silakan pilih minimal 1 asset yang akan dikembalikan'
        ];
        header("Location: index.php");
        exit;
    }
    
    // Sanitasi ID
    $id_list = implode(',', array_map('intval', $primary_ids));
    
    // Cek apakah asset yang dipilih benar-benar milik user yang login (untuk keamanan)
    $user_id = $_SESSION['user_id'];
    $user_level = $_SESSION['user_level'];
    
    // Query untuk memeriksa kepemilikan asset
    $check_query = "
        SELECT COUNT(*) as total 
        FROM tbl_primary 
        WHERE primary_id IN ($id_list)
    ";
    
    // Jika bukan admin/operator, pastikan asset milik user yang login
    if (!in_array($user_level, [1, 2])) {
        $check_query .= " AND user_id = '$user_id'";
    }
    
    // Cek juga apakah asset masih aktif (return_status = 0 atau NULL)
    $check_query .= " AND (return_status = 0 OR return_status IS NULL)";
    
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['total'] != count($primary_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Beberapa asset tidak valid atau bukan milik Anda'
        ];
        header("Location: index.php");
        exit;
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Update status return menjadi 1 (menunggu approval)
        $update = mysqli_query($conn, "
            UPDATE tbl_primary 
            SET return_status = 1 
            WHERE primary_id IN ($id_list)
        ");
        
        if (!$update) {
            throw new Exception("Gagal mengupdate status: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Pengajuan pengembalian berhasil dikirim. Menunggu approval admin.'
        ];
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
    }
    
    header("Location: index.php");
    exit;
}

// Jika tidak ada aksi yang sesuai
header("Location: index.php");
exit;
?>