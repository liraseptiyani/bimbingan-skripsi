<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/config/koneksi.php';
    echo "Koneksi ke database berhasil!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "Database Version: " . $version . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
