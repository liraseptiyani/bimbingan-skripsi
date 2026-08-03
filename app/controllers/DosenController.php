<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Only enforce Kaprodi role checks if performing state-modifying CRUD actions (except for self-profile photo update)
if (!empty($action) && $action !== 'ubah_foto') {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
        header("Location: /bimbingan-skripsi/");
        exit;
    }
} elseif ($action === 'ubah_foto') {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
        header("Location: /bimbingan-skripsi/");
        exit;
    }
}

// Ensure database tables and seed data exist
function ensureDosenTablesExist($pdo) {
    try {
        // Create dosen table matching their schema
        $pdo->exec("CREATE TABLE IF NOT EXISTS dosen (
            nip VARCHAR(50) PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            bidang_ilmu VARCHAR(255) DEFAULT NULL,
            kuota_max INT DEFAULT 10,
            kuota_terisi INT DEFAULT 0,
            profile_picture VARCHAR(255) DEFAULT NULL
        )");

        // Ensure all required columns exist in dosen table (in case the table already existed)
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

        // Create users table if not exists (usually exists)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            username VARCHAR(50) PRIMARY KEY,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            otoritas VARCHAR(50) NOT NULL
        )");

        // Check if dosen table is empty to seed default data
        $count = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
        if ($count == 0) {
            $defaultDosen = [
                ['nama' => 'Tristiyanto, S.Kom., M.I.S., Ph.D', 'nip' => '198104142005011001', 'bidang' => 'Artificial Intelligence', 'kuota' => 10, 'otoritas' => 'kaprodi'],
                ['nama' => 'Muhammad Ikhsan, S.Kom., M.Cs.',     'nip' => '199411012024061002', 'bidang' => 'Sistem Informasi',          'kuota' => 10, 'otoritas' => 'dosen'],
                ['nama' => 'Rahman Taufik, M.Kom',              'nip' => '199306272022031007', 'bidang' => 'Data Science',                       'kuota' => 30, 'otoritas' => 'dosen'],
                ['nama' => 'Yunda Heningtyas, M.Kom',           'nip' => '198901082019032014', 'bidang' => 'Grafika Komputer',                   'kuota' => 20, 'otoritas' => 'dosen'],
                ['nama' => 'Prof. Dr. Eng. ADMI SYARIF',        'nip' => '196701031992031003', 'bidang' => 'Kecerdasan Buatan',                  'kuota' => 30, 'otoritas' => 'dosen'],
                ['nama' => 'Dwi Sakethi, M.Kom.',               'nip' => '196806111998021001', 'bidang' => 'Sistem Operasi',                     'kuota' => 30, 'otoritas' => 'dosen'],
                ['nama' => 'Dr. Aristoteles, S.Si., M.Si',      'nip' => '198105212006041002', 'bidang' => 'Basis Data',                         'kuota' => 20, 'otoritas' => 'dosen'],
                ['nama' => 'RICO ANDRIAN, S.Si., M.Kom',        'nip' => '197506272005011001', 'bidang' => 'Computer Networking',                'kuota' => 30, 'otoritas' => 'dosen'],
                ['nama' => 'Muhaqiqin, S.Kom., M.T.I',          'nip' => '199305252022031009', 'bidang' => 'Pemrograman Berbasis Mobile',        'kuota' => 30, 'otoritas' => 'dosen'],
                ['nama' => 'FEBI EKA FEBRIANSYAH, M.T',         'nip' => '198002192006041001', 'bidang' => 'Automata',                           'kuota' => 20, 'otoritas' => 'dosen'],
            ];

            foreach ($defaultDosen as $d) {
                // Insert into dosen table
                $stmt = $pdo->prepare("INSERT INTO dosen (nip, nama, bidang_ilmu, kuota_max, kuota_terisi, profile_picture, universitas, fakultas, prodi) VALUES (:nip, :nama, :bidang, :kuota, 0, :foto, :univ, :fak, :prodi)");
                $stmt->execute([
                    ':nip' => $d['nip'],
                    ':nama' => $d['nama'],
                    ':bidang' => $d['bidang'],
                    ':kuota' => $d['kuota'],
                    ':foto' => 'images.jpg',
                    ':univ' => 'Universitas Lampung',
                    ':fak' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                    ':prodi' => 'Ilmu Komputer'
                ]);

                if ($userCheck->fetchColumn() == 0) {
                    $cleanPassword = str_replace(' ', '', $d['nip']);
                    $hashedPassword = password_hash($cleanPassword, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
                    $stmtUser->execute([
                        ':username' => $d['nip'],
                        ':password' => $hashedPassword,
                        ':role' => 'dosen',
                        ':otoritas' => $d['otoritas']
                    ]);
                }
            }
        }
    } catch (PDOException $e) {
        // Silent catch or logging
    }
}

// Run table check
ensureDosenTablesExist($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ubah_foto') {
        $nip = $_SESSION['username'] ?? '';
        if (empty($nip)) {
            $_SESSION['swal_error'] = 'Sesi tidak valid!';
            header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
            exit;
        }

        try {
            // Fetch current dosen
            $stmtFetch = $pdo->prepare("SELECT * FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
            $stmtFetch->execute([':nip' => $nip]);
            $currentDosen = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if (!$currentDosen) {
                $_SESSION['swal_error'] = 'Dosen tidak ditemukan!';
                header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
                exit;
            }

            // Handle photo upload
            if (isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExt = pathinfo($_FILES['foto_profile']['name'], PATHINFO_EXTENSION);
                $fotoFilename = 'profile_' . str_replace(' ', '', $nip) . '_' . time() . '.' . $fileExt;
                if (move_uploaded_file($_FILES['foto_profile']['tmp_name'], $uploadDir . $fotoFilename)) {
                    // Update dosen table
                    $stmtUpdate = $pdo->prepare("UPDATE dosen SET profile_picture = :foto WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
                    $stmtUpdate->execute([
                        ':foto' => $fotoFilename,
                        ':nip' => $nip
                    ]);
                    $_SESSION['swal_success'] = 'Foto profil berhasil diperbarui!';
                } else {
                    $_SESSION['swal_error'] = 'Gagal mengunggah foto!';
                }
            } else {
                $_SESSION['swal_error'] = 'Tidak ada file foto yang dipilih atau terjadi kesalahan!';
            }
            header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Error: ' . $e->getMessage();
            header("Location: /bimbingan-skripsi/app/views/dosen/profil.php");
            exit;
        }
    }

    if ($action === 'tambah_dosen') {
        $nama = trim($_POST['nama'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $bidang_ilmu = trim($_POST['bidang_ilmu'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $otoritas = strtolower(trim($_POST['otoritas'] ?? 'dosen'));
        $universitas = trim($_POST['universitas'] ?? '');
        $fakultas = trim($_POST['fakultas'] ?? '');
        $program_studi = trim($_POST['program_studi'] ?? '');
        $kuota_bimbingan = (int)($_POST['kuota_bimbingan'] ?? 10);

        if (empty($nama) || empty($nip) || empty($bidang_ilmu) || empty($password) || empty($confirm_password) || empty($otoritas)) {
            $_SESSION['swal_error'] = 'Harap isi semua field yang wajib (*)!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
            exit;
        }

        if ($password !== $confirm_password) {
            $_SESSION['swal_error'] = 'Password dan konfirmasi password tidak cocok!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['swal_error'] = 'Password minimal harus terdiri dari 8 karakter!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
            exit;
        }

        if (strlen($password) > 100) {
            $_SESSION['swal_error'] = 'Password tidak boleh lebih dari 100 karakter!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
            exit;
        }

        try {
            // Clean NIP (no spaces)
            $nip = str_replace(' ', '', $nip);

            // Check if NIP is already registered
            $check = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
            $check->execute([':nip' => $nip]);
            if ($check->fetchColumn() > 0) {
                $_SESSION['swal_error'] = 'Dosen dengan NIP tersebut sudah terdaftar!';
                header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
                exit;
            }

            // Handle photo upload
            $fotoFilename = 'images.jpg';
            if (isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExt = pathinfo($_FILES['foto_profile']['name'], PATHINFO_EXTENSION);
                $fotoFilename = 'profile_' . $nip . '_' . time() . '.' . $fileExt;
                move_uploaded_file($_FILES['foto_profile']['tmp_name'], $uploadDir . $fotoFilename);
            }

            // 1. Insert to users table first (auto register)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
            $stmtUser->execute([
                ':username' => $nip,
                ':password' => $hashedPassword,
                ':role' => 'dosen',
                ':otoritas' => $otoritas
            ]);

            // 2. Insert to dosen table second (referencing users username as NIP)
            $stmtDosen = $pdo->prepare("INSERT INTO dosen (nip, nama, bidang_ilmu, kuota_max, kuota_terisi, profile_picture, universitas, fakultas, prodi) VALUES (:nip, :nama, :bidang, :kuota, 0, :foto, :univ, :fak, :prodi)");
            $stmtDosen->execute([
                ':nip' => $nip,
                ':nama' => $nama,
                ':bidang' => $bidang_ilmu,
                ':univ' => $universitas ?: 'Universitas Lampung',
                ':fak' => $fakultas ?: 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                ':prodi' => $program_studi ?: 'Ilmu Komputer',
                ':foto' => $fotoFilename,
                ':kuota' => $kuota_bimbingan
            ]);

            $_SESSION['swal_success'] = 'Dosen berhasil didaftarkan!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
            exit;

        } catch (PDOException $e) {
            file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "tambah_dosen error: " . $e->getMessage() . "\n", FILE_APPEND);
            $_SESSION['swal_error'] = 'Database Error: ' . $e->getMessage();
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_add.php");
            exit;
        }
    }

    if ($action === 'edit_dosen') {
        $nip_lama = str_replace(' ', '', trim($_POST['nip_lama'] ?? ''));
        $nama = trim($_POST['nama'] ?? '');
        $nip = str_replace(' ', '', trim($_POST['nip'] ?? ''));
        $bidang_ilmu = trim($_POST['bidang_ilmu'] ?? '');
        $otoritas = strtolower(trim($_POST['otoritas'] ?? 'dosen'));
        $universitas = trim($_POST['universitas'] ?? '');
        $fakultas = trim($_POST['fakultas'] ?? '');
        $program_studi = trim($_POST['program_studi'] ?? '');
        $kuota_bimbingan = (int)($_POST['kuota_bimbingan'] ?? 10);

        if (empty($nama) || empty($nip) || empty($bidang_ilmu) || empty($otoritas) || empty($nip_lama)) {
            $_SESSION['swal_error'] = 'Harap isi semua field yang wajib (*)!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_edit.php?nip=" . urlencode($nip_lama));
            exit;
        }

        try {
            // Fetch current dosen
            $stmtFetch = $pdo->prepare("SELECT * FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
            $stmtFetch->execute([':nip' => $nip_lama]);
            $currentDosen = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if (!$currentDosen) {
                $_SESSION['swal_error'] = 'Dosen tidak ditemukan!';
                header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
                exit;
            }

            // Handle photo upload
            $fotoFilename = $currentDosen['profile_picture'] ?? 'images.jpg';
            if (isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExt = pathinfo($_FILES['foto_profile']['name'], PATHINFO_EXTENSION);
                $fotoFilename = 'profile_' . $nip . '_' . time() . '.' . $fileExt;
                move_uploaded_file($_FILES['foto_profile']['tmp_name'], $uploadDir . $fotoFilename);
            }

            // Update users and dosen tables ensuring foreign key constraints are not violated
            if ($nip !== $nip_lama) {
                // Check if new NIP already exists
                $check = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
                $check->execute([':nip' => $nip]);
                if ($check->fetchColumn() > 0) {
                    $_SESSION['swal_error'] = 'Dosen dengan NIP baru tersebut sudah terdaftar!';
                    header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_edit.php?nip=" . urlencode($nip_lama));
                    exit;
                }

                // 1. Insert new user record copying password and role from old record
                $stmtInsertNewUser = $pdo->prepare("
                    INSERT INTO users (username, password, role, otoritas)
                    SELECT :new_nip, password, role, :otoritas 
                    FROM users 
                    WHERE REPLACE(username, ' ', '') = REPLACE(:old_nip, ' ', '')
                ");
                $stmtInsertNewUser->execute([
                    ':new_nip' => $nip,
                    ':otoritas' => $otoritas,
                    ':old_nip' => $nip_lama
                ]);

                // 2. Update dosen table (re-routing NIP to the newly created user NIP)
                $stmtUpdateDosen = $pdo->prepare("UPDATE dosen SET nip = :nip, nama = :nama, bidang_ilmu = :bidang, universitas = :univ, fakultas = :fak, prodi = :prodi, profile_picture = :foto, kuota_max = :kuota WHERE REPLACE(nip, ' ', '') = REPLACE(:old_nip, ' ', '')");
                $stmtUpdateDosen->execute([
                    ':nip' => $nip,
                    ':nama' => $nama,
                    ':bidang' => $bidang_ilmu,
                    ':univ' => $universitas ?: 'Universitas Lampung',
                    ':fak' => $fakultas ?: 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                    ':prodi' => $program_studi ?: 'Ilmu Komputer',
                    ':foto' => $fotoFilename,
                    ':kuota' => $kuota_bimbingan,
                    ':old_nip' => $nip_lama
                ]);

                // 3. Delete old user record (no longer referenced by dosen)
                $stmtDeleteOldUser = $pdo->prepare("DELETE FROM users WHERE REPLACE(username, ' ', '') = REPLACE(:old_nip, ' ', '')");
                $stmtDeleteOldUser->execute([':old_nip' => $nip_lama]);

            } else {
                // If NIP didn't change, just update users otoritas and dosen table
                $stmtUpdateUser = $pdo->prepare("UPDATE users SET otoritas = :otoritas WHERE REPLACE(username, ' ', '') = REPLACE(:nip, ' ', '')");
                $stmtUpdateUser->execute([
                    ':otoritas' => $otoritas,
                    ':nip' => $nip
                ]);

                $stmtUpdateDosen = $pdo->prepare("UPDATE dosen SET nama = :nama, bidang_ilmu = :bidang, universitas = :univ, fakultas = :fak, prodi = :prodi, profile_picture = :foto, kuota_max = :kuota WHERE REPLACE(nip, ' ', '') = REPLACE(:old_nip, ' ', '')");
                $stmtUpdateDosen->execute([
                    ':nama' => $nama,
                    ':bidang' => $bidang_ilmu,
                    ':univ' => $universitas ?: 'Universitas Lampung',
                    ':fak' => $fakultas ?: 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                    ':prodi' => $program_studi ?: 'Ilmu Komputer',
                    ':foto' => $fotoFilename,
                    ':kuota' => $kuota_bimbingan,
                    ':old_nip' => $nip_lama
                ]);
            }

            $_SESSION['swal_success'] = 'Data dosen berhasil diperbarui!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
            exit;

        } catch (PDOException $e) {
            file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "edit_dosen error: " . $e->getMessage() . "\n", FILE_APPEND);
            $_SESSION['swal_error'] = 'Database Error: ' . $e->getMessage();
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing_edit.php?nip=" . urlencode($nip_lama));
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'hapus_dosen') {
        $nip = trim($_GET['nip'] ?? '');

        if (empty($nip)) {
            $_SESSION['swal_error'] = 'NIP Dosen tidak valid!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
            exit;
        }

        try {
            // Delete from dosen
            $stmtDelDosen = $pdo->prepare("DELETE FROM dosen WHERE nip = :nip");
            $stmtDelDosen->execute([':nip' => $nip]);

            // Delete from users
            $stmtDelUser = $pdo->prepare("DELETE FROM users WHERE username = :nip");
            $stmtDelUser->execute([':nip' => $nip]);

            $_SESSION['swal_success'] = 'Dosen berhasil dihapus!';
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal menghapus dosen: ' . $e->getMessage();
            header("Location: /bimbingan-skripsi/app/views/kaprodi/kuota_pembimbing.php");
            exit;
        }
    }
}
