<?php
declare(strict_types=1);

/**
 * Operasi Data Entitas Guru (Kelompok 4)
 * Menjalankan operasi CRUD pada tabel 'guru' dan mematuhi batasan ERD.
 */

require_once __DIR__ . '/db.php';

function escape_like(string $pattern): string
{
    return strtr($pattern, [
        '=' => '==',
        '%' => '=%',
        '_' => '=_',
    ]);
}

/**
 * Mengambil daftar guru dengan pencarian dan pagination.
 *
 * @param string|null $search Pencarian berdasarkan kode_guru atau nama
 * @param int $page Halaman aktif (mulai dari 1)
 * @param int $perPage Jumlah data per halaman
 * @return array
 */
function guru_get_all(?string $search = null, int $page = 1, int $perPage = 10): array
{
    $pdo = db();
    $sql = 'SELECT kode_guru, nama,
            CASE WHEN fotoguru IS NOT NULL AND length(fotoguru) > 0 THEN 1 ELSE 0 END AS has_photo
            FROM guru';
    $params = [];

    $trimmed = $search !== null ? trim($search) : '';
    if ($trimmed !== '') {
        $sql .= " WHERE kode_guru LIKE ? ESCAPE '=' OR nama LIKE ? ESCAPE '='";
        $escaped = '%' . escape_like($trimmed) . '%';
        $params[] = $escaped;
        $params[] = $escaped;
    }

    $sql .= ' ORDER BY kode_guru ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Menghitung total data guru (dengan atau tanpa filter pencarian).
 */
function guru_count(?string $search = null): int
{
    $pdo = db();
    $sql = 'SELECT COUNT(*) FROM guru';
    $params = [];

    $trimmed = $search !== null ? trim($search) : '';
    if ($trimmed !== '') {
        $sql .= " WHERE kode_guru LIKE ? ESCAPE '=' OR nama LIKE ? ESCAPE '='";
        $escaped = '%' . escape_like($trimmed) . '%';
        $params[] = $escaped;
        $params[] = $escaped;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Mencari satu guru berdasarkan KODE_GURU.
 */
function guru_find(string $kode): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT kode_guru, nama,
        CASE WHEN fotoguru IS NOT NULL AND length(fotoguru) > 0 THEN 1 ELSE 0 END AS has_photo
        FROM guru WHERE kode_guru = ?');
    $stmt->execute([$kode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mengambil binary foto guru untuk respon gambar langsung.
 */
function guru_get_photo(string $kode): ?string
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT fotoguru FROM guru WHERE kode_guru = ?');
    $stmt->execute([$kode]);
    $photo = $stmt->fetchColumn();
    if (is_string($photo) && strlen($photo) > 0) {
        return $photo;
    }
    return null;
}

/**
 * Menambahkan data guru baru.
 *
 * @param string $kode KODE_GURU
 * @param string $nama NAMA
 * @param string|null $photoBytes Isi berkas foto
 * @return array ['success' => bool, 'error' => ?string]
 */
function guru_create(string $kode, string $nama, ?string $photoBytes): array
{
    $pdo = db();

    // 1. Cek duplikasi primary key
    $check = $pdo->prepare('SELECT 1 FROM guru WHERE kode_guru = ?');
    $check->execute([$kode]);
    if ($check->fetchColumn() !== false) {
        return [
            'success' => false,
            'error' => 'Kode guru "' . $kode . '" sudah digunakan. Silakan gunakan kode lain.',
        ];
    }

    // 2. Simpan data baru
    try {
        $stmt = $pdo->prepare('INSERT INTO guru (kode_guru, nama, fotoguru) VALUES (?, ?, ?)');
        $stmt->bindValue(1, $kode, PDO::PARAM_STR);
        $stmt->bindValue(2, $nama, PDO::PARAM_STR);
        if ($photoBytes !== null) {
            $stmt->bindValue(3, $photoBytes, PDO::PARAM_LOB);
        } else {
            $stmt->bindValue(3, null, PDO::PARAM_NULL);
        }
        $stmt->execute();
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        error_log('[guru_create error] ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Gagal menyimpan data guru ke database: ' . ($e->getCode() === '23000' ? 'Kode guru sudah digunakan.' : 'Kesalahan basis data.'),
        ];
    }
}

/**
 * Memperbarui data guru.
 *
 * @param string $kode KODE_GURU (tidak boleh berubah karena PK)
 * @param string $nama NAMA baru
 * @param string|null $newPhotoBytes Foto baru (jika null, pertahankan foto lama)
 * @return array ['success' => bool, 'error' => ?string]
 */
function guru_update(string $kode, string $nama, ?string $newPhotoBytes): array
{
    $pdo = db();

    // Pastikan data yang diedit ada
    $existing = guru_find($kode);
    if ($existing === null) {
        return ['success' => false, 'error' => 'Data guru tidak ditemukan.'];
    }

    try {
        if ($newPhotoBytes !== null) {
            // Pengguna mengunggah foto baru pengganti
            $stmt = $pdo->prepare('UPDATE guru SET nama = ?, fotoguru = ? WHERE kode_guru = ?');
            $stmt->bindValue(1, $nama, PDO::PARAM_STR);
            $stmt->bindValue(2, $newPhotoBytes, PDO::PARAM_LOB);
            $stmt->bindValue(3, $kode, PDO::PARAM_STR);
        } else {
            // Foto lama tetap dipertahankan
            $stmt = $pdo->prepare('UPDATE guru SET nama = ? WHERE kode_guru = ?');
            $stmt->bindValue(1, $nama, PDO::PARAM_STR);
            $stmt->bindValue(2, $kode, PDO::PARAM_STR);
        }
        $stmt->execute();
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        error_log('[guru_update error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'Gagal memperbarui data guru pada database.'];
    }
}

/**
 * Menghapus data guru dengan validasi foreign key relasi Diampu.
 *
 * @param string $kode KODE_GURU
 * @return array ['success' => bool, 'error' => ?string]
 */
function guru_delete(string $kode): array
{
    $pdo = db();

    $existing = guru_find($kode);
    if ($existing === null) {
        return ['success' => false, 'error' => 'Data guru tidak ditemukan atau sudah dihapus.'];
    }

    // Periksa apakah guru sedang digunakan pada relasi Diampu
    try {
        $checkRelasi = $pdo->prepare('SELECT COUNT(*) FROM diampu WHERE kode_guru = ?');
        $checkRelasi->execute([$kode]);
        $relasiCount = (int) $checkRelasi->fetchColumn();
        if ($relasiCount > 0) {
            return [
                'success' => false,
                'error' => 'Data guru "' . $kode . '" tidak dapat dihapus karena masih terdaftar dalam relasi Diampu (' . $relasiCount . ' jadwal pelajaran). Hapus atau ubah relasi jadwal terlebih dahulu.',
            ];
        }
    } catch (PDOException) {
        // Jika tabel diampu belum dibuat, lanjutkan penghapusan biasa
    }

    // Jalankan penghapusan
    try {
        $stmt = $pdo->prepare('DELETE FROM guru WHERE kode_guru = ?');
        $stmt->execute([$kode]);
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        error_log('[guru_delete error] ' . $e->getMessage());
        // Tangani jika tertolak foreign key oleh constraint database
        if ($e->getCode() === '23000') {
            return [
                'success' => false,
                'error' => 'Data guru tidak dapat dihapus karena masih dirujuk oleh tabel relasi lain.',
            ];
        }
        return ['success' => false, 'error' => 'Gagal menghapus data guru dari database.'];
    }
}

/**
 * Statistik ringkasan untuk kartu dashboard.
 */
function dashboard_stats(): array
{
    $pdo = db();
    $stats = [
        'guru' => 0,
        'murid' => 0,
        'pelajaran' => 0,
    ];

    try {
        $stats['guru'] = (int) $pdo->query('SELECT COUNT(*) FROM guru')->fetchColumn();
    } catch (PDOException) {}

    try {
        $stats['murid'] = (int) $pdo->query('SELECT COUNT(*) FROM murid')->fetchColumn();
    } catch (PDOException) {}

    try {
        $stats['pelajaran'] = (int) $pdo->query('SELECT COUNT(*) FROM pelajaran')->fetchColumn();
    } catch (PDOException) {}

    return $stats;
}
