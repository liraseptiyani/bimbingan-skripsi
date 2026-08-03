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

$title = 'Kaprodi - Persetujuan Judul';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch all dosen for dropdowns
try {
    $stmtD = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
    $daftar_dosen = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_dosen = [];
}

// Fetch pending submissions only
try {
    $stmtP = $pdo->query("SELECT * FROM pengajuan_judul WHERE status = 'menunggu' ORDER BY id DESC");
    $pengajuan_list = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pengajuan_list = [];
}

// Gather unique angkatans for filter
$daftar_angkatan = [];
foreach ($pengajuan_list as $p) {
    $prefix = substr(trim($p['mahasiswa_npm']), 0, 2);
    if (is_numeric($prefix)) {
        $daftar_angkatan[] = "20" . $prefix;
    }
}
$daftar_angkatan = array_unique($daftar_angkatan);
$daftar_angkatan = array_filter($daftar_angkatan);
rsort($daftar_angkatan);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* Card Styles */
    .card.table-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        border-top: 4px solid #69a86e;
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

    .filter-angkatan-wrap {
        position: relative;
    }

    .filter-angkatan {
        appearance: none;
        -webkit-appearance: none;
        padding: 9px 34px 9px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        color: #334155;
        background: #fff;
        min-width: 170px;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .filter-angkatan-wrap::after {
        content: "\25BC";
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10px;
        color: #64748b;
        pointer-events: none;
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

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 16px;
    }

    table#judulTable {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
        margin-top: 0;
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

    /* Action Buttons */
    .btn-proses {
        background: #eef4fb;
        color: #285aa9;
        border: 1px solid rgba(40, 90, 169, 0.2);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-proses:hover {
        background: #285aa9;
        color: #ffffff;
        border-color: #285aa9;
    }

    /* Modal Overlay & Card Container */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-container {
        background: #ffffff;
        border-radius: 16px;
        width: 650px;
        max-width: 95%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }

    .modal-header {
        background: #285aa9;
        color: #ffffff;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.8);
        font-size: 20px;
        cursor: pointer;
    }

    .modal-body {
        padding: 24px;
        max-height: 500px;
        overflow-y: auto;
    }

    /* Detail Review Section */
    .detail-review {
        background: #eef4fb;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(40, 90, 169, 0.1);
    }

    .detail-review .review-row {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 6px;
        padding: 4px 0;
        font-size: 13.5px;
    }

    .detail-review .review-label {
        color: #285aa9;
        font-weight: 600;
    }

    .detail-review .review-value {
        color: #334155;
        line-height: 1.45;
    }

    /* Tab Choice styling */
    .decision-tabs {
        display: flex;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 18px;
    }

    .decision-tab {
        flex: 1;
        text-align: center;
        padding: 10px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        color: #64748b;
        transition: all 0.2s;
    }

    .decision-tab.active-setuju {
        border-color: #22c55e;
        color: #16a34a;
    }

    .decision-tab.active-tolak {
        border-color: #ef4444;
        color: #dc2626;
    }

    .decision-panel {
        display: none;
    }

    .decision-panel.active {
        display: block;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13.5px;
        color: #334155;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #285aa9;
    }

    .modal-footer {
        padding: 16px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .btn-cancel {
        background: #cbd5e1;
        color: #334155;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-save-decision {
        background: #285aa9;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 class="page-title" style="margin: 0;">Persetujuan Judul Skripsi</h1>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-toolbar">
            <div class="filter-angkatan-wrap">
                <select class="filter-angkatan" id="filterAngkatan">
                    <option value="">Filter Angkatan</option>
                    <?php foreach ($daftar_angkatan as $angkatan): ?>
                        <option value="<?= $angkatan ?>"><?= $angkatan ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari NPM, nama, judul...">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="judulTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center; white-space: nowrap;">No</th>
                    <th style="width: 120px; white-space: nowrap;">NPM</th>
                    <th style="width: 200px; white-space: nowrap;">Nama Mahasiswa</th>
                    <th>Judul Skripsi Diajukan</th>
                    <th style="width: 160px; white-space: nowrap;">Tanggal Pengajuan</th>
                    <th style="width: 100px; text-align: center; white-space: nowrap;">Status</th>
                    <th style="width: 110px; text-align: center; white-space: nowrap;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($pengajuan_list)): ?>
                    <tr class="no-data-row">
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 22px;">Tidak ada pengajuan judul baru yang memerlukan persetujuan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengajuan_list as $i => $p): 
                        $prefix = substr(trim($p['mahasiswa_npm']), 0, 2);
                        $angkatan = is_numeric($prefix) ? "20" . $prefix : "";
                    ?>
                        <tr data-keyword="<?= strtolower($p['mahasiswa_npm'] . ' ' . $p['mahasiswa_nama'] . ' ' . $p['judul'] . ' ' . ($p['judul_alternatif'] ?? '')) ?>"
                            data-angkatan="<?= $angkatan ?>">
                            <td style="text-align: center; white-space: nowrap;"><?= $i + 1 ?></td>
                            <td style="white-space: nowrap;"><?= htmlspecialchars($p['mahasiswa_npm']) ?></td>
                            <td style="white-space: nowrap;"><?= htmlspecialchars($p['mahasiswa_nama']) ?></td>
                            <td>
                                <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($p['judul']) ?></div>
                                <?php if (!empty($p['judul_alternatif'])): ?>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 4px; font-style: italic;">
                                        Alternatif: <?= htmlspecialchars($p['judul_alternatif']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap; color: #475569; font-size: 13px;">
                                <i class="fa-regular fa-clock" style="margin-right: 5px; color: #285aa9;"></i>
                                <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="badge-status menunggu">menunggu</span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="proses_pengajuan.php?id=<?= $p['id'] ?>" class="btn-proses" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-square-check"></i> Proses
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>


    // Table Search, Filter and Pagination
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const filterAngkatan = document.getElementById('filterAngkatan');
    const rowsPerPageSel = document.getElementById('rowsPerPage');
    const tableBody = document.getElementById('tableBody');
    const allRows = Array.from(tableBody.querySelectorAll('tr:not(.no-data-row)'));
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        const angkatan = filterAngkatan.value;

        return allRows.filter(row => {
            const rowKeyword = row.dataset.keyword;
            const rowAngkatan = row.dataset.angkatan;

            const matchKeyword = rowKeyword.includes(keyword);
            const matchAngkatan = angkatan === '' || rowAngkatan === angkatan;

            return matchKeyword && matchAngkatan;
        });
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
            emptyRow.innerHTML = `<td colspan="7" style="text-align: center; color: #94a3b8; padding: 22px;">Tidak ada pengajuan judul yang cocok.</td>`;
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
    filterAngkatan.addEventListener('change', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>

</body>
</html>
