<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Protect page access for lecturers
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: " . BASE_URL . "/");
    exit;
}

$npm = trim($_GET['npm'] ?? '');

if (empty($npm)) {
    $_SESSION['swal_error'] = 'NPM tidak valid!';
    header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
    exit;
}

try {
    // Self-healing migration to add status_bimbingan column if not exists
    try {
        $pdo->exec("ALTER TABLE distribusi_mahasiswa ADD COLUMN status_bimbingan VARCHAR(50) DEFAULT 'aktif'");
    } catch (PDOException $e) {}

    // Verify lecturer is actually supervising this student
    $namaDosen = $_SESSION['nama'] ?? '';
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM distribusi_mahasiswa 
        WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')
          AND (LOWER(REGEXP_REPLACE(pembimbing1, '[^a-zA-Z0-9]', '', 'g')) = LOWER(REGEXP_REPLACE(:nama, '[^a-zA-Z0-9]', '', 'g'))
           OR LOWER(REGEXP_REPLACE(pembimbing2, '[^a-zA-Z0-9]', '', 'g')) = LOWER(REGEXP_REPLACE(:nama, '[^a-zA-Z0-9]', '', 'g')))
    ");
    $stmtCheck->execute([':npm' => $npm, ':nama' => $namaDosen]);
    $isSupervised = ((int)$stmtCheck->fetchColumn() > 0);

    if (!$isSupervised) {
        $_SESSION['swal_error'] = 'Anda tidak memiliki hak untuk menandai selesai mahasiswa ini!';
        header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
        exit;
    }

    // Update status bimbingan to selesai
    $stmtUpdate = $pdo->prepare("UPDATE distribusi_mahasiswa SET status_bimbingan = 'selesai' WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')");
    $stmtUpdate->execute([':npm' => $npm]);

    $_SESSION['swal_success'] = 'Berhasil menyelesaikan status bimbingan mahasiswa!';
} catch (PDOException $e) {
    $_SESSION['swal_error'] = 'Gagal memproses data: ' . $e->getMessage();
}

header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
exit;
