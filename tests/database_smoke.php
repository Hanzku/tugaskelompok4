<?php
declare(strict_types=1);

/**
 * Smoke Test Database Real - Modul Guru (Kelompok 4)
 * Menguji operasi CRUD, Primary Key, Foreign Key Diampu, Foto BLOB, dan Pencarian.
 */

// Gunakan SQLite in-memory / temporary test database jika MySQL belum dikonfigurasi secara lokal
if (getenv('DB_DRIVER') === false && !is_file(__DIR__ . '/../config/database.php')) {
    putenv('DB_DRIVER=sqlite');
    putenv('DB_DATABASE=' . __DIR__ . '/test_guru.sqlite');
}

require_once __DIR__ . '/../includes/app.php';

$testsPassed = 0;
function smoke_assert(bool $condition, string $message): void
{
    global $testsPassed;
    if (!$condition) {
        throw new RuntimeException('SMOKE TEST GAGAL: ' . $message);
    }
    $testsPassed++;
}

echo "=== Memulai Uji Database Smoke Test Modul Guru ===\n";

$pdo = db();
smoke_assert($pdo instanceof PDO, 'Koneksi ke database gagal.');

// Setup data dummy untuk foto (1x1 pixel PNG valid)
$dummyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$testKode = 'TEST' . strtoupper(bin2hex(random_bytes(3)));
$testNama = 'Guru Pengujian Otomatis';

// 1. Uji Tambah Guru (Create)
$createRes = guru_create($testKode, $testNama, $dummyPng);
smoke_assert($createRes['success'] === true, 'Gagal menambahkan guru baru: ' . ($createRes['error'] ?? ''));

// 2. Uji Baca Guru (Read & Find)
$guru = guru_find($testKode);
smoke_assert($guru !== null, 'Data guru yang baru dibuat tidak ditemukan.');
smoke_assert($guru['kode_guru'] === $testKode, 'KODE_GURU tidak sesuai.');
smoke_assert($guru['nama'] === $testNama, 'NAMA guru tidak sesuai.');
smoke_assert($guru['has_photo'] == 1, 'Status has_photo tidak bernilai 1.');

// 3. Uji Pengambilan Binary Foto
$photoBytes = guru_get_photo($testKode);
smoke_assert($photoBytes === $dummyPng, 'Binary foto yang diambil dari basis data tidak sama dengan yang diunggah.');

// 4. Uji Penolakan KODE_GURU Duplikat (Primary Key Uniqueness)
$dupRes = guru_create($testKode, 'Guru Duplikat', null);
smoke_assert($dupRes['success'] === false, 'Kode guru duplikat berhasil disimpan (seharusnya ditolak).');
smoke_assert(str_contains($dupRes['error'] ?? '', 'sudah digunakan'), 'Pesan error duplikasi tidak sesuai.');

// 5. Uji Ubah Nama Guru (Update)
$updatedNama = 'Guru Pengujian Diperbarui';
$updateRes = guru_update($testKode, $updatedNama, null); // null photo: pertahankan foto lama
smoke_assert($updateRes['success'] === true, 'Gagal memperbarui nama guru.');

$guruAfterUpdate = guru_find($testKode);
smoke_assert($guruAfterUpdate['nama'] === $updatedNama, 'Nama baru belum tersimpan di basis data.');
smoke_assert($guruAfterUpdate['has_photo'] == 1, 'Foto lama hilang saat update dengan foto null.');

// 6. Uji Ganti Foto Guru
$dummyPng2 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$updatePhotoRes = guru_update($testKode, $updatedNama, $dummyPng2);
smoke_assert($updatePhotoRes['success'] === true, 'Gagal memperbarui foto guru.');
$photoBytes2 = guru_get_photo($testKode);
smoke_assert($photoBytes2 === $dummyPng2, 'Foto pengganti tidak sesuai dengan berkas baru.');

// 7. Uji Pencarian (Search by KODE_GURU dan NAMA)
$searchByKode = guru_get_all($testKode);
smoke_assert(count($searchByKode) >= 1 && $searchByKode[0]['kode_guru'] === $testKode, 'Pencarian berdasarkan KODE_GURU gagal.');

$searchByNama = guru_get_all('Pengujian');
smoke_assert(count($searchByNama) >= 1, 'Pencarian berdasarkan potongan NAMA gagal.');

$searchNonExistent = guru_get_all('TIDAK_MUNGKIN_ADA_KODE_INI_XYZ_999');
smoke_assert(count($searchNonExistent) === 0, 'Pencarian data non-eksisten mengembalikan hasil.');

// 8. Uji Relasi Diampu & Pencegahan Penghapusan Foreign Key
$pelajaranKode = 'PEL' . strtoupper(bin2hex(random_bytes(3)));
try {
    $pdo->prepare('INSERT INTO pelajaran (kd_pelajaran, nama_pelajaran, bobot) VALUES (?, ?, ?)')
        ->execute([$pelajaranKode, 'Pelajaran Uji Foreign Key', '10.00']);
    $pdo->prepare('INSERT INTO diampu (kode_guru, kd_pelajaran, hari) VALUES (?, ?, ?)')
        ->execute([$testKode, $pelajaranKode, 'Senin']);
} catch (PDOException $e) {
    echo "Info: Tabel pelajaran atau diampu belum siap di DB, lewati uji relasi spesifik.\n";
}

// Coba hapus guru yang sedang mengampu pelajaran -> HARUS DITOLAK
$delBlocked = guru_delete($testKode);
smoke_assert($delBlocked['success'] === false, 'Guru yang masih ada di jadwal relasi Diampu berhasil dihapus (seharusnya dicegah).');
smoke_assert(str_contains($delBlocked['error'] ?? '', 'Diampu') || str_contains($delBlocked['error'] ?? '', 'relasi'), 'Pesan pencegahan foreign key tidak sesuai.');

// Hapus relasi diampu terlebih dahulu
try {
    $pdo->prepare('DELETE FROM diampu WHERE kode_guru = ?')->execute([$testKode]);
    $pdo->prepare('DELETE FROM pelajaran WHERE kd_pelajaran = ?')->execute([$pelajaranKode]);
} catch (PDOException) {}

// 9. Uji Hapus Guru setelah Relasi Bersih
$delSuccess = guru_delete($testKode);
smoke_assert($delSuccess['success'] === true, 'Gagal menghapus guru yang sudah tidak memiliki relasi: ' . ($delSuccess['error'] ?? ''));

// Verifikasi guru benar-benar telah terhapus
$guruDeleted = guru_find($testKode);
smoke_assert($guruDeleted === null, 'Guru masih ada di basis data setelah dihapus.');

// Tutup koneksi agar file SQLite tidak terkunci di Windows
$pdo = null;

// Bersihkan file SQLite pengujian jika ada
if (is_file(__DIR__ . '/test_guru.sqlite')) {
    @unlink(__DIR__ . '/test_guru.sqlite');
}

echo "SUKSES: $testsPassed uji database smoke test berhasil dilewati tanpa kendala.\n";
