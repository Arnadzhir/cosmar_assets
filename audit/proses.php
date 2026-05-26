<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* ======================
   TAMBAH DATA AUDIT (MULTIPLE INSERT)
====================== */
if (isset($_POST['tambah'])) {

    // Ambil data utama
    $assets_id = isset($_POST['assets_id']) ? (int)$_POST['assets_id'] : 0;
    $karyawan_id = isset($_POST['karyawan_id']) ? (int)$_POST['karyawan_id'] : 0; // PERUBAHAN: user_id -> karyawan_id
    $auditor   = isset($_POST['auditor']) ? mysqli_real_escape_string($conn, $_POST['auditor']) : '';
    $audit_note = isset($_POST['audit_note']) ? mysqli_real_escape_string($conn, $_POST['audit_note']) : '';

    // Validasi data utama
    if ($assets_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Kode asset harus dipilih!'];
        header("Location: tambah.php");
        exit;
    }

    if ($karyawan_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Penanggung jawab harus dipilih!'];
        header("Location: tambah.php");
        exit;
    }

    if (empty($auditor)) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Auditor harus dipilih!'];
        header("Location: tambah.php");
        exit;
    }

    // Ambil data multiple insert
    $lokasi_ids   = isset($_POST['lokasi_id']) ? $_POST['lokasi_id'] : [];
    $kondisi_ids  = isset($_POST['kondisi_id']) ? $_POST['kondisi_id'] : [];
    $audit_qty    = isset($_POST['audit_qty']) ? $_POST['audit_qty'] : [];

    // Validasi minimal 1 detail item
    if (count($lokasi_ids) == 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Minimal 1 detail audit harus diisi!'];
        header("Location: tambah.php");
        exit;
    }

    // Folder upload
    $upload_dir = "../master/img/audit/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    // Hitung total qty audit yang baru diinput
    $new_audit_qty = 0;
    foreach ($audit_qty as $qty) {
        $new_audit_qty += (int)$qty;
    }

    // Ambil total qty audit yang sudah ada sebelumnya untuk asset ini
    $qExisting = mysqli_query($conn, "SELECT SUM(audit_qty) as total FROM tbl_audit WHERE assets_id = '$assets_id'");
    $existing = mysqli_fetch_assoc($qExisting);
    $existing_qty = (int)($existing['total'] ?? 0);

    // Hitung total qty audit setelah insert
    $total_audit_qty = $existing_qty + $new_audit_qty;

    // Ambil qty master dari tbl_assets
    $qMaster = mysqli_query($conn, "SELECT assets_qty FROM tbl_assets WHERE assets_id = '$assets_id'");
    $master = mysqli_fetch_assoc($qMaster);
    $master_qty = (int)($master['assets_qty'] ?? 0);

    // Tentukan audit_status berdasarkan total_audit_qty (akumulasi)
    if ($total_audit_qty == 0) {
        $audit_status = 0;  // Belum
    } elseif ($total_audit_qty < $master_qty) {
        $audit_status = 1;  // Kurang
    } elseif ($total_audit_qty > $master_qty) {
        $audit_status = 2;  // Lebih
    } else {
        $audit_status = 3;  // Selesai
    }

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        // Loop untuk setiap detail item
        for ($i = 0; $i < count($lokasi_ids); $i++) {
            $lokasi_id  = isset($lokasi_ids[$i]) ? mysqli_real_escape_string($conn, $lokasi_ids[$i]) : '';
            $kondisi_id = isset($kondisi_ids[$i]) ? (int)$kondisi_ids[$i] : 0;
            $qty        = isset($audit_qty[$i]) ? (int)$audit_qty[$i] : 0;

            // Validasi per item
            if (empty($lokasi_id)) {
                $errors[] = "Item " . ($i + 1) . ": Lokasi harus dipilih!";
                $error_count++;
                continue;
            }

            if ($kondisi_id <= 0) {
                $errors[] = "Item " . ($i + 1) . ": Kondisi harus dipilih!";
                $error_count++;
                continue;
            }

            if ($qty <= 0) {
                $errors[] = "Item " . ($i + 1) . ": Qty minimal 1!";
                $error_count++;
                continue;
            }

            // Proses upload gambar
            $image_name = '';
            if (isset($_FILES['audit_image']['tmp_name'][$i]) && !empty($_FILES['audit_image']['tmp_name'][$i])) {
                $file_tmp = $_FILES['audit_image']['tmp_name'][$i];
                $file_name = $_FILES['audit_image']['name'][$i];
                $file_size = $_FILES['audit_image']['size'][$i];
                
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                
                if (in_array($ext, $allowed)) {
                    if ($file_size <= 2097152) {
                        $image_name = 'AUDIT_' . time() . '_' . uniqid() . '_' . $i . '.' . $ext;
                        move_uploaded_file($file_tmp, $upload_dir . $image_name);
                    } else {
                        $errors[] = "Item " . ($i + 1) . ": Ukuran gambar maksimal 2MB!";
                        $error_count++;
                        continue;
                    }
                } else {
                    $errors[] = "Item " . ($i + 1) . ": Format gambar harus JPG/PNG!";
                    $error_count++;
                    continue;
                }
            } else {
                $errors[] = "Item " . ($i + 1) . ": Gambar wajib diupload!";
                $error_count++;
                continue;
            }

            // Insert ke tbl_audit (PERUBAHAN: user_id -> karyawan_id)
            $query = "INSERT INTO tbl_audit (
                assets_id, 
                user_id, 
                auditor, 
                audit_note,
                lokasi_id, 
                kondisi_id, 
                audit_qty, 
                audit_image,
                audit_status,
                status,
                timestamp
            ) VALUES (
                '$assets_id',
                '$karyawan_id',
                '$auditor',
                '$audit_note',
                '$lokasi_id',
                '$kondisi_id',
                '$qty',
                '$image_name',
                '$audit_status',
                1,
                NOW()
            )";

            if (mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $errors[] = "Item " . ($i + 1) . ": Gagal menyimpan data! " . mysqli_error($conn);
                $error_count++;
            }
        }

        // **UPDATE audit_status untuk SEMUA record asset ini (sinkronisasi massal)**
        $update_status = "UPDATE tbl_audit SET audit_status = '$audit_status' WHERE assets_id = '$assets_id'";
        mysqli_query($conn, $update_status);

        // Commit transaksi
        mysqli_commit($conn);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menyimpan data: ' . $e->getMessage()
        ];
        header("Location: tambah.php");
        exit;
    }

    // Redirect dengan pesan
    if ($success_count > 0) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => "Berhasil menyimpan $success_count data audit. Total audit untuk asset ini: $total_audit_qty dari $master_qty unit."
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => "Gagal menyimpan data audit!<br>" . implode("<br>", $errors)
        ];
    }

    header("Location: index.php");
    exit;
}

/* ======================
   EDIT DATA AUDIT
====================== */
if (isset($_POST['edit'])) {

    $audit_id    = isset($_POST['audit_id']) ? (int)$_POST['audit_id'] : 0;
    $assets_id   = isset($_POST['assets_id']) ? (int)$_POST['assets_id'] : 0;
    $karyawan_id = isset($_POST['karyawan_id']) ? (int)$_POST['karyawan_id'] : 0; // PERUBAHAN: user_id -> karyawan_id
    $auditor     = isset($_POST['auditor']) ? mysqli_real_escape_string($conn, $_POST['auditor']) : '';
    $audit_note  = isset($_POST['audit_note']) ? mysqli_real_escape_string($conn, $_POST['audit_note']) : '';
    $lokasi_id   = isset($_POST['lokasi_id']) ? mysqli_real_escape_string($conn, $_POST['lokasi_id']) : '';
    $kondisi_id  = isset($_POST['kondisi_id']) ? (int)$_POST['kondisi_id'] : 0;
    $audit_qty   = isset($_POST['audit_qty']) ? (int)$_POST['audit_qty'] : 0;
    $status_audit = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    // Validasi
    if (!in_array($status_audit, [1, 2])) $status_audit = 1;

    // Cek apakah data audit sudah selesai (status=2)
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM tbl_audit WHERE audit_id = '$audit_id'"));
    if ($check['status'] == 2) {
        $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Data audit sudah selesai (Done), tidak dapat diubah!'];
        header("Location: index.php");
        exit;
    }

    // Validasi
    if ($audit_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID audit tidak valid!'];
        header("Location: index.php");
        exit;
    }

    if ($assets_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Kode asset harus dipilih!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    if ($karyawan_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Penanggung jawab harus dipilih!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    if (empty($auditor)) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Auditor harus dipilih!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    if (empty($lokasi_id)) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Lokasi harus dipilih!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    if ($kondisi_id <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Kondisi harus dipilih!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    if ($audit_qty <= 0) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Qty minimal 1!'];
        header("Location: edit.php?id=$audit_id");
        exit;
    }

    // ========== HITUNG ULANG TOTAL QTY AUDIT ==========
    // 1. Ambil data audit lama (qty sebelum diubah)
    $qOld = mysqli_query($conn, "SELECT audit_qty FROM tbl_audit WHERE audit_id = '$audit_id'");
    $oldData = mysqli_fetch_assoc($qOld);
    $old_audit_qty = $oldData['audit_qty'] ?? 0;

    // 2. Hitung total qty audit SEMUA record untuk asset ini (KECUALI record yang sedang diedit)
    $qTotal = mysqli_query($conn, "
        SELECT SUM(audit_qty) as total 
        FROM tbl_audit 
        WHERE assets_id = '$assets_id' 
        AND audit_id != '$audit_id'
    ");
    $totalData = mysqli_fetch_assoc($qTotal);
    $existing_total = (int)($totalData['total'] ?? 0);

    // 3. Total qty setelah update = total existing + qty baru
    $new_total_qty = $existing_total + $audit_qty;

    // 4. Ambil qty master dari tbl_assets
    $qMaster = mysqli_query($conn, "SELECT assets_qty FROM tbl_assets WHERE assets_id = '$assets_id'");
    $master = mysqli_fetch_assoc($qMaster);
    $master_qty = (int)($master['assets_qty'] ?? 0);

    // 5. Tentukan audit_status berdasarkan total akumulasi
    if ($new_total_qty == 0) {
        $audit_status = 0;  // Belum
    } elseif ($new_total_qty < $master_qty) {
        $audit_status = 1;  // Kurang
    } elseif ($new_total_qty > $master_qty) {
        $audit_status = 2;  // Lebih
    } else {
        $audit_status = 3;  // Selesai
    }

    // Proses upload gambar baru (jika ada)
    $image_name = '';
    $update_image = '';

    // Ambil gambar lama
    $qImage = mysqli_query($conn, "SELECT audit_image FROM tbl_audit WHERE audit_id = '$audit_id'");
    $oldImageData = mysqli_fetch_assoc($qImage);
    $oldImage = $oldImageData['audit_image'] ?? '';

    if (isset($_FILES['audit_image']) && $_FILES['audit_image']['error'] === 0 && !empty($_FILES['audit_image']['name'])) {
        $upload_dir = "../master/img/audit/";
        
        $ext = strtolower(pathinfo($_FILES['audit_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed)) {
            if ($_FILES['audit_image']['size'] <= 2097152) {
                $image_name = 'AUDIT_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['audit_image']['tmp_name'], $upload_dir . $image_name)) {
                    $update_image = ", audit_image = '$image_name'";
                    // Hapus gambar lama
                    if (!empty($oldImage) && file_exists($upload_dir . $oldImage)) {
                        unlink($upload_dir . $oldImage);
                    }
                } else {
                    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal mengupload gambar!'];
                    header("Location: edit.php?id=$audit_id");
                    exit;
                }
            } else {
                $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Ukuran gambar maksimal 2MB!'];
                header("Location: edit.php?id=$audit_id");
                exit;
            }
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Format gambar harus JPG/PNG!'];
            header("Location: edit.php?id=$audit_id");
            exit;
        }
    }

    // Mulai transaksi
    mysqli_begin_transaction($conn);

    try {
        $query = "UPDATE tbl_audit SET 
                    assets_id = '$assets_id',
                    user_id = '$karyawan_id',
                    auditor = '$auditor',
                    audit_note = '$audit_note',
                    lokasi_id = '$lokasi_id',
                    kondisi_id = '$kondisi_id',
                    audit_qty = '$audit_qty',
                    audit_status = '$audit_status',
                    status = '$status_audit'
                    $update_image
                  WHERE audit_id = '$audit_id'";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Gagal update audit: " . mysqli_error($conn));
        }

        // Update audit_status untuk semua record asset ini
        $update_all = "UPDATE tbl_audit SET audit_status = '$audit_status' WHERE assets_id = '$assets_id'";
        mysqli_query($conn, $update_all);

        mysqli_commit($conn);

        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => "Data audit berhasil diupdate! Total audit untuk asset ini: $new_total_qty dari $master_qty unit."
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Gagal mengupdate data: ' . $e->getMessage()];
    }

    header("Location: index.php");
    exit;
}


/* ======================
   HAPUS DATA AUDIT
====================== */
if (isset($_GET['hapus']) && isset($_GET['id'])) {
    
    $audit_id = (int)$_GET['id'];
    
    // Cek apakah user memiliki akses (hanya level 1 yang boleh hapus)
    if ($_SESSION['user_level'] != 1) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Anda tidak memiliki akses untuk menghapus data!'];
        header("Location: index.php");
        exit;
    }
    
    // Ambil data audit yang akan dihapus
    $qData = mysqli_query($conn, "SELECT assets_id, audit_qty, audit_image FROM tbl_audit WHERE audit_id = '$audit_id'");
    $data = mysqli_fetch_assoc($qData);
    
    if (!$data) {
        $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data audit tidak ditemukan!'];
        header("Location: index.php");
        exit;
    }
    
    $assets_id = $data['assets_id'];
    $deleted_qty = $data['audit_qty'];
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Hapus data audit
        $query = "DELETE FROM tbl_audit WHERE audit_id = '$audit_id'";
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Gagal menghapus data: " . mysqli_error($conn));
        }
        
        // 2. Hapus file gambar jika ada
        if (!empty($data['audit_image'])) {
            $upload_dir = "../master/img/audit/";
            if (file_exists($upload_dir . $data['audit_image'])) {
                unlink($upload_dir . $data['audit_image']);
            }
        }
        
        // 3. Hitung ulang total qty audit untuk asset ini (setelah hapus)
        $qTotal = mysqli_query($conn, "
            SELECT SUM(audit_qty) as total 
            FROM tbl_audit 
            WHERE assets_id = '$assets_id'
        ");
        $totalData = mysqli_fetch_assoc($qTotal);
        $new_total_qty = (int)($totalData['total'] ?? 0);
        
        // 4. Ambil qty master dari tbl_assets
        $qMaster = mysqli_query($conn, "SELECT assets_qty FROM tbl_assets WHERE assets_id = '$assets_id'");
        $master = mysqli_fetch_assoc($qMaster);
        $master_qty = (int)($master['assets_qty'] ?? 0);
        
        // 5. Tentukan audit_status baru
        if ($new_total_qty == 0) {
            $new_audit_status = 0;  // Belum
        } elseif ($new_total_qty < $master_qty) {
            $new_audit_status = 1;  // Kurang
        } elseif ($new_total_qty > $master_qty) {
            $new_audit_status = 2;  // Lebih
        } else {
            $new_audit_status = 3;  // Selesai
        }
        
        // 6. UPDATE audit_status untuk SEMUA record asset ini
        if ($new_total_qty > 0) {
            $update_status = "UPDATE tbl_audit SET audit_status = '$new_audit_status' WHERE assets_id = '$assets_id'";
            mysqli_query($conn, $update_status);
        }
        
        mysqli_commit($conn);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'msg'  => "Data audit berhasil dihapus! Total audit untuk asset ini sekarang: $new_total_qty dari $master_qty unit."
        ];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => 'Gagal menghapus data: ' . $e->getMessage()
        ];
    }
    
    header("Location: index.php");
    exit;
}

// Jika tidak ada aksi yang valid
header("Location: index.php");
exit;
?>