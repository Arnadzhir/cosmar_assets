<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_lokasi WHERE lokasi_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Lokasi Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Lokasi ID</label>
                    <input type="text" name="lokasi_id" class="form-control" value="<?= $row['lokasi_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Lokasi Assets</label>
                    <input type="text" name="lokasi_name" class="form-control" value="<?= $row['lokasi_name'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Lokasi Lantai</label>
                    <select name="lokasi_lantai" class="form-control select2" style="width:100%" required>
                        <option value="">-- Pilih Lantai --</option>
                        <option value="LT 1" <?= $row['lokasi_lantai']=='LT 1' ? 'selected':'' ?>>LANTAI 1</option>
                        <option value="LT 2" <?= $row['lokasi_lantai']=='LT 2' ? 'selected':'' ?>>LANTAI 2</option>
                        <option value="LT 3" <?= $row['lokasi_lantai']=='LT 3' ? 'selected':'' ?>>LANTAI 3</option>
                    </select>
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