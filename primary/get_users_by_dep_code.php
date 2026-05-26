<?php
include '../config/koneksi.php';

// Nonaktifkan error reporting untuk output bersih
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: text/html; charset=utf-8');

if (isset($_POST['dep_code']) && !empty($_POST['dep_code'])) {
    
    $dep_code = mysqli_real_escape_string($conn, $_POST['dep_code']);
    
    // Query untuk mengambil user berdasarkan dep_code
    $query = mysqli_query($conn, "
        SELECT DISTINCT k.karyawan_id, k.karyawan_name 
        FROM tbl_karyawan k
        INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
        WHERE d.dep_code = '$dep_code'
        ORDER BY k.karyawan_name ASC
    ");

    $options = '<option value="">-- Pilih User --</option>';
    
    if ($query && mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            $options .= "<option value='{$row['karyawan_id']}'>{$row['karyawan_name']}</option>";
        }
    } else {
        $options .= '<option value="" disabled>Tidak ada user di departemen ini</option>';
    }
    
    echo $options;
    
} else {
    echo '<option value="">-- Pilih Departemen Dulu --</option>';
}
?>