<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

// Auto-create table helper if not exists
function ensureTableExists($pdo) {
    try {
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

        try {
            $pdo->exec("ALTER TABLE distribusi_mahasiswa MODIFY COLUMN judul_skripsi TEXT DEFAULT NULL");
        } catch (PDOException $e) {}
        try {
            $pdo->exec("ALTER TABLE distribusi_mahasiswa MODIFY COLUMN pembahas1 VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $e) {}
    } catch (PDOException $e) {
        // Table creation failed
    }
}

// Helper to auto-increment Nomor SK sequentially (e.g. 1439 -> 1440)
function incrementSkNumber($skNumber, $incrementBy) {
    if ($incrementBy === 0) {
        return $skNumber;
    }
    if (preg_match('/(\d+)/', $skNumber, $matches)) {
        $num = $matches[1];
        $newNum = (int)$num + $incrementBy;
        $paddedNum = str_pad($newNum, strlen($num), '0', STR_PAD_LEFT);
        
        $pos = strpos($skNumber, $num);
        if ($pos !== false) {
            return substr_replace($skNumber, $paddedNum, $pos, strlen($num));
        }
    }
    return $skNumber;
}

// Auto-generator function for next sequential Nomor SK
function getNextNomorSk($pdo) {
    try {
        // Fetch last 50 entries to find the maximum sequential number prefix
        $stmt = $pdo->query("SELECT nomor_sk FROM distribusi_mahasiswa WHERE nomor_sk LIKE '%/UN26.17.06/Tema/%' ORDER BY created_at DESC LIMIT 50");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $maxNum = 1438; // Default starting sequence - 1 (so first auto SK becomes 1439)
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
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureTableExists($pdo);

    if ($action === 'tambah_distribusi') {
        $npms = $_POST['npm'] ?? [];

        // Convert to array if single submission
        if (!is_array($npms)) {
            $npms = [$npms];
            $namas = [$_POST['nama'] ?? ''];
            $judul_skripsis = [$_POST['judul_skripsi'] ?? ''];
            $pembimbing1s = [$_POST['pembimbing1'] ?? ''];
            $pembimbing2s = [$_POST['pembimbing2'] ?? ''];
            $pembahas1s = [$_POST['pembahas1'] ?? ''];
            $pembahas2s = [$_POST['pembahas2'] ?? ''];
        } else {
            $namas = $_POST['nama'] ?? [];
            $judul_skripsis = $_POST['judul_skripsi'] ?? [];
            $pembimbing1s = $_POST['pembimbing1'] ?? [];
            $pembimbing2s = $_POST['pembimbing2'] ?? [];
            $pembahas1s = $_POST['pembahas1'] ?? [];
            $pembahas2s = $_POST['pembahas2'] ?? [];
        }

        // Auto-generate starting Nomor SK
        $nomor_sk_awal = getNextNomorSk($pdo);

        $successCount = 0;
        $pdo->beginTransaction();
        try {
            for ($i = 0; $i < count($npms); $i++) {
                $rawNpm = trim($npms[$i] ?? '');
                if (empty($rawNpm)) continue;

                $npm = $rawNpm;
                $nama = trim($namas[$i] ?? '');

                if (strpos($rawNpm, '|') !== false) {
                    $parts = explode('|', $rawNpm);
                    $npm = trim($parts[0]);
                    $nama = trim($parts[1]);
                }

                $judul_skripsi = trim($judul_skripsis[$i] ?? '');
                if (empty($judul_skripsi)) {
                    $judul_skripsi = '-';
                }

                $pembimbing1 = trim($pembimbing1s[$i] ?? '');
                $pembimbing2 = trim($pembimbing2s[$i] ?? '');
                $pembahas1 = trim($pembahas1s[$i] ?? '');
                $pembahas2 = trim($pembahas2s[$i] ?? '');
                
                // Increment Nomor SK sequentially
                $nomor_sk = incrementSkNumber($nomor_sk_awal, $successCount);

                if (empty($npm) || empty($pembimbing1) || empty($pembahas1)) {
                    continue; 
                }

                // Delete existing distribution
                $delDist = $pdo->prepare("DELETE FROM distribusi_mahasiswa WHERE npm = :npm");
                $delDist->execute([':npm' => $npm]);

                // Insert new distribution
                $sql = "INSERT INTO distribusi_mahasiswa (npm, nama, judul_skripsi, pembimbing1, pembimbing2, pembahas1, pembahas2, nomor_sk) 
                        VALUES (:npm, :nama, :judul, :p1, :p2, :pb1, :pb2, :sk)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':npm' => $npm,
                    ':nama' => $nama,
                    ':judul' => $judul_skripsi,
                    ':p1' => $pembimbing1,
                    ':p2' => !empty($pembimbing2) ? $pembimbing2 : null,
                    ':pb1' => !empty($pembahas1) ? $pembahas1 : null,
                    ':pb2' => !empty($pembahas2) ? $pembahas2 : null,
                    ':sk' => $nomor_sk
                ]);

                // Sync with pengajuan_judul: create pre-approved entry without deleting history
                $stmtLast = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE mahasiswa_npm = :npm ORDER BY id DESC LIMIT 1");
                $stmtLast->execute([':npm' => $npm]);
                $lastPengajuan = $stmtLast->fetch(PDO::FETCH_ASSOC);

                $deskripsi = $lastPengajuan ? $lastPengajuan['deskripsi'] : '';
                $file_krs = $lastPengajuan ? $lastPengajuan['file_krs'] : null;
                $file_transkrip = $lastPengajuan ? $lastPengajuan['file_transkrip'] : null;
                $file_proposal = $lastPengajuan ? $lastPengajuan['file_proposal'] : null;
                $file_ktm = $lastPengajuan ? $lastPengajuan['file_ktm'] : null;
                $file_form_tema = $lastPengajuan ? $lastPengajuan['file_form_tema'] : null;
                $file_bukti_ukt = $lastPengajuan ? $lastPengajuan['file_bukti_ukt'] : null;
                $file_krs_terakhir = $lastPengajuan ? $lastPengajuan['file_krs_terakhir'] : null;
                $file_form_verifikasi = $lastPengajuan ? $lastPengajuan['file_form_verifikasi'] : null;
                $file_bukti_acc = $lastPengajuan ? $lastPengajuan['file_bukti_acc'] : null;
                $file_form_penetapan = $lastPengajuan ? $lastPengajuan['file_form_penetapan'] : null;
                $file_bab1 = $lastPengajuan ? $lastPengajuan['file_bab1'] : null;
                $file_bab1_alt = $lastPengajuan ? $lastPengajuan['file_bab1_alt'] : null;
                $judul_alternatif = $lastPengajuan ? $lastPengajuan['judul_alternatif'] : null;
                $judul_lama = $lastPengajuan ? $lastPengajuan['judul'] : null;
                $judul_alternatif_lama = $lastPengajuan ? $lastPengajuan['judul_alternatif'] : null;
                $judul_disetujui = $lastPengajuan ? $lastPengajuan['judul_disetujui'] : 'utama';

                $sqlP = "INSERT INTO pengajuan_judul (
                            mahasiswa_npm, mahasiswa_nama, judul, deskripsi, status, judul_disetujui, 
                            pembimbing1, pembimbing2, pembahas1, pembahas2, nomor_sk, tanggal_persetujuan,
                            file_krs, file_transkrip, file_proposal, file_ktm, file_form_tema, 
                            file_bukti_ukt, file_krs_terakhir, file_form_verifikasi, file_bukti_acc, 
                            file_form_penetapan, file_bab1, file_bab1_alt, judul_alternatif, 
                            judul_lama, judul_alternatif_lama
                        ) VALUES (
                            :npm, :nama, :judul, :deskripsi, 'disetujui', :judul_disetujui,
                            :p1, :p2, :pb1, :pb2, :sk, CURRENT_TIMESTAMP,
                            :file_krs, :file_transkrip, :file_proposal, :file_ktm, :file_form_tema, 
                            :file_bukti_ukt, :file_krs_terakhir, :file_form_verifikasi, :file_bukti_acc, 
                            :file_form_penetapan, :file_bab1, :file_bab1_alt, :judul_alternatif, 
                            :judul_lama, :judul_alternatif_lama
                        )";
                $stmtP = $pdo->prepare($sqlP);
                $stmtP->execute([
                    ':npm' => $npm,
                    ':nama' => $nama,
                    ':judul' => $judul_skripsi,
                    ':deskripsi' => $deskripsi,
                    ':judul_disetujui' => $judul_disetujui,
                    ':p1' => $pembimbing1,
                    ':p2' => !empty($pembimbing2) ? $pembimbing2 : null,
                    ':pb1' => !empty($pembahas1) ? $pembahas1 : null,
                    ':pb2' => !empty($pembahas2) ? $pembahas2 : null,
                    ':sk' => $nomor_sk,
                    ':file_krs' => $file_krs,
                    ':file_transkrip' => $file_transkrip,
                    ':file_proposal' => $file_proposal,
                    ':file_ktm' => $file_ktm,
                    ':file_form_tema' => $file_form_tema,
                    ':file_bukti_ukt' => $file_bukti_ukt,
                    ':file_krs_terakhir' => $file_krs_terakhir,
                    ':file_form_verifikasi' => $file_form_verifikasi,
                    ':file_bukti_acc' => $file_bukti_acc,
                    ':file_form_penetapan' => $file_form_penetapan,
                    ':file_bab1' => $file_bab1,
                    ':file_bab1_alt' => $file_bab1_alt,
                    ':judul_alternatif' => $judul_alternatif,
                    ':judul_lama' => $judul_lama,
                    ':judul_alternatif_lama' => $judul_alternatif_lama
                ]);

                $successCount++;
            }

            $pdo->commit();
            $_SESSION['swal_success'] = "Berhasil mendistribusikan $successCount mahasiswa!";
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['swal_error'] = 'Gagal menyimpan data: ' . $e->getMessage();
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa_add.php");
            exit;
        }
    }

    elseif ($action === 'edit_distribusi') {
        $npm_lama = trim($_POST['npm_lama'] ?? '');
        $npm = trim($_POST['npm'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $judul_skripsi = trim($_POST['judul_skripsi'] ?? '');
        if (empty($judul_skripsi)) {
            $judul_skripsi = '-';
        }
        $pembimbing1 = trim($_POST['pembimbing1'] ?? '');
        $pembimbing2 = trim($_POST['pembimbing2'] ?? '');
        $pembahas1 = trim($_POST['pembahas1'] ?? '');
        $pembahas2 = trim($_POST['pembahas2'] ?? '');
        $nomor_sk = trim($_POST['nomor_sk'] ?? '');

        // If Nomor SK is empty in edit, auto-generate next one
        if (empty($nomor_sk)) {
            $nomor_sk = getNextNomorSk($pdo);
        }

        if (empty($npm_lama) || empty($npm) || empty($nama) || empty($pembimbing1) || empty($pembahas1) || empty($nomor_sk)) {
            $_SESSION['swal_error'] = 'Harap isi semua field yang wajib (*)!';
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa_edit.php?npm=" . urlencode($npm_lama));
            exit;
        }

        try {
            if ($npm !== $npm_lama) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM distribusi_mahasiswa WHERE npm = :npm");
                $checkStmt->execute([':npm' => $npm]);
                if ($checkStmt->fetchColumn() > 0) {
                    $_SESSION['swal_error'] = 'NPM baru sudah terdaftar untuk mahasiswa lain!';
                    header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa_edit.php?npm=" . urlencode($npm_lama));
                    exit;
                }
            }

            // Update
            $sql = "UPDATE distribusi_mahasiswa 
                    SET npm = :npm, nama = :nama, judul_skripsi = :judul, 
                        pembimbing1 = :p1, pembimbing2 = :p2, 
                        pembahas1 = :pb1, pembahas2 = :pb2, 
                        nomor_sk = :sk
                    WHERE npm = :npm_lama";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':npm' => $npm,
                ':nama' => $nama,
                ':judul' => $judul_skripsi,
                ':p1' => $pembimbing1,
                ':p2' => !empty($pembimbing2) ? $pembimbing2 : null,
                ':pb1' => !empty($pembahas1) ? $pembahas1 : null,
                ':pb2' => !empty($pembahas2) ? $pembahas2 : null,
                ':sk' => $nomor_sk,
                ':npm_lama' => $npm_lama
            ]);

            // If NPM changed, update the npm of old records in pengajuan_judul first
            if ($npm !== $npm_lama) {
                $updateNpm = $pdo->prepare("UPDATE pengajuan_judul SET mahasiswa_npm = :npm WHERE mahasiswa_npm = :npm_lama");
                $updateNpm->execute([':npm' => $npm, ':npm_lama' => $npm_lama]);
            }

            // Sync with pengajuan_judul: create pre-approved entry without deleting history
            $stmtLast = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE mahasiswa_npm = :npm ORDER BY id DESC LIMIT 1");
            $stmtLast->execute([':npm' => $npm]);
            $lastPengajuan = $stmtLast->fetch(PDO::FETCH_ASSOC);

            $deskripsi = $lastPengajuan ? $lastPengajuan['deskripsi'] : '';
            $file_krs = $lastPengajuan ? $lastPengajuan['file_krs'] : null;
            $file_transkrip = $lastPengajuan ? $lastPengajuan['file_transkrip'] : null;
            $file_proposal = $lastPengajuan ? $lastPengajuan['file_proposal'] : null;
            $file_ktm = $lastPengajuan ? $lastPengajuan['file_ktm'] : null;
            $file_form_tema = $lastPengajuan ? $lastPengajuan['file_form_tema'] : null;
            $file_bukti_ukt = $lastPengajuan ? $lastPengajuan['file_bukti_ukt'] : null;
            $file_krs_terakhir = $lastPengajuan ? $lastPengajuan['file_krs_terakhir'] : null;
            $file_form_verifikasi = $lastPengajuan ? $lastPengajuan['file_form_verifikasi'] : null;
            $file_bukti_acc = $lastPengajuan ? $lastPengajuan['file_bukti_acc'] : null;
            $file_form_penetapan = $lastPengajuan ? $lastPengajuan['file_form_penetapan'] : null;
            $file_bab1 = $lastPengajuan ? $lastPengajuan['file_bab1'] : null;
            $file_bab1_alt = $lastPengajuan ? $lastPengajuan['file_bab1_alt'] : null;
            $judul_alternatif = $lastPengajuan ? $lastPengajuan['judul_alternatif'] : null;
            $judul_lama = $lastPengajuan ? $lastPengajuan['judul'] : null;
            $judul_alternatif_lama = $lastPengajuan ? $lastPengajuan['judul_alternatif'] : null;
            $judul_disetujui = $lastPengajuan ? $lastPengajuan['judul_disetujui'] : 'utama';

            $sqlP = "INSERT INTO pengajuan_judul (
                        mahasiswa_npm, mahasiswa_nama, judul, deskripsi, status, judul_disetujui, 
                        pembimbing1, pembimbing2, pembahas1, pembahas2, nomor_sk, tanggal_persetujuan,
                        file_krs, file_transkrip, file_proposal, file_ktm, file_form_tema, 
                        file_bukti_ukt, file_krs_terakhir, file_form_verifikasi, file_bukti_acc, 
                        file_form_penetapan, file_bab1, file_bab1_alt, judul_alternatif, 
                        judul_lama, judul_alternatif_lama
                    ) VALUES (
                        :npm, :nama, :judul, :deskripsi, 'disetujui', :judul_disetujui,
                        :p1, :p2, :pb1, :pb2, :sk, CURRENT_TIMESTAMP,
                        :file_krs, :file_transkrip, :file_proposal, :file_ktm, :file_form_tema, 
                        :file_bukti_ukt, :file_krs_terakhir, :file_form_verifikasi, :file_bukti_acc, 
                        :file_form_penetapan, :file_bab1, :file_bab1_alt, :judul_alternatif, 
                        :judul_lama, :judul_alternatif_lama
                    )";
            $stmtP = $pdo->prepare($sqlP);
            $stmtP->execute([
                ':npm' => $npm,
                ':nama' => $nama,
                ':judul' => $judul_skripsi,
                ':deskripsi' => $deskripsi,
                ':judul_disetujui' => $judul_disetujui,
                ':p1' => $pembimbing1,
                ':p2' => !empty($pembimbing2) ? $pembimbing2 : null,
                ':pb1' => !empty($pembahas1) ? $pembahas1 : null,
                ':pb2' => !empty($pembahas2) ? $pembahas2 : null,
                ':sk' => $nomor_sk,
                ':file_krs' => $file_krs,
                ':file_transkrip' => $file_transkrip,
                ':file_proposal' => $file_proposal,
                ':file_ktm' => $file_ktm,
                ':file_form_tema' => $file_form_tema,
                ':file_bukti_ukt' => $file_bukti_ukt,
                ':file_krs_terakhir' => $file_krs_terakhir,
                ':file_form_verifikasi' => $file_form_verifikasi,
                ':file_bukti_acc' => $file_bukti_acc,
                ':file_form_penetapan' => $file_form_penetapan,
                ':file_bab1' => $file_bab1,
                ':file_bab1_alt' => $file_bab1_alt,
                ':judul_alternatif' => $judul_alternatif,
                ':judul_lama' => $judul_lama,
                ':judul_alternatif_lama' => $judul_alternatif_lama
            ]);

            $_SESSION['swal_success'] = 'Data distribusi mahasiswa berhasil diubah!';
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal mengubah data: ' . $e->getMessage();
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa_edit.php?npm=" . urlencode($npm_lama));
            exit;
        }
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ensureTableExists($pdo);

    if ($action === 'hapus_distribusi') {
        $npm = trim($_GET['npm'] ?? '');

        if (empty($npm)) {
            $_SESSION['swal_error'] = 'NPM tidak valid!';
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM distribusi_mahasiswa WHERE npm = :npm");
            $stmt->execute([':npm' => $npm]);

            // Sync: delete the entry created for it
            $stmtSync = $pdo->prepare("DELETE FROM pengajuan_judul WHERE mahasiswa_npm = :npm");
            $stmtSync->execute([':npm' => $npm]);

            $_SESSION['swal_success'] = 'Data distribusi mahasiswa berhasil dihapus!';
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal menghapus data: ' . $e->getMessage();
            header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
            exit;
        }
    }
}

header("Location: " . BASE_URL . "/app/views/kaprodi/distribusi_mahasiswa.php");
exit;
