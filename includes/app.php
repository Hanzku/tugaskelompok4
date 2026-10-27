<?php
declare(strict_types=1);

/**
 * Bootstrap Aplikasi Sistem Informasi Sederhana SMK Malaka
 * Kelompok 4: Modul Guru
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true,
    ]);
}

// Exception Handler yang aman (mencegah kebocoran kredensial ke publik)
set_exception_handler(static function (Throwable $error): void {
    error_log('[SIA SMK Malaka Error] ' . $error->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gangguan Aplikasi - SMK Malaka</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
  <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 36px; max-width: 500px; text-align: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);">
    <div style="width: 56px; height: 56px; background: #FEE2E2; color: #DC2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
    </div>
    <h2 style="font-size: 20px; font-weight: 700; color: #0F172A; margin-bottom: 8px;">Aplikasi Belum Terhubung</h2>
    <p style="font-size: 14px; color: #64748B; margin-bottom: 24px; line-height: 1.6;">
      Terjadi kendala koneksi ke basis data. Pastikan konfigurasi pada <code>config/database.php</code> atau variabel lingkungan <code>DB_*</code> telah sesuai dan server basis data aktif.
    </p>
    <a href="guru.php" style="display: inline-block; background: #2563EB; color: #FFFFFF; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
      Muat Ulang Halaman
    </a>
  </div>
</body>
</html>';
    exit;
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/guru.php';

// Escape HTML untuk mencegah XSS
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Token CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    if ($token === null || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Notifikasi Flash
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type, // 'success' atau 'error'
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect URL helper
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
