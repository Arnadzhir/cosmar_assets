<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

$id = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM tbl_supplier WHERE supplier_id='$id'");
$row = mysqli_fetch_assoc($data);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Supplier Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>supplier ID</label>
                    <input type="text" name="supplier_id" class="form-control" value="<?= $row['supplier_id'] ?>" readonly>
                </div>

                <div class="form-group">
                    <label>supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control" value="<?= $row['supplier_name'] ?>" required>
                </div>

                <div class="form-group">
                    <label>supplier Mail</label>
                    <input type="email" name="supplier_mail" class="form-control" value="<?= $row['supplier_mail'] ?>" required>
                </div>

                <div class="form-group">
                    <label>supplier Number</label>
                    <input type="text"
                        name="supplier_no"
                        class="form-control"
                        value="<?= $row['supplier_no'] ?? '' ?>"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        required>
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
