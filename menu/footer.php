<?php include_once __DIR__ . '/../config/base_url.php'; ?>

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Iyoygraphy <?= date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->
 
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin mau keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih Tombol "Logout" di bawah jika Anda siap mengakhiri sesi Anda saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="<?= BASE_URL ?>auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

<!-- Bootstrap core JavaScript-->
<script src="<?= BASE_URL ?>master/vendor/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>master/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="<?= BASE_URL ?>master/vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="<?= BASE_URL ?>master/js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="<?= BASE_URL ?>master/vendor/chart.js/Chart.min.js"></script>

<!-- Page level custom scripts -->
<!-- HAPUS atau KOMENTAR baris ini -->
<!-- <script src="js/demo/chart-area-demo.js"></script>
<script src="js/demo/chart-pie-demo.js"></script> -->

<!-- DataTables JS -->
<script src="<?= BASE_URL ?>master/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="<?= BASE_URL ?>master/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- buat semua file -->
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [10, 25, 50, 100],
            "ordering": true,
            "responsive": true,
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Search:",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": {
                    "previous": "Previous",
                    "next": "Next"
                }
            }
        });
    });
</script>

<!-- Script untuk menghilangkan Alert Otomatis (5 Detik) -->
<script>
    setTimeout(() => {
        // Mencari elemen alert versi terbaru (mendukung form topbar maupun alert bootstrap standar)
        const alertBar = document.querySelector('form .form-control.text-white')?.closest('form') || document.querySelector('.alert');
        
        if (!alertBar) return;

        // Efek animasi menghilang
        alertBar.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        alertBar.style.opacity = '0';
        alertBar.style.transform = 'translateY(-10px)';

        // Hapus elemen dari HTML setelah animasi selesai (600ms)
        setTimeout(() => {
            alertBar.remove();
        }, 600);
        
    }, 5000); // Angka 5000 artinya 5 detik
</script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- buat semua file -->
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });
</script>

<!-- buat semua file -->
<script>
    document.getElementById('primary_image').addEventListener('change', function(e){

        const file = e.target.files[0];
        const preview = document.getElementById('previewImage');

        if(file){

            // Validasi ukuran (2MB)
            if(file.size > 2000000){
                alert("Ukuran gambar maksimal 2MB!");
                this.value = "";
                preview.classList.add('d-none');
                return;
            }

            // Validasi tipe file
            const allowedTypes = ['image/jpeg', 'image/png'];
            if(!allowedTypes.includes(file.type)){
                alert("Format harus JPG atau PNG!");
                this.value = "";
                preview.classList.add('d-none');
                return;
            }

            const reader = new FileReader();

            reader.onload = function(event){
                preview.src = event.target.result;
                preview.classList.remove('d-none');
            }

            reader.readAsDataURL(file);
        }
    });
</script>

<!-- lupa buat file mana -->
<script>
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('primary_image');
    const preview = document.getElementById('previewImage');
    const removeBtn = document.getElementById('removeImage');
    const uploadContent = document.getElementById('uploadContent');

    // Klik area = buka file picker
    uploadArea.addEventListener('click', () => fileInput.click());

    // Drag Over
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    // Drag Leave
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    // Drop File
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFile(e.dataTransfer.files[0]);
    });

    // File dipilih manual
    fileInput.addEventListener('change', () => {
        handleFile(fileInput.files[0]);
    });

    function handleFile(file) {

        if (!file) return;

        // Validasi size
        if (file.size > 2000000) {
            alert("Ukuran maksimal 2MB");
            return;
        }

        // Validasi type
        const allowed = ['image/jpeg', 'image/png'];
        if (!allowed.includes(file.type)) {
            alert("Format harus JPG atau PNG");
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            removeBtn.classList.remove('d-none');
            uploadContent.classList.add('d-none');
        };
        reader.readAsDataURL(file);

        // Masukkan file ke input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }

    // Remove Image
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = "";
        preview.src = "#";
        preview.classList.add('d-none');
        removeBtn.classList.add('d-none');
        uploadContent.classList.remove('d-none');
    });
</script>

<!-- Script Password -->
<script>
    function togglePassword(){
        const pass = document.getElementById("password");
        const icon = document.getElementById("eyeIcon");

        if(pass.type === "password"){
            pass.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>