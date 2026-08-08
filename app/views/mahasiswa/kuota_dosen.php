<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

if (($_SESSION['role'] ?? '') === 'dosen') {
    if (($_SESSION['otoritas'] ?? '') === 'dosen') {
        header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
        exit;
    } elseif (($_SESSION['otoritas'] ?? '') === 'kaprodi') {
        header("Location: " . BASE_URL . "/app/views/kaprodi/dashboard.php");
        exit;
    }
}

$title = "Daftar Dosen";

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

require_once dirname(__DIR__, 3) . '/app/controllers/DosenController.php';

// Fetch all dosen
$stmt = $pdo->query("SELECT * FROM dosen ORDER BY nama ASC");
$daftarDosenRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dosen = [];
foreach ($daftarDosenRaw as $d) {
    // Calculate terisi dynamically from distribusi_mahasiswa (only active guidance: status_bimbingan != 'selesai')
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM distribusi_mahasiswa WHERE (pembimbing1 = :nip OR pembimbing2 = :nip OR pembimbing1 = :nama OR pembimbing2 = :nama) AND (status_bimbingan IS NULL OR status_bimbingan != 'selesai')");
    $stmtCount->execute([':nip' => $d['nip'], ':nama' => $d['nama']]);
    $terisi = $stmtCount->fetchColumn();

    $dosen[] = [
        'nama' => $d['nama'],
        'bidang' => $d['bidang_ilmu'] ?? '',
        'kuota' => $terisi . '/' . ($d['kuota_max'] ?? 10)
    ];
}

include '../layouts/header.php';
include '../layouts/sidebar_mahasiswa.php';
include '../layouts/topbar.php';
?>

<style>

.content{
    margin-left:270px;
    margin-top:70px;
    padding:25px;
}

.page-title{
    font-size:20px;
    font-weight:500;
    margin-bottom:15px;
}

.card{
    background:#fff;
    border-top:4px solid #6da36f;
    box-shadow:0 2px 6px rgba(0,0,0,.15);
    padding:20px;
}

.search{
    display:flex;
    justify-content:flex-end;
    margin-bottom:20px;
}

.search input{
    width:300px;
    height:38px;
    border:1px solid #ccc;
    padding:10px;
    font-size:14px;
}

.search button{
    width:40px;
    border:none;
    background:#68a86f;
    color:white;
    cursor:pointer;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#3f4c62;
    color:white;
}

th{
    padding:12px;
    font-size:13px;
    font-weight:600;
}

td{
    padding:10px;
    border:1px solid #ddd;
    font-size:13px;
}

td:nth-child(1),
td:nth-child(4){
    text-align:center;
}

/* FOOTER TABLE (CONSISTENT DESIGN) */
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
    display: inline-flex;
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

/* OVERRIDE SEARCH GAPLESS */
.card .search {
    display: flex;
    justify-content: flex-end;
    gap: 0 !important;
    margin-bottom: 20px;
}

.card .search input {
    width: 300px;
    padding: 10px;
    border: 1px solid #cccccc;
    border-right: none !important;
    border-radius: 4px 0 0 4px !important;
}

.card .search button {
    background: #4AA361 !important;
    color: #ffffff;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    border-radius: 0 4px 4px 0 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.card .search button:hover {
    background: #3d8b51 !important;
}

table thead th {
    background: #34495e !important;
    color: #ffffff;
    font-weight: 600;
    padding: 12px;
    font-size: 14px;
}

</style>

<div class="content">

    <div class="page-title">
        Daftar Dosen
    </div>

    <div class="card">

        <div class="search">
            <input type="text" id="searchInput" placeholder="Cari Dosen">
            <button type="button" id="searchBtn">
                <i class="fa fa-search"></i>
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nama Dosen</th>
                    <th>Bidang Ilmu</th>
                    <th width="120">Kuota Bimbingan</th>
                </tr>
            </thead>

            <tbody id="tableBody">
            <?php foreach($dosen as $i=>$d): ?>
                <tr data-nama="<?= htmlspecialchars(strtolower($d['nama'])) ?>" 
                    data-bidang="<?= htmlspecialchars(strtolower($d['bidang'])) ?>">
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($d['nama']) ?></td>
                    <td><?= htmlspecialchars($d['bidang']) ?></td>
                    <td><?= htmlspecialchars($d['kuota']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination-footer">
            <div class="pagination-info" id="paginationInfo">Hal 1/1 (0 data)</div>
            
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
    // Search + Pagination
    const searchInput      = document.getElementById('searchInput');
    const searchBtn        = document.getElementById('searchBtn');
    const rowsPerPageSel   = document.getElementById('rowsPerPage');
    const paginationInfo   = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    const tableBody        = document.getElementById('tableBody');
    const allRows          = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        return allRows.filter(row => {
            if (row.classList.contains('no-data-row')) return false;
            const nama = row.dataset.nama || '';
            const bidang = row.dataset.bidang || '';
            return nama.includes(keyword) || bidang.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="4" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada data dosen yang cocok.</td>`;
            tableBody.appendChild(emptyRow);
        } else {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filtered.slice(start, end).forEach((row, idx) => {
                row.style.display = '';
                row.cells[0].textContent = start + idx + 1; // update running number
            });
        }

        paginationInfo.textContent = `Hal ${currentPage}/${totalPages} (${totalData} data)`;
        renderPaginationButtons(totalPages);
    }

    function renderPaginationButtons(totalPages) {
        paginationButtons.innerHTML = '';

        const makeBtn = (label, disabled, onClick, active = false) => {
            const btn = document.createElement('button');
            btn.type = 'button';
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
            if (i >= 1 && i <= totalPages) {
                paginationButtons.appendChild(
                    makeBtn(i, false, () => { currentPage = i; renderTable(); }, i === currentPage)
                );
            }
        }

        paginationButtons.appendChild(makeBtn('&rsaquo;', currentPage === totalPages, () => { currentPage++; renderTable(); }));
        paginationButtons.appendChild(makeBtn('&raquo;', currentPage === totalPages, () => { currentPage = totalPages; renderTable(); }));
    }

    searchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
    searchBtn.addEventListener('click', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>