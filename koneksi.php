<?php
// config/koneksi.php

$host = "localhost";
$user = "root";
$pass = ""; // Sesuaikan dengan password database Anda
$db   = "db_ujian_smanda";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit();
}