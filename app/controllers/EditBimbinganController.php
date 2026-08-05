<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bimbingan_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $pesan = trim($_POST['pesan'] ?? '');
    $isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
    $npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

    // Check bimbingan status from database
    try {
        $stmtCheck = $pdo->prepare("SELECT status_bimbingan FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
        $stmtCheck->execute([':npm' => $npmMhs]);
        $statusBimbingan = $stmtCheck->fetchColumn();
        if ($statusBimbingan === 'selesai') {
            $_SESSION['swal_error'] = 'Anda tidak dapat mengubah bimbingan karena status bimbingan Anda telah selesai/lulus!';
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
        // Verify that bimbingan belongs to student and is still unreplied
        $stmtFetch = $pdo->prepare("SELECT * FROM bimbingan WHERE id = :id AND REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
        $stmtFetch->execute([':id' => $bimbingan_id, ':npm' => $npmMhs]);
        $bimb = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$bimb) {
            $_SESSION['swal_error'] = 'Bimbingan tidak ditemukan!';
            header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
            exit;
        }

        if (($bimb['status_pembimbing1'] ?? '') === 'sudah_dibalas' || ($bimb['status_pembimbing2'] ?? '') === 'sudah_dibalas') {
            $_SESSION['swal_error'] = 'Bimbingan yang sudah dibalas oleh dosen tidak dapat diubah!';
            header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
            exit;
        }

        $nama_file = $bimb['file_draft'];
        // Handle upload file draft PDF if a new file is uploaded
        if (isset($_FILES['draft']) && $_FILES['draft']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['draft']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['swal_error'] = 'Gagal mengunggah file draft! Error Code: ' . $_FILES['draft']['error'];
                header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
                exit;
            }

            // Validate that the file is a PDF
            $file_ext = strtolower(pathinfo($_FILES['draft']['name'], PATHINFO_EXTENSION));
            if ($file_ext !== 'pdf') {
                $_SESSION['swal_error'] = 'Hanya file PDF yang diperbolehkan!';
                header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
                exit;
            }

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
            if (!move_uploaded_file($_FILES['draft']['tmp_name'], $target_file)) {
                $_SESSION['swal_error'] = 'Gagal memindahkan file ke folder uploads!';
                header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
                exit;
            }
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

        // Update Session bimbingan_list if exists
        if (isset($_SESSION['bimbingan_list'])) {
            foreach ($_SESSION['bimbingan_list'] as &$item) {
                if ($item['id'] == $bimbingan_id) {
                    $item['file_draft'] = $nama_file;
                    $item['status_balasan'] = 'belum_dibalas';
                }
            }
            unset($item);
        }

        // Update Session forum_bimbingan if exists
        if (isset($_SESSION['forum_bimbingan'][$bimbingan_id])) {
            if (isset($_SESSION['forum_bimbingan'][$bimbingan_id][0])) {
                $_SESSION['forum_bimbingan'][$bimbingan_id][0]['isi'] = !empty($pesan) ? $pesan : 'Mengunggah draft bimbingan baru.';
                $_SESSION['forum_bimbingan'][$bimbingan_id][0]['file'] = $nama_file;
            }
        }

        $_SESSION['swal_success'] = 'Bimbingan berhasil diperbarui!';
    } catch (Exception $e) {
        $_SESSION['swal_error'] = 'Error: ' . $e->getMessage();
    }
}

header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
exit;
