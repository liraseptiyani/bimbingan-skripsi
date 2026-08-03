<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config/koneksi.php';

try {
    echo "Clearing database tables...\n";
    $tables = [
        'minat_topik',
        'topik_penelitian',
        'forum_bimbingan',
        'bimbingan',
        'distribusi_mahasiswa',
        'pengajuan_judul',
        'users',
        'dosen',
        'mahasiswa'
    ];
    
    // We truncate all tables using CASCADE to handle foreign key constraints
    $tablesList = implode(', ', $tables);
    $pdo->exec("TRUNCATE TABLE $tablesList CASCADE");
    echo "All tables successfully cleared!\n\n";

    echo "Seeding default lecturers...\n";
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
        // 1. Insert to users table with password_hash FIRST
        $cleanPassword = str_replace(' ', '', $d['nip']);
        $hashedPassword = password_hash($cleanPassword, PASSWORD_DEFAULT);
        $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
        $stmtU->execute([
            ':username' => $d['nip'],
            ':password' => $hashedPassword,
            ':role' => 'dosen',
            ':otoritas' => $d['otoritas']
        ]);

        // 2. Insert to dosen table SECOND
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

        echo "Seeded lecturer account: {$d['nama']} (NIP: {$d['nip']})\n";
    }

    echo "\nSeeding default students...\n";
    $defaultMhs = [
        ['npm' => '2217051151', 'nama' => 'LIRA SEPTIYANI', 'password' => '123456', 'foto' => 'profile_mhs_2217051151_1784989987.jpeg'],
        ['npm' => '2217051105', 'nama' => 'Audhia Safitri', 'password' => '654321', 'foto' => null],
        ['npm' => '2257051015', 'nama' => 'Intan Maghfirah', 'password' => '123456', 'foto' => null],
        ['npm' => '2217051101', 'nama' => 'Rizki Mahesa', 'password' => '123456', 'foto' => null],
        ['npm' => '2217051018', 'nama' => 'Adilla Aulia Desriyanti', 'password' => '123456', 'foto' => null]
    ];

    foreach ($defaultMhs as $m) {
        // 1. Insert to users table with password_hash FIRST
        $hashedPassword = password_hash($m['password'], PASSWORD_DEFAULT);
        $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, otoritas) VALUES (:username, :password, :role, :otoritas)");
        $stmtU->execute([
            ':username' => $m['npm'],
            ':password' => $hashedPassword,
            ':role' => 'mahasiswa',
            ':otoritas' => 'mahasiswa'
        ]);

        // 2. Insert to mahasiswa table SECOND
        $stmtM = $pdo->prepare("INSERT INTO mahasiswa (npm, nama, profile_picture) VALUES (:npm, :nama, :foto)");
        $stmtM->execute([
            ':npm' => $m['npm'],
            ':nama' => $m['nama'],
            ':foto' => $m['foto']
        ]);
        
        echo "Seeded student account: {$m['nama']} (NPM: {$m['npm']})\n";
    }

    echo "\nDatabase seeding completed successfully!\n";

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
