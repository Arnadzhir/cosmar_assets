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

    $assets_kode = upper($_POST['assets_kode'] ?? '');
    $assets_name = upper($_POST['assets_name'] ?? '');
    $assets_life = intval($_POST['assets_life'] ?? 0);
    $assets_price = !empty($_POST['assets_price']) ? str_replace('.', '', $_POST['assets_price']) : 'NULL';
    $assets_date = !empty($_POST['assets_date']) ? "'" . $_POST['assets_date'] . "'" : 'NULL';
    $assets_qty = intval($_POST['assets_qty'] ?? 0);
    $assets_spec = !empty($_POST['assets_spec']) ? "'" . upper($_POST['assets_spec']) . "'" : 'NULL';
    $assets_note = !empty($_POST['assets_note']) ? "'" . upper($_POST['assets_note']) . "'" : 'NULL';
    $assets_model = !empty($_POST['assets_model']) ? "'" . upper($_POST['assets_model']) . "'" : 'NULL';
    $assets_target = !empty($_POST['assets_target']) ? "'" . upper($_POST['assets_target']) . "'" : 'NULL';
    $assets_cap = !empty($_POST['assets_cap']) ? "'" . $_POST['assets_cap'] . "'" : 'NULL';
    $assets_uom = !empty($_POST['assets_uom']) ? "'" . $_POST['assets_uom'] . "'" : 'NULL';
    
    $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : 'NULL';
    $merk_id = !empty($_POST['merk_id']) ? intval($_POST['merk_id']) : 'NULL';
    $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 'NULL';
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 'NULL';
    $produsen_id = !empty($_POST['produsen_id']) ? intval($_POST['produsen_id']) : 'NULL';

    // Validasi data wajib
    $errors = [];
    if (empty($assets_name)) {
        $errors[] = "Nama assets harus diisi";
    }
    if ($assets_life <= 0) {
        $errors[] = "Estimasi masa manfaat harus diisi dengan nilai positif";
    }
    if ($kategori_id == 'NULL') {
        $errors[] = "Kategori harus dipilih";
    }
    if ($merk_id == 'NULL') {
        $errors[] = "Merk harus dipilih";
    }
    if ($type_id == 'NULL') {
        $errors[] = "Type harus dipilih";
    }

    // Cek duplikasi kode assets (jika diisi)
    if (!empty($assets_kode)) {
        $cek = mysqli_query($conn, "SELECT 1 FROM tbl_assets WHERE assets_kode = '$assets_kode'");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = "Assets Code sudah digunakan!";
        }
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: index.php");
        exit;
    }

    // Query INSERT dengan semua field
    $query = "INSERT INTO tbl_assets (
        assets_kode,
        assets_name,
        assets_life,
        assets_price,
        assets_date,
        assets_qty,
        assets_spec,
        assets_note,
        assets_model,
        assets_target,
        assets_cap,
        assets_uom,
        kategori_id,
        merk_id,
        type_id,
        supplier_id,
        produsen_id,
        timestamp
    ) VALUES (
        " . (!empty($assets_kode) ? "'$assets_kode'" : "NULL") . ",
        '$assets_name',
        $assets_life,
        $assets_price,
        $assets_date,
        $assets_qty,
        $assets_spec,
        $assets_note,
        $assets_model,
        $assets_target,
        $assets_cap,
        $assets_uom,
        $kategori_id,
        $merk_id,
        $type_id,
        $supplier_id,
        $produsen_id,
        NOW()
    )";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data assets berhasil ditambahkan'
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

    $assets_id = intval($_POST['assets_id']);
    $assets_kode = upper($_POST['assets_kode'] ?? '');
    $assets_name = upper($_POST['assets_name'] ?? '');
    $assets_life = intval($_POST['assets_life'] ?? 0);
    $assets_price = !empty($_POST['assets_price']) ? str_replace('.', '', $_POST['assets_price']) : 'NULL';
    $assets_date = !empty($_POST['assets_date']) ? "'" . $_POST['assets_date'] . "'" : 'NULL';
    $assets_qty = intval($_POST['assets_qty'] ?? 0);
    $assets_spec = !empty($_POST['assets_spec']) ? "'" . upper($_POST['assets_spec']) . "'" : 'NULL';
    $assets_note = !empty($_POST['assets_note']) ? "'" . upper($_POST['assets_note']) . "'" : 'NULL';
    $assets_model = !empty($_POST['assets_model']) ? "'" . upper($_POST['assets_model']) . "'" : 'NULL';
    $assets_target = !empty($_POST['assets_target']) ? "'" . upper($_POST['assets_target']) . "'" : 'NULL';
    $assets_cap = !empty($_POST['assets_cap']) ? "'" . $_POST['assets_cap'] . "'" : 'NULL';
    $assets_uom = !empty($_POST['assets_uom']) ? "'" . $_POST['assets_uom'] . "'" : 'NULL';
    
    $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : 'NULL';
    $merk_id = !empty($_POST['merk_id']) ? intval($_POST['merk_id']) : 'NULL';
    $type_id = !empty($_POST['type_id']) ? intval($_POST['type_id']) : 'NULL';
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 'NULL';
    $produsen_id = !empty($_POST['produsen_id']) ? intval($_POST['produsen_id']) : 'NULL';

    // Validasi data wajib
    $errors = [];
    if (empty($assets_name)) {
        $errors[] = "Nama assets harus diisi";
    }
    if ($assets_life <= 0) {
        $errors[] = "Estimasi masa manfaat harus diisi dengan nilai positif";
    }
    if ($kategori_id == 'NULL') {
        $errors[] = "Kategori harus dipilih";
    }
    if ($merk_id == 'NULL') {
        $errors[] = "Merk harus dipilih";
    }
    if ($type_id == 'NULL') {
        $errors[] = "Type harus dipilih";
    }

    // Cek duplikasi kode assets (jika diubah)
    if (!empty($assets_kode)) {
        $cek = mysqli_query($conn, "SELECT 1 FROM tbl_assets WHERE assets_kode = '$assets_kode' AND assets_id != $assets_id");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = "Assets Code sudah digunakan!";
        }
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: index.php");
        exit;
    }

    // Query UPDATE dengan semua field
    $query = "UPDATE tbl_assets SET
        assets_kode = " . (!empty($assets_kode) ? "'$assets_kode'" : "NULL") . ",
        assets_name = '$assets_name',
        assets_life = $assets_life,
        assets_price = $assets_price,
        assets_date = $assets_date,
        assets_qty = $assets_qty,
        assets_spec = $assets_spec,
        assets_note = $assets_note,
        assets_model = $assets_model,
        assets_target = $assets_target,
        assets_cap = $assets_cap,
        assets_uom = $assets_uom,
        kategori_id = $kategori_id,
        merk_id = $merk_id,
        type_id = $type_id,
        supplier_id = $supplier_id,
        produsen_id = $produsen_id,
        timestamp = NOW()
    WHERE assets_id = $assets_id";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data assets berhasil diperbarui'
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

    $assets_id = intval($_GET['id']);

    // Cek apakah assets memiliki relasi di tbl_primary
    $cekPrimary = mysqli_query($conn, "SELECT 1 FROM tbl_primary WHERE assets_id = $assets_id LIMIT 1");
    if (mysqli_num_rows($cekPrimary) > 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data assets tidak dapat dihapus karena masih memiliki relasi dengan primary assets'
        ];
        header("Location: index.php");
        exit;
    }

    // Hapus data
    $query = "DELETE FROM tbl_assets WHERE assets_id = $assets_id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data assets berhasil dihapus'
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