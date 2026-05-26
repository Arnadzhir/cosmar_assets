<?php
include '../config/koneksi.php';

if (!empty($_POST['kategori_id'])) {

    $kategori_id = mysqli_real_escape_string($conn, $_POST['kategori_id']);

    $query = "SELECT assets_id, assets_kode, assets_name 
              FROM tbl_assets 
              WHERE kategori_id = '$kategori_id'
              ORDER BY assets_kode ASC";

    $result = mysqli_query($conn, $query);

    echo '<option value="">-- Pilih Asset --</option>';

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="'.$row['assets_id'].'">'
                . htmlspecialchars($row['assets_kode'].' - '.$row['assets_name'])
                . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada asset</option>';
    }

} else {
    echo '<option value="">-- Pilih Kategori Dulu --</option>';
}