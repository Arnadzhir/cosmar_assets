<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   FUNCTION HELPER
===================== */
function upper($text) {
    return strtoupper(trim($text));
}

/* =====================
   TAMBAH DATA
===================== */
if (isset($_POST['tambah'])) {

    $assets_id       = (int) $_POST['assets_id'];
    $karyawan_id     = (int) $_POST['karyawan_id']; // PERUBAHAN: user_id -> karyawan_id
    $name            = upper($_POST['sparepart_name']);
    $merk            = upper($_POST['sparepart_merk']);
    $qty             = (int) $_POST['sparepart_qty'];
    $price           = (int) str_replace('.', '', $_POST['sparepart_price']); // Hapus titik dari format Rupiah
    $date            = $_POST['sparepart_date'];
    $spec            = upper($_POST['sparepart_spec'] ?? '');
    $note            = upper($_POST['sparepart_note'] ?? '');
    $kondisi         = $_POST['kondisi_id'];

    $imageName = '';

    // Validasi data
    $errors = [];
    
    if ($assets_id <= 0) {
        $errors[] = "Asset harus dipilih";
    }
    if ($karyawan_id <= 0) {
        $errors[] = "Penanggung jawab harus dipilih";
    }
    if (empty($name)) {
        $errors[] = "Nama sparepart harus diisi";
    }
    if (empty($merk)) {
        $errors[] = "Merk sparepart harus diisi";
    }
    if ($qty <= 0) {
        $errors[] = "Quantity harus minimal 1";
    }
    if ($price <= 0) {
        $errors[] = "Harga harus diisi dengan nilai valid";
    }
    if (empty($date)) {
        $errors[] = "Tanggal pembelian harus diisi";
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: tambah.php");
        exit;
    }

    // ================= UPLOAD GAMBAR =================
    if (!empty($_FILES['sparepart_image']['name'])) {

        $file      = $_FILES['sparepart_image'];
        $file_size = $file['size'];
        $file_tmp   = $file['tmp_name'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png'];

        // Cek ukuran (max 2MB)
        if ($file_size > 2000000) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: tambah.php");
            exit;
        }

        if (in_array($ext, $allowed)) {
            $imageName = 'SPAREPART_' . time() . '_' . uniqid() . '.' . $ext;
            $target    = "../master/img/assets/" . $imageName;

            if (!move_uploaded_file($file_tmp, $target)) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'msg'  => 'Gagal upload gambar'
                ];
                header("Location: tambah.php");
                exit;
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format gambar harus JPG, JPEG, atau PNG'
            ];
            header("Location: tambah.php");
            exit;
        }
    }

    $query = "
        INSERT INTO tbl_sparepart 
        (assets_id, user_id, sparepart_name, sparepart_merk, sparepart_qty, sparepart_price, 
         sparepart_date, sparepart_spec, sparepart_note, sparepart_image, kondisi_id)
        VALUES
        ('$assets_id','$karyawan_id','$name','$merk','$qty','$price','$date','$spec','$note','$imageName','$kondisi')
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Sparepart berhasil ditambahkan'
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


/* =====================
   EDIT DATA
===================== */
if (isset($_POST['edit'])) {

    $id              = (int) $_POST['sparepart_id'];
    $assets_id       = (int) $_POST['assets_id'];
    $karyawan_id     = (int) $_POST['karyawan_id']; // PERUBAHAN: user_id -> karyawan_id
    $name            = upper($_POST['sparepart_name']);
    $merk            = upper($_POST['sparepart_merk']);
    $qty             = (int) $_POST['sparepart_qty'];
    $price           = (int) str_replace('.', '', $_POST['sparepart_price']); // Hapus titik dari format Rupiah
    $date            = $_POST['sparepart_date'];
    $spec            = upper($_POST['sparepart_spec'] ?? '');
    $note            = upper($_POST['sparepart_note'] ?? '');
    $kondisi         = $_POST['kondisi_id'];

    // Validasi data
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = "ID sparepart tidak valid";
    }
    if ($assets_id <= 0) {
        $errors[] = "Asset harus dipilih";
    }
    if ($karyawan_id <= 0) {
        $errors[] = "Penanggung jawab harus dipilih";
    }
    if (empty($name)) {
        $errors[] = "Nama sparepart harus diisi";
    }
    if (empty($merk)) {
        $errors[] = "Merk sparepart harus diisi";
    }
    if ($qty <= 0) {
        $errors[] = "Quantity harus minimal 1";
    }
    if ($price <= 0) {
        $errors[] = "Harga harus diisi dengan nilai valid";
    }
    if (empty($date)) {
        $errors[] = "Tanggal pembelian harus diisi";
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit.php?id=$id");
        exit;
    }

    // Ambil gambar lama
    $qOld   = mysqli_query($conn, "SELECT sparepart_image FROM tbl_sparepart WHERE sparepart_id='$id'");
    $old    = mysqli_fetch_assoc($qOld);
    $imageName = $old['sparepart_image'] ?? '';

    // ================= UPLOAD GAMBAR BARU =================
    if (!empty($_FILES['sparepart_image']['name'])) {

        $file      = $_FILES['sparepart_image'];
        $file_size = $file['size'];
        $file_tmp   = $file['tmp_name'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png'];

        // Cek ukuran (max 2MB)
        if ($file_size > 2000000) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: edit.php?id=$id");
            exit;
        }

        if (in_array($ext, $allowed)) {
            // hapus gambar lama jika ada
            if (!empty($imageName) && file_exists("../master/img/assets/" . $imageName)) {
                unlink("../master/img/assets/" . $imageName);
            }

            $imageName = 'SPAREPART_' . time() . '_' . uniqid() . '.' . $ext;
            $target    = "../master/img/assets/" . $imageName;

            if (!move_uploaded_file($file_tmp, $target)) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'msg'  => 'Gagal upload gambar'
                ];
                header("Location: edit.php?id=$id");
                exit;
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format gambar harus JPG, JPEG, atau PNG'
            ];
            header("Location: edit.php?id=$id");
                exit;
        }
    }

    $query = "
        UPDATE tbl_sparepart SET
            assets_id       = '$assets_id',
            user_id         = '$karyawan_id',
            sparepart_name  = '$name',
            sparepart_merk  = '$merk',
            sparepart_qty   = '$qty',
            sparepart_price = '$price',
            sparepart_date  = '$date',
            sparepart_spec  = '$spec',
            sparepart_note  = '$note',
            kondisi_id      = '$kondisi',
            sparepart_image = '$imageName'
        WHERE sparepart_id = '$id'
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Sparepart berhasil diperbarui'
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


/* =====================
   HAPUS DATA
===================== */
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['id'];

    // Cek apakah sparepart ada
    $q = mysqli_query($conn, "SELECT sparepart_image FROM tbl_sparepart WHERE sparepart_id='$id'");
    
    if (!$q || mysqli_num_rows($q) == 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data tidak ditemukan'
        ];
        header("Location: index.php");
        exit;
    }
    
    $d = mysqli_fetch_assoc($q);

    // Hapus gambar jika ada
    if (!empty($d['sparepart_image']) && file_exists("../master/img/assets/" . $d['sparepart_image'])) {
        unlink("../master/img/assets/" . $d['sparepart_image']);
    }

    $delete = mysqli_query($conn, "DELETE FROM tbl_sparepart WHERE sparepart_id='$id'");

    if ($delete) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Sparepart berhasil dihapus'
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