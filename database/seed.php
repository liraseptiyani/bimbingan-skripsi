<?php
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "=== Memulai Seeding Database ===\n";

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

    // 1. Data Dosen Default
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

    echo "\nSeeding akun dosen default...\n";
    foreach ($defaultDosen as $d) {
        $cleanNip = str_replace(' ', '', $d['nip']);
        
        // Cek apakah user sudah ada
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE REPLACE(username, ' ', '') = :nip");
        $checkUser->execute([':nip' => $cleanNip]);
        $userExists = $checkUser->fetchColumn() > 0;

        if (!$userExists) {
            $hashedPassword = password_hash($cleanNip, PASSWORD_DEFAULT);
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
            $stmtU->execute([
                ':username' => $d['nip'],
                ':password' => $hashedPassword,
                ':role' => 'dosen',
                ':otoritas' => $d['otoritas']
            ]);
            echo "- Akun user baru dibuat untuk dosen NIP: {$d['nip']}\n";
        }

        // Cek apakah dosen sudah ada di tabel dosen
        $checkDosen = $pdo->prepare("SELECT COUNT(*) FROM dosen WHERE REPLACE(nip, ' ', '') = :nip");
        $checkDosen->execute([':nip' => $cleanNip]);
        $dosenExists = $checkDosen->fetchColumn() > 0;

        if (!$dosenExists) {
            $stmtD = $pdo->prepare("INSERT INTO dosen (nip, nama, bidang_ilmu, kuota_max, kuota_terisi, profile_picture, universitas, fakultas, prodi) VALUES (:nip, :nama, :bidang, :kuota, 0, :foto, :univ, :fak, :prodi)");
            $stmtD->execute([
                ':nip' => $d['nip'],
                ':nama' => $d['nama'],
                ':bidang' => $d['bidang'],
                ':kuota' => $d['kuota'],
                ':foto' => 'images.jpg',
                ':univ' => 'Universitas Lampung',
                ':fak' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                ':prodi' => 'Ilmu Komputer'
            ]);
            echo "- Data dosen berhasil ditambahkan: {$d['nama']} (NIP: {$d['nip']})\n";
        } else {
            echo "- Dosen {$d['nama']} sudah terdaftar.\n";
        }
    }

    // 2. Data Mahasiswa Default
    $defaultMhs = [
        ['npm' => '2217051151', 'nama' => 'LIRA SEPTIYANI', 'password' => '123456', 'foto' => 'profile_mhs_2217051151_1784989987.jpeg'],
        ['npm' => '2217051105', 'nama' => 'Audhia Safitri', 'password' => '654321', 'foto' => null],
        ['npm' => '2257051015', 'nama' => 'Intan Maghfirah', 'password' => '123456', 'foto' => null],
        ['npm' => '2217051101', 'nama' => 'Rizki Mahesa', 'password' => '123456', 'foto' => null],
        ['npm' => '2217051018', 'nama' => 'Adilla Aulia Desriyanti', 'password' => '123456', 'foto' => null]
    ];

    echo "\nSeeding akun mahasiswa default...\n";
    foreach ($defaultMhs as $m) {
        $cleanNpm = str_replace(' ', '', $m['npm']);
        
        // Cek apakah user sudah ada
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE REPLACE(username, ' ', '') = :npm");
        $checkUser->execute([':npm' => $cleanNpm]);
        $userExists = $checkUser->fetchColumn() > 0;

        if (!$userExists) {
            $hashedPassword = password_hash($m['password'], PASSWORD_DEFAULT);
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
            $stmtU->execute([
                ':username' => $m['npm'],
                ':password' => $hashedPassword,
                ':role' => 'mahasiswa',
                ':otoritas' => 'mahasiswa'
            ]);
            echo "- Akun user baru dibuat untuk mahasiswa NPM: {$m['npm']}\n";
        }

        // Cek apakah mahasiswa sudah ada di tabel mahasiswa
        $checkMhs = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa WHERE REPLACE(npm, ' ', '') = :npm");
        $checkMhs->execute([':npm' => $cleanNpm]);
        $mhsExists = $checkMhs->fetchColumn() > 0;

        if (!$mhsExists) {
            $stmtM = $pdo->prepare("INSERT INTO mahasiswa (npm, nama, profile_picture) VALUES (:npm, :nama, :foto)");
            $stmtM->execute([
                ':npm' => $m['npm'],
                ':nama' => $m['nama'],
                ':foto' => $m['foto']
            ]);
            echo "- Data mahasiswa berhasil ditambahkan: {$m['nama']} (NPM: {$m['npm']})\n";
        } else {
            echo "- Mahasiswa {$m['nama']} sudah terdaftar.\n";
        }
    }

    echo "\n=== Seeding Database Berhasil Selesai ===\n";

} catch (Exception $e) {
    echo "\n[ERROR] Seeding gagal: " . $e->getMessage() . "\n";
}
