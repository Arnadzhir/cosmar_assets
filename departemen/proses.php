<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

// =========================
// HELPER FUNCTION
// =========================
function upper($text) {
    return strtoupper(trim($text));
}

/* ==================
   TAMBAH DATA
================== */
if (isset($_POST['tambah'])) {

    $dep_code = isset($_POST['dep_code']) ? mysqli_real_escape_string($conn, $_POST['dep_code']) : '';
    $dep_name = isset($_POST['dep_name']) ? mysqli_real_escape_string($conn, $_POST['dep_name']) : '';

    // Validasi data
    $errors = [];
    
    if (empty($dep_code)) {
        $errors[] = "Kode departemen harus diisi!";
    }
    if (empty($dep_name)) {
        $errors[] = "Nama departemen harus diisi!";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: tambah.php");
        exit;
    }
    
    // CEK DUPLIKAT (berdasarkan dep_code atau dep_name)
    $cek = mysqli_query($conn, "
        SELECT 1 FROM tbl_dep 
        WHERE dep_code = '$dep_code' OR dep_name = '$dep_name'
    ");

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Kode atau nama departemen sudah terdaftar!'
        ];
        header("Location: tambah.php");
        exit;
    }

    // PERBAIKAN: INSERT hanya kolom yang ada di database
    $query = "INSERT INTO tbl_dep (dep_code, dep_name) VALUES ('$dep_code', '$dep_name')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data departemen berhasil ditambahkan'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menambahkan data: ' . mysqli_error($conn)
        ];
    }

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    // Ambil data dari form
    $dep_id   = isset($_POST['dep_id']) ? (int)$_POST['dep_id'] : 0;
    $dep_code = isset($_POST['dep_code']) ? mysqli_real_escape_string($conn, $_POST['dep_code']) : '';
    $dep_name = isset($_POST['dep_name']) ? mysqli_real_escape_string($conn, $_POST['dep_name']) : '';

    // Validasi ID
    if ($dep_id <= 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'ID departemen tidak valid!'
        ];
        header("Location: index.php");
        exit;
    }

    // Validasi data
    $errors = [];
    
    if (empty($dep_code)) {
        $errors[] = "Kode departemen harus diisi!";
    }
    if (empty($dep_name)) {
        $errors[] = "Nama departemen harus diisi!";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit.php?id=$dep_id");
        exit;
    }

    // CEK DUPLIKAT (KECUALI DIRINYA SENDIRI)
    $cek = mysqli_query($conn, "
        SELECT 1 FROM tbl_dep 
        WHERE (dep_code = '$dep_code' OR dep_name = '$dep_name')
        AND dep_id != '$dep_id'
    ");

    if ($cek && mysqli_num_rows($cek) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Kode atau nama departemen sudah digunakan oleh data lain!'
        ];
        header("Location: edit.php?id=$dep_id");
        exit;
    }

    // PERBAIKAN: UPDATE hanya kolom yang ada di database
    $query = "UPDATE tbl_dep SET 
                dep_code = '$dep_code',
                dep_name = '$dep_name'
              WHERE dep_id = '$dep_id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data departemen berhasil diperbarui'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal memperbarui data: ' . mysqli_error($conn)
        ];
    }

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    $dep_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($dep_id <= 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'ID departemen tidak valid!'
        ];
        header("Location: index.php");
        exit;
    }
    
    // CEK apakah departemen masih memiliki relasi dengan karyawan
    $cekRelasi = mysqli_query($conn, "
        SELECT 1 FROM tbl_karyawan WHERE dep_id = '$dep_id' LIMIT 1
    ");
    
    if (mysqli_num_rows($cekRelasi) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Departemen tidak dapat dihapus karena masih memiliki data karyawan yang terkait!'
        ];
        header("Location: index.php");
        exit;
    }
    
    // Hapus data departemen
    $query = "DELETE FROM tbl_dep WHERE dep_id = '$dep_id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data departemen berhasil dihapus'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menghapus data: ' . mysqli_error($conn)
        ];
    }

    header("Location: index.php");
    exit;
}

// Jika tidak ada aksi yang sesuai
header("Location: index.php");
exit;
?>