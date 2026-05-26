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
    $disposal_qty = (int)$_POST['disposal_qty'];
    $now = date('Y-m-d H:i:s');
    
    // Validasi alasan tidak boleh kosong
    if (empty($alasan)) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Alasan disposal harus diisi!'];
        header("Location: tambah2.php");
        exit;
    }
    
    if ($tipe == 'sparepart') {
        $sparepart_id = (int)$_POST['sparepart_id'];
        
        // Ambil data sparepart
        $qSparepart = mysqli_query($conn, "SELECT sparepart_qty FROM tbl_sparepart 
                                           WHERE sparepart_id = $sparepart_id 
                                           AND user_id = $user_id 
                                           AND (disposal_status IS NULL OR disposal_status = 0)");
        
        if (mysqli_num_rows($qSparepart) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Sparepart tidak ditemukan atau sudah diajukan disposal!'];
            header("Location: tambah2.php");
            exit;
        }
        
        $sparepart = mysqli_fetch_assoc($qSparepart);
        $stok_tersedia = $sparepart['sparepart_qty'];
        
        // Validasi jumlah disposal tidak melebihi stok
        if ($disposal_qty <= 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Jumlah disposal harus lebih dari 0!'];
            header("Location: tambah2.php");
            exit;
        }
        
        if ($disposal_qty > $stok_tersedia) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Jumlah disposal tidak boleh melebihi stok tersedia!'];
            header("Location: tambah2.php");
            exit;
        }
        
        // Update sparepart (kurangi qty atau set disposal status)
        if ($disposal_qty == $stok_tersedia) {
            // Jika semua stok didisposal, set status disposal = 1
            $update = "UPDATE tbl_sparepart SET disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                       WHERE sparepart_id = $sparepart_id AND user_id = $user_id";
        } else {
            // Jika hanya sebagian, kurangi qty dan set disposal status = 1
            $update = "UPDATE tbl_sparepart SET sparepart_qty = sparepart_qty - $disposal_qty, 
                       disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                       WHERE sparepart_id = $sparepart_id AND user_id = $user_id";
        }
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal sparepart berhasil dikirim'];
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal mengirim pengajuan: ' . mysqli_error($conn)];
        }
        header("Location: index.php");
        exit;
    }
    
    header("Location: index.php");
    exit;
}

// ===================== EDIT / APPROVE =====================
if (isset($_POST['edit'])) {
    $type = $_POST['type'];
    $id = (int)$_POST['id'];
    
    // Untuk admin/operator (level 1 dan 2)
    if (in_array($user_level, [1,2])) {
        $approve_status = isset($_POST['approve_status']) ? $_POST['approve_status'] : '';
        $kondisi_disposal = 70006; // ID kondisi "DISPOSAL"
        
        if (empty($approve_status)) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Status approve harus dipilih!'];
            header("Location: edit2.php?type=$type&id=$id");
            exit;
        }
        
        // Ambil data sparepart sebelum diupdate
        $qSparepart = mysqli_query($conn, "SELECT sparepart_qty, disposal_status FROM tbl_sparepart WHERE sparepart_id = $id");
        $sparepart = mysqli_fetch_assoc($qSparepart);
        
        if ($approve_status == 'approve') {
            // Set disposal_status = 2 (approved) dan kondisi_id = 70006
            $update = "UPDATE tbl_sparepart SET disposal_status = 2, kondisi_id = $kondisi_disposal 
                       WHERE sparepart_id = $id AND (disposal_status = 1 OR disposal_status IS NULL)";
            $msg = "Pengajuan disposal sparepart disetujui";
        } else {
            // Tolak: reset status
            $update = "UPDATE tbl_sparepart SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                       WHERE sparepart_id = $id AND (disposal_status = 1 OR disposal_status IS NULL)";
            $msg = "Pengajuan disposal sparepart ditolak";
        }
        
        if (mysqli_query($conn, $update)) {
            $affected = mysqli_affected_rows($conn);
            if ($affected > 0) {
                $_SESSION['alert'] = ['type' => 'success', 'msg' => $msg];
            } else {
                // Cek status saat ini
                $cekStatus = mysqli_query($conn, "SELECT disposal_status FROM tbl_sparepart WHERE sparepart_id = $id");
                $statusData = mysqli_fetch_assoc($cekStatus);
                $currentStatus = $statusData['disposal_status'] ?? 'NULL';
                
                if ($currentStatus == 2) {
                    $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Pengajuan sudah disetujui sebelumnya!'];
                } else {
                    $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Tidak ada perubahan data. Status saat ini: ' . ($currentStatus ?: 'NULL')];
                }
            }
        } else {
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
            header("Location: edit2.php?type=$type&id=$id");
            exit;
        }
        
        // Cek apakah sparepart milik user yang sedang login
        $cek = mysqli_query($conn, "SELECT sparepart_id FROM tbl_sparepart 
                                    WHERE sparepart_id = $id AND user_id = $user_id AND disposal_status = 1");
        if (mysqli_num_rows($cek) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk mengedit data ini!'];
            header("Location: index.php");
            exit;
        }
        
        $update = "UPDATE tbl_sparepart SET disposal_reason = '$alasan' WHERE sparepart_id = $id AND disposal_status = 1";
        $msg = "Alasan disposal sparepart berhasil diupdate";
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => $msg];
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
    
    if ($type == 'sparepart') {
        // Cek apakah sparepart milik user yang sedang login (untuk level 3)
        if ($user_level == 3) {
            $cek = mysqli_query($conn, "SELECT sparepart_id FROM tbl_sparepart 
                                        WHERE sparepart_id = $id AND user_id = $user_id AND disposal_status = 1");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk menghapus data ini!'];
                header("Location: index.php");
                exit;
            }
        }
        
        // Hapus pengajuan: reset status
        $update = "UPDATE tbl_sparepart SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                   WHERE sparepart_id = $id AND disposal_status = 1";
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal sparepart dibatalkan'];
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal membatalkan pengajuan: ' . mysqli_error($conn)];
        }
        header("Location: index.php");
        exit;
    }
}

header("Location: index.php");
exit;
?>