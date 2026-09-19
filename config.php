<?php

$database = require __DIR__ . '/config/environment.php';
$mysqli = new mysqli(
    $database['host'],
    $database['user'],
    $database['pass'],
    $database['name'],
    (int)$database['port']
);

if ($mysqli->connect_errno) {
    http_response_code(500);
    exit('Database connection failed. Check config/environment.php or the DB_* environment variables.');
}

$mysqli->set_charset('utf8mb4');
