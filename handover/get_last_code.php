<?php
include '../config/koneksi.php';

if (isset($_POST['kategori_kode']) && isset($_POST['tahun']) && isset($_POST['bulan'])) {
    
    $kategori_kode = mysqli_real_escape_string($conn, $_POST['kategori_kode']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
    $bulan = mysqli_real_escape_string($conn, $_POST['bulan']);
    
    $prefix = $kategori_kode . '-' . $tahun . $bulan . '-';
    
    $query = mysqli_query($conn, "
        SELECT assets_kode FROM tbl_assets 
        WHERE assets_kode LIKE '$prefix%'
        ORDER BY assets_kode DESC LIMIT 1
    ");
    
    if (mysqli_num_rows($query) > 0) {
        $last = mysqli_fetch_assoc($query);
        $last_num = intval(substr($last['assets_kode'], -3));
        echo $last_num;
    } else {
        echo 0;
    }
}
?>