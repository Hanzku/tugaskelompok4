<?php
declare(strict_types=1);

/**
 * Validasi dan Sanitasi Data Modul Guru
 * Sesuai ERD SMK Malaka: KODE_GURU (PK), NAMA, FOTOGURU
 */

function sanitize_text(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }
    return trim($value);
}

function is_valid_utf8(string $string): bool
{
    return mb_check_encoding($string, 'UTF-8');
}

function has_control_characters(string $string): bool
{
    return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $string) === 1;
}

/**
 * Validasi data guru (KODE_GURU dan NAMA).
 *
 * @param array $input Data mentah dari $_POST
 * @param bool $isEdit Apakah dalam mode edit (pada mode edit KODE_GURU tidak boleh diubah)
 * @return array [array $data, array $errors]
 */
function validate_guru_input(array $input, bool $isEdit = false): array
{
    $errors = [];
    $data = [];

    // 1. Validasi KODE_GURU
    $rawKode = $input['kode_guru'] ?? '';
    if (!is_string($rawKode)) {
        $errors['kode_guru'] = 'Kode guru harus berupa teks.';
        $rawKode = '';
    }
    if (has_control_characters($rawKode)) {
        $errors['kode_guru'] = 'Kode guru mengandung karakter tidak sah.';
    }
    $kode = strtoupper(sanitize_text($rawKode));

    if ($kode === '') {
        $errors['kode_guru'] = 'Kode guru wajib diisi.';
    } elseif (strlen($kode) > 20) {
        $errors['kode_guru'] = 'Kode guru maksimal 20 karakter.';
    } elseif (preg_match('/\s/', $kode)) {
        $errors['kode_guru'] = 'Kode guru tidak boleh mengandung spasi.';
    } elseif (preg_match('/^[A-Z0-9_-]+$/', $kode) !== 1) {
        $errors['kode_guru'] = 'Kode guru hanya boleh berisi huruf, angka, tanda minus (-), dan garis bawah (_).';
    }
    $data['kode_guru'] = $kode;

    // 2. Validasi NAMA
    $rawNama = $input['nama'] ?? '';
    if (!is_string($rawNama)) {
        $errors['nama'] = 'Nama guru harus berupa teks.';
        $rawNama = '';
    }
    if (has_control_characters($rawNama)) {
        $errors['nama'] = 'Nama guru mengandung karakter kontrol terlarang.';
    }
    if (!is_valid_utf8($rawNama)) {
        $errors['nama'] = 'Nama guru mengandung karakter yang tidak valid.';
    }
    $nama = sanitize_text($rawNama);

    if ($nama === '' && !isset($errors['nama'])) {
        $errors['nama'] = 'Nama guru wajib diisi.';
    } elseif (!isset($errors['nama']) && mb_strlen($nama) < 2) {
        $errors['nama'] = 'Nama guru minimal 2 karakter.';
    } elseif (!isset($errors['nama']) && mb_strlen($nama) > 100) {
        $errors['nama'] = 'Nama guru maksimal 100 karakter.';
    }
    $data['nama'] = $nama;

    return [$data, $errors];
}

/**
 * Validasi berkas foto yang diunggah.
 *
 * @param array|null $file $_FILES['fotoguru']
 * @param bool $isRequired Apakah foto wajib (biasanya untuk tambah data jika diwajibkan)
 * @return array [?string $photoBytes, ?string $error]
 */
function validate_photo_file(?array $file, bool $isRequired = false): array
{
    if ($file === null || !isset($file['error'])) {
        if ($isRequired) {
            return [null, 'Foto guru wajib diunggah.'];
        }
        return [null, null];
    }

    $errorCode = $file['error'];

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        if ($isRequired) {
            return [null, 'Foto guru wajib diunggah.'];
        }
        return [null, null];
    }

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'Ukuran foto melebihi batas maksimal server.'];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return [null, 'Gagal mengunggah foto (kode error: ' . $errorCode . ').'];
    }

    // Batas ukuran 3 MB
    $maxBytes = 3 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        return [null, 'Berkas foto kosong atau tidak valid.'];
    }
    if ($size > $maxBytes) {
        return [null, 'Ukuran foto tidak boleh lebih dari 3 MB.'];
    }

    // Validasi ekstensi nama file asli
    $origName = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        return [null, 'Format berkas harus JPG, JPEG, PNG, atau WebP.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || (!is_uploaded_file($tmpPath) && php_sapi_name() !== 'cli')) {
        return [null, 'Unggahan berkas foto tidak sah.'];
    }

    // Validasi MIME type dan integritas gambar
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
        return [null, 'Berkas yang diunggah bukan format gambar yang valid.'];
    }

    $allowedMimes = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    $imageType = $imageInfo[2] ?? 0;
    if (!isset($allowedMimes[$imageType])) {
        return [null, 'Tipe gambar tidak didukung. Gunakan JPG, PNG, atau WebP.'];
    }

    // Baca bytes gambar
    $photoBytes = file_get_contents($tmpPath);
    if ($photoBytes === false || strlen($photoBytes) === 0) {
        return [null, 'Gagal membaca data gambar yang diunggah.'];
    }

    return [$photoBytes, null];
}
