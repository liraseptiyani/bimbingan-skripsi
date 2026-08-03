<?php
session_start();

function getOnlyTime($tanggal_string) {
    $parts = explode(',', $tanggal_string);
    if (count($parts) >= 3) {
        return trim(end($parts));
    }
    return $tanggal_string;
}

// ==========================================================
// PROTEKSI HALAMAN: hanya dosen dengan otoritas aktif kaprodi
// ==========================================================
if (
    !isset($_SESSION['username'])
    || ($_SESSION['role'] ?? '') !== 'dosen'
    || ($_SESSION['otoritas'] ?? '') !== 'kaprodi'
) {
    header("Location: /bimbingan-skripsi/");
    exit;
}

$title = 'Kaprodi - Monitoring Progres (Riwayat Bimbingan - Detail)';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$npm = $_GET['npm'] ?? '';

// Fetch student name
$stmtM = $pdo->prepare("SELECT nama FROM mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtM->execute([':npm' => $npm]);
$nama = $stmtM->fetchColumn() ?: ($_GET['nama'] ?? '-');

// Fetch distribution details
$stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtDist->execute([':npm' => $npm]);
$distribusi = $stmtDist->fetch(PDO::FETCH_ASSOC);

$pembimbingUtama = $distribusi['pembimbing1'] ?? 'Belum ditentukan';
$pembimbingPembantu = $distribusi['pembimbing2'] ?? 'Belum ditentukan';
$pembahas = $distribusi['pembahas1'] ?? 'Belum ditentukan';
$judulSkripsi = $distribusi['judul_skripsi'] ?? 'Belum ditentukan';

$info_skripsi = [
    'nama'               => $nama,
    'npm'                => $npm,
    'pembimbing_utama'   => $pembimbingUtama,
    'pembimbing_pembantu' => $pembimbingPembantu,
    'pembahas'           => $pembahas,
    'judul_skripsi'      => $judulSkripsi,
];

// Fetch forum messages from database (full thread history for this student)
$stmtF = $pdo->prepare("
    SELECT fb.* 
    FROM forum_bimbingan fb
    JOIN bimbingan b ON fb.bimbingan_id = b.id
    WHERE REPLACE(b.npm, ' ', '') = REPLACE(:npm, ' ', '')
    ORDER BY fb.id ASC
");
$stmtF->execute([':npm' => $npm]);
$forum_messages = $stmtF->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* ================= RIWAYAT BIMBINGAN - DETAIL (khusus halaman ini) ================= */

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
        background: #eef3f9;
        padding: 24px 30px;
        margin-bottom: 28px;
        border-radius: 8px;
    }

    .info-skripsi .info-row {
        display: flex;
        margin-bottom: 10px;
    }

    .info-skripsi .info-row:last-child {
        margin-bottom: 0;
    }

    .info-skripsi .info-label {
        width: 190px;
        color: #6a7fbf;
        font-size: 14.5px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .info-skripsi .info-value {
        flex: 1;
        font-size: 14.5px;
        color: #333;
        line-height: 1.5;
    }

    /* ================= FORUM CHAT BUBBLE SYSTEM ================= */
    .forum-chat-container {
        background-color: #f3f7fa;
        background-image: radial-gradient(#c5d2e0 1.2px, transparent 0);
        background-size: 16px 16px;
        border: 1px solid #c8d7e6;
        border-radius: 12px;
        padding: 24px 20px;
        max-height: 650px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        scroll-behavior: smooth;
    }

    /* Custom scrollbar for chat container */
    .forum-chat-container::-webkit-scrollbar {
        width: 6px;
    }
    .forum-chat-container::-webkit-scrollbar-track {
        background: transparent;
    }
    .forum-chat-container::-webkit-scrollbar-thumb {
        background: rgba(40, 90, 169, 0.2);
        border-radius: 10px;
    }
    .forum-chat-container::-webkit-scrollbar-thumb:hover {
        background: rgba(40, 90, 169, 0.4);
    }

    .chat-row {
        display: flex;
        width: 100%;
        margin-bottom: 4px;
    }
    .chat-row.chat-me {
        justify-content: flex-end;
    }
    .chat-row.chat-other {
        justify-content: flex-start;
    }

    .chat-bubble {
        position: relative;
        max-width: 60%;
        width: fit-content;
        padding: 10px 14px 8px 14px;
        box-shadow: 0 1px 3px rgba(40, 90, 169, 0.08);
        font-size: 14.5px;
        line-height: 1.55;
    }
    
    @media (max-width: 768px) {
        .chat-bubble {
            max-width: 88%;
        }
    }

    .chat-row.chat-me .chat-bubble {
        background-color: #d9e7f5;
        color: #1e2e4a;
        border-radius: 12px 12px 0 12px;
    }
    .chat-row.chat-other .chat-bubble {
        background-color: #ffffff;
        color: #1e293b;
        border-radius: 12px 12px 12px 0;
        border: 1px solid #e2e8f0;
    }

    /* Bubble Speech Tails using CSS */
    .chat-row.chat-me .chat-bubble::after {
        content: "";
        position: absolute;
        top: 0;
        right: -8px;
        width: 0;
        height: 0;
        border: 8px solid transparent;
        border-left-color: #d9e7f5;
        border-top-color: #d9e7f5;
    }
    .chat-row.chat-other .chat-bubble::after {
        content: "";
        position: absolute;
        top: 0;
        left: -8px;
        width: 0;
        height: 0;
        border: 8px solid transparent;
        border-right-color: #ffffff;
        border-top-color: #ffffff;
    }

    /* Message Reply Quote Styles */
    .chat-quote-block {
        background: rgba(0, 0, 0, 0.05);
        border-left: 3px solid #285aa9;
        padding: 6px 10px;
        border-radius: 4px;
        margin-top: 2px;
        margin-bottom: 6px;
        font-size: 12.5px;
    }
    .chat-quote-sender {
        font-weight: 700;
        color: #285aa9;
        margin-bottom: 2px;
    }
    .chat-quote-body {
        color: #475569;
        font-size: 12px;
        line-height: 1.4;
        word-break: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Sender Info and Badges */
    .chat-bubble-header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 0px;
        font-size: 12.5px;
        line-height: 1.2;
    }
    .chat-sender-name {
        font-weight: 700;
        color: #285aa9;
    }
    .chat-row.chat-me .chat-sender-name {
        color: #1d4480;
    }
    .chat-sender-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        display: inline-block;
    }
    .badge-mahasiswa {
        background-color: #e2e8f0;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .badge-pembimbing1 {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .badge-pembimbing2 {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .badge-dosen {
        background-color: #f3e8ff;
        color: #6b21a8;
        border: 1px solid #e9d5ff;
    }

    .chat-bubble-body {
        color: #111b21;
        word-break: break-word;
        white-space: pre-wrap;
    }

    /* File Attachment styled cards */
    .chat-file-attachment {
        display: flex;
        align-items: center;
        gap: 12px;
        background-color: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 10px;
        margin-bottom: 4px;
        text-decoration: none;
        color: #111b21;
        transition: background-color 0.2s, border-color 0.2s;
    }
    .chat-file-attachment:hover {
        background-color: rgba(0, 0, 0, 0.06);
        border-color: rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }
    .chat-file-icon {
        font-size: 24px;
        color: #d9534f;
    }
    .chat-file-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .chat-file-name {
        font-size: 13px;
        font-weight: 600;
        color: #2b3a4a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-file-action {
        font-size: 11px;
        color: #cc3333;
        font-weight: 600;
        margin-top: 2px;
    }

    .chat-bubble-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        font-size: 11px;
        color: #667781;
        border-top: none;
        padding-top: 0;
    }
    .chat-date {
        font-style: italic;
    }
    .no-message {
        text-align: center;
        color: #94a3b8;
        padding: 40px;
        font-size: 14.5px;
    }
</style>

<div class="content">

    <a href="/bimbingan-skripsi/app/views/kaprodi/detail_progres.php?npm=<?= urlencode($npm) ?>&nama=<?= urlencode($nama) ?>" class="page-title-back">
        <i class="fa-solid fa-chevron-left"></i>
        Forum Bimbingan: <?= htmlspecialchars($info_skripsi['nama']) ?>
    </a>

    <!-- ============ INFO SKRIPSI ============ -->
    <div class="info-skripsi">
        <div class="info-row">
            <div class="info-label">Nama Mahasiswa</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['nama']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">NPM</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['npm']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembimbing Utama</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['pembimbing_utama']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembimbing Pembantu</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['pembimbing_pembantu']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Dosen Pembahas</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['pembahas']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Judul Skripsi</div>
            <div class="info-value"><?= htmlspecialchars($info_skripsi['judul_skripsi']) ?></div>
        </div>
    </div>

    <!-- ============ LOG PERCAKAPAN BIMBINGAN (CHAT THREAD) ============ -->
    <div class="forum-chat-container" id="forumThread">
        <?php if (empty($forum_messages)): ?>
            <div class="no-message">Belum ada pesan pada forum bimbingan ini.</div>
        <?php else: ?>
            <?php foreach ($forum_messages as $pesan): 
                $is_student = ($pesan['pengirim'] === 'mahasiswa');
                
                if ($is_student) {
                    $sender_display = $nama;
                    $badge_class = 'badge-mahasiswa';
                    $badge_text = 'Mahasiswa';
                } else {
                    $sender_display = !empty($pesan['pengirim_nama']) ? $pesan['pengirim_nama'] : 'Dosen';
                    $badge_class = 'badge-dosen';
                    $badge_text = 'Dosen';
                    
                    $normP1 = !empty($pembimbingUtama) ? strtolower(preg_replace('/[^a-z0-9]/', '', $pembimbingUtama)) : '';
                    $normP2 = !empty($pembimbingPembantu) ? strtolower(preg_replace('/[^a-z0-9]/', '', $pembimbingPembantu)) : '';
                    $normSender = !empty($pesan['pengirim_nama']) ? strtolower(preg_replace('/[^a-z0-9]/', '', $pesan['pengirim_nama'])) : '';
                    
                    if (!empty($normSender)) {
                        if (!empty($normP1) && ($normP1 === $normSender || strpos($normSender, $normP1) !== false || strpos($normP1, $normSender) !== false)) {
                            $badge_class = 'badge-pembimbing1';
                            $badge_text = 'Pembimbing 1';
                        } elseif (!empty($normP2) && ($normP2 === $normSender || strpos($normSender, $normP2) !== false || strpos($normP2, $normSender) !== false)) {
                            $badge_class = 'badge-pembimbing2';
                            $badge_text = 'Pembimbing 2';
                        }
                    }
                }
            ?>
            <div class="chat-row <?= $is_student ? 'chat-me' : 'chat-other' ?>">
                <div class="chat-bubble">
                    <div class="chat-bubble-header">
                        <span class="chat-sender-name"><?= htmlspecialchars($sender_display) ?></span>
                        <span class="chat-sender-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                    </div>
                    
                    <div class="chat-bubble-body"><?php
                        $clean_isi = htmlspecialchars(trim($pesan['isi']));
                        $formatted_isi = preg_replace(
                            '/\[quote=([^\]]+)\](.*?)\[\/quote\]\s*/s',
                            '<div class="chat-quote-block"><div class="chat-quote-sender"><i class="fa-solid fa-quote-left" style="font-size:10px; margin-right:4px;"></i> $1</div><div class="chat-quote-body">$2</div></div>',
                            $clean_isi
                        );
                        echo nl2br($formatted_isi);
                    ?></div>
                    
                    <?php if (!empty($pesan['file'])): ?>
                        <a href="/bimbingan-skripsi/public/uploads/draft/<?= htmlspecialchars($pesan['file']) ?>" class="chat-file-attachment" target="_blank">
                            <i class="fa-regular fa-file-pdf chat-file-icon"></i>
                            <div class="chat-file-info">
                                <span class="chat-file-name"><?= htmlspecialchars($pesan['file']) ?></span>
                                <span class="chat-file-action">Unduh Draft</span>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <div class="chat-bubble-footer">
                        <span class="chat-date" title="<?= htmlspecialchars($pesan['tanggal']) ?>" style="cursor: help;">
                            <?= htmlspecialchars(getOnlyTime($pesan['tanggal'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
