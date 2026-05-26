        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Alert -->
                    <?php if (isset($_SESSION['alert'])) : ?>
                    <div class="d-flex align-items-center ml-3 flex-grow-1" style="max-width: 70%;">
                        <div class="alert alert-<?= $_SESSION['alert']['type']; ?> alert-dismissible fade show shadow-sm mb-0 w-100 d-flex align-items-center" 
                            role="alert" 
                            style="padding: 10px 15px; border-radius: 5px; font-size: 14px;">
                            <i class="fas fa-info-circle mr-2"></i>
                            <div class="text-truncate flex-grow-1 mr-2"><?= $_SESSION['alert']['msg']; ?></div>
                            <button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close" style="padding: 0; line-height: 1;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <?php unset($_SESSION['alert']); endif; ?>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?= htmlspecialchars($_SESSION['user_name']); ?>
                                </span>
                                <?php
                                $foto = (!empty($_SESSION['user_image'])) 
                                        ? BASE_URL . "master/img/user/" . $_SESSION['user_image'] 
                                        : BASE_URL . "master/img/undraw_profile.svg";
                                ?>

                                <img class="img-profile rounded-circle" src="<?= $foto ?>">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="<?= BASE_URL ?>user/profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->