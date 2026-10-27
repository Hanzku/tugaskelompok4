<?php
declare(strict_types=1);

/**
 * Unit Test Validasi Input & Berkas Foto - Modul Guru (Kelompok 4)
 */

require_once __DIR__ . '/../includes/validation.php';

$passed = 0;
function test_assert(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('GAGAL: ' . $message);
    }
    $passed++;
}

echo "=== Memulai Uji Validasi Input Modul Guru ===\n";

// 1. Uji Data Valid Normal
[$data, $errors] = validate_guru_input(['kode_guru' => ' g001 ', 'nama' => '  Budi Santoso, M.Pd.  ']);
test_assert(empty($errors), 'Input valid ditolak: ' . json_encode($errors));
test_assert($data['kode_guru'] === 'G001', 'Normalisasi KODE_GURU ke huruf besar gagal.');
test_assert($data['nama'] === 'Budi Santoso, M.Pd.', 'Trimming NAMA gagal.');

// 2. Uji Penolakan KODE_GURU Tidak Valid
$invalidKodes = [
    '' => 'Kode kosong',
    '   ' => 'Kode hanya spasi',
    'G 001' => 'Kode mengandung spasi tengah',
    'G@001#' => 'Kode mengandung karakter spesial terlarang',
    str_repeat('G', 21) => 'Kode lebih dari 20 karakter',
];
foreach ($invalidKodes as $invKode => $desc) {
    [, $errors] = validate_guru_input(['kode_guru' => $invKode, 'nama' => 'Guru Valid']);
    test_assert(isset($errors['kode_guru']), 'KODE_GURU tidak valid lolos: ' . $desc);
}

// 3. Uji Penolakan NAMA Tidak Valid
$invalidNamas = [
    '' => 'Nama kosong',
    '   ' => 'Nama hanya spasi',
    'A' => 'Nama kurang dari 2 karakter',
    str_repeat('A', 101) => 'Nama lebih dari 100 karakter',
    "Guru\x00Jahat" => 'Nama dengan null byte / control character',
    "\xFF\xFE" => 'Nama bukan UTF-8 valid',
];
foreach ($invalidNamas as $invNama => $desc) {
    [, $errors] = validate_guru_input(['kode_guru' => 'G001', 'nama' => $invNama]);
    test_assert(isset($errors['nama']), 'NAMA tidak valid lolos: ' . $desc);
}

// 4. Uji Validasi Tipe Data Non-String (Array injection)
[, $errors] = validate_guru_input(['kode_guru' => ['array'], 'nama' => ['array']]);
test_assert(isset($errors['kode_guru']) && isset($errors['nama']), 'Input tipe array tidak ditolak.');

// 5. Uji Validasi Berkas Foto
// A. Foto opsional (null atau tidak ada file)
[$bytes, $err] = validate_photo_file(null, false);
test_assert($bytes === null && $err === null, 'Foto null opsional menghasilkan error.');

[$bytes, $err] = validate_photo_file(['error' => UPLOAD_ERR_NO_FILE], false);
test_assert($bytes === null && $err === null, 'UPLOAD_ERR_NO_FILE opsional menghasilkan error.');

// B. Foto melebihi batas 3 MB
$oversizeFile = [
    'error' => UPLOAD_ERR_OK,
    'size' => 4 * 1024 * 1024, // 4 MB
    'name' => 'foto.jpg',
    'tmp_name' => __FILE__,
];
[$bytes, $err] = validate_photo_file($oversizeFile, false);
test_assert($err !== null && str_contains($err, '3 MB'), 'Foto > 3 MB tidak ditolak.');

// C. Format ekstensi terlarang (.exe, .php)
$badExtFile = [
    'error' => UPLOAD_ERR_OK,
    'size' => 1024,
    'name' => 'script.php',
    'tmp_name' => __FILE__,
];
[$bytes, $err] = validate_photo_file($badExtFile, false);
test_assert($err !== null && str_contains($err, 'Format'), 'Berkas non-gambar (.php) tidak ditolak.');

echo "SUKSES: $passed pengujian validasi berhasil dilewati tanpa error.\n";
