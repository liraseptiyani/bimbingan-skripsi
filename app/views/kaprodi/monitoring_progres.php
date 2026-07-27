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

$title = 'Kaprodi - Monitoring Progres';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$monitoring = [];
try {
    $sql = "
        SELECT DISTINCT dm.npm, dm.nama, dm.pembimbing1, dm.pembimbing2
        FROM distribusi_mahasiswa dm
        JOIN bimbingan b ON LOWER(REPLACE(dm.npm, ' ', '')) = LOWER(REPLACE(b.npm, ' ', ''))
        ORDER BY dm.nama ASC
    ";
    $stmt = $pdo->query($sql);
    $monitoring_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monitoring_raw as $mhs) {
        $prefix = substr(trim($mhs['npm']), 0, 2);
        $angkatan = is_numeric($prefix) ? (int)('20' . $prefix) : 2022;

        $monitoring[] = [
            'npm'         => $mhs['npm'],
            'nama'        => $mhs['nama'],
            'pembimbing1' => $mhs['pembimbing1'] ?: '-',
            'pembimbing2' => $mhs['pembimbing2'] ?: '-',
            'angkatan'    => $angkatan
        ];
    }
} catch (PDOException $e) {
    // Fail silently
}

$daftar_angkatan = array_unique(array_column($monitoring, 'angkatan'));
$daftar_angkatan = array_filter($daftar_angkatan);
rsort($daftar_angkatan);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* ================= MONITORING PROGRES (khusus halaman ini) ================= */

    .card.table-card {
        border-top: 4px solid #69a86e;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #222;
        margin-bottom: 18px;
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
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 14px;
        color: #444;
        background: #fafafa;
        min-width: 170px;
        cursor: pointer;
    }

    .filter-angkatan-wrap::after {
        content: "\25BC";
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10px;
        color: #888;
        pointer-events: none;
    }

    .table-toolbar .search {
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0;
    }

    .table-toolbar .search input {
        border-radius: 4px 0 0 4px;
        border-right: none;
        height: 38px;
        box-sizing: border-box;
    }

    .table-toolbar .search button {
        border-radius: 0 4px 4px 0;
        height: 38px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #4AA361;
    }

    .table-toolbar .search button:hover {
        background: #3d8b51;
    }

    #monitoringTable th:last-child,
    #monitoringTable td:last-child {
        text-align: center;
        width: 70px;
    }

    .btn-aksi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 5px;
        background: #56BDEA;
        color: #ffffff;
        border: none;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
    }

    .btn-aksi:hover {
        background: #3ba8d8;
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
        border-top: 1px solid #eee;
    }

    .pagination-info {
        font-size: 13.5px;
        color: #1e3a8a;
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 6px 12px;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }

    table#monitoringTable thead th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
    }

    .rows-per-page {
        padding: 7px 10px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 13.5px;
        color: #444;
    }

    .pagination-buttons {
        display: flex;
        gap: 5px;
    }

    .pagination-buttons button {
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #333;
        min-width: 34px;
        height: 34px;
        border-radius: 4px;
        font-size: 13.5px;
        cursor: pointer;
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

    <h1 class="page-title">Monitoring Progres Bimbingan</h1>

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
                <input type="text" id="searchInput" placeholder="Cari Mahasiswa">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <table id="monitoringTable">
            <thead>
                <tr>
                    <th>NPM</th>
                    <th>Nama</th>
                    <th>Pembimbing 1</th>
                    <th>Pembimbing 2</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($monitoring as $mhs): ?>
                <tr data-npm="<?= $mhs['npm'] ?>" data-nama="<?= strtolower($mhs['nama']) ?>" data-angkatan="<?= $mhs['angkatan'] ?>">
                    <td><?= htmlspecialchars($mhs['npm']) ?></td>
                    <td><?= htmlspecialchars($mhs['nama']) ?></td>
                    <td><?= htmlspecialchars($mhs['pembimbing1']) ?></td>
                    <td><?= htmlspecialchars($mhs['pembimbing2']) ?></td>
                    <td>
                        <a class="btn-aksi" href="/bimbingan-skripsi/app/views/kaprodi/detail_progres.php?npm=<?= urlencode($mhs['npm']) ?>&nama=<?= urlencode($mhs['nama']) ?>" title="Lihat Detail">
                            <i class="fa-solid fa-eye"></i>
                        </a>
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
    const filterAngkatan = document.getElementById('filterAngkatan');
    const rowsPerPageSel  = document.getElementById('rowsPerPage');
    const tableBody       = document.getElementById('tableBody');
    const paginationInfo    = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        const angkatan = filterAngkatan.value;

        return allRows.filter(row => {
            const npm = row.dataset.npm.toLowerCase();
            const nama = row.dataset.nama;
            const rowAngkatan = row.dataset.angkatan;

            const matchKeyword = npm.includes(keyword) || nama.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="5">Tidak ada data mahasiswa yang cocok.</td>`;
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

        const maxButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        startPage = Math.max(1, endPage - maxButtons + 1);

        paginationButtons.appendChild(makeBtn('&laquo;', currentPage === 1, () => { currentPage = 1; renderTable(); }));
        paginationButtons.appendChild(makeBtn('&lsaquo;', currentPage === 1, () => { currentPage--; renderTable(); }));

        for (let i = startPage; i <= endPage; i++) {
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
