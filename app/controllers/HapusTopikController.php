<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    $nip_dosen = $_SESSION['username'];

    if (empty($id)) {
        $_SESSION['swal_error'] = 'ID topik tidak valid!';
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }

    try {
        // Delete topic (cascade will delete interests)
        $stmt = $pdo->prepare("DELETE FROM topik_penelitian WHERE id = :id AND REPLACE(nip_dosen, ' ', '') = REPLACE(:nip_dosen, ' ', '')");
        $stmt->execute([
            ':id' => $id,
            ':nip_dosen' => $nip_dosen
        ]);

        $_SESSION['swal_success'] = 'Topik penelitian berhasil dihapus!';
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['swal_error'] = 'Gagal menghapus topik: ' . $e->getMessage();
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }
}
