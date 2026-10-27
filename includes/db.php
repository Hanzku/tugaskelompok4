<?php
declare(strict_types=1);

/**
 * Koneksi Database PDO untuk Sistem Informasi Sederhana SMK Malaka
 * Mendukung MySQL/MariaDB (dengan TLS/Aiven/XAMPP) serta SQLite untuk lingkungan pengujian.
 */

function env_val(string $key, ?string $default = null): ?string
{
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }
    return $default;
}

function resolve_file_path(string $path): string
{
    $trimmed = trim($path);
    if ($trimmed === '') {
        return '';
    }
    if ($trimmed[0] === '/' || $trimmed[0] === '\\' || preg_match('/^[A-Za-z]:[\\\\\/]/', $trimmed) === 1) {
        return $trimmed;
    }
    return __DIR__ . '/../' . ltrim($trimmed, './\\');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // 1. Ambil file konfigurasi
    $configFile = __DIR__ . '/../config/database.php';
    if (!is_file($configFile)) {
        $configFile = __DIR__ . '/../config/database.example.php';
    }
    $cfg = is_file($configFile) ? (require $configFile) : [];
    if (!is_array($cfg)) {
        $cfg = [];
    }

    // 2. Terapkan variabel lingkungan jika tersedia
    $driver = env_val('DB_DRIVER', env_val('DB_CONNECTION', (string) ($cfg['driver'] ?? 'mysql')));
    $driver = strtolower(trim($driver));

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($driver === 'sqlite') {
        $sqlitePath = env_val('DB_DATABASE', (string) ($cfg['name'] ?? ''));
        if ($sqlitePath === '' || $sqlitePath === 'sia_malaka_kelompok4' || $sqlitePath === 'defaultdb') {
            $sqlitePath = __DIR__ . '/../database/database.sqlite';
        } else {
            $sqlitePath = resolve_file_path($sqlitePath);
        }

        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        init_sqlite_schema_if_needed($pdo);
        return $pdo;
    }

    // Default: MySQL / MariaDB
    $host = env_val('DB_HOST', (string) ($cfg['host'] ?? '127.0.0.1'));
    $port = env_val('DB_PORT', (string) ($cfg['port'] ?? '3306'));
    $name = env_val('DB_NAME', env_val('DB_DATABASE', (string) ($cfg['name'] ?? 'defaultdb')));
    $user = env_val('DB_USER', env_val('DB_USERNAME', (string) ($cfg['user'] ?? 'avnadmin')));
    $pass = env_val('DB_PASSWORD', (string) ($cfg['password'] ?? ''));

    $sslSetting = env_val('DB_SSL');
    $ssl = $sslSetting !== null ? in_array($sslSetting, ['1', 'true', 'on', 'yes'], true) : !empty($cfg['ssl']);

    if ($ssl) {
        $caConfig = env_val('DB_SSL_CA', (string) ($cfg['ssl_ca'] ?? ''));
        $caResolved = resolve_file_path($caConfig);
        if ($caResolved !== '' && is_file($caResolved) && is_readable($caResolved)) {
            $sslCaKey = defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : (defined('PDO::MYSQL_ATTR_SSL_CA') ? constant('PDO::MYSQL_ATTR_SSL_CA') : null);
            if ($sslCaKey !== null) {
                $options[$sslCaKey] = $caResolved;
            }
            $sslVerifyKey = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') : null);
            if ($sslVerifyKey !== null) {
                $options[$sslVerifyKey] = true;
            }
        }
    }

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Catat error teknis secara aman ke log server (tanpa mengekspos kredensial ke publik)
        error_log(sprintf('[Database Error] Host: %s, Database: %s, Message: %s', $host, $name, $e->getMessage()));

        // Jika di localhost dan koneksi ditolak (server MySQL lokal belum aktif),
        // fallback secara otomatis ke SQLite lokal agar aplikasi tetap berfungsi dengan basis data nyata.
        if (in_array($host, ['127.0.0.1', 'localhost'], true) && !is_file(__DIR__ . '/../config/database.php')) {
            $sqlitePath = __DIR__ . '/../database/database.sqlite';
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            init_sqlite_schema_if_needed($pdo);
            return $pdo;
        }

        throw $e;
    }
}

/**
 * Inisialisasi skema SQLite secara otomatis untuk lingkungan tes mandiri.
 */
function init_sqlite_schema_if_needed(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS murid (
            nis VARCHAR(20) NOT NULL PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            kelas VARCHAR(30) NOT NULL,
            foto BLOB NULL
        );
        CREATE TABLE IF NOT EXISTS pelajaran (
            kd_pelajaran VARCHAR(20) NOT NULL PRIMARY KEY,
            nama_pelajaran VARCHAR(100) NOT NULL,
            bobot DECIMAL(5,2) NOT NULL CHECK (bobot BETWEEN 1 AND 100)
        );
        CREATE TABLE IF NOT EXISTS guru (
            kode_guru VARCHAR(20) NOT NULL PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            fotoguru BLOB NULL
        );
        CREATE TABLE IF NOT EXISTS mempelajari (
            nomornilai INTEGER PRIMARY KEY AUTOINCREMENT,
            nis VARCHAR(20) NOT NULL,
            kd_pelajaran VARCHAR(20) NOT NULL,
            nilai DECIMAL(5,2) NOT NULL CHECK (nilai BETWEEN 0 AND 100),
            UNIQUE (nis, kd_pelajaran),
            FOREIGN KEY (nis) REFERENCES murid (nis) ON UPDATE CASCADE ON DELETE RESTRICT,
            FOREIGN KEY (kd_pelajaran) REFERENCES pelajaran (kd_pelajaran) ON UPDATE CASCADE ON DELETE RESTRICT
        );
        CREATE TABLE IF NOT EXISTS diampu (
            kode_guru VARCHAR(20) NOT NULL,
            kd_pelajaran VARCHAR(20) NOT NULL,
            hari VARCHAR(20) NOT NULL,
            PRIMARY KEY (kode_guru, kd_pelajaran, hari),
            FOREIGN KEY (kode_guru) REFERENCES guru (kode_guru) ON UPDATE CASCADE ON DELETE RESTRICT,
            FOREIGN KEY (kd_pelajaran) REFERENCES pelajaran (kd_pelajaran) ON UPDATE CASCADE ON DELETE RESTRICT
        );
    ');
}
