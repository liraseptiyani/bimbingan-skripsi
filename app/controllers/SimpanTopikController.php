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
            kategori VARCHAR(100) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'menunggu',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Migrations
        try {
            $pdo->query("SELECT kategori FROM topik_penelitian LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE topik_penelitian ADD COLUMN kategori VARCHAR(100) DEFAULT NULL");
        }
        try {
            $pdo->query("SELECT status FROM topik_penelitian LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE topik_penelitian ADD COLUMN status VARCHAR(50) DEFAULT 'menunggu'");
        }

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
    $kategori = trim($_POST['kategori'] ?? '');
    $kuota_max = (int)($_POST['kuota'] ?? 1);
    $tenggat_tanggal = !empty(trim($_POST['tenggat_tanggal'] ?? '')) ? trim($_POST['tenggat_tanggal']) : null;
    $nip_dosen = $_SESSION['username'];

    if (empty($topik) || empty($deskripsi) || empty($kategori) || $kuota_max < 1) {
        $_SESSION['swal_error'] = 'Harap isi semua bidang dengan benar!';
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }

    try {
        if (!empty($id)) {
            // Edit mode: make sure Dosen owns this topic, and reset status to 'menunggu'
            $stmt = $pdo->prepare("UPDATE topik_penelitian SET topik = :topik, deskripsi = :deskripsi, kategori = :kategori, status = 'menunggu', kuota_max = :kuota_max, tenggat_tanggal = :tenggat_tanggal WHERE id = :id AND REPLACE(nip_dosen, ' ', '') = REPLACE(:nip_dosen, ' ', '')");
            $stmt->execute([
                ':topik' => $topik,
                ':deskripsi' => $deskripsi,
                ':kategori' => $kategori,
                ':kuota_max' => $kuota_max,
                ':tenggat_tanggal' => $tenggat_tanggal,
                ':id' => $id,
                ':nip_dosen' => $nip_dosen
            ]);
            $_SESSION['swal_success'] = 'Topik penelitian berhasil diperbarui dan dikirim kembali untuk verifikasi Kaprodi!';
        } else {
            // Add mode
            $stmt = $pdo->prepare("INSERT INTO topik_penelitian (nip_dosen, topik, deskripsi, kategori, status, kuota_max, tenggat_tanggal) VALUES (:nip_dosen, :topik, :deskripsi, :kategori, 'menunggu', :kuota_max, :tenggat_tanggal)");
            $stmt->execute([
                ':nip_dosen' => $nip_dosen,
                ':topik' => $topik,
                ':deskripsi' => $deskripsi,
                ':kategori' => $kategori,
                ':kuota_max' => $kuota_max,
                ':tenggat_tanggal' => $tenggat_tanggal
            ]);
            $_SESSION['swal_success'] = 'Topik penelitian berhasil ditambahkan dan menunggu verifikasi Kaprodi!';
        }

        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['swal_error'] = 'Gagal menyimpan topik: ' . $e->getMessage();
        header("Location: /bimbingan-skripsi/app/views/dosen/topik_penelitian.php");
        exit;
    }
}
