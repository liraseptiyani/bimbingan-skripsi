<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$title = 'Verifikasi Topik';


require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$cari = trim($_GET['cari'] ?? '');
$status_filter = trim($_GET['filter_status'] ?? '');

$sql = "
    SELECT tp.id AS topik_id, tp.topik, tp.deskripsi, tp.kuota_max,
           d.nama AS nama_dosen,
           mt.id AS minat_id, mt.status, mt.alasan,
           m.nama AS nama_mahasiswa, m.npm AS npm_mahasiswa
    FROM topik_penelitian tp
    JOIN dosen d ON REPLACE(tp.nip_dosen, ' ', '') = REPLACE(d.nip, ' ', '')
    LEFT JOIN minat_topik mt ON mt.topik_id = tp.id
    LEFT JOIN mahasiswa m ON mt.mahasiswa_npm = m.npm
    WHERE 1=1
";
$params = [];

if (!empty($cari)) {
    $sql .= " AND (tp.topik ILIKE :cari OR tp.deskripsi ILIKE :cari OR d.nama ILIKE :cari OR m.nama ILIKE :cari OR m.npm ILIKE :cari)";
    $params[':cari'] = '%' . $cari . '%';
}

if (!empty($status_filter)) {
    if ($status_filter === 'belum_diajukan') {
        $sql .= " AND (mt.status IS NULL OR mt.status = '')";
    } else {
        $sql .= " AND mt.status = :status";
        $params[':status'] = $status_filter;
    }
}

$sql .= " ORDER BY tp.created_at DESC, mt.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarTopikRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$daftarTopik = [];
$no = 1;
foreach ($daftarTopikRaw as $t) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
    $stmtCount->execute([':topik_id' => $t['topik_id']]);
    $terisi = $stmtCount->fetchColumn();

    $mahasiswaStr = '-';
    if (!empty($t['nama_mahasiswa'])) {
        $mahasiswaStr = $t['nama_mahasiswa'] . ' (' . $t['npm_mahasiswa'] . ')';
    }

    $daftarTopik[] = [
        'minat_id' => $t['minat_id'],
        'no' => $no++,
        'dosen' => $t['nama_dosen'],
        'mahasiswa' => $mahasiswaStr,
        'topik' => $t['topik'],
        'deskripsi' => $t['deskripsi'],
        'alasan' => $t['alasan'] ?: '-',
        'terisi' => (int)$terisi,
        'maks' => (int)$t['kuota_max'],
        'status' => $t['status'] ?: 'tidak ada pengajuan'
    ];
}

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<style>
  
    .topik-toolbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:18px;
        flex-wrap:wrap;
    }

    .filter-status{
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 16px;
        border:1px solid #cccccc;
        border-radius:4px;
        color:#666;
        font-size:14px;
        background:#ffffff;
        min-width:170px;
    }

    .filter-status select{
        border:none;
        outline:none;
        font-size:14px;
        color:#666;
        background:transparent;
        width:100%;
        cursor:pointer;
    }

    .topik-search{
        display:flex;
        gap: 0;
        max-width:320px;
        width:100%;
    }

    .topik-search input{
        flex:1;
        padding:10px 12px;
        border:1px solid #cccccc;
        border-right:none;
        border-radius:4px 0 0 4px;
        font-size:14px;
        outline:none;
    }

    .topik-search button{
        border:none;
        background:#4AA361;
        color:#ffffff;
        padding:0 16px;
        border-radius:0 4px 4px 0;
        cursor:pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .topik-search button:hover{ background:#3d8b51; }

    table.tabel-topik td,
    table.tabel-topik th{
        border:none;
        border-bottom:1px solid #eef0f3;
        vertical-align:middle;
    }

    table.tabel-topik th.text-center,
    table.tabel-topik td.text-center{ text-align:center; }

    table.tabel-topik tbody tr:nth-child(even){ background:#f5f7fa; }

    .btn-lihat{
        width:30px;
        height:30px;
        border:none;
        border-radius:4px;
        background:#7db8db;
        color:#ffffff;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        font-size:13px;
    }

    .btn-lihat:hover{ opacity:.9; }

    .badge-status{
        display:inline-block;
        padding:6px 12px;
        border-radius:20px;
        font-size:12px;
        font-weight:600;
        white-space:nowrap;
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

    .topik-footer{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:16px;
        font-size:13px;
        color:#555;
    }

    .hal-info{
        font-size: 13.5px;
        color: #1e3a8a;
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 6px 12px;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }

    .baris-select{
        padding:6px 12px;
        border:1px solid #cccccc;
        border-radius:4px;
        font-size:13px;
    }

    .pagination{ display:flex; gap:6px; }

    .pagination button{
        width:30px;
        height:30px;
        border:1px solid #cccccc;
        background:#ffffff;
        border-radius:4px;
        cursor:pointer;
        font-size:13px;
        color:#555;
    }

    .pagination button:disabled{
        background:#eeeeee;
        color:#aaaaaa;
        cursor:not-allowed;
    }

    .pagination button.active{
        background:#285aa9;
        color:#ffffff;
        border-color:#285aa9;
    }

    /* MODAL DETAIL TOPIK*/
    .modal-overlay{
        display:none;
        position:fixed;
        top:0; left:0;
        width:100%; height:100%;
        background:rgba(0,0,0,.55);
        align-items:center;
        justify-content:center;
        z-index:100000;
        padding:20px;
    }

    .modal-overlay.show{ display:flex; }

    .modal-box{
        background:#ffffff;
        border-top:4px solid #69a86e;
        border-radius:6px;
        box-shadow:0 10px 30px rgba(0,0,0,.25);
        width:100%;
        max-width:650px;
        max-height:90vh;
        overflow-y:auto;
        padding:26px 34px 30px;
        position:relative;
    }

    .modal-close{
        position:absolute;
        top:24px;
        left:24px;
        background:none;
        border:none;
        font-size:18px;
        color:#333;
        cursor:pointer;
    }

    .modal-title{
        text-align:center;
        font-size:20px;
        font-weight:600;
        margin-bottom:24px;
        color:#222;
    }

    .modal-field{ margin-bottom:16px; }

    .modal-field label{
        display:block;
        font-size:14px;
        font-weight:600;
        color:#285aa9;
        margin-bottom:6px;
    }

    .modal-field label .req{ color:#e05252; }

    .modal-field .modal-value{
        border:1px solid #cccccc;
        border-radius:4px;
        padding:10px 12px;
        font-size:14px;
        color:#333;
        background:#fafbfc;
        min-height:20px;
    }

    .modal-actions{
        display:flex;
        justify-content:center;
        gap:14px;
        margin-top:22px;
    }

    .modal-actions button{
        border:none;
        padding:11px 34px;
        border-radius:4px;
        font-size:14px;
        font-weight:500;
        color:#ffffff;
        cursor:pointer;
    }

    .btn-setujui{ background:#3fae4e; }
    .btn-setujui:hover{ opacity:.9; }

    .btn-tolak{ background:#e05252; }
    .btn-tolak:hover{ opacity:.9; }

    .modal-actions.single{ display:block; }

    .btn-batal{
        background:#f2a13e;
        width:100%;
        text-align:center;
    }
    .btn-batal:hover{ opacity:.9; }
</style>

<div class="content">
    <div class="page-title">Daftar Topik Penelitian</div>

    <div class="card">
        <form method="get" action="" class="topik-toolbar" style="margin-bottom:18px;">
            <div class="filter-status">
                <i class="fas fa-filter"></i>
                <select name="filter_status" onchange="this.form.submit()">
                    <option value="">Filter Status</option>
                    <option value="menunggu" <?= $status_filter === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="disetujui" <?= $status_filter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="ditolak" <?= $status_filter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    <option value="belum_diajukan" <?= $status_filter === 'belum_diajukan' ? 'selected' : '' ?>>Belum Diajukan</option>
                </select>
            </div>

            <div class="topik-search">
                <input type="text" name="cari" placeholder="Cari Topik Skripsi" value="<?= htmlspecialchars($cari) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <table class="tabel-topik">
            <thead>
                <tr>
                    <th class="text-center" style="width:50px;">No</th>
                    <th>Dosen</th>
                    <th>Topik</th>
                    <th>Deskripsi</th>
                    <th class="text-center" style="width:70px;">Kuota</th>
                    <th class="text-center" style="width:60px;">Aksi</th>
                    <th class="text-center" style="width:100px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarTopik as $t): ?>
                 <tr id="row-topik-<?= $t['minat_id'] ?: '0-' . $t['no'] ?>">
                    <td class="text-center"><?= $t['no'] ?></td>
                    <td><?= htmlspecialchars($t['dosen']) ?></td>
                    <td><?= htmlspecialchars($t['topik']) ?></td>
                    <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                    <td class="text-center"><?= $t['terisi'] . '/' . $t['maks'] ?></td>
                    <td class="text-center">
                        <button type="button"
                                class="btn-lihat"
                                title="Lihat Detail"
                                onclick="bukaModalTopik(
                                    <?= $t['minat_id'] ?: 0 ?>,
                                    <?= $t['no'] ?>,
                                    '<?= htmlspecialchars(addslashes($t['dosen']), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($t['topik']), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($t['deskripsi']), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($t['alasan']), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($t['mahasiswa']), ENT_QUOTES) ?>'
                                )">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                    <td class="text-center" id="status-topik-<?= $t['minat_id'] ?: '0-' . $t['no'] ?>" data-status="<?= strtolower($t['status']) ?>">
                        <?php if (strtolower($t['status']) === 'menunggu'): ?>
                            <span class="badge-status status-menunggu">Menunggu</span>
                        <?php elseif (strtolower($t['status']) === 'disetujui'): ?>
                            <span class="badge-status status-disetujui">Disetujui</span>
                        <?php elseif (strtolower($t['status']) === 'ditolak'): ?>
                            <span class="badge-status status-ditolak">Ditolak</span>
                        <?php else: ?>
                            <span class="badge-status" style="background:#e2e5ea; color:#666;">Belum Diajukan</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="topik-footer">
            <div class="hal-info">Hal 1/1 (<?= count($daftarTopik) ?> data)</div>
            <select class="baris-select">
                <option>10 baris</option>
                <option>25 baris</option>
                <option>50 baris</option>
            </select>
            <div class="pagination">
                <button disabled><i class="fas fa-angle-double-left"></i></button>
                <button disabled><i class="fas fa-angle-left"></i></button>
                <button class="active">1</button>
                <button disabled><i class="fas fa-angle-right"></i></button>
                <button disabled><i class="fas fa-angle-double-right"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL DETAIL TOPIK ================= -->
<div class="modal-overlay" id="modalTopik">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="tutupModalTopik()">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="modal-title">Detail Topik Penelitian</div>

        <div class="modal-field">
            <label>Mahasiswa</label>
            <div class="modal-value" id="modal-mahasiswa" style="font-weight: 600; color: #1A4FA0;"></div>
        </div>

        <div class="modal-field">
            <label>Dosen</label>
            <div class="modal-value" id="modal-dosen"></div>
        </div>

        <div class="modal-field">
            <label>Topik</label>
            <div class="modal-value" id="modal-topik"></div>
        </div>

        <div class="modal-field">
            <label>Deskripsi</label>
            <div class="modal-value" id="modal-deskripsi"></div>
        </div>

        <div class="modal-field">
            <label>Alasan Tertarik <span class="req">*</span></label>
            <div class="modal-value" id="modal-alasan"></div>
        </div>

        <div class="modal-actions" style="justify-content: flex-end;">
            <button type="button" class="btn-kembali" onclick="tutupModalTopik()" style="background:#285aa9; border:none; padding:10px 24px; border-radius:4px; color:#fff; cursor:pointer;">
                <i class="fas fa-chevron-left"></i> Tutup
            </button>
        </div>
    </div>
</div>

<script>
    var topikAktifId = null;

    function bukaModalTopik(id, no, dosen, topik, deskripsi, alasan, mahasiswa) {
        topikAktifId = id;

        document.getElementById('modal-mahasiswa').textContent = mahasiswa;
        document.getElementById('modal-dosen').textContent = dosen;
        document.getElementById('modal-topik').textContent = topik;
        document.getElementById('modal-deskripsi').textContent = deskripsi;
        document.getElementById('modal-alasan').textContent = alasan;

        document.getElementById('modalTopik').classList.add('show');
    }

    function tutupModalTopik() {
        document.getElementById('modalTopik').classList.remove('show');
        topikAktifId = null;
    }

    // Tutup modal kalau klik area gelap di luar box
    document.getElementById('modalTopik').addEventListener('click', function (e) {
        if (e.target === this) {
            tutupModalTopik();
        }
    });
</script>

<?php include '../layouts/footer.php'; ?>
