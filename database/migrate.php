<?php
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "=== Memulai Migrasi Database ===\n";

try {
    // Memuat koneksi database
    $koneksiPath = dirname(__DIR__) . '/config/koneksi.php';
    if (!file_exists($koneksiPath)) {
        throw new Exception("File koneksi tidak ditemukan di: " . $koneksiPath);
    }
    require_once $koneksiPath;
    
    if (!isset($pdo)) {
        throw new Exception("Variabel PDO (\$pdo) tidak terdefinisi setelah memuat koneksi.php");
    }

    // 1. Inisialisasi tabel 'users'
    echo "Menginisialisasi tabel 'users'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        username VARCHAR(50) PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        otoritas VARCHAR(50) NOT NULL
    )");

    // 2. Inisialisasi tabel 'mahasiswa'
    echo "Menginisialisasi tabel 'mahasiswa'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mahasiswa (
        npm VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255) DEFAULT NULL
    )");

    // Self-healing migration untuk kolom profile_picture di tabel mahasiswa
    try {
        $pdo->query("SELECT profile_picture FROM mahasiswa LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE mahasiswa ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        echo "Kolom 'profile_picture' berhasil ditambahkan ke tabel 'mahasiswa'.\n";
    }

    // 3. Inisialisasi tabel 'dosen'
    echo "Menginisialisasi tabel 'dosen'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS dosen (
        nip VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        bidang_ilmu VARCHAR(255) DEFAULT NULL,
        kuota_max INT DEFAULT 10,
        kuota_terisi INT DEFAULT 0,
        profile_picture VARCHAR(255) DEFAULT NULL
    )");

    $requiredDosenColumns = [
        'bidang_ilmu' => 'VARCHAR(255) DEFAULT NULL',
        'kuota_max' => 'INT DEFAULT 10',
        'kuota_terisi' => 'INT DEFAULT 0',
        'profile_picture' => 'VARCHAR(255) DEFAULT NULL',
        'universitas' => 'VARCHAR(255) DEFAULT NULL',
        'fakultas' => 'VARCHAR(255) DEFAULT NULL',
        'prodi' => 'VARCHAR(255) DEFAULT NULL'
    ];

    foreach ($requiredDosenColumns as $col => $definition) {
        try {
            $pdo->query("SELECT $col FROM dosen LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE dosen ADD COLUMN $col $definition");
            echo "Kolom '$col' berhasil ditambahkan ke tabel 'dosen'.\n";
        }
    }

    // 4. Inisialisasi tabel 'distribusi_mahasiswa'
    echo "Menginisialisasi tabel 'distribusi_mahasiswa'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS distribusi_mahasiswa (
        npm VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        judul_skripsi TEXT DEFAULT NULL,
        pembimbing1 VARCHAR(255) NOT NULL,
        pembimbing2 VARCHAR(255) DEFAULT NULL,
        pembahas1 VARCHAR(255) DEFAULT NULL,
        pembahas2 VARCHAR(255) DEFAULT NULL,
        nomor_sk VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Inisialisasi tabel 'topik_penelitian'
    echo "Menginisialisasi tabel 'topik_penelitian'...\n";
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

    // Self-healing migration untuk kolom topik_penelitian
    $requiredTopikColumns = [
        'tenggat_tanggal' => 'VARCHAR(100) DEFAULT NULL',
        'kategori' => 'VARCHAR(100) DEFAULT NULL',
        'status' => "VARCHAR(50) DEFAULT 'menunggu'"
    ];

    foreach ($requiredTopikColumns as $col => $definition) {
        try {
            $pdo->query("SELECT $col FROM topik_penelitian LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE topik_penelitian ADD COLUMN $col $definition");
            echo "Kolom '$col' berhasil ditambahkan ke tabel 'topik_penelitian'.\n";
        }
    }

    // Legacy data backfill untuk status topik_penelitian
    $pdo->exec("UPDATE topik_penelitian SET status = 'disetujui' WHERE status IS NULL");

    // 6. Inisialisasi tabel 'minat_topik'
    echo "Menginisialisasi tabel 'minat_topik'...\n";
    try {
        $checkConstraint = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.constraint_column_usage 
            WHERE table_name = 'topik_skripsi' AND constraint_name = 'fk_minat_topik'
        ")->fetchColumn();

        if ($checkConstraint > 0) {
            $pdo->exec("DROP TABLE IF EXISTS minat_topik CASCADE");
            echo "Tabel 'minat_topik' lama di-drop karena dependensi constraint.\n";
        }
    } catch (Exception $ex) {
        // Safe fallback
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS minat_topik (
        id SERIAL PRIMARY KEY,
        topik_id INT NOT NULL REFERENCES topik_penelitian(id) ON DELETE CASCADE,
        mahasiswa_npm VARCHAR(50) NOT NULL,
        alasan TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'menunggu',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT unique_topik_mahasiswa UNIQUE (topik_id, mahasiswa_npm)
    )");

    // 7. Inisialisasi tabel 'bimbingan'
    echo "Menginisialisasi tabel 'bimbingan'...\n";
    try {
        $checkColB = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'bimbingan' AND column_name = 'npm'
        ")->fetchColumn();

        if ($checkColB == 0) {
            $pdo->exec("DROP TABLE IF EXISTS forum_bimbingan CASCADE");
            $pdo->exec("DROP TABLE IF EXISTS bimbingan CASCADE");
            echo "Tabel bimbingan & forum_bimbingan di-drop untuk penyesuaian kolom 'npm'.\n";
        }
    } catch (Exception $ex) {
        // Fallback
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS bimbingan (
        id BIGINT PRIMARY KEY,
        tanggal VARCHAR(100) NOT NULL,
        npm VARCHAR(50) NOT NULL,
        nama VARCHAR(255) NOT NULL,
        file_draft VARCHAR(255) NOT NULL,
        status_balasan VARCHAR(50) DEFAULT 'belum_dibalas',
        status_pembimbing1 VARCHAR(50) DEFAULT 'belum_dibalas',
        status_pembimbing2 VARCHAR(50) DEFAULT 'belum_dibalas'
    )");

    // Self-healing check untuk status_pembimbing1 & status_pembimbing2
    try {
        $checkColP1 = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'bimbingan' AND column_name = 'status_pembimbing1'
        ")->fetchColumn();

        if ($checkColP1 == 0) {
            $pdo->exec("ALTER TABLE bimbingan ADD COLUMN status_pembimbing1 VARCHAR(50) DEFAULT 'belum_dibalas'");
            $pdo->exec("ALTER TABLE bimbingan ADD COLUMN status_pembimbing2 VARCHAR(50) DEFAULT 'belum_dibalas'");
            echo "Kolom 'status_pembimbing1' & 'status_pembimbing2' ditambahkan ke tabel 'bimbingan'.\n";
        }
    } catch (Exception $ex) {}

    // 8. Inisialisasi tabel 'forum_bimbingan'
    echo "Menginisialisasi tabel 'forum_bimbingan'...\n";
    try {
        $checkCol = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'forum_bimbingan' AND column_name = 'bimbingan_id'
        ")->fetchColumn();

        if ($checkCol == 0) {
            $pdo->exec("DROP TABLE IF EXISTS forum_bimbingan CASCADE");
            echo "Tabel 'forum_bimbingan' di-drop untuk penyesuaian kolom 'bimbingan_id'.\n";
        }
    } catch (Exception $ex) {
        // Fallback
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS forum_bimbingan (
        id SERIAL PRIMARY KEY,
        bimbingan_id BIGINT NOT NULL REFERENCES bimbingan(id) ON DELETE CASCADE,
        pengirim VARCHAR(50) NOT NULL,
        pengirim_nama VARCHAR(255) DEFAULT NULL,
        isi TEXT NOT NULL,
        file VARCHAR(255) DEFAULT NULL,
        tanggal VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Self-healing check untuk pengirim_nama
    try {
        $checkColFN = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_name = 'forum_bimbingan' AND column_name = 'pengirim_nama'
        ")->fetchColumn();

        if ($checkColFN == 0) {
            $pdo->exec("ALTER TABLE forum_bimbingan ADD COLUMN pengirim_nama VARCHAR(255) DEFAULT NULL");
            echo "Kolom 'pengirim_nama' ditambahkan ke tabel 'forum_bimbingan'.\n";
        }
    } catch (Exception $ex) {}

    // Legacy data updates untuk forum_bimbingan dan bimbingan
    try {
        $pdo->exec("UPDATE forum_bimbingan SET tanggal = REPLACE(REPLACE(tanggal, 'Thursday', 'Kamis'), ' AM', '') WHERE tanggal LIKE '%Thursday%'");
        $pdo->exec("UPDATE forum_bimbingan SET tanggal = REPLACE(REPLACE(tanggal, 'Monday', 'Senin'), ' AM', '') WHERE tanggal LIKE '%Monday%'");
        
        // Isi nama mahasiswa jika null pada forum_bimbingan
        $pdo->exec("
            UPDATE forum_bimbingan fb
            SET pengirim_nama = b.nama
            FROM bimbingan b
            WHERE fb.bimbingan_id = b.id AND fb.pengirim = 'mahasiswa' AND fb.pengirim_nama IS NULL
        ");
        
        // Isi nama dosen jika null pada forum_bimbingan
        $pdo->exec("
            UPDATE forum_bimbingan fb
            SET pengirim_nama = dm.pembimbing1
            FROM bimbingan b
            JOIN distribusi_mahasiswa dm ON REPLACE(b.npm, ' ', '') = REPLACE(dm.npm, ' ', '')
            WHERE fb.bimbingan_id = b.id AND fb.pengirim = 'dosen' AND fb.pengirim_nama IS NULL
        ");
        
        // Atur status bimbingan tertentu untuk demo Pak Ikhsan
        $pdo->exec("
            UPDATE bimbingan 
            SET status_pembimbing1 = 'belum_dibalas', status_pembimbing2 = 'sudah_dibalas', status_balasan = 'belum_dibalas'
            WHERE id = 1784762468
        ");
        echo "Legacy data forum_bimbingan & bimbingan berhasil diperbarui.\n";
    } catch (Exception $ex) {
        echo "Gagal memperbarui legacy data forum/bimbingan: " . $ex->getMessage() . "\n";
    }

    // 9. Inisialisasi tabel 'pengajuan_judul'
    echo "Menginisialisasi tabel 'pengajuan_judul'...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengajuan_judul (
        id SERIAL PRIMARY KEY,
        mahasiswa_npm VARCHAR(50) NOT NULL,
        mahasiswa_nama VARCHAR(255) NOT NULL,
        judul TEXT NOT NULL,
        deskripsi TEXT NOT NULL,
        pembimbing1 VARCHAR(255) DEFAULT NULL,
        pembimbing2 VARCHAR(255) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'menunggu',
        alasan TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        file_krs VARCHAR(255) DEFAULT NULL,
        file_transkrip VARCHAR(255) DEFAULT NULL,
        file_proposal VARCHAR(255) DEFAULT NULL,
        judul_alternatif TEXT DEFAULT NULL,
        file_ktm VARCHAR(255) DEFAULT NULL,
        file_form_tema VARCHAR(255) DEFAULT NULL,
        file_bukti_ukt VARCHAR(255) DEFAULT NULL,
        file_krs_terakhir VARCHAR(255) DEFAULT NULL,
        file_form_verifikasi VARCHAR(255) DEFAULT NULL,
        file_bukti_acc VARCHAR(255) DEFAULT NULL,
        file_form_penetapan VARCHAR(255) DEFAULT NULL,
        file_bab1 VARCHAR(255) DEFAULT NULL,
        file_bab1_alt VARCHAR(255) DEFAULT NULL,
        judul_disetujui VARCHAR(50) DEFAULT 'utama',
        tanggal_persetujuan TIMESTAMP DEFAULT NULL,
        judul_lama TEXT DEFAULT NULL,
        judul_alternatif_lama TEXT DEFAULT NULL,
        nomor_sk VARCHAR(255) DEFAULT NULL,
        pembahas1 VARCHAR(255) DEFAULT NULL,
        pembahas2 VARCHAR(255) DEFAULT NULL
    )");

    $pengajuanJudulColumns = [
        'file_krs' => 'VARCHAR(255) DEFAULT NULL',
        'file_transkrip' => 'VARCHAR(255) DEFAULT NULL',
        'file_proposal' => 'VARCHAR(255) DEFAULT NULL',
        'judul_alternatif' => 'TEXT DEFAULT NULL',
        'file_ktm' => 'VARCHAR(255) DEFAULT NULL',
        'file_form_tema' => 'VARCHAR(255) DEFAULT NULL',
        'file_bukti_ukt' => 'VARCHAR(255) DEFAULT NULL',
        'file_krs_terakhir' => 'VARCHAR(255) DEFAULT NULL',
        'file_form_verifikasi' => 'VARCHAR(255) DEFAULT NULL',
        'file_bukti_acc' => 'VARCHAR(255) DEFAULT NULL',
        'file_form_penetapan' => 'VARCHAR(255) DEFAULT NULL',
        'file_bab1' => 'VARCHAR(255) DEFAULT NULL',
        'file_bab1_alt' => 'VARCHAR(255) DEFAULT NULL',
        'judul_disetujui' => "VARCHAR(50) DEFAULT 'utama'",
        'tanggal_persetujuan' => "TIMESTAMP DEFAULT NULL",
        'judul_lama' => "TEXT DEFAULT NULL",
        'judul_alternatif_lama' => "TEXT DEFAULT NULL",
        'nomor_sk' => 'VARCHAR(255) DEFAULT NULL',
        'pembahas1' => 'VARCHAR(255) DEFAULT NULL',
        'pembahas2' => 'VARCHAR(255) DEFAULT NULL'
    ];

    foreach ($pengajuanJudulColumns as $col => $type) {
        try {
            $pdo->query("SELECT $col FROM pengajuan_judul LIMIT 1");
        } catch (PDOException $e) {
            try {
                $pdo->exec("ALTER TABLE pengajuan_judul ADD COLUMN $col $type");
                echo "Kolom '$col' berhasil ditambahkan ke tabel 'pengajuan_judul'.\n";
            } catch (PDOException $ex) {
                echo "Gagal menambahkan kolom '$col': " . $ex->getMessage() . "\n";
            }
        }
    }

    echo "=== Migrasi Database Berhasil Selesai ===\n";
    echo "PENTING: Demi keamanan, silakan hapus atau nonaktifkan file ini (database/migrate.php) dari server produksi setelah dijalankan.\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
