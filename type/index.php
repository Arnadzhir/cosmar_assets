<?php
    session_start();
    if (!isset($_SESSION['login'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    include '../menu/header.php';
    include '../menu/sidebar.php';
    include '../menu/topbar.php';
?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Type Assets</h1>
                        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-download fa-sm text-white-50"></i> Tambah Type Assets</a>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Type Assets</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Type ID</th>
                                            <th>Type Assets</th>
                                            <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
                                            <th>Tools</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    include '../config/koneksi.php';
                                    $no = 1;
                                    $q = mysqli_query($conn, "SELECT * FROM tbl_type ORDER BY type_id DESC");

                                    while ($row = mysqli_fetch_assoc($q)) {
                                        echo "<tr>
                                            <td>{$no}</td>
                                            <td>{$row['type_id']}</td>
                                            <td>{$row['type_name']}</td>";
                                            if ($_SESSION['user_level'] == 1) {
                                            echo "
                                            <td class='text-center'>
                                                <a href='edit.php?id={$row['type_id']}' class='btn btn-sm btn-warning'>
                                                    <i class='fas fa-edit'></i>
                                                </a>
                                                <a href='proses.php?hapus=1&id={$row['type_id']}' 
                                                class='btn btn-sm btn-danger'
                                                onclick=\"return confirm('Hapus data ini?')\">
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </td>";
                                            }
                                        echo"
                                        </tr>";
                                        $no++;
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

<?php include '../menu/footer.php'; ?>

</body>

</html>