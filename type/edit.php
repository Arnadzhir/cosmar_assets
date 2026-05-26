<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_type WHERE type_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Type Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Type ID</label>
                    <input type="hidden" name="type_id" class="form-control" value="<?= $row['type_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Type Assets</label>
                    <input type="text" name="type_name" class="form-control" value="<?= $row['type_name'] ?>" required>
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