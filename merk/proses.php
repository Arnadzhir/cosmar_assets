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
    // Hapus tanda baca umum
    $text = str_replace(['.', ',', '-'], ' ', $text);
    // Ubah spasi ganda/lebih menjadi satu spasi saja
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// Fungsi Validasi Utama (Deteksi Identik & Typo)
function cek_duplikat_typo($conn, $input_name, $exclude_id = null) {
    $input_norm = normalize_string($input_name);
    
    $query = "SELECT merk_id, merk_name FROM tbl_merk";
    if ($exclude_id !== null) {
        $exclude_id = mysqli_real_escape_string($conn, $exclude_id);
        $query .= " WHERE merk_id != '$exclude_id'";
    }
    
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_norm = normalize_string($row['merk_name']);
        
        // 1. Cek Exact Match (Sama Persis setelah dinormalkan)
        if ($existing_norm === $input_norm) {
            return [
                'status' => true, 
                'msg'  => "Ditolak! Nama merk '$input_name' identik dengan '{$row['merk_name']}' yang sudah ada."
            ];
        }
        
        // 2. Cek Typo (Dijalankan untuk kata yang panjangnya lebih dari 3 karakter)
        if (strlen($input_norm) > 3 && strlen($existing_norm) > 3) {
            similar_text($existing_norm, $input_norm, $percent);
            $dist = levenshtein($existing_norm, $input_norm);
            
            // Aturan Typo: Levenshtein = 1 atau kemiripan di atas 90%
            if ($dist <= 1 || $percent >= 90) {
                return [
                    'status' => true, 
                    'msg'  => "Ditolak! Nama '$input_name' terlalu mirip dengan '{$row['merk_name']}'. Kemungkinan besar ini adalah typo."
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

    $merk_id   = mysqli_real_escape_string($conn, $_POST['merk_id']);
    $merk_name = strtoupper(trim($_POST['merk_name']));

    // JALANKAN VALIDASI DUPLIKAT & TYPO
    $validasi = cek_duplikat_typo($conn, $merk_name);
    
    if ($validasi['status'] === true) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => $validasi['msg']
        ];
        header("Location: tambah.php");
        exit;
    }

    $merk_name_safe = mysqli_real_escape_string($conn, $merk_name);
    mysqli_query($conn,
        "INSERT INTO tbl_merk (merk_id, merk_name)
         VALUES ('$merk_id', '$merk_name_safe')"
    );

    $_SESSION['alert'] = [
        'type' => 'success',
        'msg'  => 'Data merk berhasil ditambahkan'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   EDIT DATA
================== */
if (isset($_POST['edit'])) {

    $merk_id   = mysqli_real_escape_string($conn, $_POST['merk_id']);
    $merk_name = strtoupper(trim($_POST['merk_name']));

    // JALANKAN VALIDASI DUPLIKAT & TYPO (KECUALI DIRINYA SENDIRI)
    $validasi = cek_duplikat_typo($conn, $merk_name, $merk_id);
    
    if ($validasi['status'] === true) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'msg'  => $validasi['msg']
        ];
        header("Location: edit.php?id=$merk_id");
        exit;
    }

    $merk_name_safe = mysqli_real_escape_string($conn, $merk_name);
    mysqli_query($conn,
        "UPDATE tbl_merk SET
            merk_name = '$merk_name_safe'
         WHERE merk_id = '$merk_id'"
    );

    $_SESSION['alert'] = [
        'type' => 'info',
        'msg'  => 'Data merk berhasil diperbarui'
    ];

    header("Location: index.php");
    exit;
}

/* ==================
   HAPUS DATA
================== */
if (isset($_GET['hapus'])) {

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM tbl_merk WHERE merk_id='$id'");

    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data merk berhasil dihapus'
    ];

    header("Location: index.php");
    exit;
}
?>