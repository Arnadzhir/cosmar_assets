<?php
include '../auth/auth.php';
allowRole([1]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Kondisi Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Kondisi Assets</label>
                    <input type="text" name="kondisi_name" class="form-control" required>
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