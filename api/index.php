<?php
declare(strict_types=1);

$routes = [
    'index.php' => __DIR__ . '/../index.php',
    'guru.php' => __DIR__ . '/../guru.php',
    'detail_guru.php' => __DIR__ . '/../detail_guru.php',
];

$script = (string) ($_GET['script'] ?? 'index.php');
if (!isset($routes[$script])) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

require $routes[$script];
