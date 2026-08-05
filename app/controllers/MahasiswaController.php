<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ubah_foto') {
        $npm = $_SESSION['username'] ?? '';
        if (empty($npm) || ($_SESSION['role'] ?? '') !== 'mahasiswa') {
            $_SESSION['swal_error'] = 'Sesi tidak valid!';
            header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
            exit;
        }

        try {
            // Handle photo upload
            if (isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Validate file is actual image
                $check = getimagesize($_FILES['foto_profile']['tmp_name']);
                if ($check === false) {
                    $_SESSION['swal_error'] = 'File yang diunggah bukan merupakan gambar valid!';
                    header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
                    exit;
                }

                $fileExt = strtolower(pathinfo($_FILES['foto_profile']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileExt, $allowedExts)) {
                    $_SESSION['swal_error'] = 'Hanya format JPG, JPEG, PNG, dan GIF yang diperbolehkan!';
                    header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
                    exit;
                }

                $fotoFilename = 'profile_mhs_' . str_replace(' ', '', $npm) . '_' . time() . '.' . $fileExt;
                if (move_uploaded_file($_FILES['foto_profile']['tmp_name'], $uploadDir . $fotoFilename)) {
                    // Update mahasiswa table
                    $stmtUpdate = $pdo->prepare("UPDATE mahasiswa SET profile_picture = :foto WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')");
                    $stmtUpdate->execute([
                        ':foto' => $fotoFilename,
                        ':npm' => $npm
                    ]);
                    $_SESSION['swal_success'] = 'Foto profil Anda berhasil diperbarui!';
                } else {
                    $_SESSION['swal_error'] = 'Gagal memindahkan file ke direktori tujuan!';
                }
            } else {
                $_SESSION['swal_error'] = 'Tidak ada file foto yang dipilih atau terjadi kesalahan!';
            }
            header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal memperbarui foto profil: ' . $e->getMessage();
            header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
            exit;
        }
    }
}

$_SESSION['swal_error'] = 'Aksi tidak valid!';
header("Location: " . BASE_URL . "/app/views/mahasiswa/profil.php");
exit;
