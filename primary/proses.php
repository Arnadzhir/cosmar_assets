<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

// Cek role (hanya admin dan operator)
if (!in_array($_SESSION['user_level'], [1,2,3])) {
    header("Location: ../dashboard.php");
    exit;
}

/* ======================
   UPDATE DATA PRIMARY
====================== */
if (isset($_POST['update'])) {

    $primary_id = intval($_POST['primary_id']);
    $assets_id  = intval($_POST['assets_id']);
    
    // Data dari form - PERUBAHAN: user_id menjadi karyawan_id
    $karyawan_id = !empty($_POST['karyawan_id']) ? intval($_POST['karyawan_id']) : 'NULL';
    $lokasi_id   = mysqli_real_escape_string($conn, $_POST['lokasi_id']);
    $kondisi_id  = intval($_POST['kondisi_id']);
    $primary_qty = intval($_POST['primary_qty']);
    
    // Validasi data
    $errors = [];
    
    if ($primary_qty < 1) {
        $errors[] = "Quantity minimal 1";
    }
    if (empty($lokasi_id)) {
        $errors[] = "Lokasi harus dipilih";
    }
    if (empty($kondisi_id)) {
        $errors[] = "Kondisi harus dipilih";
    }
    
    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
        header("Location: edit.php?id=$primary_id");
        exit;
    }
    
    // ======================
    // UPLOAD GAMBAR (jika ada)
    // ======================
    
    $nama_file = null;
    $gambar_lama = null;
    $folder = "../master/img/assets/";
    
    if (!empty($_FILES['primary_image']['name'])) {
        
        $qGambar = mysqli_query($conn, "SELECT primary_image FROM tbl_primary WHERE primary_id = $primary_id");
        $gambarData = mysqli_fetch_assoc($qGambar);
        $gambar_lama = $gambarData['primary_image'] ?? null;
        
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
        
        if (!empty($errors)) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => implode('<br>', $errors)
            ];
            header("Location: edit.php?id=$primary_id");
            exit;
        }
        
        $ext = pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION);
        $nama_file = 'ASSET_' . time() . '_' . $primary_id . '.' . $ext;
        
        if (move_uploaded_file($file_tmp, $folder . $nama_file)) {
            if ($gambar_lama && file_exists($folder . $gambar_lama)) {
                unlink($folder . $gambar_lama);
            }
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Gagal menyimpan gambar'
            ];
            header("Location: edit.php?id=$primary_id");
            exit;
        }
    }
    
    // ======================
    // AMBIL DATA PRIMARY LAMA
    // ======================
    
    $qOld = mysqli_query($conn, "
        SELECT primary_qty, karyawan_id, lokasi_id, kondisi_id, primary_image 
        FROM tbl_primary 
        WHERE primary_id = $primary_id
    ");
    
    if (!$qOld) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . mysqli_error($conn)
        ];
        header("Location: edit.php?id=$primary_id");
        exit;
    }
    
    $oldData = mysqli_fetch_assoc($qOld);
    $old_qty = intval($oldData['primary_qty']);
    $old_karyawan_id = $oldData['karyawan_id'];
    
    // ======================
    // MULAI TRANSAKSI
    // ======================
    mysqli_begin_transaction($conn);
    
    try {
        
        // KASUS 1: Mengurangi qty dari karyawan -> pindahkan ke pool (karyawan_id = NULL)
        if ($primary_qty < $old_qty && $karyawan_id != 'NULL') {
            $selisih = $old_qty - $primary_qty;
            
            // 1. Update primary dengan qty baru
            if ($nama_file) {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        primary_image = '$nama_file',
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            } else {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            }
            
            if (!mysqli_query($conn, $queryPrimary)) {
                throw new Exception("Gagal update primary: " . mysqli_error($conn));
            }
            
            // 2. Cek apakah sudah ada pool dengan kombinasi yang sama
            $qPool = mysqli_query($conn, "
                SELECT primary_id, primary_qty 
                FROM tbl_primary 
                WHERE assets_id = $assets_id 
                AND lokasi_id = '$lokasi_id'
                AND kondisi_id = $kondisi_id
                AND (karyawan_id IS NULL OR karyawan_id = 0)
            ");
            
            if (mysqli_num_rows($qPool) > 0) {
                // Sudah ada pool, tambahkan qty ke pool yang ada
                $pool = mysqli_fetch_assoc($qPool);
                $pool_id = $pool['primary_id'];
                $pool_qty_baru = $pool['primary_qty'] + $selisih;
                
                $updatePool = "
                    UPDATE tbl_primary SET
                        primary_qty = $pool_qty_baru,
                        timestamp = NOW()
                    WHERE primary_id = $pool_id
                ";
                
                if (!mysqli_query($conn, $updatePool)) {
                    throw new Exception("Gagal update pool: " . mysqli_error($conn));
                }
                
            } else {
                // Belum ada pool, buat baru
                $gambar_pool = $nama_file ?: ($oldData['primary_image'] ?? '');
                if (empty($gambar_pool)) {
                    $gambar_pool = "''";
                } else {
                    $gambar_pool = "'$gambar_pool'";
                }
                
                $insertPool = "
                    INSERT INTO tbl_primary (
                        assets_id,
                        primary_qty,
                        primary_image,
                        kondisi_id,
                        lokasi_id,
                        karyawan_id,
                        timestamp
                    ) VALUES (
                        $assets_id,
                        $selisih,
                        $gambar_pool,
                        $kondisi_id,
                        '$lokasi_id',
                        NULL,
                        NOW()
                    )
                ";
                
                if (!mysqli_query($conn, $insertPool)) {
                    throw new Exception("Gagal insert pool: " . mysqli_error($conn));
                }
            }
            
            $total_qty_baru = $old_qty; // Total qty assets tetap sama
            
        } 
        // KASUS 2: Menambah qty ke karyawan dari pool
        elseif ($primary_qty > $old_qty && $karyawan_id != 'NULL' && ($old_karyawan_id == NULL || $old_karyawan_id == 0)) {
            $tambahan = $primary_qty - $old_qty;
            
            // Cek ketersediaan di pool
            $qPool = mysqli_query($conn, "
                SELECT primary_id, primary_qty 
                FROM tbl_primary 
                WHERE assets_id = $assets_id 
                AND lokasi_id = '$lokasi_id'
                AND kondisi_id = $kondisi_id
                AND (karyawan_id IS NULL OR karyawan_id = 0)
            ");
            
            if (mysqli_num_rows($qPool) == 0) {
                throw new Exception("Tidak ada assets tanpa pemilik yang tersedia");
            }
            
            $pool = mysqli_fetch_assoc($qPool);
            $pool_id = $pool['primary_id'];
            $pool_qty = $pool['primary_qty'];
            
            if ($pool_qty < $tambahan) {
                throw new Exception("Assets tanpa pemilik tidak mencukupi. Tersedia: $pool_qty");
            }
            
            // 1. Update primary dengan qty baru
            if ($nama_file) {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        primary_image = '$nama_file',
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            } else {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            }
            
            if (!mysqli_query($conn, $queryPrimary)) {
                throw new Exception("Gagal update primary: " . mysqli_error($conn));
            }
            
            // 2. Kurangi atau hapus pool
            $pool_sisa = $pool_qty - $tambahan;
            
            if ($pool_sisa > 0) {
                $updatePool = "
                    UPDATE tbl_primary SET
                        primary_qty = $pool_sisa,
                        timestamp = NOW()
                    WHERE primary_id = $pool_id
                ";
                if (!mysqli_query($conn, $updatePool)) {
                    throw new Exception("Gagal update pool: " . mysqli_error($conn));
                }
            } else {
                $deletePool = "DELETE FROM tbl_primary WHERE primary_id = $pool_id";
                if (!mysqli_query($conn, $deletePool)) {
                    throw new Exception("Gagal hapus pool: " . mysqli_error($conn));
                }
            }
            
            $total_qty_baru = $old_qty; // Total qty assets tetap sama
            
        } 
        // KASUS 3: Update biasa (tidak melibatkan pool)
        else {
            if ($nama_file) {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        primary_image = '$nama_file',
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            } else {
                $queryPrimary = "
                    UPDATE tbl_primary SET
                        karyawan_id = $karyawan_id,
                        lokasi_id = '$lokasi_id',
                        kondisi_id = $kondisi_id,
                        primary_qty = $primary_qty,
                        timestamp = NOW()
                    WHERE primary_id = $primary_id
                ";
            }
            
            if (!mysqli_query($conn, $queryPrimary)) {
                throw new Exception("Gagal update primary: " . mysqli_error($conn));
            }
            
            // Hitung total qty baru
            $qTotal = mysqli_query($conn, "
                SELECT SUM(primary_qty) as total 
                FROM tbl_primary 
                WHERE assets_id = $assets_id 
                AND primary_id != $primary_id
            ");
            
            if (!$qTotal) {
                throw new Exception("Error menghitung total: " . mysqli_error($conn));
            }
            
            $totalData = mysqli_fetch_assoc($qTotal);
            $total_lain = intval($totalData['total']);
            $total_qty_baru = $total_lain + $primary_qty;
        }
        
        // UPDATE tbl_assets
        $queryAssets = "
            UPDATE tbl_assets SET
                assets_qty = $total_qty_baru,
                timestamp = NOW()
            WHERE assets_id = $assets_id
        ";
        
        if (!mysqli_query($conn, $queryAssets)) {
            throw new Exception("Gagal update assets: " . mysqli_error($conn));
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
        header("Location: edit.php?id=$primary_id");
        exit;
    }
}

/* ======================
   HAPUS PRIMARY ITEM
====================== */
if (isset($_GET['hapus'])) {

    $primary_id = intval($_GET['id']);

    // Ambil data untuk proses hapus
    $q = mysqli_query($conn, "
        SELECT p.primary_image, p.assets_id, p.primary_qty
        FROM tbl_primary p
        WHERE p.primary_id = $primary_id
    ");
    
    if (!$q) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Error: ' . mysqli_error($conn)
        ];
        header("Location: index.php");
        exit;
    }
    
    $data = mysqli_fetch_assoc($q);

    if ($data) {
        
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        try {
            
            $assets_id = $data['assets_id'];
            $gambar = $data['primary_image'];
            $folder = "../master/img/assets/";

            // CEK APAKAH GAMBAR DIGUNAKAN OLEH PRIMARY LAIN
            $hapus_gambar = false;
            if (!empty($gambar)) {
                $gambar_escape = mysqli_real_escape_string($conn, $gambar);
                $qCekGambar = mysqli_query($conn, "
                    SELECT COUNT(*) as jumlah 
                    FROM tbl_primary 
                    WHERE primary_image = '$gambar_escape' 
                    AND primary_id != $primary_id
                ");
                $cekGambar = mysqli_fetch_assoc($qCekGambar);
                
                // Jika tidak ada primary lain yang menggunakan gambar ini, hapus file
                if ($cekGambar && $cekGambar['jumlah'] == 0) {
                    $hapus_gambar = true;
                }
            }

            // Hapus tbl_primary
            $deletePrimary = mysqli_query($conn, "DELETE FROM tbl_primary WHERE primary_id = $primary_id");
            if (!$deletePrimary) {
                throw new Exception("Gagal hapus data primary: " . mysqli_error($conn));
            }

            // Update total qty di tbl_assets
            $qTotal = mysqli_query($conn, "
                SELECT SUM(primary_qty) as total 
                FROM tbl_primary 
                WHERE assets_id = $assets_id
            ");
            
            $totalData = mysqli_fetch_assoc($qTotal);
            $total_qty_baru = intval($totalData['total']);
            
            $updateAssets = mysqli_query($conn, "
                UPDATE tbl_assets SET 
                    assets_qty = $total_qty_baru,
                    timestamp = NOW()
                WHERE assets_id = $assets_id
            ");
            
            if (!$updateAssets) {
                throw new Exception("Gagal update assets: " . mysqli_error($conn));
            }

            // Hapus gambar jika tidak digunakan oleh primary lain
            if ($hapus_gambar && file_exists($folder . $gambar)) {
                if (!unlink($folder . $gambar)) {
                    // Log error tapi tidak throw exception karena bukan critical error
                    error_log("Gagal menghapus file gambar: " . $folder . $gambar);
                }
            }
            
            // Commit transaksi
            mysqli_commit($conn);
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'msg'  => 'Data berhasil dihapus'
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['alert'] = [
                'type' => 'danger',
                'msg'  => 'Error: ' . $e->getMessage()
            ];
        }
        
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Data tidak ditemukan'
        ];
    }

    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
?>