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
        header("Location: tambah3.php");
        exit;
    }
    
    if ($tipe == 'tools') {
        $tools_id = (int)$_POST['tools_id'];
        
        // Ambil data tools
        $qTools = mysqli_query($conn, "SELECT tools_qty FROM tbl_tools 
                                       WHERE tools_id = $tools_id 
                                       AND user_id = $user_id 
                                       AND (disposal_status IS NULL OR disposal_status = 0)");
        
        if (mysqli_num_rows($qTools) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Tools tidak ditemukan atau sudah diajukan disposal!'];
            header("Location: tambah3.php");
            exit;
        }
        
        $tools = mysqli_fetch_assoc($qTools);
        $stok_tersedia = $tools['tools_qty'];
        
        // Validasi jumlah disposal tidak melebihi stok
        if ($disposal_qty <= 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Jumlah disposal harus lebih dari 0!'];
            header("Location: tambah3.php");
            exit;
        }
        
        if ($disposal_qty > $stok_tersedia) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Jumlah disposal tidak boleh melebihi stok tersedia!'];
            header("Location: tambah3.php");
            exit;
        }
        
        // Update tools (kurangi qty atau set disposal status)
        if ($disposal_qty == $stok_tersedia) {
            // Jika semua stok didisposal, set status disposal = 1
            $update = "UPDATE tbl_tools SET disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                       WHERE tools_id = $tools_id AND user_id = $user_id";
        } else {
            // Jika hanya sebagian, kurangi qty dan set disposal status = 1
            $update = "UPDATE tbl_tools SET tools_qty = tools_qty - $disposal_qty, 
                       disposal_status = 1, disposal_reason = '$alasan', disposal_date = '$now' 
                       WHERE tools_id = $tools_id AND user_id = $user_id";
        }
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal tools berhasil dikirim'];
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
        $approve_status = $_POST['approve_status'];
        $kondisi_disposal = 70006;
        
        if (empty($approve_status)) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Status approve harus dipilih!'];
            header("Location: edit3.php?type=$type&id=$id");
            exit;
        }
        
        if ($approve_status == 'approve') {
            // Set disposal_status = 2 (approved) dan ubah kondisi menjadi Disposal (70006)
            $update = "UPDATE tbl_tools SET disposal_status = 2, kondisi_id = $kondisi_disposal WHERE tools_id = $id";
            $msg = "Pengajuan disposal tools disetujui";
        } else {
            // Tolak: reset status (qty sudah dikurangi saat tambah, tidak bisa dikembalikan tanpa data disposal_qty)
            // Untuk sementara, hanya reset status
            $update = "UPDATE tbl_tools SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                       WHERE tools_id = $id";
            $msg = "Pengajuan disposal tools ditolak";
        }
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => $msg];
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
            header("Location: edit3.php?type=$type&id=$id");
            exit;
        }
        
        // Cek apakah tools milik user yang sedang login
        $cek = mysqli_query($conn, "SELECT tools_id FROM tbl_tools 
                                    WHERE tools_id = $id AND user_id = $user_id");
        if (mysqli_num_rows($cek) == 0) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk mengedit data ini!'];
            header("Location: index.php");
            exit;
        }
        
        $update = "UPDATE tbl_tools SET disposal_reason = '$alasan' WHERE tools_id = $id";
        $msg = "Alasan disposal tools berhasil diupdate";
        
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
    
    if ($type == 'tools') {
        // Cek apakah tools milik user yang sedang login (untuk level 3)
        if ($user_level == 3) {
            $cek = mysqli_query($conn, "SELECT tools_id FROM tbl_tools 
                                        WHERE tools_id = $id AND user_id = $user_id");
            if (mysqli_num_rows($cek) == 0) {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk menghapus data ini!'];
                header("Location: index.php");
                exit;
            }
        }
        
        // Hapus pengajuan: reset status dan kembalikan qty (perlu data disposal_qty)
        // Untuk sementara, hanya reset status
        $update = "UPDATE tbl_tools SET disposal_status = NULL, disposal_reason = NULL, disposal_date = NULL 
                   WHERE tools_id = $id";
        
        if (mysqli_query($conn, $update)) {
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Pengajuan disposal tools dibatalkan'];
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