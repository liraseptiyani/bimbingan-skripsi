<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak! Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $password_lama = trim($_POST['password_lama'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');
    $password_konfirmasi = trim($_POST['password_konfirmasi'] ?? '');

    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        echo json_encode(['success' => false, 'message' => 'Semua kolom kata sandi wajib diisi!']);
        exit;
    }

    if ($password_baru !== $password_konfirmasi) {
        echo json_encode(['success' => false, 'message' => 'Kata sandi baru dan konfirmasi kata sandi tidak cocok!']);
        exit;
    }

    try {
        // Fetch current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $current_password = $stmt->fetchColumn();

        if ($current_password === false) {
            echo json_encode(['success' => false, 'message' => 'Akun pengguna tidak ditemukan!']);
            exit;
        }

        if ($current_password !== $password_lama) {
            echo json_encode(['success' => false, 'message' => 'Kata sandi lama Anda salah!']);
            exit;
        }

        // Update password in users table
        $stmtUpdate = $pdo->prepare("UPDATE users SET password = :new_pass WHERE username = :username");
        $stmtUpdate->execute([
            ':new_pass' => $password_baru,
            ':username' => $username
        ]);

        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah kata sandi: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);
exit;
