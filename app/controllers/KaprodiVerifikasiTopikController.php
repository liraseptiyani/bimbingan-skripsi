<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Strict Kaprodi check
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak! Halaman ini hanya untuk Kaprodi.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $statusInput = strtolower(trim($_POST['status'] ?? ''));

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID topik tidak valid!']);
        exit;
    }

    $statusMap = [
        'disetujui' => 'disetujui',
        'ditolak' => 'ditolak',
        'menunggu' => 'menunggu'
    ];

    if (!isset($statusMap[$statusInput])) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid!']);
        exit;
    }

    $status = $statusMap[$statusInput];

    try {
        // Update status of the research topic
        $stmt = $pdo->prepare("UPDATE topik_penelitian SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);

        echo json_encode(['success' => true]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);
exit;
