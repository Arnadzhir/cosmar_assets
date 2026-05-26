<?php
include '../auth/auth.php';
allowRole([1]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Lokasi Assets</h1>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>lokasi ID</label>
                    <input type="text" name="lokasi_id" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>lokasi Assets</label>
                    <input type="text" name="lokasi_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Lokasi Lantai</label>
                    <select name="lokasi_lantai" class="form-control select2" style="width:100%" required>
                        <option value="">-- Pilih Lantai --</option>
                        <option value="LT 1">LANTAI 1</option>
                        <option value="LT 2">LANTAI 2</option>
                        <option value="LT 3">LANTAI 3</option>
                    </select>
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