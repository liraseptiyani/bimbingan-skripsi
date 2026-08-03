<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: /bimbingan-skripsi/");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesan = trim($_POST['pesan'] ?? '');
    $nama_file = '';

    // Handle upload file draft PDF
    if (isset($_FILES['draft']) && $_FILES['draft']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 2) . '/public/uploads/draft/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_basename = basename($_FILES['draft']['name']);
        $nama_file = time() . '_' . $file_basename;
        $target_file = $upload_dir . $nama_file;

        move_uploaded_file($_FILES['draft']['tmp_name'], $target_file);
    }

    if (!empty($nama_file)) {
        // Format tanggal Indonesia sederhana
        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $tglNow = date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y') . ', ' . date('H.i');

        $isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
        $npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';
        $namaMhs = $isMahasiswaAccount ? ($_SESSION['nama'] ?? 'LIRA SEPTIYANI') : 'LIRA SEPTIYANI';

        // Check bimbingan status from database
        try {
            $stmtCheck = $pdo->prepare("SELECT status_bimbingan FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
            $stmtCheck->execute([':npm' => $npmMhs]);
            $statusBimbingan = $stmtCheck->fetchColumn();
            if ($statusBimbingan === 'selesai') {
                $_SESSION['swal_error'] = 'Anda tidak dapat menambah bimbingan karena status bimbingan Anda telah selesai/lulus!';
                header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
                exit;
            }
        } catch (PDOException $e) {}

        $bimbingan_id = time();

        // 1. Simpan ke database jika tersedia
        try {
            $stmt = $pdo->prepare("INSERT INTO bimbingan (id, tanggal, npm, nama, file_draft, status_balasan, status_pembimbing1, status_pembimbing2) VALUES (:id, :tgl, :npm, :nama, :draft, 'belum_dibalas', 'belum_dibalas', 'belum_dibalas')");
            $stmt->execute([
                ':id' => $bimbingan_id,
                ':tgl' => $tglNow,
                ':npm' => $npmMhs,
                ':nama' => $namaMhs,
                ':draft' => $nama_file
            ]);

            // Selalu simpan isi bimbingan pertama ke forum_bimbingan
            $tglForum = getFormattedDateTimeRealtime();
            $stmtForum = $pdo->prepare("INSERT INTO forum_bimbingan (bimbingan_id, pengirim, pengirim_nama, isi, file, tanggal) VALUES (:b_id, 'mahasiswa', :p_nama, :isi, :file, :tgl)");
            $stmtForum->execute([
                ':b_id' => $bimbingan_id,
                ':p_nama' => $namaMhs,
                ':isi' => !empty($pesan) ? $pesan : 'Mengunggah draft bimbingan baru.',
                ':file' => $nama_file,
                ':tgl' => $tglForum
            ]);
        } catch (PDOException $e) {
            file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "TambahBimbingan PDOException: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // 2. Simpan ke Session
        if (!isset($_SESSION['bimbingan_list'])) {
            $_SESSION['bimbingan_list'] = [
                [
                    'id'             => 1,
                    'tanggal'        => '7 Februari 2026, 10.41',
                    'npm'            => $npmMhs,
                    'nama'           => $namaMhs,
                    'file_draft'     => 'Draft Bab1-Lira.pdf',
                    'angkatan'       => 2022,
                    'status'         => 'Aktif',
                    'status_balasan' => $_SESSION['status_balasan'][1] ?? 'belum_dibalas',
                ]
            ];
        }

        $new_item = [
            'id'             => $bimbingan_id,
            'tanggal'        => $tglNow,
            'npm'            => $npmMhs,
            'nama'           => $namaMhs,
            'file_draft'     => $nama_file,
            'angkatan'       => 2022,
            'status'         => 'Aktif',
            'status_balasan' => 'belum_dibalas',
        ];

        array_unshift($_SESSION['bimbingan_list'], $new_item);
        $_SESSION['status_balasan'][$bimbingan_id] = 'belum_dibalas';

        $_SESSION['forum_bimbingan'][$bimbingan_id] = [
            [
                'id'       => 1,
                'pengirim' => 'mahasiswa',
                'tanggal'  => date('l, j F Y, h:i A'),
                'isi'      => !empty($pesan) ? $pesan : 'Mengunggah draft bimbingan baru.',
                'file'     => $nama_file,
            ]
        ];

        $_SESSION['swal_success'] = 'Draft bimbingan berhasil diunggah!';
    }

    header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
    exit;
}

header("Location: /bimbingan-skripsi/app/views/mahasiswa/bimbingan.php");
exit;
