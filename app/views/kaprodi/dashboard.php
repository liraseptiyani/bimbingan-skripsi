<?php
session_start();

// ==========================================================
// PROTEKSI HALAMAN: hanya kaprodi yang boleh mengakses
// ==========================================================
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: " . BASE_URL . "/");
    exit;
}

if (($_SESSION['otoritas'] ?? '') === 'dosen') {
    header("Location: " . BASE_URL . "/app/views/dosen/dashboard.php");
    exit;
}

$nama_kaprodi = $_SESSION['nama'];
$title = 'Kaprodi - Dashboard';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$mahasiswa_bimbingan = [];
try {
    $stmt = $pdo->query("SELECT * FROM distribusi_mahasiswa ORDER BY created_at DESC");
    $dbMhsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($dbMhsList as $item) {
        // Parse angkatan from NPM
        $prefix = substr(trim($item['npm']), 0, 2);
        $angkatan = is_numeric($prefix) ? (int)('20' . $prefix) : 2022;
        $semester_by_angkatan = [2020 => 12, 2021 => 10, 2022 => 8, 2023 => 6];
        $sem = $semester_by_angkatan[$angkatan] ?? 8;

        $mahasiswa_bimbingan[] = [
            'npm'      => $item['npm'],
            'nama'     => $item['nama'],
            'jenjang'  => 'S1',
            'prodi'    => 'Ilmu Komputer',
            'semester' => $sem,
            'status'   => ($item['status_bimbingan'] ?? 'aktif') === 'selesai' ? 'L' : 'A',
            'angkatan' => $angkatan,
            'status_bimbingan' => $item['status_bimbingan'] ?? 'aktif'
        ];
    }
} catch (PDOException $e) {
    // Fail silently
}

// Fetch total research topics count dynamically
$topik_count = 0;
try {
    $stmtTopik = $pdo->query("SELECT COUNT(*) FROM topik_penelitian");
    $topik_count = (int)($stmtTopik ? $stmtTopik->fetchColumn() : 0);
} catch (PDOException $e) {
    // Fail silently
}

$statistik = [
    'mahasiswa_bimbingan' => count($mahasiswa_bimbingan),
    'selesai_bimbingan'   => count(array_filter($mahasiswa_bimbingan, function($x) { return $x['status_bimbingan'] === 'selesai'; })),
    'bimbingan_saat_ini'  => count(array_filter($mahasiswa_bimbingan, function($x) { return $x['status_bimbingan'] === 'aktif'; })),
    'topik_penelitian'    => $topik_count,
];

// Daftar angkatan unik untuk dropdown filter
$daftar_angkatan = array_unique(array_column($mahasiswa_bimbingan, 'angkatan'));
$daftar_angkatan = array_filter($daftar_angkatan);
rsort($daftar_angkatan);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    :root {
        --primary-color: #285aa9;
        --primary-dark: #1e3a8a;
        --primary-light: #e7eef9;
        --success-color: #22c55e;
        --success-light: #f0fdf4;
        --warning-color: #eab308;
        --warning-light: #fefbeb;
        --text-dark: #1e293b;
        --text-gray: #64748b;
        --bg-light: #f8fafc;
        --border-color: #e2e8f0;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    }

    .content {
        background-color: #f8fafc !important;
        min-height: calc(100vh - 70px);
        font-family: 'Outfit', 'Inter', sans-serif;
    }

    /* =====================================================
       WELCOME BANNER
    ===================================================== */
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-radius: 16px;
        padding: 32px;
        color: #ffffff;
        box-shadow: 0 8px 30px rgba(40, 90, 169, 0.15);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 28px;
    }

    .welcome-banner:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(40, 90, 169, 0.2);
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 350px;
        height: 350px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
    }

    .welcome-banner-content h1 {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        color: #ffffff;
    }

    .welcome-banner-content p {
        font-size: 15px;
        opacity: 0.9;
        margin-bottom: 20px;
        line-height: 1.5;
        color: #e2e8f0;
    }

    .welcome-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .welcome-badge {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(4px);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    /* =====================================================
       STATISTIC CARDS (MODERN WHITE CARD WITH ACCENT BORDERS)
    ===================================================== */
    .stat-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-box {
        background: #ffffff;
        border-radius: 12px;
        padding: 22px 24px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 110px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .stat-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .stat-box::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .stat-blue::after { background: var(--primary-color); }
    .stat-green::after { background: #3fae4e; }
    .stat-orange::after { background: #f2a13e; }
    .stat-red::after { background: #da6e64; }

    .stat-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1;
    }

    .stat-icon {
        font-size: 24px;
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-blue .stat-icon { background: #eff6ff; color: var(--primary-color); }
    .stat-green .stat-icon { background: #f0fdf4; color: #3fae4e; }
    .stat-orange .stat-icon { background: #fffbeb; color: #f2a13e; }
    .stat-red .stat-icon { background: #fff1f2; color: #da6e64; }

    .stat-label {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-gray);
        margin-top: 12px;
    }

    /* =====================================================
       TABLE AND TOOLBARS
    ===================================================== */
    .card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 28px;
    }

    .section-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
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
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 14px;
        color: var(--text-dark);
        min-width: 180px;
        background-color: #fff;
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
        border: 1px solid var(--border-color);
        border-radius: 6px 0 0 6px;
        border-right: none;
        height: 38px;
        padding: 8px 14px;
        font-size: 14px;
        width: 220px;
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

    table#bimbinganTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    table#bimbinganTable thead th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        text-align: left;
    }

    table#bimbinganTable tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }

    table#bimbinganTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Soft status badges */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-A { 
        background: #d1fae5; 
        color: #15803d; 
        border: 1px solid rgba(21, 128, 61, 0.15); 
    }
    .status-L { 
        background: #dbeafe; 
        color: #2563eb; 
        border: 1px solid rgba(37, 99, 235, 0.15); 
    }

    /* Pagination design */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid var(--border-color);
    }

    .pagination-info {
        font-size: 13.5px;
        color: var(--primary-color);
        background: var(--primary-light);
        border-left: 4px solid var(--primary-color);
        padding: 6px 12px;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }

    .rows-per-page {
        padding: 7px 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 13.5px;
        color: var(--text-dark);
        background-color: #fff;
    }

    .pagination-buttons {
        display: flex;
        gap: 5px;
    }

    .pagination-buttons button {
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: var(--text-dark);
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
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #ffffff;
    }

    .pagination-buttons button:disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .pagination-buttons button:hover:not(.active):not(:disabled) {
        background: var(--slate-100);
    }

    @media (max-width: 992px) {
        .stat-cards {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stat-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content">

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div class="welcome-banner-content">
            <h1>Selamat Datang, <?= htmlspecialchars($nama_kaprodi) ?>!</h1>
            <p>Sistem Informasi Bimbingan Skripsi Universitas Lampung. Pantau sebaran mahasiswa bimbingan, distribusikan judul skripsi, dan monitor progres bimbingan mahasiswa secara terpusat.</p>
            <div class="welcome-badges">
                <div class="welcome-badge">
                    <i class="fa-solid fa-id-badge"></i>
                    NIP: <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
                <div class="welcome-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    Akses: Kaprodi (Ketua Program Studi)
                </div>
            </div>
        </div>
    </div>

    <!-- ============ CARD STATISTIK ============ -->
    <div class="stat-cards">
        <div class="stat-box stat-blue">
            <div class="stat-content">
                <div class="stat-value"><?= $statistik['mahasiswa_bimbingan'] ?></div>
                <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
            </div>
            <div class="stat-label">Total Mahasiswa</div>
        </div>

        <div class="stat-box stat-green">
            <div class="stat-content">
                <div class="stat-value"><?= $statistik['selesai_bimbingan'] ?></div>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-label">Selesai Bimbingan</div>
        </div>

        <div class="stat-box stat-orange">
            <div class="stat-content">
                <div class="stat-value"><?= $statistik['bimbingan_saat_ini'] ?></div>
                <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
            </div>
            <div class="stat-label">Bimbingan Saat Ini</div>
        </div>

        <div class="stat-box stat-red">
            <div class="stat-content">
                <div class="stat-value"><?= $statistik['topik_penelitian'] ?></div>
                <div class="stat-icon"><i class="fa-solid fa-book-bookmark"></i></div>
            </div>
            <div class="stat-label">Topik Penelitian</div>
        </div>
    </div>

    <!-- ============ TABEL MAHASISWA BIMBINGAN ============ -->
    <div class="card table-card">
        <div class="section-title">
            <i class="fa-solid fa-users" style="color: var(--primary-color); margin-right: 6px;"></i>
            Daftar Mahasiswa Bimbingan Aktif (Program Studi)
        </div>

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

        <table id="bimbinganTable">
            <thead>
                <tr>
                    <th style="width: 150px;">NPM</th>
                    <th>Nama</th>
                    <th style="width: 100px;">Jenjang</th>
                    <th>Program Studi</th>
                    <th style="width: 120px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($mahasiswa_bimbingan as $mhs):
                    switch ($mhs['status']) {
                        case 'A':
                            $statusClass = 'status-A';
                            $statusText = 'Aktif';
                            break;
                        case 'L':
                            $statusClass = 'status-L';
                            $statusText = 'Lulus';
                            break;
                        default:
                            $statusClass = '';
                            $statusText = $mhs['status'];
                    }
                ?>
                <tr data-npm="<?= $mhs['npm'] ?>" data-nama="<?= strtolower($mhs['nama']) ?>" data-angkatan="<?= $mhs['angkatan'] ?>">
                    <td><?= htmlspecialchars($mhs['npm']) ?></td>
                    <td><strong><?= htmlspecialchars($mhs['nama']) ?></strong></td>
                    <td><?= htmlspecialchars($mhs['jenjang']) ?></td>
                    <td><?= htmlspecialchars($mhs['prodi']) ?></td>
                    <td style="text-align: center;"><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
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
