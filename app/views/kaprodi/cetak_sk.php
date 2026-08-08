<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// PROTEKSI HALAMAN: hanya user terautentikasi (mhs/dosen)
// ==========================================================
if (!isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Auto-copy the signature scan image from Gemini AppData to public img folder
$destPath = dirname(__DIR__, 3) . '/public/img/ttd_tristiyanto.png';
if (!file_exists($destPath)) {
    $srcPath = 'C:/Users/asus/.gemini/antigravity-ide/brain/0fc964f9-9679-40a0-ba92-59c915833e8e/media__1785098511043.png';
    if (file_exists($srcPath)) {
        copy($srcPath, $destPath);
    }
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div style='padding:20px; font-family:sans-serif; text-align:center;'>ID Pengajuan tidak valid.</div>";
    exit;
}

// Fetch pengajuan
try {
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $p = null;
}

if (!$p) {
    echo "<div style='padding:20px; font-family:sans-serif; text-align:center;'>Data pengajuan tidak ditemukan.</div>";
    exit;
}

// If student, restrict to their own NPM
if ($role === 'mahasiswa') {
    $normUser = strtolower(preg_replace('/[^a-z0-9]/', '', $username));
    $normMhs = strtolower(preg_replace('/[^a-z0-9]/', '', $p['mahasiswa_npm']));
    if ($normUser !== $normMhs) {
        echo "<div style='padding:20px; font-family:sans-serif; text-align:center; color:red;'>Anda tidak memiliki akses ke dokumen ini.</div>";
        exit;
    }
}

// Fetch distribution details
try {
    $stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmtDist->execute([':npm' => $p['mahasiswa_npm']]);
    $dist = $stmtDist->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dist = null;
}

// Synchronize values: prefer values from the specific pengajuan_judul record if available, fallback to active distribution
$nomor_sk = !empty($p['nomor_sk']) ? $p['nomor_sk'] : ($dist['nomor_sk'] ?? 'No: -');
$p1 = !empty($p['pembimbing1']) ? $p['pembimbing1'] : ($dist['pembimbing1'] ?? '-');
$p2 = !empty($p['pembimbing2']) ? $p['pembimbing2'] : ($dist['pembimbing2'] ?? null);
$pb1 = !empty($p['pembahas1']) ? $p['pembahas1'] : ($dist['pembahas1'] ?? '-');
$pb2 = !empty($p['pembahas2']) ? $p['pembahas2'] : ($dist['pembahas2'] ?? null);

$judul_disetujui = ($p['judul_disetujui'] === 'alternatif' && !empty($p['judul_alternatif'])) ? $p['judul_alternatif'] : $p['judul'];

// Date helpers
function formatSkDate($dateStr) {
    if (empty($dateStr)) return '-';
    $timestamp = strtotime($dateStr);
    return date('d-M-Y', $timestamp);
}

// Dynamic NIP helper (Fetches NIP from dosen table where NIP corresponds to lecturer username)
function getDosenDetails($pdo, $name) {
    if (empty($name) || $name === '-') {
        return ['nama' => '-', 'nip' => '-'];
    }
    // Clean name for matching
    $cleanName = strtolower(preg_replace('/[^a-z0-9]/', '', $name));
    try {
        // Method 1: Exact match on clean string
        $stmt = $pdo->prepare("SELECT nama, nip FROM dosen WHERE REPLACE(REPLACE(REPLACE(LOWER(nama), ' ', ''), ',', ''), '.', '') = :name LIMIT 1");
        $stmt->execute([':name' => str_replace('.', '', $cleanName)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) return formatNip($res);

        // Method 2: LIKE search on clean name
        $stmt = $pdo->prepare("SELECT nama, nip FROM dosen WHERE REPLACE(REPLACE(LOWER(nama), ' ', ''), ',', '') LIKE :name LIMIT 1");
        $stmt->execute([':name' => '%' . $cleanName . '%']);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) return formatNip($res);

        // Method 3: Keyword search for multi-word matches
        $words = explode(' ', preg_replace('/[,\.]/', ' ', strtolower(trim($name))));
        $words = array_values(array_filter($words, function($w) {
            return strlen($w) > 2 && !in_array($w, ['kom', 'ssi', 'eng', 'prof', 'dr', 'phd', 'scom', 'mcs', 'mti', 'mt']);
        }));
        if (count($words) >= 1) {
            $queryStr = "SELECT nama, nip FROM dosen WHERE ";
            $conditions = [];
            $params = [];
            foreach ($words as $idx => $word) {
                $conditions[] = "LOWER(nama) LIKE :w" . $idx;
                $params[':w' . $idx] = '%' . $word . '%';
            }
            $queryStr .= implode(' AND ', $conditions) . ' LIMIT 1';
            $stmt = $pdo->prepare($queryStr);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return formatNip($res);
        }
    } catch (PDOException $e) {}
    return ['nama' => $name, 'nip' => '-'];
}

function formatNip($res) {
    $nip = preg_replace('/[^0-9]/', '', $res['nip']);
    if (strlen($nip) === 18) {
        $res['nip'] = substr($nip, 0, 8) . ' ' . substr($nip, 8, 6) . ' ' . substr($nip, 14, 1) . ' ' . substr($nip, 15);
    } else {
        $res['nip'] = $res['nip'];
    }
    return $res;
}

$dosenP1 = getDosenDetails($pdo, $p1);
$dosenP2 = ($p2 && $p2 !== '-') ? getDosenDetails($pdo, $p2) : ['nama' => '-', 'nip' => '-'];
$dosenPb1 = getDosenDetails($pdo, $pb1);
$dosenPb2 = ($pb2 && $pb2 !== '-') ? getDosenDetails($pdo, $pb2) : ['nama' => '-', 'nip' => '-'];

// Standard Kajur and Kaprodi matching
$kajurD = getDosenDetails($pdo, 'Dwi Sakethi');
$kajurName = $kajurD['nama'] !== '-' ? $kajurD['nama'] : 'Dwi Sakethi, S.Si., M.Kom';
$kajurNip = $kajurD['nip'] !== '-' ? $kajurD['nip'] : '19680611 199802 1 001';

$kaprodiD = getDosenDetails($pdo, 'Tristiyanto');
$kaprodiName = $kaprodiD['nama'] !== '-' ? $kaprodiD['nama'] : 'Tristiyanto, S.Kom., M.I.S., Ph.D';
$kaprodiNip = $kaprodiD['nip'] !== '-' ? $kaprodiD['nip'] : '19810414 200501 1 001';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK Penetapan Tema - <?= htmlspecialchars($p['mahasiswa_npm']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            font-family: Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif;
            font-size: 10pt;
            color: #000000;
        }

        /* Toolbar */
        .toolbar {
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .toolbar-title {
            font-family: sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toolbar-buttons {
            display: flex;
            gap: 12px;
        }
        .btn-tool {
            font-family: sans-serif;
            padding: 8px 16px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            transition: all 0.2s;
        }
        .btn-tool:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .btn-tool.btn-primary {
            background: #285aa9;
            color: #ffffff;
            border-color: #285aa9;
        }
        .btn-tool.btn-primary:hover {
            background: #1e4480;
        }

        /* Document Paper Container */
        .paper-wrapper {
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }
        .paper {
            background: #ffffff;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            box-sizing: border-box;
            padding: 18mm 20mm 15mm 20mm; /* exact PDF template margins */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
            font-family: "Times New Roman", Times, serif;
        }
        .kop-logo {
            width: 85px;
            vertical-align: top;
            padding-right: 15px;
            text-align: left;
        }
        .kop-logo img {
            width: 80px;
            height: auto;
        }
        .kop-text {
            text-align: center;
            vertical-align: top;
            font-size: 11pt;
            line-height: 1.3;
            font-weight: bold;
        }
        .kop-text .dept {
            font-size: 12pt;
            margin-bottom: 2px;
            letter-spacing: 0.2px;
        }
        .kop-text .univ {
            font-size: 13.5pt;
            margin-bottom: 2px;
        }
        .kop-text .fac {
            font-size: 12.5pt;
            margin-bottom: 2px;
        }
        .kop-text .jur {
            font-size: 13.5pt;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .kop-text .address {
            font-size: 9.5pt;
            font-weight: normal;
            line-height: 1.35;
        }

        .kop-divider {
            border: none;
            border-top: 2px solid #000000;
            margin-top: 6px;
            margin-bottom: 16px;
            height: 0;
        }

        /* Document Header */
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.35;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        /* Table SOP Layout */
        .sop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
            font-size: 10pt;
            table-layout: fixed; /* Keep columns perfectly consistent across all rows */
        }
        .sop-table th, 
        .sop-table td {
            border: 1px solid #a0aec0;
            padding: 6px 10px;
            vertical-align: middle;
            text-align: left;
            box-sizing: border-box;
            font-size: 10pt;
            height: 34px;
        }
        .sop-label {
            background-color: #dce6f1; /* exact blue-grey from PDF */
            font-weight: bold;
            color: #000000;
            white-space: nowrap; /* prevent labels from wrapping */
        }
        .sop-table td.sop-separator {
            background-color: #dce6f1;
            font-weight: bold;
            text-align: center !important;
            padding: 6px 0px !important;
        }
        .sop-val {
            background-color: #ffffff;
            font-weight: normal;
        }
        .sop-val-bold {
            font-weight: bold;
        }

        /* Signatures block */
        .sig-container {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            font-size: 10pt;
        }
        .sig-container td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .sig-space {
            height: 75px;
            position: relative;
        }
        .sig-image-wrap {
            position: absolute;
            top: -12px;
            left: 20px;
            z-index: 10;
        }
        .sig-image-wrap img {
            width: 130px;
            height: auto;
        }

        /* SOP Footer code bottom left */
        .sop-footer-code {
            position: absolute;
            bottom: 12mm;
            left: 20mm;
            font-size: 9.5pt;
            color: #000000;
            font-family: Calibri, sans-serif;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                margin: 0;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .paper-wrapper {
                padding: 0;
            }
            .paper {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                min-height: auto;
            }
            /* Reset positioning so footer prints correctly */
            .sop-footer-code {
                position: fixed;
                bottom: 10mm;
                left: 20mm;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR (NO PRINT) -->
    <div class="toolbar no-print">
        <div class="toolbar-title">
            <i class="fa-solid fa-file-pdf" style="color:#ef4444; font-size:20px;"></i>
            <span>Cetak SK Penetapan Tema & Pembimbing</span>
        </div>
        <div class="toolbar-buttons">
            <button class="btn-tool" onclick="window.history.back();">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </button>
            <button class="btn-tool btn-primary" onclick="window.print();">
                <i class="fa-solid fa-print"></i> Cetak SK
            </button>
        </div>
    </div>

    <!-- DOCUMENT CONTAINER -->
    <div class="paper-wrapper">
        <div class="paper">
            
            <!-- KOP SURAT -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo">
                        <img src="<?= BASE_URL ?>/public/img/Logo_UnivLampung.png" alt="Logo Unila">
                    </td>
                    <td class="kop-text">
                        <div class="dept">KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</div>
                        <div class="univ">UNIVERSITAS LAMPUNG</div>
                        <div class="fac">FAKULTAS MATEMATIKA DAN ILMU PENGETAHUAN ALAM</div>
                        <div class="jur">JURUSAN ILMU KOMPUTER</div>
                        <div class="address">
                            Jl .Prof. Dr. Sumantri Brojonegoro No. 1 Bandar Lampung 35145 Telp/Fax (0721)704625<br>
                            Email: ilmu.komputer@fmipa.unila.ac.id, web: http//ilkom.unila.ac.id
                        </div>
                    </td>
                </tr>
            </table>

            <div class="kop-divider"></div>

            <!-- TITLE BLOCK -->
            <div class="doc-title">
                FORMULIR PENETAPAN<br>
                TEMA PENELITIAN DAN PEMBIMBING/PEMBAHAS SKRIPSI<br>
                JURUSAN ILMU KOMPUTER FMIPA UNIVERSITAS LAMPUNG<br>
                <div style="font-weight:normal; margin-top:6px; font-size:10pt;">
                    <?= htmlspecialchars($nomor_sk) ?>
                </div>
            </div>

            <!-- TABLE LAYOUT -->
            <table class="sop-table">
                <colgroup>
                    <col style="width: 22%;">
                    <col style="width: 3%;">
                    <col style="width: 42%;">
                    <col style="width: 6%;">
                    <col style="width: 3%;">
                    <col style="width: 24%;">
                </colgroup>
                <tr>
                    <td class="sop-label">NAMA</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($p['mahasiswa_nama']) ?></td>
                    <td class="sop-label">NPM</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($p['mahasiswa_npm']) ?></td>
                </tr>
                <tr>
                    <td class="sop-label">PROGRAM STUDI</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;">S1 Ilmu Komputer</td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
                <tr>
                    <td class="sop-val" colspan="6" style="background:#ffffff; font-style:normal; padding: 10px 12px; height: auto; border-top: none; border-bottom: none;">
                        Berdasarkan pengajuan tema pada <?= formatSkDate($p['created_at']) ?> , dengan ini menyetujui tema penelitian Skripsi dengan
                    </td>
                </tr>
                <tr>
                    <td class="sop-label" style="height: 46px;">JUDUL</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val sop-val-bold" colspan="4" style="line-height: 1.45; text-transform: uppercase; vertical-align: middle;">
                        <?= htmlspecialchars($judul_disetujui) ?>
                    </td>
                </tr>
                <!-- Empty rows mimicking the PDF template layout space for long titles (diatur dinamis) -->
                <?php
                $numSigs = 2; // Pembimbing 1 dan Pembahas 1 selalu ada
                if ($p2 && $p2 !== '-') $numSigs++;
                if ($pb2 && $pb2 !== '-') $numSigs++;
                $numSpacers = 4 - $numSigs;
                for ($s = 0; $s < $numSpacers; $s++):
                ?>
                <tr>
                    <td class="sop-label" style="height: 24px;"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val" colspan="4"></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td class="sop-label" colspan="6" style="background-color: #dce6f1; font-weight: bold; height: 34px;">
                        Dan menetapkan
                    </td>
                </tr>
                <!-- Pembimbing Utama -->
                <tr>
                    <td class="sop-label">Pembimbing Utama</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenP1['nama']) ?></td>
                    <td class="sop-label">NIP</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenP1['nip']) ?></td>
                </tr>
                <tr>
                    <td class="sop-label">TANDA TANGAN</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val"></td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
                <!-- Pembimbing Pembantu (jika ada) -->
                <?php if ($p2 && $p2 !== '-'): ?>
                <tr>
                    <td class="sop-label">Pembimbing Pembantu</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenP2['nama']) ?></td>
                    <td class="sop-label">NIP</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenP2['nip']) ?></td>
                </tr>
                <tr>
                    <td class="sop-label">TANDA TANGAN</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val"></td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
                <?php endif; ?>
                <!-- Pembahas 1 -->
                <tr>
                    <td class="sop-label"><?= ($pb2 && $pb2 !== '-') ? 'Pembahas I' : 'Pembahas' ?></td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenPb1['nama']) ?></td>
                    <td class="sop-label">NIP</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenPb1['nip']) ?></td>
                </tr>
                <tr>
                    <td class="sop-label">TANDA TANGAN</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val"></td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
                <!-- Pembahas 2 (jika ada) -->
                <?php if ($pb2 && $pb2 !== '-'): ?>
                <tr>
                    <td class="sop-label">Pembahas II</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenPb2['nama']) ?></td>
                    <td class="sop-label">NIP</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val" style="white-space: nowrap;"><?= htmlspecialchars($dosenPb2['nip']) ?></td>
                </tr>
                <tr>
                    <td class="sop-label">TANDA TANGAN</td>
                    <td class="sop-separator">:</td>
                    <td class="sop-val"></td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
                <?php endif; ?>
                <!-- Blank row at bottom of table matching the PDF template layout -->
                <tr>
                    <td class="sop-label" style="height: 24px;"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                    <td class="sop-label"></td>
                    <td class="sop-separator"></td>
                    <td class="sop-val"></td>
                </tr>
            </table>

            <!-- SIGNATURES SECTION -->
            <div style="width:100%; text-align:right; font-size:10pt; margin-bottom:12px; padding-right: 5px;">
                Bandar Lampung, <?= formatSkDate($p['tanggal_persetujuan'] ?: date('Y-m-d H:i:s')) ?>
            </div>

            <div style="text-align: center; font-size: 10pt; font-weight: bold; margin-bottom: 8px;">
                Menyetujui
            </div>

            <table class="sig-container" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; text-align: left; vertical-align: top; padding: 0;">
                        Ketua Jurusan,<br>
                        <div class="sig-space" style="height: 75px;">
                            <!-- space for manual sig of kajur -->
                        </div>
                        <strong><?= htmlspecialchars($kajurName) ?></strong><br>
                        NIP. <?= htmlspecialchars($kajurNip) ?>
                    </td>
                    <td style="width: 50%; text-align: left; vertical-align: top; padding: 0; padding-left: 80px; position: relative;">
                        Koordinator Skripsi<br>
                        <div class="sig-space" style="height: 75px; position: relative;">
                            <?php if (file_exists($destPath)): ?>
                                <div class="sig-image-wrap" style="position: absolute; top: -12px; left: 0px; z-index: 10;">
                                    <img src="<?= BASE_URL ?>/public/img/ttd_tristiyanto.png" alt="Tanda Tangan Tristiyanto">
                                </div>
                            <?php endif; ?>
                        </div>
                        <strong><?= htmlspecialchars($kaprodiName) ?></strong><br>
                        NIP. <?= preg_replace('/[^0-9]/', '', $kaprodiNip) ?>
                    </td>
                </tr>
            </table>

            <!-- FOOTER CODE -->
            <div class="sop-footer-code">
                F-02/SOP/MIPA/7.5/II/11/002
            </div>

        </div>
    </div>

</body>
</html>
