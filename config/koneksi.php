<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Jakarta');

$host = "ep-twilight-base-ao8gz75j-pooler.c-2.ap-southeast-1.aws.neon.tech";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_umw8ZUzN5Fef";

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $start = microtime(true);

    $pdo = new PDO($dsn, $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $lama = round(microtime(true) - $start, 3);

    error_log("Koneksi PostgreSQL: {$lama} detik");

} catch (PDOException $e) {
    die($e->getMessage());
}