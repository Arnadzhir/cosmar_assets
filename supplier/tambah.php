<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Supplier Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>supplier Mail</label>
                    <input type="email" name="supplier_mail" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Supplier Number</label>
                    <input type="text"
                        name      ="supplier_no"
                        class     ="form-control"
                        pattern   ="[0-9]+"
                        oninput   ="this.value = this.value.replace(/[^0-9]/g, '')"
                        required>
                </div>

                <button type="submit" name="tambah" class="btn btn-primary">
                    Simpan
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