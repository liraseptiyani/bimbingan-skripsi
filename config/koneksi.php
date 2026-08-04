<?php
date_default_timezone_set('Asia/Jakarta');

$host = "ep-twilight-base-ao8gz75j-pooler.c-2.ap-southeast-1.aws.neon.tech";
$port = "5432";
$dbname = "neondb";
$user = "neondb_owner";
$password = "npg_umw8ZUzN5Fef";

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->exec("SET timezone = 'Asia/Jakarta'");

    // Auto-initialize Dosen schema
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS dosen (
            nip VARCHAR(50) PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            bidang_ilmu VARCHAR(255) DEFAULT NULL,
            kuota_max INT DEFAULT 10,
            kuota_terisi INT DEFAULT 0,
            profile_picture VARCHAR(255) DEFAULT NULL
        )");

        $requiredColumns = [
            'bidang_ilmu' => 'VARCHAR(255) DEFAULT NULL',
            'kuota_max' => 'INT DEFAULT 10',
            'kuota_terisi' => 'INT DEFAULT 0',
            'profile_picture' => 'VARCHAR(255) DEFAULT NULL',
            'universitas' => 'VARCHAR(255) DEFAULT NULL',
            'fakultas' => 'VARCHAR(255) DEFAULT NULL',
            'prodi' => 'VARCHAR(255) DEFAULT NULL'
        ];

        foreach ($requiredColumns as $col => $definition) {
            try {
                $pdo->query("SELECT $col FROM dosen LIMIT 1");
            } catch (PDOException $e) {
                $pdo->exec("ALTER TABLE dosen ADD COLUMN $col $definition");
            }
        }
    } catch (PDOException $e) {
        // Silent catch
    }

    // Auto-initialize Topik Skripsi schema
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS topik_penelitian (
            id SERIAL PRIMARY KEY,
            nip_dosen VARCHAR(50) NOT NULL,
            topik VARCHAR(255) NOT NULL,
            deskripsi TEXT NOT NULL,
            kuota_max INT DEFAULT 1,
            tenggat_tanggal VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Self-healing migration to add tenggat_tanggal column if missing
        try {
            $pdo->query("SELECT tenggat_tanggal FROM topik_penelitian LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE topik_penelitian ADD COLUMN tenggat_tanggal VARCHAR(100) DEFAULT NULL");
        }

        try {
            $checkConstraint = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.constraint_column_usage 
                WHERE table_name = 'topik_skripsi' AND constraint_name = 'fk_minat_topik'
            ")->fetchColumn();

            if ($checkConstraint > 0) {
                $pdo->exec("DROP TABLE IF EXISTS minat_topik CASCADE");
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
    } catch (PDOException $e) {
        // Silent catch
    }

    // Auto-initialize Bimbingan and Forum Bimbingan schema
    try {
        try {
            $checkColB = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.columns 
                WHERE table_name = 'bimbingan' AND column_name = 'npm'
            ")->fetchColumn();

            if ($checkColB == 0) {
                $pdo->exec("DROP TABLE IF EXISTS forum_bimbingan CASCADE");
                $pdo->exec("DROP TABLE IF EXISTS bimbingan CASCADE");
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

        // Self-healing check to add status_pembimbing1 & status_pembimbing2 columns if missing
        try {
            $checkColP1 = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.columns 
                WHERE table_name = 'bimbingan' AND column_name = 'status_pembimbing1'
            ")->fetchColumn();

            if ($checkColP1 == 0) {
                $pdo->exec("ALTER TABLE bimbingan ADD COLUMN status_pembimbing1 VARCHAR(50) DEFAULT 'belum_dibalas'");
                $pdo->exec("ALTER TABLE bimbingan ADD COLUMN status_pembimbing2 VARCHAR(50) DEFAULT 'belum_dibalas'");
            }
        } catch (Exception $ex) {}

        try {
            $checkCol = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.columns 
                WHERE table_name = 'forum_bimbingan' AND column_name = 'bimbingan_id'
            ")->fetchColumn();

            if ($checkCol == 0) {
                $pdo->exec("DROP TABLE IF EXISTS forum_bimbingan CASCADE");
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

        // Self-healing check to add pengirim_nama column if missing
        try {
            $checkColFN = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.columns 
                WHERE table_name = 'forum_bimbingan' AND column_name = 'pengirim_nama'
            ")->fetchColumn();

            if ($checkColFN == 0) {
                $pdo->exec("ALTER TABLE forum_bimbingan ADD COLUMN pengirim_nama VARCHAR(255) DEFAULT NULL");
            }
        } catch (Exception $ex) {}

        try {
            $pdo->exec("UPDATE forum_bimbingan SET tanggal = REPLACE(REPLACE(tanggal, 'Thursday', 'Kamis'), ' AM', '') WHERE tanggal LIKE '%Thursday%'");
            $pdo->exec("UPDATE forum_bimbingan SET tanggal = REPLACE(REPLACE(tanggal, 'Monday', 'Senin'), ' AM', '') WHERE tanggal LIKE '%Monday%'");
            
            // Populate legacy null names for students
            $pdo->exec("
                UPDATE forum_bimbingan fb
                SET pengirim_nama = b.nama
                FROM bimbingan b
                WHERE fb.bimbingan_id = b.id AND fb.pengirim = 'mahasiswa' AND fb.pengirim_nama IS NULL
            ");
            // Populate legacy null names for supervisors
            $pdo->exec("
                UPDATE forum_bimbingan fb
                SET pengirim_nama = dm.pembimbing1
                FROM bimbingan b
                JOIN distribusi_mahasiswa dm ON REPLACE(b.npm, ' ', '') = REPLACE(dm.npm, ' ', '')
                WHERE fb.bimbingan_id = b.id AND fb.pengirim = 'dosen' AND fb.pengirim_nama IS NULL
            ");
            
            // Adjust specific bimbingan record status for Pak Ikhsan's demo
            $pdo->exec("
                UPDATE bimbingan 
                SET status_pembimbing1 = 'belum_dibalas', status_pembimbing2 = 'sudah_dibalas', status_balasan = 'belum_dibalas'
                WHERE id = 1784762468
            ");

        } catch (Exception $ex) {}

        // Seed initial data if tables are empty (disabled to allow total database clearing)
        /*
        $countQueryB = $pdo->query("SELECT COUNT(*) FROM bimbingan");
        $countB = $countQueryB ? $countQueryB->fetchColumn() : 0;
        if ($countB == 0) {
            $b_id = 1717051151;
            $pdo->exec("INSERT INTO bimbingan (id, tanggal, npm, nama, file_draft, status_balasan, status_pembimbing1, status_pembimbing2) 
                VALUES ($b_id, '7 Februari 2026, 10.41', '2217051151', 'LIRA SEPTIYANI', 'Draft Bab1-Lira.pdf', 'belum_dibalas', 'belum_dibalas', 'belum_dibalas')");
            
            $pdo->exec("INSERT INTO forum_bimbingan (bimbingan_id, pengirim, pengirim_nama, isi, file, tanggal) 
                VALUES ($b_id, 'mahasiswa', 'LIRA SEPTIYANI', 'Assalamualaikum Pak, Izin mengirimkan Draft Bab 1 saya. Mohon koreksinya pak, terima kasih.', 'Draft Bab1-Lira.pdf', 'Monday, 4 Mei 2026, 10:14 AM')");
        }
        */
    } catch (PDOException $e) {
        // Silent catch
    }

    // Auto-initialize Pengajuan Judul schema
    try {
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // Silent catch
    }

    // Self-healing migration for Mahasiswa profile_picture column
    try {
        $pdo->query("SELECT profile_picture FROM mahasiswa LIMIT 1");
    } catch (PDOException $e) {
        try {
            $pdo->exec("ALTER TABLE mahasiswa ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $ex) {}
    }

    // Self-healing migration for pengajuan_judul file upload columns
    try {
        $pdo->query("SELECT file_krs FROM pengajuan_judul LIMIT 1");
    } catch (PDOException $e) {
        try {
            $pdo->exec("ALTER TABLE pengajuan_judul ADD COLUMN file_krs VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE pengajuan_judul ADD COLUMN file_transkrip VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE pengajuan_judul ADD COLUMN file_proposal VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $ex) {}
    }

    // Detailed file requirement columns for pengajuan_judul
    $columnsToAdd = [
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

    foreach ($columnsToAdd as $col => $type) {
        try {
            $pdo->query("SELECT $col FROM pengajuan_judul LIMIT 1");
        } catch (PDOException $e) {
            try {
                $pdo->exec("ALTER TABLE pengajuan_judul ADD COLUMN $col $type");
            } catch (PDOException $ex) {}
        }
    }

} catch (PDOException $e) {

    echo "Koneksi gagal: " . $e->getMessage();
}
