<?php
session_start();

// ==========================================================
// PROTEKSI HALAMAN: hanya dosen yang boleh mengakses
// ==========================================================
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if (($_SESSION['otoritas'] ?? '') === 'kaprodi') {
    header("Location: /bimbingan-skripsi/app/views/kaprodi/dashboard.php");
    exit;
}

$title = 'Dosen - Bimbingan';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$nipDosen = $_SESSION['username'];
// Get Dosen name
$stmtDosen = $pdo->prepare("SELECT nama FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '') LIMIT 1");
$stmtDosen->execute([':nip' => $nipDosen]);
$namaDosen = $stmtDosen->fetchColumn();

// Fetch bimbingan list from database for students assigned to this Dosen
$stmtBimb = $pdo->prepare("
    SELECT b.*, dm.pembimbing1, dm.pembimbing2
    FROM bimbingan b
    JOIN distribusi_mahasiswa dm ON REPLACE(b.npm, ' ', '') = REPLACE(dm.npm, ' ', '')
    WHERE LOWER(REGEXP_REPLACE(dm.pembimbing1, '[^a-zA-Z0-9]', '', 'g')) = LOWER(REGEXP_REPLACE(:nama, '[^a-zA-Z0-9]', '', 'g'))
       OR LOWER(REGEXP_REPLACE(dm.pembimbing2, '[^a-zA-Z0-9]', '', 'g')) = LOWER(REGEXP_REPLACE(:nama, '[^a-zA-Z0-9]', '', 'g'))
    ORDER BY b.id DESC
");
$stmtBimb->execute([':nama' => $namaDosen]);
$bimbingan_raw = $stmtBimb->fetchAll(PDO::FETCH_ASSOC);

$daftar_bimbingan = [];
foreach ($bimbingan_raw as $b) {
    $angkatan = 2000 + (int)substr(trim($b['npm']), 0, 2);
    $daftar_bimbingan[] = [
        'id'             => $b['id'],
        'tanggal'        => $b['tanggal'],
        'npm'            => $b['npm'],
        'nama'           => $b['nama'],
        'file_draft'     => $b['file_draft'],
        'angkatan'       => $angkatan,
        'status'         => 'Aktif',
        'status_balasan' => $b['status_balasan'],
        'status_pembimbing1' => $b['status_pembimbing1'],
        'status_pembimbing2' => $b['status_pembimbing2'],
        'pembimbing1' => $b['pembimbing1'],
        'pembimbing2' => $b['pembimbing2'],
    ];
}

// Opsi dropdown filter (dummy, nanti ambil dari data unik di database)
$daftar_angkatan = [2021, 2022, 2023, 2024];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_dosen.php';
?>

<style>
    /* ================= BIMBINGAN (khusus halaman ini) ================= */

    /* --- Card Filter (border oranye) --- */
    .card-filter {
        background: #ffffff;
        border-top: 4px solid #E1A043;
        box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        padding: 22px 24px;
        border-radius: 4px;
        margin-bottom: 22px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px 40px;
    }

    .filter-item {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .filter-item label {
        width: 100px;
        flex-shrink: 0;
        color: #d18a2a;
        font-weight: 600;
        font-size: 14.5px;
    }

    .filter-item select {
        flex: 1;
        padding: 9px 12px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        color: #333;
        background: #ffffff;
    }

    .filter-item select:focus {
        outline: none;
        border-color: #285aa9;
    }

    /* --- Toolbar tabel (search saja, rata kanan) --- */
    .table-toolbar-right {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 18px;
    }

    .table-toolbar-right .search {
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0;
    }

    .table-toolbar-right .search input {
        width: 260px;
        border-radius: 4px 0 0 4px;
        border-right: none;
        height: 38px;
        box-sizing: border-box;
    }

    .table-toolbar-right .search button {
        border-radius: 0 4px 4px 0;
        height: 38px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #4AA361;
    }

    .table-toolbar-right .search button:hover {
        background: #3d8b51;
    }

    /* --- Kolom tabel --- */
    table td.col-draft {
        text-align: center;
    }

    .draft-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        justify-content: center;
    }

    .draft-link i {
        font-size: 16px;
        color: #d9534f;
        flex-shrink: 0;
    }

    .draft-link span {
        font-size: 13px;
        color: #285aa9;
        font-weight: 500;
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: middle;
    }

    .draft-link:hover span {
        text-decoration: underline;
    }

    table th.col-aksi,
    table td.col-aksi {
        width: 90px;
        text-align: center;
    }

    .aksi-group {
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .aksi-group a {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 4px;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
    }

    .btn-lihat { background: #7db8db; }
    .btn-lihat:hover { opacity: .9; }

    .no-data-row td {
        text-align: center;
        color: #94a3b8;
        padding: 22px !important;
    }

    /* --- Pagination gaya "Hal X/Y (N data)" --- */
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

    table#bimbinganTable thead th {
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

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .table-toolbar-right .search input {
            width: 180px;
        }
    }
</style>

<div class="content">

    <h1 class="page-title">Daftar Bimbingan</h1>

    <!-- ============ CARD FILTER ============ -->
    <div class="card-filter">
        <div class="filter-grid">

            <div class="filter-item">
                <label>Jenis TA</label>
                <select id="filterJenisTA">
                    <option value="Skripsi">Skripsi</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Status</label>
                <select id="filterStatus">
                    <option value="Aktif">Aktif</option>
                    <option value="Selesai">Selesai</option>
                    <option value="">Semua Status</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Unit / Prodi</label>
                <select id="filterProdi">
                    <option value="S1 Ilmu Komputer">S1 Ilmu Komputer</option>
                </select>
            </div>

            <div class="filter-item">
                <label>Angkatan</label>
                <select id="filterAngkatan">
                    <option value="">--Semua Angkatan--</option>
                    <?php foreach ($daftar_angkatan as $angkatan): ?>
                        <option value="<?= $angkatan ?>"><?= $angkatan ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
    </div>

    <!-- ============ TABEL DAFTAR BIMBINGAN ============ -->
    <div class="card">

        <div class="table-toolbar-right">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari Daftar Bimbingan">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <table id="bimbinganTable">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>NPM</th>
                    <th>Nama</th>
                    <th style="width:160px;">Draft</th>
                    <th style="text-align:center; width:90px;">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($daftar_bimbingan as $b): ?>
                <tr data-npm="<?= $b['npm'] ?>"
                    data-nama="<?= strtolower($b['nama']) ?>"
                    data-angkatan="<?= $b['angkatan'] ?>"
                    data-status="<?= $b['status'] ?>">
                    <td><?= htmlspecialchars($b['tanggal']) ?></td>
                    <td><?= htmlspecialchars($b['npm']) ?></td>
                    <td><?= htmlspecialchars($b['nama']) ?></td>
                    <td class="col-draft">
                        <a href="/bimbingan-skripsi/public/uploads/draft/<?= htmlspecialchars($b['file_draft']) ?>" class="draft-link" target="_blank" title="<?= htmlspecialchars($b['file_draft']) ?>">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span><?= htmlspecialchars($b['file_draft']) ?></span>
                        </a>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; font-weight: 500; text-align: left; width: fit-content; margin: 0 auto;">
                            <?php if (!empty($b['pembimbing1'])): ?>
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="color: #475569;">P1:</span>
                                    <?php if (isset($b['status_pembimbing1']) && $b['status_pembimbing1'] === 'sudah_dibalas'): ?>
                                        <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 1 Sudah Membalas"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 1 Belum Membalas"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($b['pembimbing2'])): ?>
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="color: #475569;">P2:</span>
                                    <?php if (isset($b['status_pembimbing2']) && $b['status_pembimbing2'] === 'sudah_dibalas'): ?>
                                        <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 2 Sudah Membalas"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 2 Belum Membalas"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="col-aksi">
                        <div class="aksi-group">
                            <a href="/bimbingan-skripsi/app/views/dosen/detail_bimbingan.php?id=<?= $b['id'] ?>" class="btn-lihat" title="Lihat Detail">
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
    // ==========================================================
    // Search + Filter + Pagination (client-side, karena masih dummy)
    // Nanti kalau sudah pakai PDO, ini diganti server-side query
    // ==========================================================
    const searchInput    = document.getElementById('searchInput');
    const searchBtn      = document.getElementById('searchBtn');
    const filterStatus   = document.getElementById('filterStatus');
    const filterAngkatan = document.getElementById('filterAngkatan');
    const rowsPerPageSel = document.getElementById('rowsPerPage');
    const tableBody      = document.getElementById('tableBody');
    const paginationInfo    = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        const status = filterStatus.value;
        const angkatan = filterAngkatan.value;

        return allRows.filter(row => {
            const npm = row.dataset.npm.toLowerCase();
            const nama = row.dataset.nama;
            const rowStatus = row.dataset.status;
            const rowAngkatan = row.dataset.angkatan;

            const matchKeyword  = npm.includes(keyword) || nama.includes(keyword);
            const matchStatus   = status === '' || rowStatus === status;
            const matchAngkatan = angkatan === '' || rowAngkatan === angkatan;

            return matchKeyword && matchStatus && matchAngkatan;
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
            emptyRow.innerHTML = `<td colspan="6">Tidak ada data bimbingan yang cocok.</td>`;
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
    filterStatus.addEventListener('change', () => { currentPage = 1; renderTable(); });
    filterAngkatan.addEventListener('change', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>

</body>
</html>