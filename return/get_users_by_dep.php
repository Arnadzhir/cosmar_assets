<?php
include '../config/koneksi.php';

if(isset($_POST['dep_code'])){

    $dep_code = mysqli_real_escape_string($conn, $_POST['dep_code']);

    $q = mysqli_query($conn,"
        SELECT k.karyawan_id, k.karyawan_name 
        FROM tbl_karyawan k
        INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
        WHERE d.dep_code = '$dep_code'
        ORDER BY k.karyawan_name ASC
    ");

    echo '<option value="">-- Pilih Penanggung Jawab --</option>';

    if(mysqli_num_rows($q) > 0) {
        while($d = mysqli_fetch_assoc($q)){
            echo "<option value='{$d['karyawan_id']}'>{$d['karyawan_name']}</option>";
        }
    } else {
        echo '<option value="" disabled>Tidak ada karyawan di departemen ini</option>';
    }
    
} else {
    echo '<option value="">-- Pilih Departemen Dulu --</option>';
}
?>