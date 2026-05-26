<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Cek role (hanya admin dan operator)
if (!in_array($_SESSION['user_level'], [1, 2])) {
    header("Location: ../dashboard.php");
    exit;
}

function upper($text) {
    return strtoupper(trim($text));
}

/* ======================
   APPROVE / UPDATE ASSETS
====================== */ 
if (isset($_POST['approve'])) {

    // ======================
    // AMBIL DATA POST
    // ======================

    $assets_id = intval($_POST['assets_id']);
    $karyawan_id = !empty($_POST['karyawan_id']) ? intval($_POST['karyawan_id']) : 'NULL';

    // Data untuk tbl_assets
    $assets_name   = upper($_POST['assets_name']);
    $assets_life   = intval($_POST['assets_life']);
    $assets_model  = upper($_POST['assets_model'] ?? '');
    $assets_spec   = upper($_POST['assets_spec'] ?? '');
    $assets_target = upper($_POST['assets_target'] ?? '');
    $assets_cap    = $_POST['assets_cap'] ?? '';
    $assets_uom    = $_POST['assets_uom'] ?? '';
    $assets_note   = upper($_POST['assets_note'] ?? '');
    $assets_price  = str_replace('.', '', $_POST['assets_price'] ?? '0');
    $assets_date   = $_POST['assets_date'] ?? null;
    $assets_kode   = mysqli_real_escape_string($conn, $_POST['assets_kode'] ?? '');
    
    $kategori_id   = intval($_POST['kategori_id']);
    $merk_id       = intval($_POST['merk_id']);
    $type_id       = intval($_POST['type_id']);
    $supplier_id   = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 'NULL';
    $produsen_id   = !empty($_POST['produsen_id']) ? intval($_POST['produsen_id']) : 'NULL';

    // Validasi data wajib
    $errors = [];

    if (empty($assets_name)) {
        $errors[] = "Nama assets harus diisi";
    }
    if (empty($assets_life) || $assets_life <= 0) {
        $errors[] = "Estimasi masa manfaat harus diisi dengan nilai positif";
    }
    if (empty($kategori_id)) {
        $errors[] = "Kategori harus dipilih";
    }
    if (empty($merk_id)) {
        $errors[] = "Merk harus dipilih";
    }
    if (empty($type_id)) {
        $errors[] = "Type harus dipilih";
    }
    if (empty($assets_price) || $assets_price <= 0) {
        $errors[] = "Harga harus diisi dengan nilai valid";
    }
    if (empty($assets_date)) {
        $errors[] = "Tanggal pembelian harus diisi";
    }
    if (empty($assets_kode)) {
        $errors[] = "Kode assets harus diisi";
    } else {
        // Cek duplikasi kode
        $cek = mysqli_query($conn, "
            SELECT assets_id FROM tbl_assets 
            WHERE assets_kode = '$assets_kode' 
            AND assets_id != $assets_id
        ");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = "Kode assets '$assets_kode' sudah digunakan";
        }
    }

    // Validasi karyawan_id
    if ($karyawan_id == 'NULL' || $karyawan_id <= 0) {
        $errors[] = "Penanggung jawab harus dipilih";
    }

    // ======================
    // UPLOAD GAMBAR
    // ======================

    $nama_file = null;
    $gambar_lama = null;
    $folder = "../master/img/assets/";
    
    // Ambil gambar lama
    $qGambarLama = mysqli_query($conn, "
        SELECT primary_image FROM tbl_primary WHERE assets_id = $assets_id LIMIT 1
    ");
    $gambarLamaData = mysqli_fetch_assoc($qGambarLama);
    $gambar_lama = $gambarLamaData['primary_image'] ?? null;
    
    // Upload gambar baru
    if (!empty($_FILES['primary_image']['name'])) {
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $file_tmp = $_FILES['primary_image']['tmp_name'];
        $file_size = $_FILES['primary_image']['size'];
        $file_type = $_FILES['primary_image']['type'];
        $file_error = $_FILES['primary_image']['error'];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Error upload gambar: " . $file_error;
        }
        if ($file_size > 2000000) {
            $errors[] = "Ukuran gambar maksimal 2MB";
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Format gambar harus JPG atau PNG";
        }
        
        if (empty($errors)) {
            $ext = pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION);
            $nama_file = 'ASSET_' . time() . '_' . uniqid() . '.' . $ext;
            
            if (move_uploaded_file($file_tmp, $folder . $nama_file)) {
                if ($gambar_lama && file_exists($folder . $gambar_lama)) {
                    unlink($folder . $gambar_lama);
                }
            } else {
                $errors[] = "Gagal menyimpan gambar";
            }
        }
    }
    
    // Hapus gambar jika dicentang
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1' && !empty($gambar_lama)) {
        if (file_exists($folder . $gambar_lama)) {
            unlink($folder . $gambar_lama);
        }
        $nama_file = null;
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit2.php?id=$assets_id");
        exit;
    }

    // ======================
    // PROSES DATA LOKASI
    // ======================
    $primary_ids = $_POST['primary_id'] ?? [];
    $lokasi_ids = $_POST['lokasi_id'] ?? [];
    $primary_qtys = $_POST['primary_qty'] ?? [];
    $kondisi_ids = $_POST['kondisi_id'] ?? [];

    // Validasi jumlah data
    if (count($primary_ids) == 0) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Tidak ada data lokasi'
        ];
        header("Location: edit2.php?id=$assets_id");
        exit;
    }

    if (count($primary_ids) != count($lokasi_ids) || 
        count($primary_ids) != count($primary_qtys) || 
        count($primary_ids) != count($kondisi_ids)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data lokasi tidak lengkap'
        ];
        header("Location: edit2.php?id=$assets_id");
        exit;
    }

    // ======================
    // MULAI TRANSAKSI
    // ======================
    mysqli_begin_transaction($conn);

    try {
        
        // 1. UPDATE tbl_assets (dengan assets_model yang terpisah)
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
                assets_price = $assets_price,
                assets_date = " . ($assets_date ? "'$assets_date'" : "NULL") . ",
                assets_kode = " . ($assets_kode ? "'$assets_kode'" : "NULL") . ",
                kategori_id = $kategori_id,
                merk_id = $merk_id,
                type_id = $type_id,
                supplier_id = $supplier_id,
                produsen_id = $produsen_id,
                timestamp = NOW()
            WHERE assets_id = $assets_id
        ";
        
        if (!mysqli_query($conn, $queryAssets)) {
            throw new Exception("Gagal update assets: " . mysqli_error($conn));
        }

        // 2. PROSES PER LOKASI
        $ids_to_keep = [];
        $total_qty = 0;

        for ($i = 0; $i < count($primary_ids); $i++) {
            $primary_id = $primary_ids[$i];
            $lokasi_id = mysqli_real_escape_string($conn, $lokasi_ids[$i]);
            $qty = intval($primary_qtys[$i]);
            $kondisi_id = intval($kondisi_ids[$i]);
            
            $total_qty += $qty;

            if (empty($lokasi_id)) {
                throw new Exception("Lokasi baris ke-" . ($i+1) . " harus dipilih");
            }
            if ($qty < 1) {
                throw new Exception("Qty baris ke-" . ($i+1) . " minimal 1");
            }
            if (empty($kondisi_id)) {
                throw new Exception("Kondisi baris ke-" . ($i+1) . " harus dipilih");
            }

            if (strpos($primary_id, 'new_') === 0) {
                // INSERT baru (menggunakan karyawan_id)
                $query = "
                    INSERT INTO tbl_primary (
                        assets_id, primary_qty, kondisi_id, lokasi_id, 
                        primary_image, karyawan_id, approve_date, timestamp
                    ) VALUES (
                        $assets_id, $qty, $kondisi_id, '$lokasi_id',
                        " . ($nama_file ? "'$nama_file'" : "NULL") . ",
                        $karyawan_id,
                        NOW(),
                        NOW()
                    )
                ";
                
                if (!mysqli_query($conn, $query)) {
                    throw new Exception("Gagal insert lokasi baru: " . mysqli_error($conn));
                }
                
                $ids_to_keep[] = mysqli_insert_id($conn);
            } else {
                // UPDATE existing
                $primary_id_int = intval($primary_id);
                $ids_to_keep[] = $primary_id_int;
                
                $query = "
                    UPDATE tbl_primary SET
                        primary_qty = $qty,
                        kondisi_id = $kondisi_id,
                        lokasi_id = '$lokasi_id',
                        karyawan_id = $karyawan_id,
                        approve_date = NOW(),
                        timestamp = NOW()
                ";
                
                if ($nama_file) {
                    $query .= ", primary_image = '$nama_file'";
                }
                
                $query .= " WHERE primary_id = $primary_id_int AND assets_id = $assets_id";
                
                if (!mysqli_query($conn, $query)) {
                    throw new Exception("Gagal update lokasi: " . mysqli_error($conn));
                }
            }
        }

        // 3. HAPUS data yang tidak ada dalam list
        if (!empty($ids_to_keep)) {
            $ids_string = implode(',', $ids_to_keep);
            $delete_query = "
                DELETE FROM tbl_primary 
                WHERE assets_id = $assets_id 
                AND primary_id NOT IN ($ids_string)
            ";
        } else {
            $delete_query = "DELETE FROM tbl_primary WHERE assets_id = $assets_id";
        }
        
        if (!mysqli_query($conn, $delete_query)) {
            throw new Exception("Gagal hapus data tidak terpakai: " . mysqli_error($conn));
        }

        // 4. UPDATE total qty di tbl_assets
        $updateQty = "UPDATE tbl_assets SET assets_qty = $total_qty WHERE assets_id = $assets_id";
        if (!mysqli_query($conn, $updateQty)) {
            throw new Exception("Gagal update total qty: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => "Assets berhasil diapprove dengan kode: $assets_kode<br>Total $total_qty pcs di " . count($ids_to_keep) . " lokasi"
        ];

        header("Location: index2.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        
        // Hapus gambar yang baru diupload jika error
        if ($nama_file && file_exists($folder . $nama_file)) {
            unlink($folder . $nama_file);
        }
        
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . $e->getMessage()
        ];
        header("Location: edit2.php?id=$assets_id");
        exit;
    }
}

/* ======================
   HAPUS ASSETS (TOLAK)
====================== */
if (isset($_GET['hapus'])) {

    $assets_id = intval($_GET['id']);

    // Hapus gambar
    $q = mysqli_query($conn, "SELECT primary_image FROM tbl_primary WHERE assets_id = $assets_id");
    $folder = "../master/img/assets/";
    while ($data = mysqli_fetch_assoc($q)) {
        if (!empty($data['primary_image']) && file_exists($folder . $data['primary_image'])) {
            unlink($folder . $data['primary_image']);
        }
    }

    // Hapus primary terlebih dahulu
    mysqli_query($conn, "DELETE FROM tbl_primary WHERE assets_id = $assets_id");
    
    // Hapus assets
    $delete = mysqli_query($conn, "DELETE FROM tbl_assets WHERE assets_id = $assets_id");

    if ($delete) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => 'Data assets berhasil ditolak dan dihapus'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menghapus data: ' . mysqli_error($conn)
        ];
    }

    header("Location: index2.php");
    exit;
}

header("Location: index2.php");
exit;
?>