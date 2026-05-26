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
   TAMBAH DATA TOOLS
===================== */
if (isset($_POST['tambah'])) {

    $karyawan_id     = (int) $_POST['karyawan_id'];
    $tools_name      = upper($_POST['tools_name']);
    $tools_merk      = upper($_POST['tools_merk']);
    $tools_qty       = (int) $_POST['tools_qty'];
    $tools_price     = (int) str_replace('.', '', $_POST['tools_price']);
    $tools_date      = $_POST['tools_date'];
    $tools_spec      = upper($_POST['tools_spec'] ?? '');
    $tools_note      = upper($_POST['tools_note'] ?? '');
    $kondisi_id      = (int) $_POST['kondisi_id'];

    $imageName = '';

    // Validasi data
    $errors = [];
    
    if ($karyawan_id <= 0) {
        $errors[] = "Penanggung jawab harus dipilih";
    }
    if (empty($tools_name)) {
        $errors[] = "Nama tools harus diisi";
    }
    if (empty($tools_merk)) {
        $errors[] = "Merk tools harus diisi";
    }
    if ($tools_qty <= 0) {
        $errors[] = "Quantity harus minimal 1";
    }
    if ($tools_price <= 0) {
        $errors[] = "Harga harus diisi dengan nilai valid";
    }
    if (empty($tools_date)) {
        $errors[] = "Tanggal pembelian harus diisi";
    }
    if ($kondisi_id <= 0) {
        $errors[] = "Kondisi harus dipilih";
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
    $folder = "../master/img/tools/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    if (!empty($_FILES['tools_image']['name'])) {
        $file      = $_FILES['tools_image'];
        $file_size = $file['size'];
        $file_tmp  = $file['tmp_name'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png'];

        if ($file_size > 2000000) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: tambah.php");
            exit;
        }

        if (in_array($ext, $allowed)) {
            $imageName = 'TOOLS_' . time() . '_' . uniqid() . '.' . $ext;
            $target    = $folder . $imageName;

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
        INSERT INTO tbl_tools 
        (user_id, tools_name, tools_merk, tools_qty, tools_price, 
         tools_date, tools_spec, tools_note, tools_image, kondisi_id)
        VALUES
        ('$karyawan_id','$tools_name','$tools_merk','$tools_qty','$tools_price',
         '$tools_date','$tools_spec','$tools_note','$imageName','$kondisi_id')
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Tools berhasil ditambahkan'
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
   EDIT DATA TOOLS
===================== */
if (isset($_POST['edit'])) {

    $id              = (int) $_POST['tools_id'];
    $karyawan_id     = (int) $_POST['karyawan_id'];
    $tools_name      = upper($_POST['tools_name']);
    $tools_merk      = upper($_POST['tools_merk']);
    $tools_qty       = (int) $_POST['tools_qty'];
    $tools_price     = (int) str_replace('.', '', $_POST['tools_price']);
    $tools_date      = $_POST['tools_date'];
    $tools_spec      = upper($_POST['tools_spec'] ?? '');
    $tools_note      = upper($_POST['tools_note'] ?? '');
    $kondisi_id      = (int) $_POST['kondisi_id'];

    // Validasi data
    $errors = [];
    
    if ($id <= 0) {
        $errors[] = "ID tools tidak valid";
    }
    if ($karyawan_id <= 0) {
        $errors[] = "Penanggung jawab harus dipilih";
    }
    if (empty($tools_name)) {
        $errors[] = "Nama tools harus diisi";
    }
    if (empty($tools_merk)) {
        $errors[] = "Merk tools harus diisi";
    }
    if ($tools_qty <= 0) {
        $errors[] = "Quantity harus minimal 1";
    }
    if ($tools_price <= 0) {
        $errors[] = "Harga harus diisi dengan nilai valid";
    }
    if (empty($tools_date)) {
        $errors[] = "Tanggal pembelian harus diisi";
    }
    if ($kondisi_id <= 0) {
        $errors[] = "Kondisi harus dipilih";
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
    $qOld   = mysqli_query($conn, "SELECT tools_image FROM tbl_tools WHERE tools_id='$id'");
    $old    = mysqli_fetch_assoc($qOld);
    $imageName = $old['tools_image'] ?? '';

    // ================= UPLOAD GAMBAR BARU =================
    $folder = "../master/img/tools/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    if (!empty($_FILES['tools_image']['name'])) {
        $file      = $_FILES['tools_image'];
        $file_size = $file['size'];
        $file_tmp  = $file['tmp_name'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png'];

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
            if (!empty($imageName) && file_exists($folder . $imageName)) {
                unlink($folder . $imageName);
            }

            $imageName = 'TOOLS_' . time() . '_' . uniqid() . '.' . $ext;
            $target    = $folder . $imageName;

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

    // Hapus gambar jika dicentang
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
        if (!empty($imageName) && file_exists($folder . $imageName)) {
            unlink($folder . $imageName);
        }
        $imageName = '';
    }

    $query = "
        UPDATE tbl_tools SET
            user_id         = '$karyawan_id',
            tools_name      = '$tools_name',
            tools_merk      = '$tools_merk',
            tools_qty       = '$tools_qty',
            tools_price     = '$tools_price',
            tools_date      = '$tools_date',
            tools_spec      = '$tools_spec',
            tools_note      = '$tools_note',
            tools_image     = " . (empty($imageName) ? "NULL" : "'$imageName'") . ",
            kondisi_id      = '$kondisi_id'
        WHERE tools_id = '$id'
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Tools berhasil diperbarui'
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
   HAPUS DATA TOOLS
===================== */
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['id'];

    // Cek apakah tools ada
    $q = mysqli_query($conn, "SELECT tools_image FROM tbl_tools WHERE tools_id='$id'");
    
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
    $folder = "../master/img/tools/";
    if (!empty($d['tools_image']) && file_exists($folder . $d['tools_image'])) {
        unlink($folder . $d['tools_image']);
    }

    $delete = mysqli_query($conn, "DELETE FROM tbl_tools WHERE tools_id='$id'");

    if ($delete) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data Tools berhasil dihapus'
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