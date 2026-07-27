<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bimbingan_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $pesan = trim($_POST['pesan'] ?? '');
    $isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
    $npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

    if ($bimbingan_id <= 0) {
        $_SESSION['swal_error'] = 'ID bimbingan tidak valid!';
        header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
        exit;
    }

    try {
        // Verify that bimbingan belongs to student and is still unreplied
        $stmtFetch = $pdo->prepare("SELECT * FROM bimbingan WHERE id = :id AND REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
        $stmtFetch->execute([':id' => $bimbingan_id, ':npm' => $npmMhs]);
        $bimb = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$bimb) {
            $_SESSION['swal_error'] = 'Bimbingan tidak ditemukan!';
            header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
            exit;
        }

        if (($bimb['status_pembimbing1'] ?? '') === 'sudah_dibalas' || ($bimb['status_pembimbing2'] ?? '') === 'sudah_dibalas') {
            $_SESSION['swal_error'] = 'Bimbingan yang sudah dibalas oleh dosen tidak dapat diubah!';
            header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
            exit;
        }

        $nama_file = $bimb['file_draft'];
        // Handle upload file draft PDF if a new file is uploaded
        if (isset($_FILES['draft']) && $_FILES['draft']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__, 2) . '/public/uploads/draft/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            // Remove old file if exists
            if (!empty($bimb['file_draft']) && file_exists($upload_dir . $bimb['file_draft'])) {
                @unlink($upload_dir . $bimb['file_draft']);
            }

            $file_basename = basename($_FILES['draft']['name']);
            $nama_file = time() . '_' . $file_basename;
            $target_file = $upload_dir . $nama_file;
            move_uploaded_file($_FILES['draft']['tmp_name'], $target_file);
        }

        // Update bimbingan record and reset reply statuses
        $stmtUpdate = $pdo->prepare("UPDATE bimbingan SET file_draft = :draft, status_balasan = 'belum_dibalas', status_pembimbing1 = 'belum_dibalas', status_pembimbing2 = 'belum_dibalas' WHERE id = :id");
        $stmtUpdate->execute([
            ':draft' => $nama_file,
            ':id' => $bimbingan_id
        ]);

        // Update the corresponding forum_bimbingan record (the first/initial message)
        $stmtForumGet = $pdo->prepare("SELECT id FROM forum_bimbingan WHERE bimbingan_id = :b_id ORDER BY id ASC LIMIT 1");
        $stmtForumGet->execute([':b_id' => $bimbingan_id]);
        $forumId = $stmtForumGet->fetchColumn();

        if ($forumId) {
            $stmtForumUpdate = $pdo->prepare("UPDATE forum_bimbingan SET isi = :isi, file = :file WHERE id = :id");
            $stmtForumUpdate->execute([
                ':isi' => !empty($pesan) ? $pesan : 'Mengunggah draft bimbingan baru.',
                ':file' => $nama_file,
                ':id' => $forumId
            ]);
        }

        $_SESSION['swal_success'] = 'Bimbingan berhasil diperbarui!';
    } catch (PDOException $e) {
        $_SESSION['swal_error'] = 'Database Error: ' . $e->getMessage();
    }
}

header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
exit;
