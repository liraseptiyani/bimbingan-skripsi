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

$title = 'Dosen - Topik Penelitian';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$nip_dosen = $_SESSION['username'];

$stmt = $pdo->prepare("SELECT * FROM topik_penelitian WHERE REPLACE(nip_dosen, ' ', '') = REPLACE(:nip_dosen, ' ', '') ORDER BY created_at DESC");
$stmt->execute([':nip_dosen' => $nip_dosen]);
$topik_penelitian_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topik_penelitian = [];
foreach ($topik_penelitian_raw as $t) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
    $stmtCount->execute([':topik_id' => $t['id']]);
    $terisi = $stmtCount->fetchColumn();

    // Fetch all applicants for this topic
    $stmtMhs = $pdo->prepare("
        SELECT mt.id AS minat_id, mt.status, mt.alasan, m.nama, m.npm 
        FROM minat_topik mt
        JOIN mahasiswa m ON mt.mahasiswa_npm = m.npm
        WHERE mt.topik_id = :topik_id
        ORDER BY mt.created_at ASC
    ");
    $stmtMhs->execute([':topik_id' => $t['id']]);
    $applicants = $stmtMhs->fetchAll(PDO::FETCH_ASSOC);

    $topik_penelitian[] = [
        'id'         => $t['id'],
        'topik'      => $t['topik'],
        'deskripsi'  => $t['deskripsi'],
        'tenggat_tanggal' => $t['tenggat_tanggal'] ?: '',
        'kuota_terisi' => (int)$terisi,
        'kuota_max'    => (int)$t['kuota_max'],
        'applicants'   => $applicants
    ];
}


require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_dosen.php';
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
        width: 260px;
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

    .btn-tambah {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #4AA361;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        height: 38px;
        box-sizing: border-box;
    }

    .btn-tambah:hover {
        background: #3d8b51;
        color: #ffffff;
    }

    table td.col-no {
        width: 50px;
        text-align: center;
    }

    table td.col-kuota {
        width: 80px;
        text-align: center;
    }

    table th.col-aksi,
    table td.col-aksi {
        width: 220px;
        text-align: center;
    }

    .aksi-group {
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .aksi-group a,
    .aksi-group button {
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

    .btn-lihat  { background: #7db8db; }
    .btn-edit   { background: #e0a83a; }
    .btn-hapus  { background: #da6e64; }

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
        transition: all 0.2s;
    }
    .aksi-group a.btn-seleksi-peminat:hover {
        background: #dbeafe;
        border-color: rgba(40, 90, 169, 0.35);
        color: #1e3b8a;
        transform: translateY(-1px);
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-block;
        text-align: center;
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

    /* --- Form Detail (Grid-Style) --- */
    .form-group-detail {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
    }
    .form-group-detail label {
        width: 140px;
        flex-shrink: 0;
        font-size: 14px;
        color: #285aa9;
        font-weight: 600;
        padding-top: 8px;
    }
    .form-group-detail .detail-value {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        background: #ffffff;
        color: #333;
        font-family: 'Segoe UI', sans-serif;
        line-height: 1.5;
        resize: none;
    }
    .form-group-detail input.detail-value {
        height: 38px;
        box-sizing: border-box;
    }

    .btn-lihat:hover,
    .btn-edit:hover,
    .btn-hapus:hover {
        opacity: .9;
    }

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

    table#topikTable thead th {
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
        .table-toolbar .search input {
            width: 180px;
        }
    }

    /* ================= MODAL (dasar, dipakai bersama) ================= */

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, .55);
        z-index: 20000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-box {
        background: #ffffff;
        width: 100%;
        max-width: 560px;
        border-radius: 6px;
        border-top: 4px solid #69a86e;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
        padding: 30px 35px;
    }

    .modal-title {
        text-align: center;
        font-size: 20px;
        font-weight: 600;
        color: #222;
        margin-bottom: 26px;
    }

    /* --- Form Tambah/Edit --- */

    .form-group {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 18px;
    }

    .form-group label {
        width: 110px;
        flex-shrink: 0;
        padding-top: 10px;
        font-size: 14.5px;
        color: #285aa9;
        font-weight: 600;
    }

    .form-group label .required {
        color: #da6e64;
    }

    .form-group input,
    .form-group textarea {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Segoe UI', sans-serif;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #285aa9;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    .btn-kembali,
    .btn-simpan,
    .btn-batal,
    .btn-hapus-confirm {
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
        padding: 10px 18px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        color: #ffffff;
    }

    .btn-kembali { background: #285aa9; }
    .btn-simpan  { background: #69a86e; }

    .btn-kembali:hover,
    .btn-simpan:hover {
        opacity: .9;
    }

    /* --- Modal Konfirmasi Hapus --- */

    .modal-box.modal-hapus {
        max-width: 420px;
        border-top-color: #da6e64;
        text-align: center;
    }

    .modal-hapus .icon-warning {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #fdecea;
        color: #da6e64;
        font-size: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
    }

    .modal-hapus .modal-title {
        margin-bottom: 10px;
    }

    .modal-hapus p {
        color: #555;
        font-size: 14px;
        margin-bottom: 26px;
    }

    .modal-hapus p strong {
        color: #222;
    }

    .modal-hapus .modal-footer {
        justify-content: center;
    }

    .btn-batal {
        background: #9aa5b1;
    }

    .btn-hapus-confirm {
        background: #da6e64;
    }

    .btn-batal:hover,
    .btn-hapus-confirm:hover {
        opacity: .9;
    }
</style>

<div class="content">

    <h1 class="page-title">Daftar Topik Penelitian</h1>

    <div class="card">

        <div class="table-toolbar">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari Topik Skripsi">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>

            <button type="button" class="btn-tambah" onclick="bukaModalTambah()">
                <i class="fa-solid fa-plus"></i> Tambah
            </button>
        </div>

        <table id="topikTable">
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>Topik</th>
                    <th>Deskripsi</th>
                    <th style="width:110px;">Tenggat</th>
                    <th style="width:80px;">Kuota</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($topik_penelitian as $i => $t): ?>
                <tr data-topik="<?= strtolower($t['topik']) ?>">
                    <td class="col-no"><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($t['topik']) ?></td>
                    <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                    <td><?= !empty($t['tenggat_tanggal']) ? htmlspecialchars($t['tenggat_tanggal']) : '-' ?></td>
                    <td class="col-kuota"><?= $t['kuota_terisi'] ?>/<?= $t['kuota_max'] ?></td>
                    <td class="col-aksi">
                        <div class="aksi-group">
                            <a href="/bimbingan-skripsi/app/views/dosen/seleksi_mahasiswa.php?id=<?= $t['id'] ?>" class="btn-seleksi-peminat" title="Seleksi Peminat">
                                <i class="fa-solid fa-user-check"></i>
                                Seleksi (<?= count($t['applicants']) ?>)
                            </a>

                            <?php if (empty($t['applicants'])): ?>
                            <button type="button" class="btn-edit" title="Edit"
                                onclick="bukaModalEdit(<?= $t['id'] ?>, <?= htmlspecialchars(json_encode($t['topik'])) ?>, <?= htmlspecialchars(json_encode($t['deskripsi'])) ?>, <?= (int)$t['kuota_max'] ?>, '<?= $t['tenggat_tanggal'] ?>')">
                                <i class="fa-solid fa-pen"></i>
                            </button>

                            <button type="button" class="btn-hapus" title="Hapus"
                                onclick="bukaModalHapus(<?= $t['id'] ?>, <?= htmlspecialchars(json_encode($t['topik'])) ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <?php endif; ?>
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

    <!-- ============ MODAL TAMBAH / EDIT TOPIK PENELITIAN (1 modal, 2 mode) ============ -->
    <div class="modal-overlay" id="modalTambah">
        <div class="modal-box">

            <div class="modal-title" id="modalTitle">Tambah Topik Penelitian</div>

            <form id="formTambahTopik" method="POST" action="/bimbingan-skripsi/app/controllers/SimpanTopikController.php">

                <input type="hidden" name="id" id="inputId" value="">

                <div class="form-group">
                    <label>Topik<span class="required">*</span></label>
                    <input type="text" name="topik" id="inputTopik" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi<span class="required">*</span></label>
                    <textarea name="deskripsi" id="inputDeskripsi" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label>Kuota<span class="required">*</span></label>
                    <input type="number" name="kuota" id="inputKuota" min="1" required>
                </div>

                <div class="form-group">
                    <label>Tenggat<span class="required">*</span></label>
                    <input type="date" name="tenggat_tanggal" id="inputTenggat" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-kembali" onclick="tutupModalTambah()">
                        <i class="fa-solid fa-chevron-left"></i> Kembali ke daftar
                    </button>
                    <button type="submit" class="btn-simpan">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- ============ MODAL KONFIRMASI HAPUS TOPIK ============ -->
    <div class="modal-overlay" id="modalHapus">
        <div class="modal-box modal-hapus">

            <div class="icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>

            <div class="modal-title">Hapus Topik Penelitian?</div>
            <p>Apakah Anda yakin ingin menghapus topik <strong id="namaTopikHapus"></strong>? Tindakan ini tidak dapat dibatalkan.</p>

            <form id="formHapusTopik" method="POST" action="/bimbingan-skripsi/app/controllers/HapusTopikController.php">
                <input type="hidden" name="id" id="inputIdHapus" value="">

                <div class="modal-footer">
                    <button type="button" class="btn-batal" onclick="tutupModalHapus()">
                        <i class="fa-solid fa-xmark"></i> Batal
                    </button>
                    <button type="submit" class="btn-hapus-confirm">
                        <i class="fa-solid fa-trash"></i> Ya, Hapus
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const topicsData = <?= json_encode($topik_penelitian) ?>;

    // ==========================================================
    // Search + Pagination (client-side, karena masih dummy)
    // Nanti kalau sudah pakai PDO, ini diganti server-side query
    // ==========================================================
    const searchInput      = document.getElementById('searchInput');
    const searchBtn        = document.getElementById('searchBtn');
    const rowsPerPageSel   = document.getElementById('rowsPerPage');
    const tableBody        = document.getElementById('tableBody');
    const paginationInfo    = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        return allRows.filter(row => row.dataset.topik.includes(keyword));
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
            emptyRow.innerHTML = `<td colspan="6">Tidak ada topik yang cocok.</td>`;
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

    // ==========================================================
    // Modal Tambah / Edit Topik Penelitian (1 modal, 2 mode)
    // ==========================================================
    const modalTambah     = document.getElementById('modalTambah');
    const formTambahTopik = document.getElementById('formTambahTopik');
    const modalTitle      = document.getElementById('modalTitle');
    const inputId         = document.getElementById('inputId');
    const inputTopik      = document.getElementById('inputTopik');
    const inputDeskripsi  = document.getElementById('inputDeskripsi');
    const inputKuota      = document.getElementById('inputKuota');
    const inputTenggat    = document.getElementById('inputTenggat');

    // Mode TAMBAH: form kosong
    function bukaModalTambah() {
        modalTitle.textContent = 'Tambah Topik Penelitian';
        formTambahTopik.reset();
        inputId.value = '';
        if (inputTenggat) inputTenggat.value = '';
        modalTambah.classList.add('show');
    }

    // Mode EDIT: form terisi data topik yang dipilih
    function bukaModalEdit(id, topik, deskripsi, kuota, tenggat) {
        modalTitle.textContent = 'Edit Topik Penelitian';
        inputId.value = id;
        inputTopik.value = topik;
        inputDeskripsi.value = deskripsi;
        inputKuota.value = kuota;
        if (inputTenggat) inputTenggat.value = tenggat || '';
        modalTambah.classList.add('show');
    }

    function tutupModalTambah() {
        modalTambah.classList.remove('show');
        formTambahTopik.reset();
        inputId.value = '';
        if (inputTenggat) inputTenggat.value = '';
    }

    modalTambah.addEventListener('click', function (e) {
        if (e.target === modalTambah) tutupModalTambah();
    });

    // ==========================================================
    // Modal Konfirmasi Hapus Topik
    // ==========================================================
    const modalHapus     = document.getElementById('modalHapus');
    const formHapusTopik = document.getElementById('formHapusTopik');
    const inputIdHapus   = document.getElementById('inputIdHapus');
    const namaTopikHapus = document.getElementById('namaTopikHapus');

    function bukaModalHapus(id, namaTopik) {
        inputIdHapus.value = id;
        namaTopikHapus.textContent = `"${namaTopik}"`;
        modalHapus.classList.add('show');
    }

    function tutupModalHapus() {
        modalHapus.classList.remove('show');
        inputIdHapus.value = '';
    }

    modalHapus.addEventListener('click', function (e) {
        if (e.target === modalHapus) tutupModalHapus();
    });

    // Tutup modal manapun yang sedang terbuka dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (modalTambah.classList.contains('show')) tutupModalTambah();
        if (modalHapus.classList.contains('show')) tutupModalHapus();
    });

    renderTable();
</script>

</body>
</html>
