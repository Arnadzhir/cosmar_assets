<?php
include '../auth/auth.php';
allowRole([1]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah produsen Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>produsen Code</label>
                    <input type="text" name="produsen_code" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>produsen Region</label>
                    <input type="text" name="produsen_region" class="form-control" required>
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