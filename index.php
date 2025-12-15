<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bintang Bimble - Sistem Informasi Bimbingan Belajar</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/landing.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                <div class="logo-icon">
                    B
                </div>
                <span class="ms-2">Bintang Bimble</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#actors">Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#flow">Alur Sistem</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-primary btn-login" href="login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Sistem
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Sistem Informasi <span class="text-primary">Bintang Bimble</span>
                </h1>
                <p class="hero-description">
                    Platform digital untuk mengelola bimbingan belajar Bintang Bimble. 
                    Kelola siswa, jadwal, pembelajaran, dan evaluasi dalam satu sistem terintegrasi.
                </p>
                <div class="hero-buttons">
                    <a href="pendaftaran/form-pendaftaran.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Daftar Siswa Baru
                    </a>
                    <a href="#features" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-book me-2"></i>Pelajari Sistem
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title">Keunggulan Sistem</h2>
            <p class="section-subtitle">Temukan fitur-fitur unggulan yang membuat pembelajaran lebih efektif</p>
            
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Manajemen Siswa</h3>
                        <p>Kelola data siswa, nilai, dan perkembangan belajar dengan sistem yang terorganisir.</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Penjadwalan Pintar</h3>
                        <p>Atur jadwal belajar fleksibel untuk semua kelas dengan sistem otomatis.</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Materi Digital</h3>
                        <p>Akses materi pembelajaran, tugas, dan latihan soal secara online.</p>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Laporan Berkala</h3>
                        <p>Pantau perkembangan belajar siswa melalui laporan yang terperinci.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Actors Section -->
    <section id="actors" class="actors-section">
        <div class="container">
            <h2 class="section-title">Pengguna Sistem</h2>
            <p class="section-subtitle">Sistem ini digunakan oleh berbagai peran untuk optimalisasi pembelajaran</p>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="actor-card">
                        <div class="actor-icon admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3>Admin</h3>
                        <p>Mengelola seluruh sistem, data siswa, tutor, dan konfigurasi platform.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="actor-card">
                        <div class="actor-icon tutor">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3>Tutor</h3>
                        <p>Mengajar, input nilai, berinteraksi dengan siswa, dan membuat materi.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="actor-card">
                        <div class="actor-icon student">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>Siswa</h3>
                        <p>Mengikuti pembelajaran, mengerjakan tugas, dan melihat hasil belajar.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Flow Section -->
    <section id="flow" class="flow-section">
        <div class="container">
            <h2 class="section-title">Alur Sistem</h2>
            <p class="section-subtitle">Proses pembelajaran yang terstruktur untuk hasil optimal</p>
            
            <div class="flow-container">
                <div class="flow-card">
                    <div class="step-number">1</div>
                    <h4>Pendaftaran</h4>
                    <p>Siswa mendaftar melalui formulir online dengan mudah dan cepat.</p>
                </div>
                
                <div class="flow-card">
                    <div class="step-number">2</div>
                    <h4>Penempatan</h4>
                    <p>Penempatan kelas berdasarkan tingkat kemampuan dan tes awal.</p>
                </div>
                
                <div class="flow-card">
                    <div class="step-number">3</div>
                    <h4>Pembelajaran</h4>
                    <p>Mengikuti sesi belajar interaktif dengan tutor berpengalaman.</p>
                </div>
                
                <div class="flow-card">
                    <div class="step-number">4</div>
                    <h4>Evaluasi</h4>
                    <p>Ujian berkala dan laporan perkembangan hasil belajar secara berkala.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <div class="logo-icon">B</div>
                        <h3>Bintang Bimble</h3>
                    </div>
                    <p class="footer-description">
                        Bimbingan belajar berkualitas untuk siswa SD, SMP, dan SMA dengan sistem pembelajaran terpadu dan modern.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <h4 class="footer-title">Kontak Kami</h4>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Jl. Pendidikan No. 123, Jakarta Selatan</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>(021) 1234-5678</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>info@bintangbimble.id</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Senin-Sabtu: 08:00-20:00</span>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-12">
                    <h4 class="footer-title">Akses Sistem</h4>
                    <ul class="access-links">
                        <li><a href="login.php?role=siswa"><i class="fas fa-sign-in-alt"></i> Login Siswa</a></li>
                        <li><a href="login.php?role=tutor"><i class="fas fa-sign-in-alt"></i> Login Tutor</a></li>
                        <li><a href="login.php?role=admin"><i class="fas fa-sign-in-alt"></i> Login Admin</a></li>
                        <li><a href="form-pendaftaran.php"><i class="fas fa-user-plus"></i> Daftar Siswa Baru</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Bintang Bimble - Sistem Informasi Bimbingan Belajar. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>