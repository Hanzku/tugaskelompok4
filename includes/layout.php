<?php
declare(strict_types=1);

/**
 * Layout Templating SIA SMK Malaka - Kelompok 4 (Modul Guru)
 * Struktur HTML, Sidebar, Topbar, dan Navigasi sesuai referensi visual.
 */

function render_header(string $title, string $activePage = 'guru', array $breadcrumbs = []): void
{
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> - Sistem Informasi Sederhana SMK Malaka</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
  <link rel="alternate icon" type="image/png" href="assets/img/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <!-- Mobile Sidebar Backdrop -->
  <div class="sidebar-backdrop-mobile" id="mobileBackdrop"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="appSidebar" aria-label="Navigasi Utama">
    <div class="sidebar-header">
      <a href="index.php?page=dashboard" class="brand-link" title="Kembali ke Dashboard">
        <div class="brand-icon-wrap">
          <img src="assets/img/logo.svg" alt="Logo SMK Malaka" class="brand-logo-img">
        </div>
        <div class="brand-info">
          <h1>SMK MALAKA</h1>
          <p>Sistem Informasi Sederhana</p>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="index.php?page=dashboard" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/>
          <rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/>
        </svg>
        <span>Dashboard</span>
      </a>

      <div class="nav-section-title">DATA MASTER</div>
      <a href="index.php?page=murid" class="nav-link <?= $activePage === 'murid' ? 'active' : '' ?>" title="Data Murid">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span>Murid</span>
      </a>

      <a href="index.php?page=pelajaran" class="nav-link <?= $activePage === 'pelajaran' ? 'active' : '' ?>" title="Data Pelajaran">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
        <span>Pelajaran</span>
      </a>

      <a href="guru.php" class="nav-link <?= $activePage === 'guru' ? 'active' : '' ?>" title="Data Guru (Fokus Kelompok 4)">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Guru</span>
      </a>

      <div class="nav-section-title">Relasi</div>
      <a href="index.php?page=mempelajari" class="nav-link <?= $activePage === 'mempelajari' ? 'active' : '' ?>" title="Relasi Mempelajari">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="16 3 21 3 21 8"/>
          <line x1="4" y1="20" x2="21" y2="3"/>
          <polyline points="21 16 21 21 16 21"/>
          <line x1="15" y1="15" x2="21" y2="21"/>
          <line x1="4" y1="4" x2="9" y2="9"/>
        </svg>
        <span>Mempelajari</span>
      </a>

      <a href="index.php?page=diampu" class="nav-link <?= $activePage === 'diampu' ? 'active' : '' ?>" title="Relasi Diampu">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
          <line x1="8" y1="21" x2="16" y2="21"/>
          <line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
        <span>Diampu</span>
      </a>

      <div class="nav-section-title">Lainnya</div>
      <a href="index.php?page=tentang" class="nav-link <?= $activePage === 'tentang' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="16" x2="12" y2="12"/>
          <line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
        <span>Tentang</span>
      </a>
    </nav>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka Menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <div class="breadcrumb">
          <a href="index.php?page=dashboard" title="Dashboard">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </a>
          <?php foreach ($breadcrumbs as $bc): ?>
            <span class="breadcrumb-sep">/</span>
            <?php if (!empty($bc['url'])): ?>
              <a href="<?= e($bc['url']) ?>"><?= e($bc['label']) ?></a>
            <?php else: ?>
              <span class="breadcrumb-current"><?= e($bc['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="topbar-right">
        <div class="user-profile">
          <img src="assets/img/admin-avatar.svg" alt="Avatar Admin" class="user-avatar">
          <span class="user-name">Admin</span>
          <svg class="user-caret" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
    </header>

    <!-- Content Area -->
    <main class="content-body">
      <?php if ($flash !== null): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="alert">
          <?php if ($flash['type'] === 'success'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          <?php else: ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          <?php endif; ?>
          <span><?= e($flash['message']) ?></span>
        </div>
      <?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
    </main>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php
}
