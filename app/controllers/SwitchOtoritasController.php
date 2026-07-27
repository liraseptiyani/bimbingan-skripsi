<?php

session_start();

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// ==========================================================
// Hanya akun dosen yang boleh switch otoritas
// (mahasiswa tidak punya opsi ini)
// ==========================================================
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
    exit;
}

$otoritas_baru = trim($_POST['otoritas'] ?? '');

// validasi: dosen boleh switch antara 'dosen' atau 'kaprodi'
if (!in_array($otoritas_baru, ['dosen', 'kaprodi'], true)) {
    $_SESSION['error'] = "Otoritas tidak valid.";
    header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
    exit;
}

try {

    $sql = "
        UPDATE users
        SET otoritas = :otoritas
        WHERE username = :username
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':otoritas' => $otoritas_baru,
        ':username' => $_SESSION['username']
    ]);

    // =====================
    // Update session supaya langsung berlaku
    // tanpa perlu logout/login ulang
    // =====================
    $_SESSION['otoritas'] = $otoritas_baru;

    // =====================
    // Flash message sukses, ditampilkan sekali di dashboard tujuan
    // =====================
    $label_otoritas = [
        'dosen'     => 'Dosen Pembimbing',
        'kaprodi'   => 'Kepala Program Studi',
    ];
    $_SESSION['flash_success'] = 'Otoritas berhasil diubah menjadi ' . ($label_otoritas[$otoritas_baru] ?? $otoritas_baru) . '.';

    // =====================
    // Redirect ke dashboard sesuai otoritas baru
    // =====================
    if ($otoritas_baru === 'kaprodi') {
        header("Location: /bimbingan-skripsi/app/views/kaprodi/dashboard.php");
    } else {
        header("Location: /bimbingan-skripsi/app/views/dosen/dashboard.php");
    }

    exit;

} catch (PDOException $e) {

    // Jika terjadi kegagalan query DB, tetapkan di Session agar tetap berfungsi
    $_SESSION['otoritas'] = $otoritas_baru;
    
    if ($otoritas_baru === 'kaprodi') {
        header("Location: /bimbingan-skripsi/app/views/kaprodi/dashboard.php");
    } else {
        header("Location: /bimbingan-skripsi/app/views/dosen/dashboard.php");
    }

    exit;

}
