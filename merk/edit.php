<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_merk WHERE merk_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Merk Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Merk ID</label>
                    <input type="hidden" name="merk_id" class="form-control" value="<?= $row['merk_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Merk Assets</label>
                    <input type="text" name="merk_name" class="form-control" value="<?= $row['merk_name'] ?>" required>
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