<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Cosmar Assets - Login</title>

    <!-- Font Awesome -->
    <link href="../master/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- SB Admin 2 -->
    <link href="../master/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../master/img/assets/logo.png">

</head>

<body class="bg-gradient-primary">

<div class="container">

    <div class="row justify-content-center">

        <div class="col-xl-10 col-lg-12 col-md-9">

            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">

                    <div class="row">

                        <!-- LEFT SIDE (LOGO) -->
                        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-light">
                            <div class="text-center p-4">
                                <img src="../master/img/assets/logo.png"
                                     alt="Cosmar Logo"
                                     style="max-width:200px;"
                                     class="mb-3">
                                <h4 class="text-primary font-weight-bold">Cosmar Assets</h4>
                                <p class="text-muted small">
                                    Asset Management System
                                </p>
                            </div>
                        </div>

                        <!-- RIGHT SIDE (FORM LOGIN) -->
                        <div class="col-lg-6">
                            <div class="p-5">

                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                </div>

                                <form class="user" method="POST" action="login_process.php">

                                    <div class="form-group">
                                        <input type="number"
                                               class="form-control form-control-user"
                                               name="user_id"
                                               placeholder="ID Karyawan"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <input type="password"
                                               class="form-control form-control-user"
                                               name="user_password"
                                               placeholder="Password"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" id="remember">
                                            <label class="custom-control-label" for="remember">
                                                Remember Me
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-user btn-block">
                                        Login
                                    </button>

                                </form>

                                <hr>

                                <div class="text-center">
                                    <a class="small" href="forgot-password.php">Lupa Password?</a>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

<!-- JS -->
<script src="../master/vendor/jquery/jquery.min.js"></script>
<script src="../master/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../master/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../master/js/sb-admin-2.min.js"></script>

</body>
</html>
