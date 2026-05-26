<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

function upper($text) {
    return strtoupper(trim($text));
}

/* ======================
   CREATE - TAMBAH ASSET BARU (DRAFT)
====================== */
if (isset($_POST['create'])) {

    // ======================
    // AMBIL DATA POST
    // ======================

    $karyawan_id   = (int)$_POST['karyawan_id'];
    $dep_id        = !empty($_POST['dep_id']) ? (int)$_POST['dep_id'] : 'NULL';

    // Data untuk tbl_assets
    $assets_name   = upper($_POST['assets_name']);
    $assets_life   = (int)$_POST['assets_life'];
    $assets_model  = upper($_POST['assets_model'] ?? '');
    $assets_spec   = upper($_POST['assets_spec'] ?? '');
    $assets_target = upper($_POST['assets_target'] ?? '');
    $assets_cap    = $_POST['assets_cap'] ?? '';
    $assets_uom    = $_POST['assets_uom'] ?? '';
    $assets_note   = upper($_POST['assets_note'] ?? '');

    // Foreign keys untuk tbl_assets
    $merk_id      = (int)$_POST['merk_id'];
    $type_id      = (int)$_POST['type_id'];
    $supplier_id  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 'NULL';
    $produsen_id  = !empty($_POST['produsen_id']) ? (int)$_POST['produsen_id'] : 'NULL';

    // Kondisi asset (dari form)
    $kondisi_id   = !empty($_POST['kondisi_id']) ? (int)$_POST['kondisi_id'] : 'NULL';

    // Ambil array lokasi
    $lokasi_ids = $_POST['lokasi_id'] ?? [];
    $qtys       = $_POST['qty'] ?? [];
    
    // Validasi jumlah data
    if (count($lokasi_ids) != count($qtys)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data lokasi dan qty tidak valid'
        ];
        header("Location: tambah.php");
        exit;
    }
    
    // Hitung total qty
    $total_qty = 0;
    foreach ($qtys as $q) {
        $total_qty += intval($q);
    }
    
    if ($total_qty < 1) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Total Qty harus minimal 1'
        ];
        header("Location: tambah.php");
        exit;
    }

    // Validasi minimal 1 lokasi
    if (count($lokasi_ids) < 1) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Minimal 1 lokasi harus diisi'
        ];
        header("Location: tambah.php");
        exit;
    }

    // ======================
    // UPLOAD GAMBAR
    // ======================

    $nama_file = null;
    if (!empty($_FILES['primary_image']['name'])) {
        $folder = "../master/img/assets/";
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        // Validasi file
        $file_tmp = $_FILES['primary_image']['tmp_name'];
        $file_size = $_FILES['primary_image']['size'];
        $file_type = $_FILES['primary_image']['type'];
        
        // Cek ukuran (max 2MB)
        if ($file_size > 2000000) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Ukuran gambar maksimal 2MB'
            ];
            header("Location: tambah.php");
            exit;
        }
        
        // Cek format
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Format gambar harus JPG atau PNG'
            ];
            header("Location: tambah.php");
            exit;
        }
        
        $ext = pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION);
        $nama_file = 'ASSET_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (!move_uploaded_file($file_tmp, $folder . $nama_file)) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Gagal upload gambar'
            ];
            header("Location: tambah.php");
            exit;
        }
    }

    // ======================
    // INSERT KE tbl_assets (1 BARIS)
    // assets_model dan assets_spec disimpan TERPISAH
    // ======================
    $queryAssets = "
        INSERT INTO tbl_assets
        (assets_kode, assets_name, assets_life, assets_model, assets_spec, assets_target, 
         assets_cap, assets_uom, assets_note, assets_qty,
         merk_id, type_id, supplier_id, produsen_id, timestamp)
        VALUES
        (NULL, '$assets_name', $assets_life, " . ($assets_model ? "'$assets_model'" : "NULL") . ", " . ($assets_spec ? "'$assets_spec'" : "NULL") . ", " . ($assets_target ? "'$assets_target'" : "NULL") . ",
         " . ($assets_cap ? "'$assets_cap'" : "NULL") . ", " . ($assets_uom ? "'$assets_uom'" : "NULL") . ", " . ($assets_note ? "'$assets_note'" : "NULL") . ", $total_qty,
         $merk_id, $type_id, $supplier_id, $produsen_id, NOW())
    ";
    
    if (!mysqli_query($conn, $queryAssets)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal insert assets: ' . mysqli_error($conn)
        ];
        header("Location: tambah.php");
        exit;
    }
    
    $assets_id = mysqli_insert_id($conn);

    // ======================
    // INSERT KE tbl_primary PER BARIS (dengan dep_id)
    // ======================
    $success = true;
    $inserted_count = 0;
    
    for ($i = 0; $i < count($lokasi_ids); $i++) {
        $lokasi_id = mysqli_real_escape_string($conn, $lokasi_ids[$i]);
        $qty_this = intval($qtys[$i]);
        
        // Insert per baris - setiap baris menjadi record terpisah di tbl_primary
        for ($j = 0; $j < $qty_this; $j++) {
            $query = "
                INSERT INTO tbl_primary
                (
                    assets_id,
                    primary_qty,
                    primary_image,
                    kondisi_id,
                    lokasi_id,
                    karyawan_id,
                    dep_id,
                    timestamp
                )
                VALUES
                (
                    $assets_id,
                    1,
                    " . ($nama_file ? "'$nama_file'" : "NULL") . ",
                    " . ($kondisi_id != 'NULL' ? $kondisi_id : "NULL") . ",
                    '$lokasi_id',
                    $karyawan_id,
                    $dep_id,
                    NOW()
                )
            ";
            
            if (mysqli_query($conn, $query)) {
                $inserted_count++;
            } else {
                $success = false;
                error_log("Error insert primary: " . mysqli_error($conn));
                break 2; // Keluar dari kedua loop
            }
        }
    }

    if ($success && $inserted_count > 0) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => "Data berhasil diajukan: $inserted_count unit asset"
        ];
    } else {
        // Jika gagal, hapus assets yang sudah diinsert
        mysqli_query($conn, "DELETE FROM tbl_assets WHERE assets_id = $assets_id");
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menambahkan data'
        ];
    }

    header("Location: index.php");
    exit;
}

/* ======================
   UPDATE - EDIT DRAFT ASSET
====================== */
if (isset($_POST['update'])) {

    $assets_id = intval($_POST['assets_id']);
    $karyawan_id = isset($_POST['karyawan_id']) ? (int)$_POST['karyawan_id'] : 'NULL';
    $dep_id = isset($_POST['dep_id']) ? (int)$_POST['dep_id'] : 'NULL'; // TAMBAHKAN UNTUK dep_id
    
    // Data untuk tbl_assets
    $assets_name   = upper($_POST['assets_name']);
    $assets_life   = (int)$_POST['assets_life'];
    $assets_model  = upper($_POST['assets_model'] ?? '');
    $assets_spec   = upper($_POST['assets_spec_detail'] ?? '');
    $assets_target = upper($_POST['assets_target'] ?? '');
    $assets_cap    = $_POST['assets_cap'] ?? '';
    $assets_uom    = $_POST['assets_uom'] ?? '';
    $assets_note   = upper($_POST['assets_note'] ?? '');
    
    $merk_id      = (int)$_POST['merk_id'];
    $type_id      = (int)$_POST['type_id'];
    $supplier_id  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 'NULL';
    $produsen_id  = !empty($_POST['produsen_id']) ? (int)$_POST['produsen_id'] : 'NULL';
    $kondisi_id   = !empty($_POST['kondisi_id']) ? (int)$_POST['kondisi_id'] : 'NULL';
    
    // Data multiple lokasi
    $primary_ids = $_POST['primary_id'] ?? [];
    $lokasi_ids = $_POST['lokasi_id'] ?? [];
    
    // Validasi
    if (count($primary_ids) != count($lokasi_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data lokasi tidak valid'
        ];
        header("Location: edit.php?id=$assets_id");
        exit;
    }
    
    if (count($lokasi_ids) < 1) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Minimal 1 lokasi harus diisi'
        ];
        header("Location: edit.php?id=$assets_id");
        exit;
    }
    
    // Upload gambar baru
    $nama_file = null;
    $gambar_lama = null;
    $folder = "../master/img/assets/";
    
    if (!empty($_FILES['primary_image']['name'])) {
        $qGambar = mysqli_query($conn, "SELECT primary_image FROM tbl_primary WHERE assets_id = $assets_id LIMIT 1");
        $gambarData = mysqli_fetch_assoc($qGambar);
        $gambar_lama = $gambarData['primary_image'] ?? null;
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $file_tmp = $_FILES['primary_image']['tmp_name'];
        $file_size = $_FILES['primary_image']['size'];
        $file_type = $_FILES['primary_image']['type'];
        
        if ($file_size > 2000000) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Ukuran gambar maksimal 2MB'];
            header("Location: edit.php?id=$assets_id");
            exit;
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Format gambar harus JPG atau PNG'];
            header("Location: edit.php?id=$assets_id");
            exit;
        }
        
        $ext = pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION);
        $nama_file = 'ASSET_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($file_tmp, $folder . $nama_file)) {
            if ($gambar_lama && file_exists($folder . $gambar_lama)) {
                unlink($folder . $gambar_lama);
            }
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal upload gambar'];
            header("Location: edit.php?id=$assets_id");
            exit;
        }
    }
    
    // Cek hapus gambar
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1' && !empty($gambar_lama)) {
        if (file_exists($folder . $gambar_lama)) {
            unlink($folder . $gambar_lama);
        }
        $nama_file = null;
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Update tbl_assets (assets_model dan assets_spec disimpan TERPISAH)
        $queryAssets = "
            UPDATE tbl_assets SET
                assets_name = '$assets_name',
                assets_life = $assets_life,
                assets_model = " . ($assets_model ? "'$assets_model'" : "NULL") . ",
                assets_spec = " . ($assets_spec ? "'$assets_spec'" : "NULL") . ",
                assets_target = " . ($assets_target ? "'$assets_target'" : "NULL") . ",
                assets_cap = " . ($assets_cap ? "'$assets_cap'" : "NULL") . ",
                assets_uom = " . ($assets_uom ? "'$assets_uom'" : "NULL") . ",
                assets_note = " . ($assets_note ? "'$assets_note'" : "NULL") . ",
                assets_qty = " . count($lokasi_ids) . ",
                merk_id = $merk_id,
                type_id = $type_id,
                supplier_id = $supplier_id,
                produsen_id = $produsen_id,
                timestamp = NOW()
            WHERE assets_id = $assets_id AND (assets_kode IS NULL OR assets_kode = '')
        ";
        
        if (!mysqli_query($conn, $queryAssets)) {
            throw new Exception("Gagal update assets: " . mysqli_error($conn));
        }
        
        // Array untuk menyimpan primary_id yang akan dihapus
        $existing_ids = [];
        $new_ids = [];
        
        foreach ($primary_ids as $index => $pid) {
            $lokasi_id = mysqli_real_escape_string($conn, $lokasi_ids[$index]);
            
            if (strpos($pid, 'new_') === 0) {
                // Insert baru (dengan dep_id)
                $queryInsert = "
                    INSERT INTO tbl_primary (
                        assets_id, primary_qty, primary_image, kondisi_id, lokasi_id, karyawan_id, dep_id, timestamp
                    ) VALUES (
                        $assets_id, 1, " . ($nama_file ? "'$nama_file'" : "NULL") . ", " . ($kondisi_id != 'NULL' ? $kondisi_id : "NULL") . ", '$lokasi_id', " . ($karyawan_id != 'NULL' ? $karyawan_id : "NULL") . ", $dep_id, NOW()
                    )
                ";
                if (!mysqli_query($conn, $queryInsert)) {
                    throw new Exception("Gagal insert primary baru: " . mysqli_error($conn));
                }
                $new_ids[] = mysqli_insert_id($conn);
            } else {
                // Update existing
                $existing_ids[] = $pid;
                
                // Bangun query update dinamis
                $updateFields = "lokasi_id = '$lokasi_id', timestamp = NOW()";
                if ($nama_file) {
                    $updateFields .= ", primary_image = '$nama_file'";
                }
                if ($karyawan_id != 'NULL') {
                    $updateFields .= ", karyawan_id = $karyawan_id";
                }
                if ($kondisi_id != 'NULL') {
                    $updateFields .= ", kondisi_id = $kondisi_id";
                }
                if ($dep_id != 'NULL') {
                    $updateFields .= ", dep_id = $dep_id";
                }
                
                $queryUpdate = "
                    UPDATE tbl_primary SET
                        $updateFields
                    WHERE primary_id = $pid AND assets_id = $assets_id
                ";
                if (!mysqli_query($conn, $queryUpdate)) {
                    throw new Exception("Gagal update primary: " . mysqli_error($conn));
                }
            }
        }
        
        // Hapus primary yang tidak ada di list
        $ids_to_keep = array_merge($existing_ids, $new_ids);
        if (!empty($ids_to_keep)) {
            $ids_string = implode(',', $ids_to_keep);
            $deleteQuery = "DELETE FROM tbl_primary WHERE assets_id = $assets_id AND primary_id NOT IN ($ids_string)";
        } else {
            $deleteQuery = "DELETE FROM tbl_primary WHERE assets_id = $assets_id";
        }
        
        if (!mysqli_query($conn, $deleteQuery)) {
            throw new Exception("Gagal hapus primary yang tidak terpakai: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data berhasil diupdate'
        ];
        
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        
        if ($nama_file && file_exists($folder . $nama_file)) {
            unlink($folder . $nama_file);
        }
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
        header("Location: edit.php?id=$assets_id");
        exit;
    }
}

/* ======================
   APPROVE - MENYETUJUI ASSET
====================== */
if (isset($_POST['approve'])) {

    $assets_id = intval($_POST['assets_id']);
    $assets_kode = mysqli_real_escape_string($conn, $_POST['assets_kode']);
    $kategori_id = !empty($_POST['kategori_id']) ? (int)$_POST['kategori_id'] : 'NULL';
    $assets_price = !empty($_POST['assets_price']) ? (int)$_POST['assets_price'] : 'NULL';
    $assets_date = !empty($_POST['assets_date']) ? "'" . $_POST['assets_date'] . "'" : 'NULL';
    
    // Validasi
    if (empty($assets_kode)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Kode asset harus diisi'
        ];
        header("Location: approve.php?id=$assets_id");
        exit;
    }
    
    // Update tbl_assets
    $query = "
        UPDATE tbl_assets SET
            assets_kode = '$assets_kode',
            kategori_id = $kategori_id,
            assets_price = $assets_price,
            assets_date = $assets_date,
            timestamp = NOW()
        WHERE assets_id = $assets_id
    ";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Asset berhasil di-approve'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal approve asset: ' . mysqli_error($conn)
        ];
    }
    
    header("Location: index.php");
    exit;
}

/* ======================
   HAPUS ASSET BESERTA SEMUA PRIMARY NYA
====================== */
if (isset($_GET['hapus_assets'])) {
    
    $assets_id = intval($_GET['id']);
    
    // Ambil semua primary dari assets ini
    $q = mysqli_query($conn, "
        SELECT primary_id, primary_image 
        FROM tbl_primary 
        WHERE assets_id = $assets_id
    ");
    
    $gambar_to_hapus = [];
    
    while ($row = mysqli_fetch_assoc($q)) {
        if (!empty($row['primary_image'])) {
            $gambar_to_hapus[] = $row['primary_image'];
        }
    }
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Hapus gambar
        $folder = "../master/img/assets/";
        foreach ($gambar_to_hapus as $gambar) {
            if (file_exists($folder . $gambar)) {
                unlink($folder . $gambar);
            }
        }
        
        // Hapus semua primary
        $deletePrimary = mysqli_query($conn, "DELETE FROM tbl_primary WHERE assets_id = $assets_id");
        if (!$deletePrimary) {
            throw new Exception("Gagal hapus primary: " . mysqli_error($conn));
        }
        
        // Hapus assets
        $deleteAssets = mysqli_query($conn, "DELETE FROM tbl_assets WHERE assets_id = $assets_id");
        if (!$deleteAssets) {
            throw new Exception("Gagal hapus assets: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Asset beserta semua data berhasil dihapus'
        ];
        
    } catch (Exception $e) {
        // Rollback
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
    }
    
    header("Location: index.php");
    exit;
}

/* ======================
   HAPUS PRIMARY ITEM (UNIT)
====================== */
if (isset($_GET['hapus_primary'])) {

    $primary_id = intval($_GET['id']);

    // ambil data dulu
    $q = mysqli_query($conn, "
        SELECT p.primary_image, p.assets_id
        FROM tbl_primary p
        WHERE p.primary_id = $primary_id
    ");
    
    if (mysqli_num_rows($q) == 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data tidak ditemukan'
        ];
        header("Location: index.php");
        exit;
    }
    
    $data = mysqli_fetch_assoc($q);
    $assets_id = $data['assets_id'];

    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // Cek apakah gambar digunakan oleh primary lain
        $gambar = $data['primary_image'];
        if (!empty($gambar)) {
            $gambar_escape = mysqli_real_escape_string($conn, $gambar);
            $qCek = mysqli_query($conn, "
                SELECT COUNT(*) as jumlah 
                FROM tbl_primary 
                WHERE primary_image = '$gambar_escape' 
                AND primary_id != $primary_id
            ");
            $cek = mysqli_fetch_assoc($qCek);
            
            if ($cek['jumlah'] == 0 && file_exists("../master/img/assets/" . $gambar)) {
                unlink("../master/img/assets/" . $gambar);
            }
        }
        
        // Hapus primary
        $delete = mysqli_query($conn, "DELETE FROM tbl_primary WHERE primary_id = $primary_id");
        if (!$delete) {
            throw new Exception("Gagal hapus unit: " . mysqli_error($conn));
        }
        
        // Hitung sisa unit
        $qTotal = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_primary WHERE assets_id = $assets_id");
        $total = mysqli_fetch_assoc($qTotal);
        $total_sisa = $total['total'];
        
        // Update qty di tbl_assets
        $update = mysqli_query($conn, "
            UPDATE tbl_assets SET assets_qty = $total_sisa WHERE assets_id = $assets_id
        ");
        if (!$update) {
            throw new Exception("Gagal update qty asset: " . mysqli_error($conn));
        }
        
        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Unit berhasil dihapus'
        ];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
    }

    header("Location: edit.php?id=$assets_id");
    exit;
}

// Jika tidak ada aksi yang sesuai
header("Location: index.php");
exit;
?>