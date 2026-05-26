<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_Produsen WHERE Produsen_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Produsen Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Produsen ID</label>
                    <input type="text" name="produsen_id" class="form-control" value="<?= $row['produsen_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Produsen Code</label>
                    <input type="text" name="produsen_code" class="form-control" value="<?= $row['produsen_code'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Produsen Region</label>
                    <input type="text" name="produsen_region" class="form-control" value="<?= $row['produsen_region'] ?>" required>
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