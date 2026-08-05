<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

$bimbingan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

// Check bimbingan status from database
try {
    $stmtCheck = $pdo->prepare("SELECT status_bimbingan FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmtCheck->execute([':npm' => $npmMhs]);
    $statusBimbingan = $stmtCheck->fetchColumn();
    if ($statusBimbingan === 'selesai') {
        $_SESSION['swal_error'] = 'Anda tidak dapat menghapus bimbingan karena status bimbingan Anda telah selesai/lulus!';
        header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
        exit;
    }
} catch (PDOException $e) {}

if ($bimbingan_id <= 0) {
    $_SESSION['swal_error'] = 'ID bimbingan tidak valid!';
    header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
    exit;
}

try {
    // Verify bimbingan details and check status_balasan
    $stmtFetch = $pdo->prepare("SELECT * FROM bimbingan WHERE id = :id AND REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmtFetch->execute([':id' => $bimbingan_id, ':npm' => $npmMhs]);
    $bimb = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$bimb) {
        $_SESSION['swal_error'] = 'Bimbingan tidak ditemukan!';
        header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
        exit;
    }

    if (($bimb['status_pembimbing1'] ?? '') === 'sudah_dibalas' || ($bimb['status_pembimbing2'] ?? '') === 'sudah_dibalas') {
        $_SESSION['swal_error'] = 'Bimbingan yang sudah dibalas oleh dosen tidak dapat dihapus!';
        header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
        exit;
    }

    // Delete files attached in bimbingan
    $upload_dir = dirname(__DIR__, 2) . '/public/uploads/draft/';
    if (!empty($bimb['file_draft']) && file_exists($upload_dir . $bimb['file_draft'])) {
        @unlink($upload_dir . $bimb['file_draft']);
    }

    // Delete the bimbingan record (cascades to forum_bimbingan)
    $stmtDelete = $pdo->prepare("DELETE FROM bimbingan WHERE id = :id");
    $stmtDelete->execute([':id' => $bimbingan_id]);

    $_SESSION['swal_success'] = 'Bimbingan berhasil dihapus!';
} catch (PDOException $e) {
    $_SESSION['swal_error'] = 'Database Error: ' . $e->getMessage();
}

header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
exit;
