<?php
include '../config/koneksi.php';

if(isset($_POST['dep_code'])){

    $dep_code = mysqli_real_escape_string($conn, $_POST['dep_code']);

    $query = mysqli_query($conn,"
        SELECT DISTINCT dep_name
        FROM tbl_dep
        WHERE dep_code='$dep_code'
        ORDER BY dep_name ASC
    ");

    echo '<option value="">-- Pilih Departemen --</option>';

    while($row = mysqli_fetch_assoc($query)){
        echo "<option value='".$row['dep_name']."'>".$row['dep_name']."</option>";
    }
}
?>
