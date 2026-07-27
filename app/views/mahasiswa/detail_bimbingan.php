<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /bimbingan-skripsi/");
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

$title = 'Mahasiswa - Detail Bimbingan';
require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmtB = $pdo->prepare("SELECT * FROM bimbingan WHERE id = :id LIMIT 1");
$stmtB->execute([':id' => $id]);
$bimbingan = $stmtB->fetch(PDO::FETCH_ASSOC);

if (!$bimbingan) {
    header("Location: bimbingan.php");
    exit;
}

$npmMhs = $bimbingan['npm'];
$namaMhs = $bimbingan['nama'];

// Fetch distribution details
$stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtDist->execute([':npm' => $npmMhs]);
$distribusi = $stmtDist->fetch(PDO::FETCH_ASSOC);

if (!$distribusi || empty($distribusi['pembimbing1'])) {
    $_SESSION['swal_error'] = 'Fitur bimbingan belum aktif. Silakan tunggu plotting Pembimbing dan Judul Skripsi oleh Kaprodi.';
    header("Location: /bimbingan-skripsi/app/views/mahasiswa/dashboard.php");
    exit;
}

$pembimbingUtama = $distribusi['pembimbing1'] ?? 'Belum ditentukan';
$pembimbingPembantu = $distribusi['pembimbing2'] ?? 'Belum ditentukan';
$pembahas = $distribusi['pembahas1'] ?? 'Belum ditentukan';
$judulSkripsi = $distribusi['judul_skripsi'] ?? 'Belum ditentukan';

$mahasiswa = [
    'nama'                 => $namaMhs,
    'npm'                  => $npmMhs,
    'pembimbing_utama'     => $pembimbingUtama,
    'pembimbing_pembantu'  => $pembimbingPembantu,
    'pembahas'             => $pembahas,
    'judul_skripsi'        => $judulSkripsi,
];

// Fetch forum messages from database
$stmtF = $pdo->prepare("SELECT * FROM forum_bimbingan WHERE bimbingan_id = :b_id ORDER BY id ASC");
$stmtF->execute([':b_id' => $id]);
$pesan_forum = $stmtF->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_mahasiswa.php';
?>

<style>
    .back-link {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #222;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .back-link:hover { color: #285aa9; }

    .info-box {
        background: #f4f8fc;
        border-radius: 4px;
        padding: 24px 30px;
        margin-bottom: 24px;
    }
    .info-row {
        display: flex;
        gap: 20px;
        margin-bottom: 12px;
        font-size: 14.5px;
    }
    .info-row .info-label {
        width: 180px;
        flex-shrink: 0;
        color: #6a7fbf;
        font-weight: 700;
    }
    .info-row .info-value { color: #222; }

    /* ================= FORUM CHAT BUBBLE SYSTEM ================= */
    .forum-chat-container {
        background-color: #f3f7fa;
        background-image: radial-gradient(#c5d2e0 1.2px, transparent 0);
        background-size: 16px 16px;
        border: 1px solid #c8d7e6;
        border-radius: 12px;
        padding: 24px 20px;
        max-height: 550px;
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
        max-width: 75%;
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

    /* Sender Info and Badges */
    .chat-bubble-header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 6px;
        font-size: 12.5px;
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
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-top: 8px;
        font-size: 11px;
        color: #667781;
        border-top: 1px dashed rgba(0, 0, 0, 0.05);
        padding-top: 4px;
    }
    .chat-date {
        font-style: italic;
    }
    .chat-action-reply {
        cursor: pointer;
        color: #285aa9;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: opacity 0.2s;
    }
    .chat-action-reply:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    /* --- Kotak Balas Pesan (Mahasiswa) --- */
    .reply-box {
        background: #ffffff;
        border: 1px solid #e3e3e3;
        border-radius: 6px;
        padding: 18px 20px;
        margin-bottom: 30px;
    }

    .reply-box textarea {
        width: 100%;
        min-height: 80px;
        padding: 12px 14px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Segoe UI', sans-serif;
        resize: vertical;
        margin-bottom: 12px;
    }

    .reply-box textarea:focus {
        outline: none;
        border-color: #285aa9;
    }

    .reply-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .attach-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #555;
        font-size: 14px;
        cursor: pointer;
    }

    .attach-btn input[type="file"] {
        display: none;
    }

    .attach-filename {
        font-size: 13px;
        color: #285aa9;
        margin-left: 8px;
    }

    .btn-kirim {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #69a86e;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-kirim:hover {
        opacity: .9;
    }
</style>

<div class="content">

    <a href="/bimbingan-skripsi/app/views/mahasiswa/bimbingan.php" class="back-link">
        <i class="fa-solid fa-chevron-left"></i> Forum Bimbingan
    </a>

    <div class="info-box">
        <div class="info-row">
            <div class="info-label">Nama</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['nama']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">NPM</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['npm']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembimbing Utama</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['pembimbing_utama']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembimbing Pembantu</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['pembimbing_pembantu']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembahas</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['pembahas']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Judul Skripsi</div>
            <div class="info-value"><?= htmlspecialchars($mahasiswa['judul_skripsi']) ?></div>
        </div>
    </div>

    <div class="forum-chat-container" id="forumThread">
        <?php foreach ($pesan_forum as $pesan): 
            $is_me = ($pesan['pengirim'] === 'mahasiswa');
            
            // Determine badge and name
            $badge_class = 'badge-mahasiswa';
            $badge_text = 'Mahasiswa';
            $sender_display = !empty($pesan['pengirim_nama']) ? $pesan['pengirim_nama'] : ($pesan['pengirim'] === 'mahasiswa' ? $mahasiswa['nama'] : 'Dosen');
            
            if ($is_me) {
                $sender_display = 'Anda';
            } else {
                // If it is from dosen, determine if Pembimbing 1 or 2
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
        <div class="chat-row <?= $is_me ? 'chat-me' : 'chat-other' ?>">
            <div class="chat-bubble">
                <div class="chat-bubble-header">
                    <span class="chat-sender-name"><?= htmlspecialchars($sender_display) ?></span>
                    <span class="chat-sender-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                </div>
                
                <div class="chat-bubble-body"><?= nl2br(htmlspecialchars($pesan['isi'])) ?></div>
                
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
                    <span class="chat-date"><?= htmlspecialchars($pesan['tanggal']) ?></span>
                    <span class="chat-action-reply" onclick="document.getElementById('inputPesan').focus()">
                        <i class="fa-solid fa-reply"></i> Reply
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ============ KOTAK BALAS PESAN ============ -->
    <div class="reply-box">
        <form id="formBalasForum" method="POST" action="/bimbingan-skripsi/app/controllers/BalasForumController.php" enctype="multipart/form-data">
            <input type="hidden" name="bimbingan_id" value="<?= $id ?>">

            <textarea name="pesan" id="inputPesan" placeholder="Tulis tanggapan / pertanyaan bimbingan..." required></textarea>

            <div class="reply-footer">
                <label class="attach-btn">
                    <i class="fa-solid fa-paperclip"></i> Lampirkan File
                    <input type="file" name="lampiran" id="inputLampiran">
                    <span class="attach-filename" id="namaFileLampiran"></span>
                </label>

                <button type="submit" class="btn-kirim">
                    <i class="fa-solid fa-paper-plane"></i> Kirim
                </button>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const inputLampiran = document.getElementById('inputLampiran');
    const namaFileLampiran = document.getElementById('namaFileLampiran');

    if (inputLampiran) {
        inputLampiran.addEventListener('change', function () {
            namaFileLampiran.textContent = this.files.length > 0 ? this.files[0].name : '';
        });
    }

    const forumThread = document.getElementById('forumThread');
    if (forumThread) {
        forumThread.scrollTop = forumThread.scrollHeight;
    }
</script>

<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Dikirim!',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>',
        timer: 2500,
        showConfirmButton: true,
        confirmButtonColor: '#285aa9'
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

</body>
</html>
