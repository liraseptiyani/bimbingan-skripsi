<?php
session_start();

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

$title = 'Kaprodi - Monitoring Progres (Riwayat Bimbingan)';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$npm  = $_GET['npm']  ?? '-';

// Fetch student name
$stmtM = $pdo->prepare("SELECT nama FROM mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtM->execute([':npm' => $npm]);
$nama = $stmtM->fetchColumn() ?: ($_GET['nama'] ?? '-');

// Fetch distribution details
$stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtDist->execute([':npm' => $npm]);
$distribusi = $stmtDist->fetch(PDO::FETCH_ASSOC);

$pembimbingUtama = $distribusi['pembimbing1'] ?? '';
$pembimbingPembantu = $distribusi['pembimbing2'] ?? '';

// Fetch bimbingan list from database for this student
$stmtBimb = $pdo->prepare("SELECT * FROM bimbingan WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') ORDER BY id DESC");
$stmtBimb->execute([':npm' => $npm]);
$bimbingan_rows = $stmtBimb->fetchAll(PDO::FETCH_ASSOC);

$riwayat = [];
foreach ($bimbingan_rows as $row) {
    $riwayat[] = [
        'tanggal' => $row['tanggal'],
        'npm'     => $row['npm'],
        'nama'    => $row['nama'],
        'draft'   => $row['file_draft'],
        'id'      => $row['id'],
        'status_pembimbing1' => $row['status_pembimbing1'] ?? '',
        'status_pembimbing2' => $row['status_pembimbing2'] ?? ''
    ];
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* ================= RIWAYAT BIMBINGAN - LIST (khusus halaman ini) ================= */

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

    .card.table-card {
        border-top: 4px solid #69a86e;
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 28px;
    }

    .table-toolbar-single {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 18px;
    }

    .table-toolbar-single .search {
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0;
    }

    .table-toolbar-single .search input {
        border: 1px solid #cbd5e1;
        border-radius: 6px 0 0 6px;
        border-right: none;
        height: 38px;
        padding: 8px 14px;
        font-size: 14px;
        width: 240px;
        box-sizing: border-box;
    }

    .table-toolbar-single .search button {
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

    .table-toolbar-single .search button:hover {
        background: #3d8b51;
    }

    table#riwayatTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    table#riwayatTable th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        text-align: left;
    }

    table#riwayatTable td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14.5px;
        color: #334155;
        vertical-align: middle;
    }

    table#riwayatTable tbody tr:hover {
        background: #f8fafc;
    }

    .draft-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #e14434;
        text-decoration: none;
        font-weight: 500;
    }

    .draft-link:hover {
        text-decoration: underline;
    }

    .draft-link i {
        font-size: 18px;
    }

    .draft-link span {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13.5px;
    }

    .aksi-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-lihat {
        background: #eef4fb;
        color: #285aa9;
        border: 1px solid rgba(40, 90, 169, 0.2);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-lihat:hover {
        background: #dbeafe;
        border-color: rgba(40, 90, 169, 0.35);
        transform: translateY(-1px);
    }

    .no-data-row td {
        text-align: center;
        color: #94a3b8;
        padding: 22px !important;
    }

    /* --- Pagination --- */
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
</style>

<div class="content">

    <a href="/bimbingan-skripsi/app/views/kaprodi/monitoring_progres.php" class="page-title-back">
        <i class="fa-solid fa-chevron-left"></i>
        Riwayat Bimbingan: <?= htmlspecialchars($nama) ?>
    </a>

    <div class="card table-card">

        <div class="table-toolbar-single">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari Riwayat Bimbingan">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <table id="riwayatTable">
            <thead>
                <tr>
                    <th style="width: 130px;">Tanggal</th>
                    <th style="width: 120px;">NPM</th>
                    <th>Nama</th>
                    <th>Draft</th>
                    <th style="text-align: center; width: 90px;">Status</th>
                    <th style="width: 70px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($riwayat as $r): ?>
                <tr data-keyword="<?= strtolower($r['nama'] . ' ' . $r['npm'] . ' ' . $r['tanggal']) ?>">
                    <td style="white-space: nowrap;"><?= htmlspecialchars($r['tanggal']) ?></td>
                    <td style="white-space: nowrap;"><?= htmlspecialchars($r['npm']) ?></td>
                    <td><?= htmlspecialchars($r['nama']) ?></td>
                    <td class="col-draft">
                        <a href="/bimbingan-skripsi/public/uploads/draft/<?= htmlspecialchars($r['draft']) ?>" class="draft-link" target="_blank" title="<?= htmlspecialchars($r['draft']) ?>">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span><?= htmlspecialchars($r['draft']) ?></span>
                        </a>
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; font-weight: 500; text-align: left; width: fit-content; margin: 0 auto;">
                            <?php if (!empty($pembimbingUtama)): ?>
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="color: #475569;">P1:</span>
                                    <?php if ($r['status_pembimbing1'] === 'sudah_dibalas'): ?>
                                        <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 1 Sudah Membalas"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 1 Belum Membalas"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($pembimbingPembantu)): ?>
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="color: #475569;">P2:</span>
                                    <?php if ($r['status_pembimbing2'] === 'sudah_dibalas'): ?>
                                        <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 2 Sudah Membalas"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 2 Belum Membalas"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="aksi-group" style="justify-content: center;">
                            <a class="btn-lihat"
                               href="/bimbingan-skripsi/app/views/kaprodi/riwayat_detail.php?npm=<?= urlencode($r['npm']) ?>&nama=<?= urlencode($r['nama']) ?>&id=<?= $r['id'] ?>"
                               title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
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

<script>
    const searchInput    = document.getElementById('searchInput');
    const searchBtn      = document.getElementById('searchBtn');
    const rowsPerPageSel  = document.getElementById('rowsPerPage');
    const tableBody       = document.getElementById('tableBody');
    const paginationInfo    = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

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
            emptyRow.innerHTML = `<td colspan="6" style="text-align:center;color:#94a3b8;padding:22px;">Tidak ada riwayat bimbingan yang cocok.</td>`;
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

        paginationButtons.appendChild(makeBtn('«', currentPage === 1, () => { currentPage = 1; renderTable(); }));
        paginationButtons.appendChild(makeBtn('‹', currentPage === 1, () => { currentPage--; renderTable(); }));

        for (let i = 1; i <= totalPages; i++) {
            paginationButtons.appendChild(
                makeBtn(i, false, () => { currentPage = i; renderTable(); }, i === currentPage)
            );
        }

        paginationButtons.appendChild(makeBtn('›', currentPage === totalPages, () => { currentPage++; renderTable(); }));
        paginationButtons.appendChild(makeBtn('»', currentPage === totalPages, () => { currentPage = totalPages; renderTable(); }));
    }

    searchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
    searchBtn.addEventListener('click', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>

</body>
</html>
