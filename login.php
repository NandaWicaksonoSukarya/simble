<?php
session_start();
require "config.php";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // MD5 KONSISTEN
    $role     = $_POST['role'];

    // ==========================
    // LOGIN TERPUSAT (users)
    // ==========================
    $query = mysqli_query($conn, "
        SELECT * FROM users 
        WHERE username='$username' 
        AND password='$password'
        AND role='$role'
    ");

    $data = mysqli_fetch_assoc($query);

    if ($data) {

        $_SESSION['login'] = true;
        $_SESSION['id_user'] = $data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];

        // 🔥 INI YANG HILANG SEBELUMNYA
        if ($data['role'] === 'tutor') {
            $_SESSION['id_tutor'] = $data['id_tutor'];
        }

        if ($data['role'] === 'siswa') {
            $_SESSION['id_siswa'] = $data['id_siswa'];
        }

        // Redirect sesuai role
        if ($role == 'admin') {
            header("Location: admin/dashboardadmin.php");
        } elseif ($role == 'tutor') {
            header("Location: tutor/dashboard.php");
        } else {
            header("Location: siswa/dashboard.php");
        }
        exit;

    } else {
        $error = "Username, password, atau role salah!";
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="row w-100">
            <div class="col-md-6 mx-auto">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-book-fill text-primary" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Sistem Informasi Bimbel</h2>
                            <p class="text-muted">Silakan login untuk melanjutkan</p>
                        </div>

                        <!-- FORM LOGIN -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                            </div>

                            <div class="mb-3">
                                <select class="form-select" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="siswa">Siswa</option>
                                    <option value="tutor">Tutor</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                            </div>

                            <?php if (isset($error)) { ?>
                                <div class="alert alert-danger mt-3"><?= $error ?></div>
                            <?php } ?>
                        </form>

                        <div class="text-center mt-3">
                            <a href="pendaftaran/form-pendaftaran.php" class="text-decoration-none">Belum punya akun? Daftar di sini</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>