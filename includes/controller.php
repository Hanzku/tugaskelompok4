<?php
declare(strict_types=1);

/**
 * Main Controller untuk SIA SMK Malaka - Kelompok 4 (Modul Guru)
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/layout.php';

function handle_request(): void
{
    $page = $_GET['page'] ?? 'guru';
    $action = $_GET['action'] ?? '';

    // ==========================================
    // 1. Endpoint Foto Guru (GET)
    // ==========================================
    if ($page === 'photo' || $action === 'photo') {
        $kode = strtoupper(trim((string) ($_GET['kode'] ?? '')));
        if ($kode === '') {
            http_response_code(404);
            exit('Foto tidak ditemukan.');
        }

        $photoBytes = guru_get_photo($kode);
        if ($photoBytes !== null) {
            $info = @getimagesizefromstring($photoBytes);
            $mime = 'image/jpeg';
            if ($info !== false && !empty($info['mime'])) {
                $mime = $info['mime'];
            }
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . strlen($photoBytes));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=300');
            echo $photoBytes;
            exit;
        }

        // Fallback ke avatar placeholder SVG
        $svgPath = __DIR__ . '/../assets/img/avatar-placeholder.svg';
        if (is_file($svgPath)) {
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=86400');
            readfile($svgPath);
            exit;
        }

        http_response_code(404);
        exit('Foto tidak ditemukan.');
    }

    // ==========================================
    // 2. Handler POST Aksi Guru (CRUD)
    // ==========================================
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $postAction = $_POST['action'] ?? $action;
        $csrfToken = (string) ($_POST['csrf_token'] ?? '');

        if (!verify_csrf($csrfToken)) {
            set_flash('error', 'Sesi tidak sah atau telah kedaluwarsa. Silakan coba kembali.');
            redirect('guru.php');
        }

        // --- A. Tambah Guru ---
        if ($postAction === 'create') {
            [$data, $errors] = validate_guru_input($_POST, false);
            [$photoBytes, $photoError] = validate_photo_file($_FILES['fotoguru'] ?? null, false);

            if ($photoError !== null) {
                $errors['fotoguru'] = $photoError;
            }

            if (!empty($errors)) {
                $firstError = reset($errors);
                set_flash('error', $firstError);
                redirect('guru.php');
            }

            $result = guru_create($data['kode_guru'], $data['nama'], $photoBytes);
            if ($result['success']) {
                set_flash('success', 'Data guru "' . $data['nama'] . '" (' . $data['kode_guru'] . ') berhasil ditambahkan.');
            } else {
                set_flash('error', $result['error'] ?? 'Gagal menambahkan data guru.');
            }
            redirect('guru.php');
        }

        // --- B. Edit Guru ---
        if ($postAction === 'update') {
            [$data, $errors] = validate_guru_input($_POST, true);
            [$photoBytes, $photoError] = validate_photo_file($_FILES['fotoguru'] ?? null, false);

            if ($photoError !== null) {
                $errors['fotoguru'] = $photoError;
            }

            if (!empty($errors)) {
                $firstError = reset($errors);
                set_flash('error', $firstError);
                redirect('guru.php');
            }

            $result = guru_update($data['kode_guru'], $data['nama'], $photoBytes);
            if ($result['success']) {
                set_flash('success', 'Data guru "' . $data['nama'] . '" (' . $data['kode_guru'] . ') berhasil diperbarui.');
            } else {
                set_flash('error', $result['error'] ?? 'Gagal memperbarui data guru.');
            }
            redirect('guru.php');
        }

        // --- C. Hapus Guru ---
        if ($postAction === 'delete') {
            $kode = strtoupper(trim((string) ($_POST['kode_guru'] ?? '')));
            if ($kode === '') {
                set_flash('error', 'Kode guru yang akan dihapus tidak valid.');
                redirect('guru.php');
            }

            $result = guru_delete($kode);
            if ($result['success']) {
                set_flash('success', 'Data guru (' . $kode . ') berhasil dihapus.');
            } else {
                set_flash('error', $result['error'] ?? 'Gagal menghapus data guru.');
            }
            redirect('guru.php');
        }

        redirect('guru.php');
    }

    // ==========================================
    // 3. Halaman Detail Guru
    // ==========================================
    if ($page === 'detail' || $action === 'detail') {
        $kode = strtoupper(trim((string) ($_GET['kode'] ?? '')));
        $guru = guru_find($kode);

        if ($guru === null) {
            set_flash('error', 'Data guru dengan kode "' . $kode . '" tidak ditemukan.');
            redirect('guru.php');
        }

        render_header('Detail Guru - ' . $guru['nama'], 'guru', [
            ['label' => 'Guru', 'url' => 'guru.php'],
            ['label' => 'Detail', 'url' => ''],
        ]);
        ?>
        <div class="page-header">
          <div>
            <h2 class="page-title">Detail Guru</h2>
            <p class="page-subtitle">Informasi lengkap data guru berdasarkan ERD Sistem Informasi Sederhana</p>
          </div>
          <a href="guru.php" class="btn btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Kembali
          </a>
        </div>

        <div class="detail-card">
          <div class="detail-layout">
            <div class="detail-photo-wrap">
              <?php if (!empty($guru['has_photo'])): ?>
                <img src="index.php?page=photo&kode=<?= rawurlencode($guru['kode_guru']) ?>" alt="Foto <?= e($guru['nama']) ?>" class="detail-photo">
              <?php else: ?>
                <img src="assets/img/avatar-placeholder.svg" alt="Default Avatar" class="detail-photo">
              <?php endif; ?>
            </div>

            <div class="detail-info">
              <div class="detail-field">
                <label>Kode Guru</label>
                <div class="detail-value"><?= e($guru['kode_guru']) ?></div>
              </div>

              <div class="detail-field">
                <label>Nama</label>
                <div class="detail-value"><?= e($guru['nama']) ?></div>
              </div>

              <div class="detail-field">
                <label>Foto Guru</label>
                <div class="detail-value" style="font-size: 14px; font-weight: normal; color: var(--text-muted);">
                  <?= !empty($guru['has_photo']) ? 'Tersedia di basis data' : 'Foto belum diunggah' ?>
                </div>
              </div>

              <div class="detail-actions">
                <button type="button" class="btn btn-primary btn-edit-guru"
                        data-kode="<?= e($guru['kode_guru']) ?>"
                        data-nama="<?= e($guru['nama']) ?>"
                        data-has-photo="<?= !empty($guru['has_photo']) ? '1' : '0' ?>">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                  Edit
                </button>
                <button type="button" class="btn btn-danger btn-hapus-guru"
                        data-kode="<?= e($guru['kode_guru']) ?>"
                        data-nama="<?= e($guru['nama']) ?>">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </div>
        <?php
        render_modals();
        render_footer();
        return;
    }

    // ==========================================
    // 4. Halaman Dashboard
    // ==========================================
    if ($page === 'dashboard') {
        $stats = dashboard_stats();
        render_header('Dashboard', 'dashboard', [
            ['label' => 'Dashboard', 'url' => ''],
        ]);
        ?>
        <div class="dashboard-hero">
          <div class="dashboard-hero-text">
            <h2>Selamat Datang, Admin</h2>
            <p>Sistem Informasi Sederhana SMK Malaka</p>
          </div>
          <img src="assets/img/school-hero.svg" alt="Ilustrasi Sekolah" class="dashboard-hero-icon">
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div>
              <div class="stat-value"><?= (int) $stats['guru'] ?></div>
              <div class="stat-label">Data Guru</div>
            </div>
            <div class="stat-icon-wrap stat-icon-blue">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
              </svg>
            </div>
          </div>

          <div class="stat-card">
            <div>
              <div class="stat-value"><?= (int) $stats['murid'] ?></div>
              <div class="stat-label">Data Murid</div>
            </div>
            <div class="stat-icon-wrap stat-icon-green">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
          </div>

          <div class="stat-card">
            <div>
              <div class="stat-value"><?= (int) $stats['pelajaran'] ?></div>
              <div class="stat-label">Data Pelajaran</div>
            </div>
            <div class="stat-icon-wrap stat-icon-yellow">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
              </svg>
            </div>
          </div>
        </div>

        <h3 class="quick-menu-title">Menu Cepat</h3>
        <div class="quick-menu-grid">
          <a href="guru.php" class="quick-menu-card">
            <div style="color: var(--primary-blue);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <h4>Data Guru</h4>
            <p>Kelola data guru (Kelompok 4)</p>
          </a>

          <a href="index.php?page=murid" class="quick-menu-card">
            <div style="color: var(--success-green);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
              </svg>
            </div>
            <h4>Data Murid</h4>
            <p>Kelola data murid</p>
          </a>

          <a href="index.php?page=pelajaran" class="quick-menu-card">
            <div style="color: var(--warning-yellow);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
              </svg>
            </div>
            <h4>Data Pelajaran</h4>
            <p>Kelola data pelajaran</p>
          </a>

          <a href="index.php?page=diampu" class="quick-menu-card">
            <div style="color: #8B5CF6;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </div>
            <h4>Relasi Diampu</h4>
            <p>Guru dan pelajaran</p>
          </a>
        </div>
        <?php
        render_footer();
        return;
    }

    // ==========================================
    // 5. Halaman Modul Lain (Informasional)
    // ==========================================
    if (in_array($page, ['murid', 'pelajaran', 'mempelajari', 'diampu', 'tentang'], true)) {
        $titles = [
            'murid' => 'Data Murid',
            'pelajaran' => 'Data Pelajaran',
            'mempelajari' => 'Relasi Mempelajari',
            'diampu' => 'Relasi Diampu',
            'tentang' => 'Tentang Aplikasi',
        ];
        $currentTitle = $titles[$page] ?? 'SIA SMK Malaka';
        render_header($currentTitle, $page, [
            ['label' => $currentTitle, 'url' => ''],
        ]);
        ?>
        <div class="page-header">
          <div>
            <h2 class="page-title"><?= e($currentTitle) ?></h2>
            <p class="page-subtitle">Sistem Informasi Sederhana SMK Malaka - ERD Akademik</p>
          </div>
          <a href="guru.php" class="btn btn-primary">Ke Modul Guru</a>
        </div>

        <div class="table-card" style="padding: 32px; max-width: 720px;">
          <?php if ($page === 'tentang'): ?>
            <h3 style="margin-bottom: 12px; font-size: 18px;">Sistem Informasi Sederhana SMK Malaka</h3>
            <p style="color: var(--text-secondary); margin-bottom: 16px;">
              Aplikasi ini dikembangkan untuk mengelola data akademik SMK Malaka berdasarkan Diagram Entitas Relasi (ERD).
            </p>
            <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 20px;">
              <h4 style="font-size: 15px; margin-bottom: 8px; color: var(--primary-blue);">Kelompok 4: Modul Guru</h4>
              <ul style="padding-left: 20px; color: var(--text-secondary); font-size: 13.5px; line-height: 1.8;">
                <li><strong>Entitas:</strong> Guru (<code>KODE_GURU</code>, <code>NAMA</code>, <code>FOTOGURU</code>)</li>
                <li><strong>Relasi Terkait:</strong> Diampu (<code>KODE_GURU</code>, <code>KD_PELAJARAN</code>, <code>HARI</code>)</li>
                <li><strong>Basis Data:</strong> MySQL/MariaDB dengan dukungan TLS & SQLite</li>
                <li><strong>Desain:</strong> Responsif desktop & mobile sesuai referensi desain</li>
              </ul>
            </div>
            <a href="guru.php" class="btn btn-primary">Buka Modul Guru</a>
          <?php else: ?>
            <div style="text-align: center; padding: 24px 0;">
              <div class="empty-icon" style="color: var(--primary-blue);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
              </div>
              <h3 style="font-size: 18px; margin-bottom: 8px;">Informasi Modul <?= e($currentTitle) ?></h3>
              <p style="color: var(--text-muted); font-size: 14px; max-width: 500px; margin: 0 auto 20px;">
                Modul ini merupakan bagian dari sistem akademik SMK Malaka sesuai pembagian kelompok.
                Pekerjaan repositori ini berfokus pada <strong>Kelompok 4: Modul Guru</strong>.
              </p>
              <a href="guru.php" class="btn btn-primary">Kembali ke Modul Guru</a>
            </div>
          <?php endif; ?>
        </div>
        <?php
        render_footer();
        return;
    }

    // ==========================================
    // 6. Halaman Utama Modul Guru (Default)
    // ==========================================
    $q = trim((string) ($_GET['q'] ?? ''));
    $pageNo = max(1, (int) ($_GET['p'] ?? 1));
    $perPage = 10;

    $totalGuru = guru_count($q !== '' ? $q : null);
    $totalPages = max(1, (int) ceil($totalGuru / $perPage));
    if ($pageNo > $totalPages) {
        $pageNo = $totalPages;
    }

    $guruList = guru_get_all($q !== '' ? $q : null, $pageNo, $perPage);
    $startItem = $totalGuru > 0 ? ($pageNo - 1) * $perPage + 1 : 0;
    $endItem = min($pageNo * $perPage, $totalGuru);

    render_header('Data Guru', 'guru', [
        ['label' => 'Guru', 'url' => ''],
    ]);
    ?>
    <div class="page-header">
      <div>
        <h2 class="page-title">Data Guru</h2>
        <p class="page-subtitle">Kelola data guru sesuai dengan ERD. Kelompok 4 - Modul Guru</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary" id="btnTambahGuru">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Guru
        </button>
      </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="filter-card">
      <form method="GET" action="guru.php" class="search-form">
        <div class="search-input-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="q" value="<?= e($q) ?>" class="search-input" placeholder="Cari berdasarkan kode guru atau nama..." maxlength="100">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Cari
        </button>
        <?php if ($q !== ''): ?>
          <a href="guru.php" class="btn btn-secondary btn-sm" title="Hapus pencarian">Reset</a>
        <?php endif; ?>
      </form>

      <div class="total-data-badge">
        Total Data: <strong><?= $totalGuru ?></strong>
      </div>
    </div>

    <!-- Table Card (Desktop) & Card List (Mobile) -->
    <div class="table-card">
      <?php if (empty($guruList)): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14"></line>
              <line x1="22" y1="11" x2="16" y2="11"></line>
            </svg>
          </div>
          <div class="empty-title">
            <?= $q !== '' ? 'Data Tidak Ditemukan' : 'Belum Ada Data Guru' ?>
          </div>
          <div class="empty-desc">
            <?= $q !== ''
              ? 'Tidak ada data guru yang cocok dengan kata kunci "' . e($q) . '".'
              : 'Belum ada data guru di dalam basis data. Klik tombol "Tambah Guru" untuk memasukkan data pertama.' ?>
          </div>
          <?php if ($q !== ''): ?>
            <a href="guru.php" class="btn btn-secondary btn-sm">Tampilkan Semua Data</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <!-- Desktop Table View -->
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th class="col-kode">KODE GURU</th>
                <th class="col-nama">NAMA</th>
                <th class="col-foto">FOTO GURU</th>
                <th class="col-aksi">AKSI</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($guruList as $idx => $g): ?>
                <tr>
                  <td class="col-no"><?= $startItem + $idx ?></td>
                  <td class="col-kode">
                    <a href="detail_guru.php?kode=<?= rawurlencode($g['kode_guru']) ?>" style="color: var(--primary-blue); text-decoration: none; font-weight: 600;">
                      <?= e($g['kode_guru']) ?>
                    </a>
                  </td>
                  <td class="col-nama">
                    <a href="detail_guru.php?kode=<?= rawurlencode($g['kode_guru']) ?>" style="color: inherit; text-decoration: none;">
                      <?= e($g['nama']) ?>
                    </a>
                  </td>
                  <td class="col-foto">
                    <?php if (!empty($g['has_photo'])): ?>
                      <img src="index.php?page=photo&kode=<?= rawurlencode($g['kode_guru']) ?>" alt="Foto <?= e($g['nama']) ?>" class="teacher-photo" loading="lazy">
                    <?php else: ?>
                      <div class="teacher-photo-placeholder" title="Tidak ada foto">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                          <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="col-aksi">
                    <div class="action-group">
                      <button type="button" class="btn btn-primary btn-sm btn-edit-guru"
                              data-kode="<?= e($g['kode_guru']) ?>"
                              data-nama="<?= e($g['nama']) ?>"
                              data-has-photo="<?= !empty($g['has_photo']) ? '1' : '0' ?>"
                              title="Edit Guru">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-hapus-guru"
                              data-kode="<?= e($g['kode_guru']) ?>"
                              data-nama="<?= e($g['nama']) ?>"
                              title="Hapus Guru">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <polyline points="3 6 5 6 21 6"></polyline>
                          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile View (Android / Handphone) -->
        <div class="mobile-card-list">
          <?php foreach ($guruList as $g): ?>
            <div class="mobile-teacher-card">
              <div class="mobile-card-left">
                <?php if (!empty($g['has_photo'])): ?>
                  <img src="index.php?page=photo&kode=<?= rawurlencode($g['kode_guru']) ?>" alt="Foto <?= e($g['nama']) ?>" class="teacher-photo" style="width: 46px; height: 46px;">
                <?php else: ?>
                  <div class="teacher-photo-placeholder" style="width: 46px; height: 46px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                  </div>
                <?php endif; ?>
                <div class="mobile-card-info">
                  <div class="mobile-kode"><?= e($g['kode_guru']) ?></div>
                  <div class="mobile-nama"><?= e($g['nama']) ?></div>
                </div>
              </div>
              <div class="action-group">
                <button type="button" class="btn btn-primary btn-icon-only btn-edit-guru"
                        data-kode="<?= e($g['kode_guru']) ?>"
                        data-nama="<?= e($g['nama']) ?>"
                        data-has-photo="<?= !empty($g['has_photo']) ? '1' : '0' ?>"
                        aria-label="Edit Guru">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button type="button" class="btn btn-danger btn-icon-only btn-hapus-guru"
                        data-kode="<?= e($g['kode_guru']) ?>"
                        data-nama="<?= e($g['nama']) ?>"
                        aria-label="Hapus Guru">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="table-footer">
          <div>
            Menampilkan <?= $startItem ?> - <?= $endItem ?> dari <?= $totalGuru ?> data
          </div>
          <div class="pagination">
            <a href="guru.php?<?= http_build_query(array_merge($_GET, ['p' => max(1, $pageNo - 1)])) ?>"
               class="page-btn <?= $pageNo <= 1 ? 'disabled' : '' ?>"
               aria-label="Halaman Sebelumnya">&lt;</a>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="guru.php?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"
                 class="page-btn <?= $i === $pageNo ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <a href="guru.php?<?= http_build_query(array_merge($_GET, ['p' => min($totalPages, $pageNo + 1)])) ?>"
               class="page-btn <?= $pageNo >= $totalPages ? 'disabled' : '' ?>"
               aria-label="Halaman Berikutnya">&gt;</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php
    render_modals();
    render_footer();
}

/**
 * Render Modals: Tambah Guru, Edit Guru, dan Hapus Guru
 */
function render_modals(): void
{
    $token = csrf_token();
    ?>
    <!-- 1. Modal Tambah Guru -->
    <div class="modal-backdrop" id="modalTambah" role="dialog" aria-modal="true" aria-labelledby="modalTambahTitle">
      <div class="modal-dialog">
        <div class="modal-header">
          <h3 class="modal-title" id="modalTambahTitle">Tambah Guru</h3>
          <button type="button" class="modal-close-btn" data-close-modal aria-label="Tutup">&times;</button>
        </div>
        <form method="POST" action="guru.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label" for="tambahKodeGuru">KODE GURU <span class="req">*</span></label>
              <input type="text" name="kode_guru" id="tambahKodeGuru" class="form-control" placeholder="Contoh: G006" required maxlength="20" autofocus>
            </div>

            <div class="form-group">
              <label class="form-label" for="tambahNama">NAMA <span class="req">*</span></label>
              <input type="text" name="nama" id="tambahNama" class="form-control" placeholder="Masukkan nama guru" required maxlength="100">
            </div>

            <div class="form-group">
              <label class="form-label">FOTO GURU</label>
              <div class="file-dropzone" id="tambahDropzone">
                <input type="file" name="fotoguru" id="tambahFotoInput" accept="image/jpeg,image/png,image/webp">
                <div class="dropzone-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                  </svg>
                </div>
                <div class="dropzone-text">Klik untuk memilih gambar</div>
                <div class="dropzone-hint">Format: JPG, PNG, WebP (Maks. 3 MB)</div>
              </div>
              <div class="preview-container" id="tambahPhotoPreview" style="display: none;">
                <img src="" alt="Pratinjau Foto" class="preview-thumb">
                <span class="file-name" style="font-size: 13px; color: var(--text-secondary);"></span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 2. Modal Edit Guru -->
    <div class="modal-backdrop" id="modalEdit" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
      <div class="modal-dialog">
        <div class="modal-header">
          <h3 class="modal-title" id="modalEditTitle">Edit Guru</h3>
          <button type="button" class="modal-close-btn" data-close-modal aria-label="Tutup">&times;</button>
        </div>
        <form method="POST" action="guru.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
          <input type="hidden" name="kode_guru" id="editHiddenKodeGuru" value="">
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label" for="editKodeGuru">KODE GURU</label>
              <input type="text" id="editKodeGuru" class="form-control" disabled readonly title="Primary Key tidak dapat diubah">
            </div>

            <div class="form-group">
              <label class="form-label" for="editNama">NAMA <span class="req">*</span></label>
              <input type="text" name="nama" id="editNama" class="form-control" required maxlength="100">
            </div>

            <div class="form-group">
              <label class="form-label">FOTO GURU</label>
              <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                <img id="editCurrentPhoto" src="" alt="Foto Guru" class="preview-thumb">
                <label class="btn btn-secondary btn-sm" style="cursor: pointer;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                  </svg>
                  Pilih Gambar
                  <input type="file" name="fotoguru" id="editFotoInput" hidden accept="image/jpeg,image/png,image/webp">
                </label>
              </div>
              <div class="preview-container" id="editNewPhotoPreview" style="display: none;">
                <img src="" alt="Foto Baru" class="preview-thumb">
                <span class="file-name" style="font-size: 13px; color: var(--text-secondary);"></span>
              </div>
              <div class="dropzone-hint" style="margin-top: 6px;">
                Kosongkan jika tidak ingin mengubah foto<br>
                Format: JPG, PNG, WebP (Maks. 3 MB)
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
            <button type="submit" class="btn btn-primary">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- 3. Modal Hapus Data Guru -->
    <div class="modal-backdrop" id="modalHapus" role="dialog" aria-modal="true" aria-labelledby="modalHapusTitle">
      <div class="modal-dialog">
        <form method="POST" action="guru.php">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
          <input type="hidden" name="kode_guru" id="hapusHiddenKode" value="">
          <div class="modal-body delete-dialog">
            <div class="delete-icon-badge">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </div>
            <h3 class="delete-title" id="modalHapusTitle">Hapus Data Guru?</h3>
            <p class="delete-desc">
              Apakah Anda yakin ingin menghapus data guru <span id="hapusNamaPlaceholder" style="font-weight: 600; color: var(--text-main);"></span>?<br>
              Data yang sudah dihapus tidak dapat dikembalikan.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center;">
              <button type="button" class="btn btn-secondary" data-close-modal>Batal</button>
              <button type="submit" class="btn btn-danger">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Hapus
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php
}
