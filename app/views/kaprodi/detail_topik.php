<?php
session_start();

// Protection: Kaprodi only
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: " . BASE_URL . "/");
    exit;
}

if (($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
    exit;
}

$title = 'Detail Peminat Topik';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$topik_id = (int)($_GET['id'] ?? 0);
if ($topik_id <= 0) {
    header("Location: " . BASE_URL . "/app/views/kaprodi/topik_penelitian.php");
    exit;
}

// Fetch topic details
try {
    $stmtTopik = $pdo->prepare("
        SELECT tp.*, d.nama AS nama_dosen 
        FROM topik_penelitian tp 
        JOIN dosen d ON REPLACE(tp.nip_dosen, ' ', '') = REPLACE(d.nip, ' ', '')
        WHERE tp.id = :id
        LIMIT 1
    ");
    $stmtTopik->execute([':id' => $topik_id]);
    $topik = $stmtTopik->fetch(PDO::FETCH_ASSOC);
    
    if (!$topik) {
        $_SESSION['swal_error'] = 'Topik penelitian tidak ditemukan!';
        header("Location: " . BASE_URL . "/app/views/kaprodi/topik_penelitian.php");
        exit;
    }

    // Count approved quota
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
    $stmtCount->execute([':topik_id' => $topik_id]);
    $terisi = (int)$stmtCount->fetchColumn();

    // Fetch applicants (interested students)
    $stmtApplicants = $pdo->prepare("
        SELECT mt.id AS minat_id, mt.status, mt.alasan, mt.created_at, m.nama AS nama_mhs, m.npm AS npm_mhs
        FROM minat_topik mt
        JOIN mahasiswa m ON mt.mahasiswa_npm = m.npm
        WHERE mt.topik_id = :topik_id
        ORDER BY mt.created_at ASC
    ");
    $stmtApplicants->execute([':topik_id' => $topik_id]);
    $applicants = $stmtApplicants->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['swal_error'] = 'Database error: ' . $e->getMessage();
    header("Location: " . BASE_URL . "/app/views/kaprodi/topik_penelitian.php");
    exit;
}

function getInitials($name) {
    $words = explode(" ", preg_replace('/\s+/', ' ', trim($name)));
    $initials = "";
    $count = 0;
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper($w[0]);
            $count++;
            if ($count >= 2) break;
        }
    }
    return $initials ?: "?";
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    .page-title-back {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 600;
        color: #222;
        margin-bottom: 20px;
        text-decoration: none;
        transition: color 0.2s;
    }

    .page-title-back i {
        font-size: 18px;
        color: #444;
    }

    .page-title-back:hover {
        color: #285aa9;
    }

    .page-title-back:hover i {
        color: #285aa9;
    }

    .info-skripsi {
        background: #eef4fb;
        padding: 28px 35px;
        margin-bottom: 28px;
        border-left: 4px solid #d9e3f0;
    }

    .info-skripsi .info-row {
        display: flex;
        margin-bottom: 20px;
    }

    .info-skripsi .info-row:last-child {
        margin-bottom: 0;
    }

    .info-skripsi .info-label {
        width: 190px;
        color: #7d8bc2;
        font-size: 15px;
        font-weight: 500;
        flex-shrink: 0;
    }

    .info-skripsi .info-value {
        flex: 1;
        font-size: 15px;
        color: #333;
        line-height: 1.8;
    }

    .section-title {
        font-size: 17px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .peminat-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 40px;
    }

    .peminat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    }

    .peminat-card:hover {
        transform: translateY(-2px);
        border-color: rgba(40, 90, 169, 0.25);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.08);
    }

    .card-header-sleek {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }

    .student-profile {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .student-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #eef4fb;
        color: #285aa9;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(40, 90, 169, 0.15);
    }

    .student-details h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 0.2px;
    }

    .student-details span {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 500;
        display: block;
        margin-top: 2px;
    }

    .card-body-sleek {
        padding: 20px 24px;
        background: #fff;
    }

    .alasan-minat-box {
        background: #f8fafc;
        border-left: 4px solid #285aa9;
        padding: 14px 18px;
        border-radius: 0 8px 8px 0;
        font-size: 13.5px;
        color: #475569;
        line-height: 1.6;
        font-weight: 500;
    }

    .alasan-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }

    .card-footer-sleek {
        padding: 14px 24px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    /* Soft pill badges */
    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-menunggu { 
        background: #fef3c7; 
        color: #d97706; 
        border: 1px solid rgba(217, 119, 6, 0.15); 
    }
    .status-disetujui { 
        background: #d1fae5; 
        color: #15803d; 
        border: 1px solid rgba(21, 128, 61, 0.15); 
    }
    .status-ditolak { 
        background: #fee2e2; 
        color: #b91c1c; 
        border: 1px solid rgba(185, 28, 28, 0.15); 
    }

    .empty-state-card {
        background: #ffffff;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        color: #64748b;
        font-size: 14.5px;
    }
</style>

<div class="content">

    <!-- BREADCRUMB -->
    <a href="<?= BASE_URL ?>/app/views/kaprodi/topik_penelitian.php" class="page-title-back">
        <i class="fa-solid fa-chevron-left"></i> Detail Peminat Topik
    </a>

    <!-- TOPIC DETAILS CONTAINER -->
    <div class="info-skripsi">
        <div class="info-row">
            <div class="info-label">Dosen Pengusul</div>
            <div class="info-value"><strong><?= htmlspecialchars($topik['nama_dosen']) ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Topik / Judul Penelitian</div>
            <div class="info-value"><strong><?= htmlspecialchars($topik['topik']) ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Kategori</div>
            <div class="info-value"><strong><?= htmlspecialchars($topik['kategori'] ?? '-') ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Deskripsi</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($topik['deskripsi'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Kuota Bimbingan</div>
            <div class="info-value">
                <strong><?= $terisi ?></strong> / <strong><?= htmlspecialchars($topik['kuota_max']) ?></strong> Mahasiswa Disetujui
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Tenggat Pendaftaran</div>
            <div class="info-value">
                <?= !empty($topik['tenggat_tanggal']) ? htmlspecialchars($topik['tenggat_tanggal']) : 'Tidak ada tenggat waktu' ?>
            </div>
        </div>
    </div>

    <!-- LIST OF INTERESTED STUDENTS (PEMINAT LIST) -->
    <div>
        <div class="section-title">
            <i class="fa-solid fa-users" style="color: #285aa9;"></i>
            Daftar Mahasiswa Peminat (<?= count($applicants) ?>)
        </div>

        <?php if (empty($applicants)): ?>
            <div class="empty-state-card">
                <i class="fa-solid fa-user-slash" style="font-size: 32px; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                Belum ada mahasiswa yang mengajukan minat pada topik penelitian ini.
            </div>
        <?php else: ?>
            <div class="peminat-list">
                <?php foreach ($applicants as $idx => $app): 
                    $initials = getInitials($app['nama_mhs']);
                    $statusLabel = ucfirst($app['status']);
                    $badgeClass = 'status-menunggu';
                    if ($app['status'] === 'disetujui') $badgeClass = 'status-disetujui';
                    if ($app['status'] === 'ditolak') $badgeClass = 'status-ditolak';
                ?>
                    <div class="peminat-card">
                        
                        <!-- CARD HEADER -->
                        <div class="card-header-sleek">
                            <div class="student-profile">
                                <div class="student-avatar">
                                    <?= $initials ?>
                                </div>
                                <div class="student-details">
                                    <h4><?= htmlspecialchars(strtoupper($app['nama_mhs'])) ?></h4>
                                    <span>NPM: <?= htmlspecialchars($app['npm_mhs']) ?></span>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #94a3b8; font-weight: 500;">
                                <i class="fa-regular fa-clock"></i> <?= date('d M Y', strtotime($app['created_at'])) ?>
                            </div>
                        </div>

                        <!-- CARD BODY (ALASAN MINAT) -->
                        <div class="card-body-sleek">
                            <div class="alasan-minat-box">
                                <div class="alasan-label">Alasan Ketertarikan</div>
                                <div><?= nl2br(htmlspecialchars($app['alasan'])) ?></div>
                            </div>
                        </div>

                        <!-- CARD FOOTER (STATUS BADGE ONLY - NO ACTIONS) -->
                        <div class="card-footer-sleek">
                            <div>
                                <span class="badge-status <?= $badgeClass ?>">
                                    <?php if ($app['status'] === 'menunggu'): ?>
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    <?php elseif ($app['status'] === 'disetujui'): ?>
                                        <i class="fa-solid fa-circle-check"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    <?php endif; ?>
                                    <?= $statusLabel ?>
                                </span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
