<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

/* ======================
   TAMBAH DATA USER
====================== */
if (isset($_POST['tambah'])) {

    // ======================
    // AMBIL DATA DARI FORM
    // ======================
    $user_id       = mysqli_real_escape_string($conn, $_POST['user_id']);
    $user_password = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
    $user_name     = mysqli_real_escape_string($conn, $_POST['user_name']);
    $user_mail     = mysqli_real_escape_string($conn, $_POST['user_mail']);
    $user_gender   = mysqli_real_escape_string($conn, $_POST['user_gender']);
    $user_level    = intval($_POST['user_level']);
    $dep_id        = intval($_POST['dep_id']);

    // ======================
    // VALIDASI DATA
    // ======================
    $errors = [];
    
    if (empty($user_id)) $errors[] = "User ID harus diisi!";
    if (empty($user_name)) $errors[] = "Nama user harus diisi!";
    if (empty($user_mail)) $errors[] = "Email harus diisi!";
    if (empty($user_gender)) $errors[] = "Gender harus dipilih!";
    if ($user_level <= 0) $errors[] = "Hak akses harus dipilih!";
    if ($dep_id <= 0) $errors[] = "Departemen harus dipilih!";
    
    // Cek duplikat user_id
    $cek = mysqli_query($conn, "SELECT 1 FROM tbl_user WHERE user_id = '$user_id'");
    if (mysqli_num_rows($cek) > 0) {
        $errors[] = "User ID sudah terdaftar!";
    }
    
    // Validasi email format
    if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
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
    // UPLOAD GAMBAR
    // ======================
    $nama_file = null;

    if (!empty($_FILES['user_image']['name'])) {
        $folder = "../master/img/user/";
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['user_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowed) && $_FILES['user_image']['size'] <= 2097152) {
            $nama_file = 'USER_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['user_image']['tmp_name'], $folder . $nama_file);
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format gambar harus JPG/PNG dan ukuran maksimal 2MB'
            ];
            header("Location: tambah.php");
            exit;
        }
    }

    // ======================
    // INSERT DATA
    // ======================
    $query = "
        INSERT INTO tbl_user
        (
            user_id,
            user_password,
            user_name,
            dep_id,
            user_mail,
            user_gender,
            user_image,
            user_level
        )
        VALUES
        (
            '$user_id',
            '$user_password',
            '$user_name',
            '$dep_id',
            '$user_mail',
            '$user_gender',
            " . ($nama_file ? "'$nama_file'" : "NULL") . ",
            '$user_level'
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data User berhasil ditambahkan'
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
   UPDATE DATA USER
====================== */
if (isset($_POST['update'])) {

    $user_id     = mysqli_real_escape_string($conn, $_POST['user_id']);
    $user_name   = mysqli_real_escape_string($conn, $_POST['user_name']);
    $user_mail   = mysqli_real_escape_string($conn, $_POST['user_mail']);
    $user_gender = mysqli_real_escape_string($conn, $_POST['user_gender']);
    $user_level  = intval($_POST['user_level']);
    $dep_id      = intval($_POST['dep_id']);

    // ======================
    // VALIDASI DATA
    // ======================
    $errors = [];
    
    if (empty($user_id)) $errors[] = "User ID tidak valid!";
    if (empty($user_name)) $errors[] = "Nama user harus diisi!";
    if (empty($user_mail)) $errors[] = "Email harus diisi!";
    if (empty($user_gender)) $errors[] = "Gender harus dipilih!";
    if ($user_level <= 0) $errors[] = "Hak akses harus dipilih!";
    if ($dep_id <= 0) $errors[] = "Departemen harus dipilih!";
    
    // Validasi email format
    if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit.php?id=$user_id");
        exit;
    }

    // ======================
    // CEK PASSWORD
    // ======================
    $password_query = "";
    if (!empty($_POST['user_password'])) {
        if (strlen($_POST['user_password']) < 8) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Password minimal 8 karakter'
            ];
            header("Location: edit.php?id=$user_id");
            exit;
        }
        
        $user_password = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
        $password_query = ", user_password = '$user_password'";
    }

    // ======================
    // CEK GAMBAR LAMA
    // ======================
    $qOld = mysqli_query($conn, "SELECT user_image FROM tbl_user WHERE user_id='$user_id'");
    $oldData = mysqli_fetch_assoc($qOld);
    $oldImage = $oldData['user_image'] ?? null;

    $nama_file = $oldImage;

    // ======================
    // UPLOAD GAMBAR BARU
    // ======================
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === 0 && !empty($_FILES['user_image']['name'])) {
        $folder = "../master/img/user/";
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        // Validasi ukuran (2MB)
        if ($_FILES['user_image']['size'] > 2097152) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: edit.php?id=$user_id");
            exit;
        }

        // Validasi ekstensi
        $ext = strtolower(pathinfo($_FILES['user_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($ext, $allowed)) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format harus JPG/PNG'
            ];
            header("Location: edit.php?id=$user_id");
            exit;
        }

        $nama_file = 'USER_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['user_image']['tmp_name'], $folder . $nama_file)) {
            // hapus gambar lama
            if (!empty($oldImage) && file_exists($folder . $oldImage)) {
                unlink($folder . $oldImage);
            }
        }
    }
    
    // Hapus gambar jika dicentang
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
        $folder = "../master/img/user/";
        if (!empty($oldImage) && file_exists($folder . $oldImage)) {
            unlink($folder . $oldImage);
        }
        $nama_file = '';
    }

    // ======================
    // UPDATE DATA
    // ======================
    $query = "
        UPDATE tbl_user SET
            user_name   = '$user_name',
            user_mail   = '$user_mail',
            user_gender = '$user_gender',
            user_image  = " . (empty($nama_file) ? "NULL" : "'$nama_file'") . ",
            user_level  = '$user_level',
            dep_id      = '$dep_id'
            $password_query
        WHERE user_id = '$user_id'
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data User berhasil diupdate'
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
   HAPUS DATA USER
====================== */
if (isset($_GET['hapus'])) {

    $user_id = intval($_GET['id']);
    
    // Cek apakah user yang akan dihapus adalah user yang sedang login
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Anda tidak dapat menghapus akun sendiri!'
        ];
        header("Location: index.php");
        exit;
    }

    $q = mysqli_query($conn, "SELECT user_image FROM tbl_user WHERE user_id='$user_id'");
    $data = mysqli_fetch_assoc($q);

    if ($data) {
        $folder = "../master/img/user/";

        if (!empty($data['user_image']) && file_exists($folder . $data['user_image'])) {
            unlink($folder . $data['user_image']);
        }

        mysqli_query($conn, "DELETE FROM tbl_user WHERE user_id='$user_id'");

        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data User berhasil dihapus'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data user tidak ditemukan'
        ];
    }

    header("Location: index.php");
    exit;
}

/* ======================
   UPDATE PROFILE USER (untuk user sendiri)
====================== */
if (isset($_POST['update_profile'])) {

    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // ======================
    // CEK PASSWORD (jika diisi)
    // ======================
    $password_query = "";
    if (!empty($_POST['user_password'])) {
        if (strlen($_POST['user_password']) < 8) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Password minimal 8 karakter'
            ];
            header("Location: profile.php");
            exit;
        }
        
        $user_password = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
        $password_query = ", user_password = '$user_password'";
    }

    // ======================
    // CEK GAMBAR LAMA
    // ======================
    $qOld = mysqli_query($conn, "SELECT user_image FROM tbl_user WHERE user_id='$user_id'");
    $oldData = mysqli_fetch_assoc($qOld);
    $oldImage = $oldData['user_image'] ?? null;

    $nama_file = $oldImage;

    // ======================
    // UPLOAD GAMBAR BARU
    // ======================
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === 0 && !empty($_FILES['user_image']['name'])) {
        $folder = "../master/img/user/";
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        if ($_FILES['user_image']['size'] > 2097152) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: profile.php");
            exit;
        }

        $ext = strtolower(pathinfo($_FILES['user_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($ext, $allowed)) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format harus JPG/PNG'
            ];
            header("Location: profile.php");
            exit;
        }

        $nama_file = 'USER_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['user_image']['tmp_name'], $folder . $nama_file)) {
            if (!empty($oldImage) && file_exists($folder . $oldImage)) {
                unlink($folder . $oldImage);
            }
        }
    }
    
    // Hapus gambar jika dicentang
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
        $folder = "../master/img/user/";
        if (!empty($oldImage) && file_exists($folder . $oldImage)) {
            unlink($folder . $oldImage);
        }
        $nama_file = '';
    }

    // ======================
    // UPDATE DATA
    // ======================
    $query = "
        UPDATE tbl_user SET
            user_image = " . (empty($nama_file) ? "NULL" : "'$nama_file'") . "
            $password_query
        WHERE user_id = '$user_id'
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Profil berhasil diperbarui!'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal memperbarui profil: ' . mysqli_error($conn)
        ];
    }

    header("Location: profile.php");
    exit;
}

// Jika tidak ada aksi yang sesuai
header("Location: index.php");
exit;
?>