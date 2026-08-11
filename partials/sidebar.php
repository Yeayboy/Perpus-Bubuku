<?php


$activePage   = $activePage   ?? '';
$pageTitle    = $pageTitle    ?? 'Perpus Bubuku';
$pageSubtitle = $pageSubtitle ?? '';
$user         = $user         ?? ($_SESSION['user'] ?? ['nama_lengkap' => 'Pengguna', 'role' => 'petugas']);
$isAdmin      = ($user['role'] ?? '') === 'admin';
$isGuest      = ($user['role'] ?? '') === 'tamu';

$initial = strtoupper(substr($user['nama_lengkap'] ?? 'P', 0, 1));

$pbMenu = [
    'guest_dashboard' => ['Dashboard Saya', 'guest_dashboard.php', 'bi-grid-1x2-fill', ['tamu']],
    'dashboard' => ['Dashboard', 'index.php', 'bi-grid-1x2-fill', ['admin', 'petugas']],
    'books'     => ['Master Data Buku', 'books.php', 'bi-journal-text', ['admin', 'petugas']],
    'members'   => ['Data Anggota', 'members.php', 'bi-people-fill', ['admin', 'petugas']],
    'loans'     => ['Sirkulasi & Laporan', 'loans.php', 'bi-arrow-left-right', ['admin', 'petugas']],
    'documents' => ['Metadata Dokumen', 'documents.php', 'bi-folder2-open', ['admin', 'petugas']],
    'import'    => ['Impor Data Anggota', 'import_members.php', 'bi-file-earmark-arrow-up', ['admin']],
    'quality'   => ['Kelola Kualitas Data', 'data_quality.php', 'bi-shield-check', ['admin']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Perpus Bubuku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>

<div class="pb-shell" id="pbShell">
    <div class="pb-overlay" id="pbOverlay"></div>

    
    <aside class="pb-sidebar">
        <div class="pb-brand">
            <img src="assets/img/logo.png" alt="Logo Perpus Bubuku">
            <div class="pb-brand-text">
                Perpus Bubuku
                <small>Sistem Perpustakaan</small>
            </div>
        </div>

        <div class="pb-user">
            <div class="pb-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="flex-grow-1" style="min-width:0;">
                <div class="pb-user-name"><?= htmlspecialchars($user['nama_lengkap'] ?? '-') ?></div>
                <span class="pb-role-badge"><?= htmlspecialchars(strtoupper($isGuest ? 'Pengunjung' : ($user['role'] ?? ''))) ?></span>
            </div>
        </div>

        <ul class="pb-nav">
            <li class="pb-nav-label">Menu Utama</li>
            <?php foreach ($pbMenu as $slug => $item):
                [$label, $href, $icon, $roles] = $item;
                if (!in_array($user['role'] ?? '', $roles, true)) continue;
            ?>
                <li>
                    <a href="<?= htmlspecialchars($href) ?>" class="<?= $activePage === $slug ? 'active' : '' ?>">
                        <i class="bi <?= $icon ?>"></i><span><?= htmlspecialchars($label) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="pb-sidebar-foot">
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
        </div>
    </aside>

    
    <div class="pb-main">
        <header class="pb-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="pb-burger" id="pbBurger" type="button" aria-label="Buka menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div>
                    <p class="pb-topbar-title"><i class="bi bi-book-half text-primary"></i> <?= htmlspecialchars($pageTitle) ?></p>
                    <?php if ($pageSubtitle): ?>
                        <p class="pb-topbar-sub"><?= htmlspecialchars($pageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-none d-md-flex align-items-center gap-2 text-muted small">
                <i class="bi bi-calendar3"></i> <?= date('d F Y') ?>
            </div>
        </header>

        <main class="pb-content">
