<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: /bimbingan-skripsi/');
    exit;
}

if (($_SESSION['role'] ?? '') === 'dosen') {
    if (($_SESSION['otoritas'] ?? '') === 'dosen') {
        header("Location: /bimbingan-skripsi/app/views/dosen/dashboard.php");
        exit;
    } elseif (($_SESSION['otoritas'] ?? '') === 'kaprodi') {
        header("Location: /bimbingan-skripsi/app/views/kaprodi/dashboard.php");
        exit;
    }
}

$title = "Dashboard Mahasiswa";

$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';
$namaMhs = $isMahasiswaAccount ? ($_SESSION['nama'] ?? 'LIRA SEPTIYANI') : 'LIRA SEPTIYANI';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch distribution details if available
$dospem1 = '-';
$dospem2 = '-';
$pembahas = '-';
$judul = '-';

try {
    $stmt = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmt->execute([':npm' => $npmMhs]);
    $dist = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dist) {
        $dospem1 = $dist['pembimbing1'] ?: '-';
        $dospem2 = $dist['pembimbing2'] ?: '-';
        
        $p1 = $dist['pembahas1'] ?? '';
        $p2 = $dist['pembahas2'] ?? '';
        if (!empty($p1) && !empty($p2)) {
            $pembahas = $p1 . ' / ' . $p2;
        } elseif (!empty($p1)) {
            $pembahas = $p1;
        } elseif (!empty($p2)) {
            $pembahas = $p2;
        } else {
            $pembahas = '-';
        }
        
        $judul = $dist['judul_skripsi'] ?: '-';
    }
} catch (PDOException $e) {
    // Fail silently
}

// Check if student has at least one bimbingan to update milestone progress
$hasBimbingan = false;
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bimbingan WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')");
    $stmtCount->execute([':npm' => $npmMhs]);
    $hasBimbingan = ((int)$stmtCount->fetchColumn() > 0);
} catch (PDOException $e) {
    // Fail silently
}

// Extract Angkatan from NPM (e.g. 2217051151 -> Angkatan 2022)
$prefix = substr(trim($npmMhs), 0, 2);
$angkatanMhs = is_numeric($prefix) ? '20' . $prefix : '2022';

// Semester logic mapping
$semesterMhs = 8;
if ($angkatanMhs == '2020') $semesterMhs = 12;
elseif ($angkatanMhs == '2021') $semesterMhs = 10;
elseif ($angkatanMhs == '2022') $semesterMhs = 8;
elseif ($angkatanMhs == '2023') $semesterMhs = 6;
elseif ($angkatanMhs == '2024') $semesterMhs = 4;
elseif ($angkatanMhs == '2025') $semesterMhs = 2;

// Compute milestone step
$currentStep = 1; // 1: Pengajuan Topik
if ($dospem1 !== '-' && $dospem1 !== 'Belum ditentukan') {
    $currentStep = 2; // 2: Dosen & Judul Disetujui
    if ($hasBimbingan) {
        $currentStep = 3; // 3: Proses Bimbingan
    }
    if (($dist['status_bimbingan'] ?? 'aktif') === 'selesai') {
        $currentStep = 5; // 4: Selesai / Sidang completed (meaning all 4 steps are green)
    }
}

$mahasiswa = [
    'npm'      => $npmMhs,
    'nama'     => $namaMhs,
    'prodi'    => 'S1-Ilmu Komputer',
    'angkatan' => $angkatanMhs,
    'semester' => $semesterMhs,
    'ipk'      => '3.55',
    'status'   => 'Aktif',
    'dospem1'  => $dospem1,
    'dospem2'  => $dospem2,
    'pembahas' => $pembahas,
    'judul'    => $judul
];

// Helper to generate dynamic initial-based avatars
function getInitialsAvatar($name) {
    if (!$name || $name === '-' || $name === 'Belum ditentukan') {
        return '<div class="avatar-init fallback"><i class="fa-solid fa-user-tie"></i></div>';
    }
    // Clean potential degree titles
    $cleanName = preg_replace('/(\b[A-Z][a-z]*\.\,?\s*|\s*\,?\s*\b[A-Z][a-z]*\.?)/', '', $name);
    $words = explode(' ', trim($name));
    $initials = '';
    $count = 0;
    foreach ($words as $w) {
        if (!empty($w) && $count < 2) {
            $initials .= strtoupper($w[0]);
            $count++;
        }
    }
    return '<div class="avatar-init">' . htmlspecialchars($initials) . '</div>';
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_mahasiswa.php';
include __DIR__ . '/../layouts/topbar.php';
?>

<style>
:root {
    --primary-color: #285aa9;
    --primary-dark: #1e3a8a;
    --primary-light: #e7eef9;
    --success-color: #22c55e;
    --success-light: #f0fdf4;
    --warning-color: #eab308;
    --warning-light: #fefbeb;
    --text-dark: #1e293b;
    --text-gray: #64748b;
    --bg-light: #f8fafc;
    --border-color: #e2e8f0;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

.content {
    background-color: #f8fafc !important;
    min-height: calc(100vh - 70px);
}

/* =====================================================
   HEADER GRID LAYOUT (BANNER + PROFILE)
===================================================== */
.header-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 28px;
}

/* =====================================================
   WELCOME BANNER
===================================================== */
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    border-radius: 16px;
    padding: 32px;
    color: #ffffff;
    box-shadow: 0 8px 30px rgba(40, 90, 169, 0.2);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.welcome-banner:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(40, 90, 169, 0.25);
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 350px;
    height: 350px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    pointer-events: none;
}

.welcome-banner-content h1 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.welcome-banner-content p {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 20px;
    line-height: 1.5;
}

.welcome-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.welcome-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.welcome-badge i {
    font-size: 14px;
}

/* =====================================================
   ACADEMIC PROFILE CARD
===================================================== */
.academic-panel {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.panel-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.panel-title i {
    color: var(--primary-color);
}

.profile-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed var(--border-color);
    font-size: 13.5px;
}

.profile-row:last-of-type {
    border-bottom: none;
}

.profile-label {
    color: var(--text-gray);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-label i {
    width: 16px;
    text-align: center;
}

.profile-val {
    color: var(--text-dark);
    font-weight: 600;
}

/* IPK Progress */
.ipk-bar-wrapper {
    margin-top: 12px;
    background: var(--bg-light);
    border-radius: 8px;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
}

.ipk-bar-header {
    display: flex;
    justify-content: space-between;
    font-size: 12.5px;
    font-weight: 600;
    margin-bottom: 6px;
}

.ipk-bar-outer {
    height: 8px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.ipk-bar-inner {
    height: 100%;
    background: var(--primary-color);
    border-radius: 10px;
}

/* =====================================================
   MILESTONE TRACKER
===================================================== */
.milestone-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 28px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.milestone-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.milestone-title i {
    color: var(--primary-color);
}

.milestone-tracker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    padding: 10px 0;
}

.milestone-line-bg {
    position: absolute;
    top: 30px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #e2e8f0;
    z-index: 1;
}

.milestone-line-progress {
    position: absolute;
    top: 30px;
    left: 40px;
    height: 4px;
    background: var(--primary-color);
    z-index: 2;
    transition: width 0.5s ease;
    width: <?= (($currentStep - 1) / 3) * 100 ?>%;
}

.milestone-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 3;
    width: 120px;
    text-align: center;
}

.milestone-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.milestone-step.completed .milestone-circle {
    border-color: var(--success-color);
    background: var(--success-color);
    color: #ffffff;
}

.milestone-step.active .milestone-circle {
    border-color: var(--primary-color);
    background: var(--primary-light);
    color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(40, 90, 169, 0.2);
}

.milestone-label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    transition: color 0.3s ease;
}

.milestone-step.completed .milestone-label {
    color: var(--success-color);
}

.milestone-step.active .milestone-label {
    color: var(--primary-color);
}

/* =====================================================
   SUPERVISORS & TITLE CARD
===================================================== */
.dashboard-panel {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 28px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.panel-header-simple {
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
}

.thesis-title-box {
    background: #f1f5f9;
    border-left: 4px solid var(--primary-color);
    padding: 18px 24px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 24px;
}

.thesis-tag {
    font-size: 11px;
    font-weight: 700;
    color: var(--primary-color);
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 6px;
}

.thesis-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-dark);
    line-height: 1.6;
}

.supervisors-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.supervisor-card {
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.supervisor-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-shadow);
    border-color: #cbd5e1;
}

.avatar-init {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: var(--primary-light);
    color: var(--primary-color);
    font-weight: 600;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.avatar-init.fallback {
    background: #e2e8f0;
    color: #64748b;
}

.supervisor-role {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-gray);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 6px;
}

.supervisor-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
    line-height: 1.4;
}

/* =====================================================
   RESPONSIVENESS
===================================================== */
@media (max-width: 1024px) {
    .header-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .milestone-tracker {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        padding-left: 20px;
    }
    
    .milestone-line-bg,
    .milestone-line-progress {
        display: none;
    }
    
    .milestone-step {
        flex-direction: row;
        width: 100%;
        text-align: left;
        gap: 14px;
    }
    
    .milestone-circle {
        margin-bottom: 0;
    }
    
    .supervisors-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content">

    <!-- HEADER GRID LAYOUT (BANNER + PROFILE) -->
    <div class="header-grid">
        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-banner-content">
                <h1>Selamat Datang, <?= htmlspecialchars($mahasiswa['nama']) ?>!</h1>
                <p>Sistem Informasi Bimbingan Skripsi Universitas Lampung. Pantau progres akademik, ajukan topik penelitian, dan selesaikan bimbingan skripsi Anda dengan mudah melalui sistem ini.</p>
                <div class="welcome-badges">
                    <div class="welcome-badge">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <?= htmlspecialchars($mahasiswa['prodi']) ?>
                    </div>
                    <div class="welcome-badge">
                        <i class="fa-solid fa-toggle-on" style="color: #22c55e;"></i>
                        Status: <?= htmlspecialchars($mahasiswa['status']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACADEMIC PROFILE -->
        <div class="academic-panel">
            <div class="panel-title">
                <i class="fa-solid fa-id-card-clip"></i>
                Profil Akademik
            </div>
            <div>
                <div class="profile-row">
                    <span class="profile-label"><i class="fa-solid fa-id-card"></i> NPM</span>
                    <span class="profile-val"><?= htmlspecialchars($mahasiswa['npm']) ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-label"><i class="fa-solid fa-calendar-day"></i> Angkatan</span>
                    <span class="profile-val"><?= htmlspecialchars($mahasiswa['angkatan']) ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-label"><i class="fa-solid fa-hourglass-half"></i> Semester</span>
                    <span class="profile-val">Semester <?= htmlspecialchars($mahasiswa['semester']) ?></span>
                </div>
            </div>

            <!-- IPK display -->
            <div class="ipk-bar-wrapper">
                <div class="ipk-bar-header">
                    <span>Indeks Prestasi Kumulatif (IPK)</span>
                    <span style="color: var(--primary-color); font-weight: 700;"><?= htmlspecialchars($mahasiswa['ipk']) ?></span>
                </div>
                <div class="ipk-bar-outer">
                    <div class="ipk-bar-inner" style="width: <?= ($mahasiswa['ipk'] / 4.0) * 100 ?>%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MILESTONE TRACKER -->
    <div class="milestone-card">
        <div class="milestone-title">
            <i class="fa-solid fa-chart-line"></i>
            Progres Tahapan Skripsi
        </div>
        <div class="milestone-tracker">
            <div class="milestone-line-bg"></div>
            <div class="milestone-line-progress"></div>
            
            <div class="milestone-step <?= $currentStep >= 1 ? ($currentStep > 1 ? 'completed' : 'active') : '' ?>">
                <div class="milestone-circle">
                    <?php if ($currentStep > 1): ?>
                        <i class="fa-solid fa-check"></i>
                    <?php else: ?>
                        1
                    <?php endif; ?>
                </div>
                <div class="milestone-label">Pengajuan Topik</div>
            </div>

            <div class="milestone-step <?= $currentStep >= 2 ? ($currentStep > 2 ? 'completed' : 'active') : '' ?>">
                <div class="milestone-circle">
                    <?php if ($currentStep > 2): ?>
                        <i class="fa-solid fa-check"></i>
                    <?php else: ?>
                        2
                    <?php endif; ?>
                </div>
                <div class="milestone-label">Distribusi Dosen</div>
            </div>

            <div class="milestone-step <?= $currentStep >= 3 ? ($currentStep > 3 ? 'completed' : 'active') : '' ?>">
                <div class="milestone-circle">
                    <?php if ($currentStep > 3): ?>
                        <i class="fa-solid fa-check"></i>
                    <?php else: ?>
                        3
                    <?php endif; ?>
                </div>
                <div class="milestone-label">Bimbingan Aktif</div>
            </div>

            <div class="milestone-step <?= $currentStep >= 4 ? ($currentStep > 4 ? 'completed' : 'active') : '' ?>">
                <div class="milestone-circle">
                    <?php if ($currentStep > 4): ?>
                        <i class="fa-solid fa-check"></i>
                    <?php else: ?>
                        4
                    <?php endif; ?>
                </div>
                <div class="milestone-label">Selesai / Sidang</div>
            </div>
        </div>
    </div>

    <!-- INFORMASI DOSEN & JUDUL -->
    <div class="dashboard-panel">
        <div class="panel-header-simple">
            <div class="panel-title">
                <i class="fa-solid fa-users-gears"></i>
                Dosen Pembimbing & Pembahas
            </div>
        </div>
        
        <div class="thesis-title-box">
            <span class="thesis-tag">JUDUL SKRIPSI DISETUJUI</span>
            <div class="thesis-title">
                <?= $mahasiswa['judul'] !== '-' ? '"' . htmlspecialchars($mahasiswa['judul']) . '"' : 'Belum memiliki judul skripsi disetujui.' ?>
            </div>
        </div>

        <div class="supervisors-grid">
            <!-- Pembimbing Utama -->
            <div class="supervisor-card">
                <?= getInitialsAvatar($mahasiswa['dospem1']) ?>
                <div class="supervisor-role">Pembimbing Utama</div>
                <div class="supervisor-name"><?= htmlspecialchars($mahasiswa['dospem1']) ?></div>
            </div>
            
            <!-- Pembimbing Pembantu -->
            <div class="supervisor-card">
                <?= getInitialsAvatar($mahasiswa['dospem2']) ?>
                <div class="supervisor-role">Pembimbing Pembantu</div>
                <div class="supervisor-name"><?= htmlspecialchars($mahasiswa['dospem2']) ?></div>
            </div>

            <!-- Pembahas -->
            <div class="supervisor-card">
                <?= getInitialsAvatar($mahasiswa['pembahas']) ?>
                <div class="supervisor-role">Dosen Pembahas</div>
                <div class="supervisor-name"><?= htmlspecialchars($mahasiswa['pembahas']) ?></div>
            </div>
        </div>
    </div>

</div>

<?php
include __DIR__ . '/../layouts/footer.php';
?>