<?php
include '../config/koneksi.php';

// Menggunakan dep_code (bukan dep_id) untuk konsistensi dengan file lain
if (isset($_POST['dep_code']) && !empty($_POST['dep_code'])) {
    $dep_code = mysqli_real_escape_string($conn, $_POST['dep_code']);
    
    $query = mysqli_query($conn, "
        SELECT k.karyawan_id, k.karyawan_name 
        FROM tbl_karyawan k
        INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
        WHERE d.dep_code = '$dep_code'
        ORDER BY k.karyawan_name ASC
    ");
    
    $options = '<option value="">-- Pilih Penanggung Jawab --</option>';
    
    if ($query && mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            $options .= "<option value='{$row['karyawan_id']}'>{$row['karyawan_name']}</option>";
        }
    } else {
        $options .= '<option value="" disabled>Tidak ada karyawan di departemen ini</option>';
    }
    
    echo $options;
} else {
    echo '<option value="">-- Pilih Departemen Dulu --</option>';
}
?>