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
                        <h1 class="h3 mb-0 text-gray-800">Data supplier Assets</h1>
                        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-download fa-sm text-white-50"></i> Tambah supplier Assets</a>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data supplier Assets</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>supplier ID</th>
                                            <th>supplier Name</th>
                                            <th>supplier Mail</th>
                                            <th>supplier Number</th>
                                            <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1 || $_SESSION['user_level'] == 2)) : ?>
                                            <th>Tools</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    include '../config/koneksi.php';
                                    $no = 1;
                                    $q = mysqli_query($conn, "SELECT * FROM tbl_supplier ORDER BY supplier_id DESC");

                                    while ($row = mysqli_fetch_assoc($q)) {
                                        // Format nomor ke 62
                                        $no_hp = preg_replace('/[^0-9]/', '', $row['supplier_no']); // hapus selain angka

                                        if (substr($no_hp, 0, 1) == '0') {
                                            $no_hp = '62' . substr($no_hp, 1);
                                        }
                                        echo "<tr>
                                            <td>{$no}</td>
                                            <td>{$row['supplier_id']}</td>
                                            <td>{$row['supplier_name']}</td>
                                            <td>
                                                <a href='mailto:{$row['supplier_mail']}' class='text-primary'>
                                                    <i class='fas fa-envelope'></i> {$row['supplier_mail']}
                                                </a>
                                            </td>
                                            <td>
                                                <a href='https://wa.me/{$no_hp}' target='_blank' class='text-success'>
                                                    <i class='fab fa-whatsapp'></i> {$row['supplier_no']}
                                                </a>
                                            </td>";
                                            if ($_SESSION['user_level'] == 1 || $_SESSION['user_level'] == 2) {
                                            echo "
                                            <td class='text-center'>
                                                <a href='edit.php?id={$row['supplier_id']}' class='btn btn-sm btn-warning'>
                                                    <i class='fas fa-edit'></i>
                                                </a>
                                                <a href='proses.php?hapus=1&id={$row['supplier_id']}' 
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