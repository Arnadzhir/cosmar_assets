<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

// =========================
// HELPER FUNCTIONS
// =========================

// Fungsi untuk menormalkan teks (hapus tanda baca & spasi ganda)
function normalize_string($text) {
    $text = strtoupper(trim($text));
    // Hapus tanda baca umum agar "PT. EPSON" dan "PT EPSON" dianggap sama
    $text = str_replace(['.', ',', '-'], ' ', $text);
    // Ubah spasi ganda/lebih menjadi satu spasi saja
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Fungsi Validasi Utama (Deteksi Identik & Typo)
function cek_duplikat_typo($conn, $input_name, $exclude_id = null) {
    $input_norm = normalize_string($input_name);
    
    $query = "SELECT supplier_id, supplier_name FROM tbl_supplier";
    if ($exclude_id !== null) {
        $exclude_id = mysqli_real_escape_string($conn, $exclude_id);
        $query .= " WHERE supplier_id != '$exclude_id'";
    }
    
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_norm = normalize_string($row['supplier_name']);
        
        // 1. Cek Exact Match (Sama Persis setelah dinormalkan dari spasi dobel/tanda baca)
        if ($existing_norm === $input_norm) {
            return [
                'status' => true, 
                'msg'  => "Ditolak! Nama supplier '$input_name' identik dengan '{$row['supplier_name']}' yang sudah ada."
            ];
        }
        
        // 2. Cek Typo (Dijalankan untuk kata yang panjangnya lebih dari 3 karakter)
        if (strlen($input_norm) > 3 && strlen($existing_norm) > 3) {
            similar_text($existing_norm, $input_norm, $percent);
            $dist = levenshtein($existing_norm, $input_norm);
            
            // Aturan Typo: 
            // - Levenshtein = 1 (hanya beda 1 sisipan huruf, misal: EPSON vs EPSOON)
            // - ATAU Kemiripan teks (similar_text) di atas 90%
            if ($dist <= 1 || $percent >= 90) {
                return [
                    'status' => true, 
                    'msg'  => "Ditolak! Nama '$input_name' terlalu mirip dengan '{$row['supplier_name']}'. Kemungkinan besar ini adalah typo."
                ];
            }
        }
    }
    
    return ['status' => false]; // Aman
}

/* ==================
   TAMBAH DATA
================== */
if (isset($_POST['tambah'])) {

    $supplier_id     = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $supplier_name   = strtoupper(trim($_POST['supplier_name'])); // Tetap simpan format asli user, hanya jadikan uppercase
    $supplier_mail   = mysqli_real_escape_string($conn, $_POST['supplier_mail']);
    $supplier_no     = mysqli_real_escape_string($conn, $_POST['supplier_no']);

    // JALANKAN VALIDASI DUPLIKAT & TYPO
    $validasi = cek_duplikat_typo($conn, $supplier_name);
    
    if ($validasi['status'] === true) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => $validasi['msg'] // Memunculkan pesan spesifik
        ];
        header("Location: tambah.php");
        exit;
    }

    $supplier_name_safe = mysqli_real_escape_string($conn, $supplier_name);
    mysqli_query($conn,
        "INSERT INTO tbl_supplier (supplier_id, supplier_name, supplier_mail, supplier_no)
         VALUES ('$supplier_id', '$supplier_name_safe', '$supplier_mail', '$supplier_no')"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data supplier berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    $supplier_id     = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $supplier_name   = strtoupper(trim($_POST['supplier_name']));
    $supplier_mail   = mysqli_real_escape_string($conn, $_POST['supplier_mail']);
    $supplier_no     = mysqli_real_escape_string($conn, $_POST['supplier_no']);

    // JALANKAN VALIDASI DUPLIKAT & TYPO (KECUALI DIRINYA SENDIRI)
    $validasi = cek_duplikat_typo($conn, $supplier_name, $supplier_id);
    
    if ($validasi['status'] === true) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => $validasi['msg']
        ];
        header("Location: edit.php?id=$supplier_id");
        exit;
    }

    $supplier_name_safe = mysqli_real_escape_string($conn, $supplier_name);
    mysqli_query($conn,
        "UPDATE tbl_supplier SET
            supplier_name  = '$supplier_name_safe',
            supplier_mail  = '$supplier_mail',
            supplier_no    = '$supplier_no'
         WHERE supplier_id = '$supplier_id'"
    );

    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data supplier berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM tbl_supplier WHERE supplier_id='$id'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data supplier berhasil dihapus' // Diperbaiki dari 'Data assets' jadi 'Data supplier'
    ];

    header("Location: index.php");
    exit;
}
?>