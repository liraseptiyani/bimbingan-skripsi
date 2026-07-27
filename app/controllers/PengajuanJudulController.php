<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak! Silakan login kembali.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ajukan') {
        // MAHASISWA SUBMIT TITLE
        if (($_SESSION['role'] ?? '') !== 'mahasiswa') {
            echo json_encode(['success' => false, 'message' => 'Hanya mahasiswa yang dapat mengajukan judul!']);
            exit;
        }

        $npm = $_SESSION['username'];
        $nama = $_SESSION['nama'] ?? 'Mahasiswa';
        $judul = trim($_POST['judul'] ?? '');
        $judul_alternatif = trim($_POST['judul_alternatif'] ?? '');
        $deskripsi = '';
        $pembimbing1 = null;
        $pembimbing2 = null;

        if (empty($judul)) {
            echo json_encode(['success' => false, 'message' => 'Judul wajib diisi!']);
            exit;
        }

        try {
            // Check if there is an active submission
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_judul WHERE mahasiswa_npm = :npm AND status = 'menunggu'");
            $stmtCheck->execute([':npm' => $npm]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Anda masih memiliki pengajuan judul yang berstatus menunggu persetujuan Kaprodi!']);
                exit;
            }

            // Upload helper
            $uploadHelper = function($fileKey, $npm, $prefix, $allowedExts = ['pdf']) {
                if (!isset($_FILES[$fileKey])) {
                    return ['success' => false, 'message' => 'Berkas ' . $fileKey . ' wajib diunggah!'];
                }

                // Check if multiple files are uploaded (name is array)
                if (is_array($_FILES[$fileKey]['name'])) {
                    $filenames = [];
                    $filesCount = count($_FILES[$fileKey]['name']);
                    
                    // Validate upload errors and extensions first
                    for ($i = 0; $i < $filesCount; $i++) {
                        if ($_FILES[$fileKey]['error'][$i] !== UPLOAD_ERR_OK) {
                            return ['success' => false, 'message' => 'Gagal mengunggah beberapa berkas untuk ' . $fileKey];
                        }
                        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'][$i], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExts)) {
                            return ['success' => false, 'message' => 'Format berkas untuk ' . $fileKey . ' tidak valid (harus ' . implode('/', $allowedExts) . ')!'];
                        }
                    }

                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/persyaratan/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    for ($i = 0; $i < $filesCount; $i++) {
                        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'][$i], PATHINFO_EXTENSION));
                        $filename = $prefix . '_' . ($i + 1) . '_' . str_replace(' ', '', $npm) . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'][$i], $uploadDir . $filename)) {
                            $filenames[] = $filename;
                        } else {
                            return ['success' => false, 'message' => 'Gagal mengunggah berkas ke-' . ($i + 1) . ' untuk ' . $fileKey];
                        }
                    }

                    return ['success' => true, 'filename' => implode(',', $filenames)];
                } else {
                    // Single file upload
                    if ($_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
                        return ['success' => false, 'message' => 'Berkas ' . $fileKey . ' wajib diunggah!'];
                    }
                    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExts)) {
                        return ['success' => false, 'message' => 'Format berkas untuk ' . $fileKey . ' tidak valid (harus ' . implode('/', $allowedExts) . ')!'];
                    }
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/persyaratan/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filename = $prefix . '_' . str_replace(' ', '', $npm) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename)) {
                        return ['success' => true, 'filename' => $filename];
                    }
                    return ['success' => false, 'message' => 'Gagal mengunggah berkas ' . $fileKey];
                }
            };

            // 1. Transkrip Akademik (PDF)
            $res = $uploadHelper('file_transkrip', $npm, 'transkrip', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_transkrip = $res['filename'];

            // 2. KTM (PDF)
            $res = $uploadHelper('file_ktm', $npm, 'ktm', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_ktm = $res['filename'];

            // 3. Form Pengajuan Tema (PDF)
            $res = $uploadHelper('file_form_tema', $npm, 'form_tema', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_form_tema = $res['filename'];

            // 4. Bukti Pembayaran UKT Semester 1 (PDF)
            $res = $uploadHelper('file_bukti_ukt', $npm, 'bukti_ukt', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_bukti_ukt = $res['filename'];

            // 5. KRS Terakhir (PDF)
            $res = $uploadHelper('file_krs_terakhir', $npm, 'krs_terakhir', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_krs_terakhir = $res['filename'];

            // 6. Form Verifikasi Berkas (PDF)
            $res = $uploadHelper('file_form_verifikasi', $npm, 'form_verifikasi', ['pdf']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_form_verifikasi = $res['filename'];

            // 7. Bukti ACC dengan Pembimbing (Optional PDF)
            $file_bukti_acc = null;
            if (isset($_FILES['file_bukti_acc']) && $_FILES['file_bukti_acc']['error'] === UPLOAD_ERR_OK) {
                $res = $uploadHelper('file_bukti_acc', $npm, 'bukti_acc', ['pdf']);
                if (!$res['success']) { echo json_encode($res); exit; }
                $file_bukti_acc = $res['filename'];
            }

            // 8. Form Penetapan Tema Penelitian (DOCX/DOC)
            $res = $uploadHelper('file_form_penetapan', $npm, 'form_penetapan', ['docx', 'doc']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_form_penetapan = $res['filename'];

            // 9. Halaman Judul & Bab 1 Utama (DOCX/DOC)
            $res = $uploadHelper('file_bab1', $npm, 'bab1', ['docx', 'doc']);
            if (!$res['success']) { echo json_encode($res); exit; }
            $file_bab1 = $res['filename'];

            // 10. Halaman Judul & Bab 1 Alternatif (Optional DOCX/DOC)
            $file_bab1_alt = null;
            if (isset($_FILES['file_bab1_alt']) && $_FILES['file_bab1_alt']['error'] === UPLOAD_ERR_OK) {
                $res = $uploadHelper('file_bab1_alt', $npm, 'bab1_alt', ['docx', 'doc']);
                if (!$res['success']) { echo json_encode($res); exit; }
                $file_bab1_alt = $res['filename'];
            }

            // Insert new submission
            $stmtInsert = $pdo->prepare("
                INSERT INTO pengajuan_judul (
                    mahasiswa_npm, mahasiswa_nama, judul, judul_alternatif, deskripsi, pembimbing1, pembimbing2, status, 
                    file_transkrip, file_ktm, file_form_tema, file_bukti_ukt, file_krs_terakhir, file_form_verifikasi, 
                    file_bukti_acc, file_form_penetapan, file_bab1, file_bab1_alt
                )
                VALUES (
                    :npm, :nama, :judul, :judul_alt, :deskripsi, :p1, :p2, 'menunggu', 
                    :f_transkrip, :f_ktm, :f_form_tema, :f_bukti_ukt, :f_krs_terakhir, :f_form_verifikasi, 
                    :f_bukti_acc, :f_form_penetapan, :f_bab1, :f_bab1_alt
                )
            ");
            $stmtInsert->execute([
                ':npm' => $npm,
                ':nama' => $nama,
                ':judul' => $judul,
                ':judul_alt' => $judul_alternatif ?: null,
                ':deskripsi' => $deskripsi,
                ':p1' => $pembimbing1,
                ':p2' => $pembimbing2 ?: null,
                ':f_transkrip' => $file_transkrip,
                ':f_ktm' => $file_ktm,
                ':f_form_tema' => $file_form_tema,
                ':f_bukti_ukt' => $file_bukti_ukt,
                ':f_krs_terakhir' => $file_krs_terakhir,
                ':f_form_verifikasi' => $file_form_verifikasi,
                ':f_bukti_acc' => $file_bukti_acc,
                ':f_form_penetapan' => $file_form_penetapan,
                ':f_bab1' => $file_bab1,
                ':f_bab1_alt' => $file_bab1_alt
            ]);

            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pengajuan: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'setujui') {
        // KAPRODI APPROVES AND DISTRIBUTES
        if (($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
            echo json_encode(['success' => false, 'message' => 'Hanya Kaprodi yang memiliki hak akses ini!']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $p1 = trim($_POST['pembimbing1'] ?? '');
        $p2 = trim($_POST['pembimbing2'] ?? '');
        $pb1 = trim($_POST['pembahas1'] ?? '');
        $pb2 = trim($_POST['pembahas2'] ?? '');
        $judul_disetujui_pilihan = trim($_POST['judul_disetujui_pilihan'] ?? 'utama');

        if ($id <= 0 || empty($p1)) {
            echo json_encode(['success' => false, 'message' => 'Field Pembimbing 1 wajib diisi!']);
            exit;
        }

        // Helper to generate the next sequential SK number
        $getNextNomorSk = function($pdo) {
            try {
                $stmt = $pdo->query("SELECT nomor_sk FROM distribusi_mahasiswa WHERE nomor_sk LIKE '%/UN26.17.06/Tema/%' ORDER BY created_at DESC LIMIT 50");
                $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $maxNum = 1438;
                foreach ($results as $sk) {
                    if (preg_match('/(\d+)/', $sk, $matches)) {
                        $num = (int)$matches[1];
                        if ($num > $maxNum) {
                            $maxNum = $num;
                        }
                    }
                }
                $nextNum = $maxNum + 1;
                $year = date('Y');
                return "No: $nextNum/UN26.17.06/Tema/$year";
            } catch (PDOException $e) {
                return "No: 1439/UN26.17.06/Tema/" . date('Y');
            }
        };

        try {
            // Get the submission info
            $stmtGet = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE id = :id LIMIT 1");
            $stmtGet->execute([':id' => $id]);
            $pengajuan = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$pengajuan) {
                echo json_encode(['success' => false, 'message' => 'Data pengajuan tidak ditemukan!']);
                exit;
            }

            // Determine which title is approved
            $judul_disetujui = ($judul_disetujui_pilihan === 'alternatif' && !empty($pengajuan['judul_alternatif'])) ? 'alternatif' : 'utama';
            $approved_title = ($judul_disetujui === 'alternatif') ? $pengajuan['judul_alternatif'] : $pengajuan['judul'];

            // Update status and choices in pengajuan_judul
            $stmtUpdate = $pdo->prepare("
                UPDATE pengajuan_judul 
                SET status = 'disetujui', 
                    pembimbing1 = :p1, 
                    pembimbing2 = :p2, 
                    judul_disetujui = :judul_disetujui,
                    tanggal_persetujuan = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':p1' => $p1,
                ':p2' => $p2 ?: null,
                ':judul_disetujui' => $judul_disetujui,
                ':id' => $id
            ]);

            // Auto-generate next Nomor SK sequentially
            $sk = $getNextNomorSk($pdo);

            // Upsert in distribusi_mahasiswa
            // First, make sure the table exists
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

            // Run alters just in case
            try {
                $pdo->exec("ALTER TABLE distribusi_mahasiswa MODIFY COLUMN judul_skripsi TEXT DEFAULT NULL");
            } catch (PDOException $e) {}
            try {
                $pdo->exec("ALTER TABLE distribusi_mahasiswa MODIFY COLUMN pembahas1 VARCHAR(255) DEFAULT NULL");
            } catch (PDOException $e) {}

            // Check if already exists in distribusi_mahasiswa
            $stmtCheckD = $pdo->prepare("SELECT COUNT(*) FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')");
            $stmtCheckD->execute([':npm' => $pengajuan['mahasiswa_npm']]);
            $exists = ((int)$stmtCheckD->fetchColumn() > 0);

            if ($exists) {
                $stmtUpsert = $pdo->prepare("
                    UPDATE distribusi_mahasiswa 
                    SET nama = :nama, judul_skripsi = :judul, pembimbing1 = :p1, pembimbing2 = :p2, pembahas1 = :pb1, pembahas2 = :pb2, nomor_sk = :sk
                    WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')
                ");
            } else {
                $stmtUpsert = $pdo->prepare("
                    INSERT INTO distribusi_mahasiswa (npm, nama, judul_skripsi, pembimbing1, pembimbing2, pembahas1, pembahas2, nomor_sk)
                    VALUES (:npm, :nama, :judul, :p1, :p2, :pb1, :pb2, :sk)
                ");
            }

            $stmtUpsert->execute([
                ':npm'  => $pengajuan['mahasiswa_npm'],
                ':nama' => $pengajuan['mahasiswa_nama'],
                ':judul' => $approved_title,
                ':p1'   => $p1,
                ':p2'   => $p2 ?: null,
                ':pb1'  => !empty($pb1) ? $pb1 : '-',
                ':pb2'  => !empty($pb2) ? $pb2 : null,
                ':sk'   => $sk
            ]);

            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'tolak') {
        // KAPRODI REJECTS
        if (($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
            echo json_encode(['success' => false, 'message' => 'Hanya Kaprodi yang memiliki hak akses ini!']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $alasan = trim($_POST['alasan'] ?? '');

        if ($id <= 0 || empty($alasan)) {
            echo json_encode(['success' => false, 'message' => 'Alasan penolakan wajib diisi!']);
            exit;
        }

        try {
            $stmtUpdate = $pdo->prepare("UPDATE pengajuan_judul SET status = 'ditolak', alasan = :alasan WHERE id = :id");
            $stmtUpdate->execute([
                ':alasan' => $alasan,
                ':id' => $id
            ]);

            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);
exit;
