<?php
include '../auth/auth.php';
allowRole([1]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-info-circle"></i> Tambah Sistem Assets
        </h1>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <div class="form-group">
                    <label>Kode Assets</label>
                    <input type="text" name="assets_kode" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Nama Assets</label>
                    <input type="text" name="assets_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Estimated Life (Tahun)</label>
                    <input type="number" name="assets_life" class="form-control">
                </div>

                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="assets_price" class="form-control">
                </div>

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="assets_date" class="form-control">
                </div>

                <div class="form-group">
                    <label>Qty</label>
                    <input type="number" name="assets_qty" class="form-control">
                </div>

                <div class="form-group">
                    <label>Note</label>
                    <input type="text" name="assets_note" class="form-control" required>
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