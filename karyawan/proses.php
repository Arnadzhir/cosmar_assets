<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

/* ======================
   TAMBAH DATA KARYAWAN
====================== */ 
if (isset($_POST['tambah'])) {

    // ======================
    // AMBIL DATA DARI FORM
    // ======================
    $karyawan_id     = mysqli_real_escape_string($conn, $_POST['karyawan_id']);
    $karyawan_name   = mysqli_real_escape_string($conn, $_POST['karyawan_name']);
    $karyawan_no     = mysqli_real_escape_string($conn, $_POST['karyawan_no']);
    $karyawan_gender = mysqli_real_escape_string($conn, $_POST['karyawan_gender']);
    $karyawan_level  = mysqli_real_escape_string($conn, $_POST['karyawan_level']);
    $dep_id          = intval($_POST['dep_id']);

    // ======================
    // VALIDASI DATA
    // ======================
    $errors = [];
    
    if (empty($karyawan_id)) $errors[] = "ID Karyawan harus diisi!";
    if (empty($karyawan_name)) $errors[] = "Nama karyawan harus diisi!";
    if (empty($karyawan_gender)) $errors[] = "Jenis kelamin harus dipilih!";
    if (empty($karyawan_no)) $errors[] = "No. telepon harus diisi!";
    if (empty($karyawan_level)) $errors[] = "Level harus dipilih!";
    if ($dep_id <= 0) $errors[] = "Departemen harus dipilih!";
    
    // Cek duplikat karyawan_id
    $cek = mysqli_query($conn, "SELECT 1 FROM tbl_karyawan WHERE karyawan_id = '$karyawan_id'");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = "ID Karyawan sudah terdaftar!";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: tambah.php");
        exit;
    }

    // ======================
    // INSERT DATA KE tbl_karyawan
    // ======================
    $query = "
        INSERT INTO tbl_karyawan
        (
            karyawan_id,
            karyawan_name,
            karyawan_no,
            karyawan_gender,
            karyawan_level,
            dep_id
        )
        VALUES
        (
            '$karyawan_id',
            '$karyawan_name',
            '$karyawan_no',
            '$karyawan_gender',
            '$karyawan_level',
            '$dep_id'
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data karyawan berhasil ditambahkan'
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

/* ======================
   UPDATE DATA KARYAWAN
====================== */
if (isset($_POST['update'])) {

    $karyawan_id     = mysqli_real_escape_string($conn, $_POST['karyawan_id']);
    $karyawan_name   = mysqli_real_escape_string($conn, $_POST['karyawan_name']);
    $karyawan_no     = mysqli_real_escape_string($conn, $_POST['karyawan_no']);
    $karyawan_gender = mysqli_real_escape_string($conn, $_POST['karyawan_gender']);
    $karyawan_level  = mysqli_real_escape_string($conn, $_POST['karyawan_level']);
    $dep_id          = intval($_POST['dep_id']);

    // ======================
    // VALIDASI DATA
    // ======================
    $errors = [];
    
    if (empty($karyawan_id)) $errors[] = "ID Karyawan tidak valid!";
    if (empty($karyawan_name)) $errors[] = "Nama karyawan harus diisi!";
    if (empty($karyawan_gender)) $errors[] = "Jenis kelamin harus dipilih!";
    if (empty($karyawan_no)) $errors[] = "No. telepon harus diisi!";
    if (empty($karyawan_level)) $errors[] = "Level harus dipilih!";
    if ($dep_id <= 0) $errors[] = "Departemen harus dipilih!";
    
    // Cek apakah karyawan ada
    $cek = mysqli_query($conn, "SELECT 1 FROM tbl_karyawan WHERE karyawan_id = '$karyawan_id'");
    if (mysqli_num_rows($cek) == 0) {
        $errors[] = "Data karyawan tidak ditemukan!";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit.php?id=$karyawan_id");
        exit;
    }

    // ======================
    // UPDATE DATA
    // ======================
    $query = "
        UPDATE tbl_karyawan SET
            karyawan_name   = '$karyawan_name',
            karyawan_no     = '$karyawan_no',
            karyawan_gender = '$karyawan_gender',
            karyawan_level  = '$karyawan_level',
            dep_id          = '$dep_id'
        WHERE karyawan_id = '$karyawan_id'
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data karyawan berhasil diupdate'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal mengupdate data: ' . mysqli_error($conn)
        ];
    }

    header("Location: index.php");
    exit;
}

/* ======================
   HAPUS DATA KARYAWAN
====================== */
if (isset($_GET['hapus'])) {

    $karyawan_id = intval($_GET['id']);
    
    // Cek apakah karyawan memiliki relasi dengan tbl_user
    $cekUser = mysqli_query($conn, "SELECT 1 FROM tbl_user WHERE user_id = '$karyawan_id' LIMIT 1");
    if (mysqli_num_rows($cekUser) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Karyawan tidak dapat dihapus karena masih memiliki akun user yang terkait!'
        ];
        header("Location: index.php");
        exit;
    }
    
    // Hapus data karyawan
    $query = "DELETE FROM tbl_karyawan WHERE karyawan_id = '$karyawan_id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data karyawan berhasil dihapus'
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