<?php
require_once 'auth.php';
requireLogin(); 

$user = $_SESSION['user'];
if ($user['role'] === 'tamu') {
    header('Location: guest_dashboard.php');
    exit;
}

$activePage   = 'dashboard';
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Ringkasan modul operasional perpustakaan';
require 'partials/sidebar.php';
?>

        <div class="p-4 bg-white rounded-4 shadow-sm border-0 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background:linear-gradient(120deg, var(--pb-navy-2), var(--pb-indigo)) !important;">
            <div>
                <h3 class="m-0 text-white">Selamat Datang, <?= htmlspecialchars(explode(' ', $user['nama_lengkap'])[0]) ?> 👋</h3>
                <p class="mt-1 mb-0" style="color:rgba(255,255,255,.8);">Sistem terintegrasi pengelolaan data, kualitas data, dan dokumentasi basis data.</p>
            </div>
            <i class="bi bi-book-half" style="font-size:3.2rem;color:rgba(255,255,255,.35);"></i>
        </div>

        
        <div class="row g-3">

            
            <div class="col-md-4">
                <a href="books.php" class="pb-stat is-navy d-block h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Master Data Buku</span>
                        <i class="bi bi-journal-text pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(255,255,255,.85);">Kelola data buku, kategori, dan ketersediaan stok fisik.</p>
                        <span class="pb-stat-link">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            
            <div class="col-md-4">
                <a href="members.php" class="pb-stat is-indigo d-block h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Data Anggota</span>
                        <i class="bi bi-people-fill pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(255,255,255,.85);">Kelola data anggota, daftar anggota baru, dan hapus anggota.</p>
                        <span class="pb-stat-link">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            
            <div class="col-md-4">
                <a href="loans.php" class="pb-stat is-coral d-block h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Sirkulasi & Laporan</span>
                        <i class="bi bi-arrow-left-right pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(255,255,255,.85);">Pencatatan transaksi peminjaman, pengembalian, dan query laporan.</p>
                        <span class="pb-stat-link">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            
            <div class="col-md-4">
                <a href="documents.php" class="pb-stat is-mint d-block h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Metadata Dokumen</span>
                        <i class="bi bi-folder2-open pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(255,255,255,.85);">Pengelolaan arsip digital, SOP, dan repository dokumen kampus.</p>
                        <span class="pb-stat-link">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
            
            <div class="col-md-4">
                <a href="import_members.php" class="pb-stat is-gold d-block h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Impor Data Anggota (CSV)</span>
                        <i class="bi bi-file-earmark-arrow-up pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(58,42,5,.75);">Integrasikan data anggota eksternal dari format CSV ke database SQL.</p>
                        <span class="pb-stat-link" style="color:#3a2a05;">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>

            
            <div class="col-md-4">
                <a href="data_quality.php" class="pb-stat d-block h-100" style="background:linear-gradient(135deg,#7A81A0,#4B5177);">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="pb-stat-label">Kelola Kualitas Data</span>
                        <i class="bi bi-shield-check pb-stat-icon"></i>
                    </div>
                    <div>
                        <p class="small mb-2" style="color:rgba(255,255,255,.85);">Deteksi dan perbaiki data ganda, format invalid, serta stok bermasalah.</p>
                        <span class="pb-stat-link">Buka Modul <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            <?php endif; ?>

        </div>

<?php require 'partials/footer.php'; ?>