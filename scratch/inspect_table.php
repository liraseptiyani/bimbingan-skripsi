<?php
require_once dirname(__DIR__) . '/config/koneksi.php';

try {
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'pengajuan_judul'
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNS OF pengajuan_judul:\n";
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
