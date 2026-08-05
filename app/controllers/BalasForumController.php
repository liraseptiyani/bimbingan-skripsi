<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

function getFormattedDateTimeRealtime() {
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $dayEng = date('l');
    $dayIndo = $days[$dayEng] ?? $dayEng;
    $dayNum = date('j');
    $monthName = $months[(int)date('n')];
    $year = date('Y');
    $timeStr = date('H:i');
    return "$dayIndo, $dayNum $monthName $year, $timeStr";
}

$otoritas = $_SESSION['otoritas'] ?? $_SESSION['role'] ?? 'mahasiswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bimbingan_id = isset($_POST['bimbingan_id']) ? (int)$_POST['bimbingan_id'] : 0;

    if ($otoritas === 'mahasiswa') {
        $isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
        $npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';
        try {
            $stmtCheckStatus = $pdo->prepare("SELECT status_bimbingan FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
            $stmtCheckStatus->execute([':npm' => $npmMhs]);
            $statusBimbingan = $stmtCheckStatus->fetchColumn();
            if ($statusBimbingan === 'selesai') {
                $_SESSION['swal_error'] = 'Anda tidak dapat mengirim tanggapan bimbingan karena status bimbingan Anda telah selesai/lulus!';
                header("Location: " . BASE_URL . "/app/views/mahasiswa/detail_bimbingan.php?id=" . $bimbingan_id);
                exit;
            }
        } catch (PDOException $e) {}
    }
    $pesan = trim($_POST['pesan'] ?? '');
    $nama_file = '';

    // Handle upload file jika ada
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 2) . '/public/uploads/draft/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_basename = basename($_FILES['lampiran']['name']);
        $target_file = $upload_dir . time() . '_' . $file_basename;
        
        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
            $nama_file = time() . '_' . $file_basename;
        }
    }

    if (!empty($pesan) && $bimbingan_id > 0) {
        $tanggal = getFormattedDateTimeRealtime();
        $pengirim = ($otoritas === 'mahasiswa') ? 'mahasiswa' : 'dosen';

        // 1. Simpan ke database Neon jika tabel tersedia
        try {
            // Fetch bimbingan details and supervisors names to match who is replying
            $stmtB = $pdo->prepare("
                SELECT b.*, dm.pembimbing1, dm.pembimbing2 
                FROM bimbingan b
                LEFT JOIN distribusi_mahasiswa dm ON REPLACE(b.npm, ' ', '') = REPLACE(dm.npm, ' ', '')
                WHERE b.id = :id
                LIMIT 1
            ");
            $stmtB->execute([':id' => $bimbingan_id]);
            $bimb = $stmtB->fetch(PDO::FETCH_ASSOC);

            $p_nama = $_SESSION['nama'] ?? ($pengirim === 'mahasiswa' ? ($bimb['nama'] ?? 'Mahasiswa') : 'Dosen');

            $stmt = $pdo->prepare("INSERT INTO forum_bimbingan (bimbingan_id, pengirim, pengirim_nama, isi, file, tanggal) VALUES (:b_id, :pengirim, :p_nama, :isi, :file, :tgl)");
            $stmt->execute([
                ':b_id' => $bimbingan_id,
                ':pengirim' => $pengirim,
                ':p_nama' => $p_nama,
                ':isi' => $pesan,
                ':file' => $nama_file,
                ':tgl' => $tanggal
            ]);

            // Update individual and overall statuses
            if ($pengirim === 'mahasiswa') {
                $stmtUpdate = $pdo->prepare("UPDATE bimbingan SET status_balasan = 'belum_dibalas', status_pembimbing1 = 'belum_dibalas', status_pembimbing2 = 'belum_dibalas' WHERE id = :id");
                $stmtUpdate->execute([':id' => $bimbingan_id]);
            } else {
                // Sender is dosen -> track individual response status
                $statusP1 = $bimb['status_pembimbing1'] ?? 'belum_dibalas';
                $statusP2 = $bimb['status_pembimbing2'] ?? 'belum_dibalas';

                $matchesP1 = false;
                $matchesP2 = false;

                if (!empty($bimb['pembimbing1'])) {
                    // Match normalized names (remove non-alphabet characters to prevent title mismatches)
                    $normP1 = strtolower(preg_replace('/[^a-z]/', '', $bimb['pembimbing1']));
                    $normSender = strtolower(preg_replace('/[^a-z]/', '', $p_nama));
                    if ($normP1 === $normSender || strpos($normP1, $normSender) !== false || strpos($normSender, $normP1) !== false) {
                        $statusP1 = 'sudah_dibalas';
                        $matchesP1 = true;
                    }
                }
                if (!empty($bimb['pembimbing2'])) {
                    $normP2 = strtolower(preg_replace('/[^a-z]/', '', $bimb['pembimbing2']));
                    $normSender = strtolower(preg_replace('/[^a-z]/', '', $p_nama));
                    if ($normP2 === $normSender || strpos($normP2, $normSender) !== false || strpos($normSender, $normP2) !== false) {
                        $statusP2 = 'sudah_dibalas';
                        $matchesP2 = true;
                    }
                }

                // Fallback if no supervisor matched directly
                if (!$matchesP1 && !$matchesP2) {
                    $statusP1 = 'sudah_dibalas';
                }

                // Overall status is 'sudah_dibalas' if all assigned supervisors have replied
                $needP1 = !empty($bimb['pembimbing1']);
                $needP2 = !empty($bimb['pembimbing2']);

                $allReplied = true;
                if ($needP1 && $statusP1 !== 'sudah_dibalas') $allReplied = false;
                if ($needP2 && $statusP2 !== 'sudah_dibalas') $allReplied = false;

                $new_status = $allReplied ? 'sudah_dibalas' : 'belum_dibalas';

                $stmtUpdate = $pdo->prepare("UPDATE bimbingan SET status_balasan = :status, status_pembimbing1 = :p1, status_pembimbing2 = :p2 WHERE id = :id");
                $stmtUpdate->execute([
                    ':status' => $new_status,
                    ':p1' => $statusP1,
                    ':p2' => $statusP2,
                    ':id' => $bimbingan_id
                ]);
            }
        } catch (PDOException $e) {
            // Jika tabel belum siap di database, abaikan error PDO dan simpan di session
        }

        // 2. Simpan juga ke Session untuk tampilan interaktif realtime/demo
        if (!isset($_SESSION['forum_bimbingan'][$bimbingan_id])) {
            $_SESSION['forum_bimbingan'][$bimbingan_id] = [
                [
                    'id'       => 1,
                    'pengirim' => 'mahasiswa',
                    'tanggal'  => 'Monday, 4 Mei 2026, 10:14 AM',
                    'isi'      => 'Assalamualaikum Pak, Izin mengirimkan Draft Bab 1 saya. Mohon koreksinya pak, terima kasih.',
                    'file'     => 'Draft Bab1-Lira.pdf',
                ]
            ];
        }

        $_SESSION['forum_bimbingan'][$bimbingan_id][] = [
            'id'       => time(),
            'pengirim' => $pengirim,
            'tanggal'  => $tanggal,
            'isi'      => $pesan,
            'file'     => $nama_file
        ];

        // Tandai status balasan di Session
        $_SESSION['status_balasan'][$bimbingan_id] = $new_status;

        // Update di session list bimbingan jika ada
        if (isset($_SESSION['bimbingan_list'])) {
            foreach ($_SESSION['bimbingan_list'] as &$item) {
                if ($item['id'] == $bimbingan_id) {
                    $item['status_balasan'] = $new_status;
                }
            }
            unset($item);
        }

        // Pesan Sukses untuk Pop-up Alert
        $_SESSION['swal_success'] = 'Pesan forum bimbingan berhasil dikirim!';
    }

    if ($otoritas === 'mahasiswa') {
        header("Location: " . BASE_URL . "/app/views/mahasiswa/detail_bimbingan.php?id=" . $bimbingan_id);
    } else {
        header("Location: " . BASE_URL . "/app/views/dosen/detail_bimbingan.php?id=" . $bimbingan_id);
    }
    exit;
}

if ($otoritas === 'mahasiswa') {
    header("Location: " . BASE_URL . "/app/views/mahasiswa/bimbingan.php");
} else {
    header("Location: " . BASE_URL . "/app/views/dosen/bimbingan.php");
}
exit;
