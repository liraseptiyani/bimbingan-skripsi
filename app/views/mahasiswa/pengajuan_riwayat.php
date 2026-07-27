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

$title = "Mahasiswa - Riwayat Pengajuan Judul";

$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch past submissions
try {
    $stmtP = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE mahasiswa_npm = :npm ORDER BY id DESC");
    $stmtP->execute([':npm' => $npmMhs]);
    $pengajuan_list = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pengajuan_list = [];
}

// Fetch distribution to match SK numbers and actual assigned pembimbing
$distData = null;
try {
    $stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmtDist->execute([':npm' => $npmMhs]);
    $distData = $stmtDist->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_mahasiswa.php';
?>

<style>
    /* Card Styles */
    .card.table-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 28px;
    }

    .table-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .table-toolbar .search {
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0;
    }

    .table-toolbar .search input {
        border: 1px solid #cbd5e1;
        border-radius: 6px 0 0 6px;
        border-right: none;
        height: 38px;
        padding: 8px 14px;
        font-size: 14px;
        width: 240px;
        box-sizing: border-box;
    }

    .table-toolbar .search button {
        border: none;
        border-radius: 0 6px 6px 0;
        height: 38px;
        width: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #4AA361;
        color: #fff;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s;
    }

    .table-toolbar .search button:hover {
        background: #3d8b51;
    }

    table#judulTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    table#judulTable th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        text-align: left;
    }

    table#judulTable td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }

    table#judulTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Badge status capsule */
    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 30px;
        text-transform: capitalize;
    }
    .badge-status.menunggu {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid rgba(217, 119, 6, 0.15);
    }
    .badge-status.disetujui {
        background-color: #d1fae5;
        color: #15803d;
        border: 1px solid rgba(21, 128, 61, 0.15);
    }
    .badge-status.ditolak {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid rgba(185, 28, 28, 0.15);
    }

    /* Decision details block */
    .decision-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        color: #334155;
    }

    .decision-info .d-row {
        display: grid;
        grid-template-columns: 100px 1fr;
    }

    .decision-info .d-label {
        font-weight: 600;
        color: #285aa9;
    }

    /* Pagination */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
    }

    .pagination-info {
        font-size: 13.5px;
        color: #285aa9;
        background: #e7eef9;
        border-left: 4px solid #285aa9;
        padding: 6px 12px;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }

    .rows-per-page {
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13.5px;
        color: #334155;
        background-color: #fff;
    }

    .pagination-buttons {
        display: flex;
        gap: 5px;
    }

    .pagination-buttons button {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #1e293b;
        min-width: 34px;
        height: 34px;
        border-radius: 6px;
        font-size: 13.5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .pagination-buttons button.active {
        background: #285aa9;
        border-color: #285aa9;
        color: #ffffff;
    }

    .pagination-buttons button:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .pagination-buttons button:hover:not(.active):not(:disabled) {
        background: #f1f5f9;
    }

    .btn-lihat-berkas:hover {
        background: #285aa9 !important;
        color: #ffffff !important;
        border-color: #285aa9 !important;
    }

    /* ================= MODAL DETAIL TOPIK ================= */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 99999;
    }

    .modal-card {
        background: #ffffff;
        width: 680px;
        max-width: 92%;
        border-radius: 6px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        padding: 30px 35px;
        animation: fadeInModal 0.25s ease-out;
        max-height: 90vh;
        overflow-y: auto;
    }

    @keyframes fadeInModal {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-title {
        text-align: center;
        font-size: 20px;
        font-weight: 600;
        color: #222;
        margin-bottom: 25px;
    }

    .modal-form-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .modal-form-row {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .modal-form-row label {
        width: 150px;
        flex-shrink: 0;
        font-size: 14px;
        font-weight: 700;
        color: #5568b2;
        padding-top: 8px;
    }

    .modal-input-field {
        flex: 1;
        width: 100%;
        border: 1px solid #dcdfe6;
        border-radius: 4px;
        padding: 9px 12px;
        font-size: 14px;
        color: #333;
        background: #ffffff;
        outline: none;
        transition: border-color 0.2s;
    }

    .modal-input-field:focus {
        border-color: #285aa9;
    }

    textarea.modal-input-field {
        resize: vertical;
        font-family: inherit;
        line-height: 1.5;
    }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 25px;
    }

    .btn-modal-back {
        background: #285aa9;
        color: #ffffff;
        border: none;
        padding: 9px 18px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-modal-back:hover {
        background: #1e4687;
    }
</style>

<div class="content">

    <div style="margin-bottom: 24px;">
        <h1 class="page-title" style="margin: 0;">Riwayat Pengajuan Judul</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Pantau hasil keputusan dan berkas Surat Keputusan (SK) penetapan judul skripsi Anda</p>
    </div>

    <div class="card table-card">
        <div class="table-toolbar">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari judul skripsi...">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <table id="judulTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Judul Skripsi</th>
                    <th style="width: 125px; white-space: nowrap;">Tanggal Pengajuan</th>
                    <th style="width: 100px; text-align: center;">Status</th>
                    <th style="width: 260px;">Hasil Keputusan Kaprodi / Nomor SK</th>
                    <th style="width: 130px; text-align: center;">Berkas</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($pengajuan_list)): ?>
                    <tr class="no-data-row">
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 22px;">Belum ada riwayat pengajuan judul.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengajuan_list as $i => $p): ?>
                        <tr data-keyword="<?= strtolower($p['judul'] . ' ' . ($p['judul_alternatif'] ?? '') . ' ' . $p['status']) ?>">
                            <td style="text-align: center; white-space: nowrap;"><?= $i + 1 ?></td>
                            <td>
                                <?php 
                                $isApproved = ($p['status'] === 'disetujui');
                                $approvedType = $p['judul_disetujui'] ?? 'utama';
                                ?>
                                <div style="font-weight: 600; color: #1e293b; display: flex; align-items: flex-start; gap: 6px;">
                                    <?php if ($isApproved && $approvedType === 'utama'): ?>
                                        <span class="badge-status disetujui" style="padding: 2px 8px; font-size: 11px; margin-top: 1px;"><i class="fa-solid fa-circle-check"></i> Utama</span>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($p['judul']) ?></span>
                                </div>
                                <?php if (!empty($p['judul_alternatif'])): ?>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 6px; font-style: italic; display: flex; align-items: flex-start; gap: 6px;">
                                        <?php if ($isApproved && $approvedType === 'alternatif'): ?>
                                            <span class="badge-status disetujui" style="padding: 2px 8px; font-size: 11px; margin-top: 1px; font-style: normal;"><i class="fa-solid fa-circle-check"></i> Alternatif</span>
                                        <?php endif; ?>
                                        <span>Alternatif: <?= htmlspecialchars($p['judul_alternatif']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap; color: #475569; font-size: 13px;">
                                <i class="fa-regular fa-calendar" style="margin-right: 5px; color: #285aa9;"></i>
                                <?= date('d M Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="badge-status <?= $p['status'] ?>"><?= $p['status'] ?></span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'disetujui'): ?>
                                    <div class="decision-info">
                                        <div class="d-row">
                                            <span class="d-label">Pembimbing 1:</span>
                                            <span><?= htmlspecialchars($distData['pembimbing1'] ?? $p['pembimbing1'] ?? '-') ?></span>
                                        </div>
                                        <div class="d-row">
                                            <span class="d-label">Pembimbing 2:</span>
                                            <span><?= htmlspecialchars($distData['pembimbing2'] ?? ($p['pembimbing2'] ?: '-')) ?></span>
                                        </div>
                                        <div class="d-row">
                                            <span class="d-label">Pembahas:</span>
                                            <span><?= htmlspecialchars($distData['pembahas1'] ?? '-') ?></span>
                                        </div>
                                        <div class="d-row" style="margin-top: 4px; border-top: 1px dashed #e2e8f0; padding-top: 4px;">
                                            <span class="d-label">Nomor SK:</span>
                                            <span style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($distData['nomor_sk'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                <?php elseif ($p['status'] === 'ditolak'): ?>
                                    <div style="color: #ef4444; font-size: 13px; font-style: italic; background: #fee2e2; border: 1px solid rgba(185,28,28,0.1); border-radius: 8px; padding: 10px 14px;">
                                        <strong>Alasan Penolakan:</strong><br>
                                        <?= !empty($p['alasan']) ? htmlspecialchars($p['alasan']) : 'Tidak ada alasan rinci yang dicantumkan.' ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #64748b; font-style: italic; font-size: 13px;">Sedang diverifikasi di loket/Kaprodi.</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: center; justify-content: center;">
                                    <button class="btn-lihat-berkas" type="button" onclick='bukaModalDetail(<?= json_encode($p) ?>, <?= json_encode($distData) ?>)' style="background: #eef4fb; color: #285aa9; border: 1px solid rgba(40, 90, 169, 0.2); padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; white-space: nowrap; width: 110px; justify-content: center; box-sizing: border-box;">
                                        <i class="fa-solid fa-folder-open"></i> Lihat Berkas
                                    </button>
                                    <?php if ($p['status'] === 'disetujui'): ?>
                                        <a href="../kaprodi/cetak_sk.php?id=<?= $p['id'] ?>" target="_blank" style="background: #eff6ff; color: #1e40af; border: 1px solid rgba(30, 64, 175, 0.2); padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; text-decoration: none; white-space: nowrap; width: 110px; justify-content: center; box-sizing: border-box;">
                                            <i class="fa-solid fa-print"></i> Cetak SK
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination-footer">
            <div class="pagination-info" id="paginationInfo"></div>
            <select class="rows-per-page" id="rowsPerPage">
                <option value="10">10 baris</option>
                <option value="25">25 baris</option>
                <option value="50">50 baris</option>
            </select>
            <div class="pagination-buttons" id="paginationButtons"></div>
        </div>
    </div>

</div>

<!-- MODAL DETAIL BERKAS MAHASISWA -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-card">
        <h3 class="modal-title">Detail Berkas Pengajuan</h3>

        <div class="modal-form-grid">
            <div class="modal-form-row">
                <label>Judul Utama</label>
                <textarea id="view_judul" class="modal-input-field" rows="2" readonly></textarea>
            </div>
            
            <div class="modal-form-row" id="row_judul_alt">
                <label>Judul Alternatif</label>
                <textarea id="view_judul_alternatif" class="modal-input-field" rows="2" readonly></textarea>
            </div>

            <div class="modal-form-row">
                <label>Berkas Persyaratan</label>
                <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                    <div id="btn_transkrip"></div>
                    <div id="btn_ktm"></div>
                    <div id="btn_form_tema"></div>
                    <div id="btn_bukti_ukt"></div>
                    <div id="btn_krs_terakhir"></div>
                    <div id="btn_form_verifikasi"></div>
                    <div id="btn_bukti_acc"></div>
                    <div id="btn_form_penetapan"></div>
                    <div id="btn_bab1"></div>
                    <div id="btn_bab1_alt"></div>
                </div>
            </div>

        </div>

        <div class="modal-buttons" style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <button type="button" class="btn-modal-back" onclick="closeAllModals()">
                <i class="fa-solid fa-chevron-left"></i> Kembali ke daftar
            </button>
            <div id="modal_print_action"></div>
        </div>
    </div>
</div>

<script>
    // Table search & pagination
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const rowsPerPageSel = document.getElementById('rowsPerPage');
    const tableBody = document.getElementById('tableBody');
    const allRows = Array.from(tableBody.querySelectorAll('tr:not(.no-data-row)'));
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    let currentPage = 1;

    const modalOverlay = document.getElementById('modalOverlay');

    function makeDocLink(filename, label, isDocx = false) {
        if (!filename) return `<span style="color: #94a3b8; font-style: italic;"><i class="fa-solid fa-minus"></i> ${label} (Tidak ada)</span>`;
        const path = `/bimbingan-skripsi/public/uploads/persyaratan/${filename}`;
        const icon = isDocx ? 'fa-file-word' : 'fa-file-pdf';
        const color = isDocx ? '#2563eb' : '#dc2626';
        return `<a href="${path}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; color: ${color}; font-weight: 600; text-decoration: none;"><i class="fa-solid ${icon}"></i> ${label}</a>`;
    }

    function bukaModalDetail(p, dist) {
        document.getElementById('view_judul').value = p.judul;
        
        const altRow = document.getElementById('row_judul_alt');
        if (p.judul_alternatif) {
            altRow.style.display = 'flex';
            document.getElementById('view_judul_alternatif').value = p.judul_alternatif;
        } else {
            altRow.style.display = 'none';
        }

        // Dynamically add approval labels
        const labelUtama = document.querySelector('#view_judul').previousElementSibling;
        const labelAlternatif = document.querySelector('#view_judul_alternatif').previousElementSibling;
        
        labelUtama.innerHTML = 'Judul Utama';
        labelAlternatif.innerHTML = 'Judul Alternatif';
        
        if (p.status === 'disetujui' && p.judul_disetujui === 'alternatif') {
            labelAlternatif.innerHTML = 'Judul Alternatif <span class="badge-status disetujui" style="padding: 1px 6px; font-size: 10px; margin-left: 6px; font-weight: normal;"><i class="fa-solid fa-circle-check"></i> Disetujui</span>';
        } else if (p.status === 'disetujui') {
            labelUtama.innerHTML = 'Judul Utama <span class="badge-status disetujui" style="padding: 1px 6px; font-size: 10px; margin-left: 6px; font-weight: normal;"><i class="fa-solid fa-circle-check"></i> Disetujui</span>';
        }

        // Fill documents buttons
        document.getElementById('btn_transkrip').innerHTML = makeDocLink(p.file_transkrip, 'Transkrip Nilai');
        document.getElementById('btn_ktm').innerHTML = makeDocLink(p.file_ktm, 'KTM');
        document.getElementById('btn_form_tema').innerHTML = makeDocLink(p.file_form_tema, 'Form Pengajuan Tema');
        document.getElementById('btn_bukti_ukt').innerHTML = makeDocLink(p.file_bukti_ukt, 'Bukti UKT');
        document.getElementById('btn_krs_terakhir').innerHTML = makeDocLink(p.file_krs_terakhir, 'KRS Terakhir');
        document.getElementById('btn_form_verifikasi').innerHTML = makeDocLink(p.file_form_verifikasi, 'Form Verifikasi');
        document.getElementById('btn_bukti_acc').innerHTML = makeDocLink(p.file_bukti_acc, 'Bukti ACC Dosen');
        document.getElementById('btn_form_penetapan').innerHTML = makeDocLink(p.file_form_penetapan, 'Form Penetapan Tema', true);
        document.getElementById('btn_bab1').innerHTML = makeDocLink(p.file_bab1, 'Bab 1 (Utama)', true);
        document.getElementById('btn_bab1_alt').innerHTML = makeDocLink(p.file_bab1_alt, 'Bab 1 (Alternatif)', true);

        const printActionDiv = document.getElementById('modal_print_action');
        if (p.status === 'disetujui') {
            printActionDiv.innerHTML = `<a href="../kaprodi/cetak_sk.php?id=${p.id}" target="_blank" style="background: #285aa9; color: #ffffff; padding: 9px 18px; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-print"></i> Cetak SK</a>`;
        } else {
            printActionDiv.innerHTML = '';
        }

        modalOverlay.style.display = 'flex';
    }

    function closeAllModals() {
        modalOverlay.style.display = 'none';
    }

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        return allRows.filter(row => row.dataset.keyword.includes(keyword));
    }

    function renderTable() {
        const filtered = getFilteredRows();
        const rowsPerPage = parseInt(rowsPerPageSel.value, 10);
        const totalData = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalData / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;

        allRows.forEach(row => row.style.display = 'none');

        const existingEmpty = tableBody.querySelector('.no-data-row');
        if (existingEmpty) existingEmpty.remove();

        if (totalData === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'no-data-row';
            emptyRow.innerHTML = `<td colspan="5" style="text-align: center; color: #94a3b8; padding: 22px;">Tidak ada pengajuan judul yang cocok.</td>`;
            tableBody.appendChild(emptyRow);
        } else {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filtered.slice(start, end).forEach(row => row.style.display = '');
        }

        paginationInfo.textContent = `Hal ${currentPage}/${totalPages} (${totalData} data)`;
        renderPaginationButtons(totalPages);
    }

    function renderPaginationButtons(totalPages) {
        paginationButtons.innerHTML = '';

        const makeBtn = (label, disabled, onClick, active = false) => {
            const btn = document.createElement('button');
            btn.innerHTML = label;
            btn.disabled = disabled;
            if (active) btn.classList.add('active');
            btn.onclick = onClick;
            return btn;
        };

        paginationButtons.appendChild(makeBtn('&laquo;', currentPage === 1, () => { currentPage = 1; renderTable(); }));
        paginationButtons.appendChild(makeBtn('&lsaquo;', currentPage === 1, () => { currentPage--; renderTable(); }));

        for (let i = 1; i <= totalPages; i++) {
            paginationButtons.appendChild(
                makeBtn(i, false, () => { currentPage = i; renderTable(); }, i === currentPage)
            );
        }

        paginationButtons.appendChild(makeBtn('&rsaquo;', currentPage === totalPages, () => { currentPage++; renderTable(); }));
        paginationButtons.appendChild(makeBtn('&raquo;', currentPage === totalPages, () => { currentPage = totalPages; renderTable(); }));
    }

    searchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
    searchBtn.addEventListener('click', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>

</body>
</html>
