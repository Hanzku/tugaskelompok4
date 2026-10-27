<?php
declare(strict_types=1);

// Konfigurasi Database SIA SMK Malaka - Kelompok 4
// Salin berkas ini ke database.php untuk konfigurasi server lokal/produksi.
// Jangan commit berkas database.php yang berisi kredensial nyata ke repositori Git.

return [
    'driver' => 'mysql', // 'mysql' (default) atau 'sqlite'
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'sia_malaka_kelompok4',
    'user' => 'root',
    'password' => '',
    'ssl' => false,
    'ssl_ca' => __DIR__ . '/aiven-ca.pem',
];
