<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
        header("Location: /bimbingan-skripsi/");
        exit;
    }
}

function ensureTopikTablesExist($pdo) {
    try {
        // Create topik_penelitian matching their schema
        $pdo->exec("CREATE TABLE IF NOT EXISTS topik_penelitian (
            id SERIAL PRIMARY KEY,
            nip_dosen VARCHAR(50) NOT NULL,
            topik VARCHAR(255) NOT NULL,
            deskripsi TEXT NOT NULL,
            kuota_max INT DEFAULT 1,
            tenggat_tanggal VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create minat_topik
        $pdo->exec("CREATE TABLE IF NOT EXISTS minat_topik (
            id SERIAL PRIMARY KEY,
            topik_id INT NOT NULL REFERENCES topik_penelitian(id) ON DELETE CASCADE,
            npm_mahasiswa VARCHAR(50) NOT NULL,
            alasan TEXT NOT NULL,
            status VARCHAR(50) DEFAULT 'Menunggu',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT unique_topik_mahasiswa UNIQUE (topik_id, npm_mahasiswa)
        )");
    } catch (PDOException $e) {
        // Silent catch
    }
}

// Run table checks
ensureTopikTablesExist($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    $topik = trim($_POST['topik'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kuota_max = (int)($_POST['kuota'] ?? 1);
    $tenggat_tanggal = !empty(trim($_POST['tenggat_tanggal'] ?? '')) ? trim($_POST['tenggat_tanggal']) : null;
    $nip_dosen = $_SESSION['username'];

    if (empty($topik) || empty($deskripsi) || $kuota_max < 1) {
        $_SESSION['swal_error'] = 'Harap isi semua bidang dengan benar!';
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }

    try {
        if (!empty($id)) {
            // Edit mode: make sure Dosen owns this topic
            $stmt = $pdo->prepare("UPDATE topik_penelitian SET topik = :topik, deskripsi = :deskripsi, kuota_max = :kuota_max, tenggat_tanggal = :tenggat_tanggal WHERE id = :id AND REPLACE(nip_dosen, ' ', '') = REPLACE(:nip_dosen, ' ', '')");
            $stmt->execute([
                ':topik' => $topik,
                ':deskripsi' => $deskripsi,
                ':kuota_max' => $kuota_max,
                ':tenggat_tanggal' => $tenggat_tanggal,
                ':id' => $id,
                ':nip_dosen' => $nip_dosen
            ]);
            $_SESSION['swal_success'] = 'Topik penelitian berhasil diperbarui!';
        } else {
            // Add mode
            $stmt = $pdo->prepare("INSERT INTO topik_penelitian (nip_dosen, topik, deskripsi, kuota_max, tenggat_tanggal) VALUES (:nip_dosen, :topik, :deskripsi, :kuota_max, :tenggat_tanggal)");
            $stmt->execute([
                ':nip_dosen' => $nip_dosen,
                ':topik' => $topik,
                ':deskripsi' => $deskripsi,
                ':kuota_max' => $kuota_max,
                ':tenggat_tanggal' => $tenggat_tanggal
            ]);
            $_SESSION['swal_success'] = 'Topik penelitian berhasil ditambahkan!';
        }

        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['swal_error'] = 'Gagal menyimpan topik: ' . $e->getMessage();
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }
}
