<?php
declare(strict_types=1);

// Konfigurasi Database SIA SMK Malaka - Kelompok 4 (Aiven MySQL Cloud)
// Salin berkas ini ke database.php untuk konfigurasi server lokal/produksi.
// Jangan commit berkas database.php yang berisi kata sandi nyata ke repositori Git.

return [
    'driver' => 'mysql',
    'host' => 'mysql-858478d-dyoxo-8ff0.l.aivencloud.com',
    'port' => '23495',
    'name' => 'defaultdb',
    'user' => 'avnadmin',
    'password' => '', // Isi dengan kata sandi Aiven Anda di berkas config/database.php lokal
    'ssl' => true,
    'ssl_ca' => __DIR__ . '/aiven-ca.pem',
];
