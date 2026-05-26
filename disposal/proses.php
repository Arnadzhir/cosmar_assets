<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id = $_SESSION['dep_id'] ?? 0;

// ===================== TAMBAH PENGAJUAN =====================
if (isset($_POST['tambah'])) {
    $tipe = $_POST['tipe'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    $now = date('Y-m-d H:i:s');
    
    if (empty($alasan)) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Alasan disposal harus diisi!'];
        header("Location: tambah.php");
        exit;
    }
    
    if ($tipe == 'asset') {
        $primary_id = (int)$_POST['primary_id'];
        
        $cek = mysqli_query($conn, "SELECT primary_id FROM tbl_primary 
                                    WHERE primary_id = $primary_id 
                                    AND karyawan_id = $user_id 
                                    AND (disposal_status IS NULL OR disposal_status = 0)");
        
        if (mysqli_num_rows($cek) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Asset tidak ditemukan atau sudah diajukan disposal!'];
            header("Location: tambah.php");
            exit;
        }
        
        $update = "UPDATE tbl_primary SET disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                   WHERE primary_id = $primary_id";
    } else {
        $sparepart_id = (int)$_POST['sparepart_id'];
        
        $cek = mysqli_query($conn, "SELECT sparepart_id FROM tbl_sparepart 
                                    WHERE sparepart_id = $sparepart_id 
                                    AND user_id = $user_id 
                                    AND (disposal_status IS NULL OR disposal_status = 0)");
        
        if (mysqli_num_rows($cek) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Sparepart tidak ditemukan atau sudah diajukan disposal!'];
            header("Location: tambah2.php");
            exit;
        }
        
        $update = "UPDATE tbl_sparepart SET disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                   WHERE sparepart_id = $sparepart_id AND user_id = $user_id";
    }
    
    if (mysqli_query($conn, $update)) {
        $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal berhasil dikirim'];
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal mengirim pengajuan: ' . mysqli_error($conn)];
    }
    header("Location: index.php");
    exit;
}

// ===================== EDIT / APPROVE ASSET =====================
if (isset($_POST['edit'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    
    // DEBUG: Cek apakah data POST sampai
    error_log("Proses.php - Edit diterima - Type: $type, ID: $id, User Level: $user_level");
    
    // Untuk admin/operator (level 1 dan 2)
    if (in_array($user_level, [1,2])) {
        $approve_status = isset($_POST['approve_status']) ? $_POST['approve_status'] : '';
        $kondisi_disposal = 70006;
        
        if (empty($approve_status)) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Status approve harus dipilih!'];
            header("Location: edit.php?type=$type&id=$id");
            exit;
        }
        
        if ($approve_status == 'approve') {
            if ($type == 'asset') {
                $update = "UPDATE tbl_primary SET disposal_status = 2, kondisi_id = $kondisi_disposal 
                           WHERE primary_id = $id AND disposal_status = 1";
                $msg = "Pengajuan disposal asset disetujui";
            } else {
                $update = "UPDATE tbl_sparepart SET disposal_status = 2 
                           WHERE sparepart_id = $id AND disposal_status = 1";
                $msg = "Pengajuan disposal sparepart disetujui";
            }
        } else {
            if ($type == 'asset') {
                $update = "UPDATE tbl_primary SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL
                           WHERE primary_id = $id AND disposal_status = 1";
            } else {
                $update = "UPDATE tbl_sparepart SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL
                           WHERE sparepart_id = $id AND disposal_status = 1";
            }
            $msg = "Pengajuan disposal ditolak";
        }
        
        if (mysqli_query($conn, $update)) {
            $affected = mysqli_affected_rows($conn);
            error_log("Update berhasil, affected rows: $affected");
            if ($affected > 0) {
                $_SESSION['alert'] = ['type' => 'success', 'msg' => $msg];
            } else {
                $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Tidak ada perubahan data. Pengajuan mungkin sudah diproses sebelumnya.'];
            }
        } else {
            error_log("Error update: " . mysqli_error($conn));
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal memproses data: ' . mysqli_error($conn)];
        }
        header("Location: index.php");
        exit;
    } 
    // Untuk user biasa (level 3) - hanya edit alasan
    else {
        $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
        
        if (empty($alasan)) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Alasan disposal harus diisi!'];
            header("Location: edit.php?type=$type&id=$id");
            exit;
        }
        
        if ($type == 'asset') {
            $cek = mysqli_query($conn, "SELECT primary_id FROM tbl_primary 
                                        WHERE primary_id = $id AND karyawan_id = $user_id AND disposal_status = 1");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk mengedit data ini!'];
                header("Location: index.php");
                exit;
            }
            $update = "UPDATE tbl_primary SET disposal_reason = '$alasan' WHERE primary_id = $id AND disposal_status = 1";
        } else {
            $cek = mysqli_query($conn, "SELECT sparepart_id FROM tbl_sparepart 
                                        WHERE sparepart_id = $id AND user_id = $user_id AND disposal_status = 1");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk mengedit data ini!'];
                header("Location: index.php");
                exit;
            }
            $update = "UPDATE tbl_sparepart SET disposal_reason = '$alasan' WHERE sparepart_id = $id AND disposal_status = 1";
        }
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Alasan disposal berhasil diupdate'];
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal memproses data: ' . mysqli_error($conn)];
        }
        header("Location: index.php");
        exit;
    }
}

// ===================== HAPUS PENGAJUAN =====================
if (isset($_GET['hapus'])) {
    $type = $_GET['type'];
    $id = (int)$_GET['id'];
    
    if ($type == 'asset') {
        if ($user_level == 3) {
            $cek = mysqli_query($conn, "SELECT primary_id FROM tbl_primary 
                                        WHERE primary_id = $id AND karyawan_id = $user_id AND disposal_status = 1");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk menghapus data ini!'];
                header("Location: index.php");
                exit;
            }
        }
        $update = "UPDATE tbl_primary SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                   WHERE primary_id = $id AND disposal_status = 1";
    } else {
        if ($user_level == 3) {
            $cek = mysqli_query($conn, "SELECT sparepart_id FROM tbl_sparepart 
                                        WHERE sparepart_id = $id AND user_id = $user_id AND disposal_status = 1");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk menghapus data ini!'];
                header("Location: index.php");
                exit;
            }
        }
        $update = "UPDATE tbl_sparepart SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                   WHERE sparepart_id = $id AND disposal_status = 1";
    }
    
    if (mysqli_query($conn, $update)) {
        $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal dibatalkan'];
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal membatalkan pengajuan: ' . mysqli_error($conn)];
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
?>