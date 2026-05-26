<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_kategori WHERE kategori_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit kategori Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <input type="hidden" name="kategori_id" class="form-control" value="<?= $row['kategori_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>kategori Name</label>
                    <input type="text" name="kategori_name" class="form-control" value="<?= $row['kategori_name'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Line</label>
                    <select name="kategori_line" class="form-control select2" style="width:100%" required>
                        <option value="">-- Pilih Line --</option>
                        <option value="OFFICE" <?= $row['kategori_line']=='OFFICE' ? 'selected':'' ?>>OFFICE</option>
                        <option value="LAB" <?= $row['kategori_line']=='LAB' ? 'selected':'' ?>>LAB</option>
                        <option value="PRODUCTION" <?= $row['kategori_line']=='PRODUCTION' ? 'selected':'' ?>>PRODUCTION</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>kategori Code</label>
                    <input type="text" name="kategori_code" class="form-control" value="<?= $row['kategori_code'] ?>" required>
                </div>

                <button type="submit" name="edit" class="btn btn-warning">
                    Update
                </button>
                <a href="index.php" class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>

</html>