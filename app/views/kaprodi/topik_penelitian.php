<?php
session_start();

// Protection: Kaprodi only
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if (($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: /bimbingan-skripsi/app/views/dosen/dashboard.php");
    exit;
}

$title = 'Topik Penelitian';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch all topics with their applicants count
$topik_penelitian = [];
try {
    $stmt = $pdo->query("
        SELECT tp.*, d.nama AS nama_dosen 
        FROM topik_penelitian tp 
        JOIN dosen d ON REPLACE(tp.nip_dosen, ' ', '') = REPLACE(d.nip, ' ', '')
        ORDER BY tp.created_at DESC
    ");
    $topik_penelitian_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($topik_penelitian_raw as $t) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
        $stmtCount->execute([':topik_id' => $t['id']]);
        $terisi = $stmtCount->fetchColumn();

        // Fetch all applicants for this topic to count total interested students
        $stmtApplicantsCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id");
        $stmtApplicantsCount->execute([':topik_id' => $t['id']]);
        $jumlah_peminat = (int)$stmtApplicantsCount->fetchColumn();

        $topik_penelitian[] = [
            'id'             => $t['id'],
            'topik'          => $t['topik'],
            'deskripsi'      => $t['deskripsi'],
            'tenggat_tanggal' => $t['tenggat_tanggal'] ?: '',
            'dosen'          => $t['nama_dosen'],
            'kuota_terisi'   => (int)$terisi,
            'kuota_max'      => (int)$t['kuota_max'],
            'jumlah_peminat' => $jumlah_peminat
        ];
    }
} catch (PDOException $e) {
    // Fail silently
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* ================= TOPIK PENELITIAN (khusus halaman ini) ================= */

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

    .card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        border-top: 4px solid #69a86e;
    }

    table#topikTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    table#topikTable thead th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        text-align: left;
    }

    table#topikTable tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14.5px;
        color: #334155;
        vertical-align: middle;
    }

    table#topikTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Col-aksi styling and override width constraint */
    .aksi-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .aksi-group a.btn-seleksi-peminat {
        width: auto;
        height: 32px;
        background: #eef4fb;
        color: #285aa9;
        border: 1px solid rgba(40, 90, 169, 0.2);
        padding: 0 14px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .aksi-group a.btn-seleksi-peminat:hover {
        background: #dbeafe;
        border-color: rgba(40, 90, 169, 0.35);
        color: #1e3b8a;
        transform: translateY(-1px);
    }

    /* Pagination design matches Dosen page */
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

    .welcome-text {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 22px;
        margin-top: 4px;
    }
</style>

<div class="content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 class="page-title" style="margin: 0;">Topik Penelitian</h1>
        </div>
    </div>

    <div class="card">
        <div class="table-toolbar">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari topik, dosen, deskripsi...">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <table id="topikTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 250px;">Dosen Pengusul</th>
                    <th style="width: 220px;">Topik / Judul</th>
                    <th>Deskripsi</th>
                    <th style="width: 100px;">Tenggat</th>
                    <th style="width: 80px; text-align: center;">Kuota</th>
                    <th style="width: 150px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($topik_penelitian as $i => $t): ?>
                <tr data-dosen="<?= strtolower($t['dosen']) ?>" data-topik="<?= strtolower($t['topik']) ?>" data-deskripsi="<?= strtolower($t['deskripsi']) ?>">
                    <td style="text-align: center; white-space: nowrap;"><?= $i + 1 ?></td>
                    <td style="white-space: nowrap;"><?= htmlspecialchars($t['dosen']) ?></td>
                    <td><?= htmlspecialchars($t['topik']) ?></td>
                    <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                    <td style="white-space: nowrap;"><?= !empty($t['tenggat_tanggal']) ? htmlspecialchars($t['tenggat_tanggal']) : '-' ?></td>
                    <td style="text-align: center; white-space: nowrap;"><?= $t['kuota_terisi'] ?>/<?= $t['kuota_max'] ?></td>
                    <td style="white-space: nowrap;">
                        <div class="aksi-group">
                            <a href="/bimbingan-skripsi/app/views/kaprodi/detail_topik.php?id=<?= $t['id'] ?>" class="btn-seleksi-peminat" title="Detail Peminat">
                                <i class="fa-solid fa-eye"></i>
                                Peminat (<?= $t['jumlah_peminat'] ?>)
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
        return allRows.filter(row => {
            const dosen = row.dataset.dosen;
            const topik = row.dataset.topik;
            const deskripsi = row.dataset.deskripsi;
            return dosen.includes(keyword) || topik.includes(keyword) || deskripsi.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="7" style="text-align:center;color:#94a3b8;padding:22px;">Tidak ada topik yang cocok.</td>`;
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
