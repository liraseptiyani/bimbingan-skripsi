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

$title = "Riwayat Bimbingan Skripsi";

include __DIR__.'/../layouts/header.php';
include __DIR__.'/../layouts/sidebar_mahasiswa.php';
include __DIR__.'/../layouts/topbar.php';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

// Fetch student name
$stmtMhs = $pdo->prepare("SELECT nama FROM mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtMhs->execute([':npm' => $npmMhs]);
$namaMhs = $stmtMhs->fetchColumn() ?: ($isMahasiswaAccount ? ($_SESSION['nama'] ?? 'LIRA SEPTIYANI') : 'LIRA SEPTIYANI');

// Fetch distribution details
$stmtDist = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
$stmtDist->execute([':npm' => $npmMhs]);
$distribusi = $stmtDist->fetch(PDO::FETCH_ASSOC);

if (!$distribusi || empty($distribusi['pembimbing1'])) {
    $_SESSION['swal_error'] = 'Fitur bimbingan belum aktif. Silakan tunggu plotting Pembimbing dan Judul Skripsi oleh Kaprodi.';
    header("Location: /bimbingan-skripsi/app/views/mahasiswa/dashboard.php");
    exit;
}

$pembimbingUtama = $distribusi['pembimbing1'] ?? 'Belum ditentukan';
$pembimbingPembantu = $distribusi['pembimbing2'] ?? 'Belum ditentukan';
$pembahas = $distribusi['pembahas1'] ?? 'Belum ditentukan';
$judulSkripsi = $distribusi['judul_skripsi'] ?? 'Belum ditentukan';

// Fetch bimbingan list from database
$stmtBimb = $pdo->prepare("
    SELECT b.*, dm.pembimbing1, dm.pembimbing2
    FROM bimbingan b
    LEFT JOIN distribusi_mahasiswa dm ON REPLACE(b.npm, ' ', '') = REPLACE(dm.npm, ' ', '')
    WHERE REPLACE(b.npm, ' ', '') = REPLACE(:npm, ' ', '') 
    ORDER BY b.id DESC
");
$stmtBimb->execute([':npm' => $npmMhs]);
$data = $stmtBimb->fetchAll(PDO::FETCH_ASSOC);
?>

<style>

.content{
    margin-left:270px;
    margin-top:70px;
    padding:25px;
    background:#fff;
    min-height:100vh;
}

.page-title{
    font-size:20px;
    font-weight:500;
    margin-bottom:25px;
}

/* CARD INFO */

.info-card{
    background:#eef4fb;
    padding:28px 35px;
    margin-bottom:35px;
    border-left:4px solid #d9e3f0;
}

.info-row{
    display:flex;
    margin-bottom:20px;
}

.info-label{
    width:200px;
    color:#7d8bc2;
    font-size:15px;
    font-weight:500;
}

.info-value{
    flex:1;
    font-size:15px;
    color:#333;
    line-height:1.8;
}

/* TABLE CARD */

.table-card{
    background:#fff;
    border-top:4px solid #69a86e;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
    padding:18px;
}

/* HEADER TABLE */

.table-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.search-box{
    display:flex;
}

.search-box input{
    width:260px;
    height:36px;
    border:1px solid #ccc;
    padding:10px;
    font-size:14px;
}

.search-box button{
    width:40px;
    border:none;
    background:#69a86e;
    color:#fff;
    cursor:pointer;
}

.btn-add{
    background:#69a86e;
    color:white;
    border:none;
    padding:10px 18px;
    font-size:14px;
    border-radius:3px;
    cursor:pointer;
}

.btn-add i{
    margin-right:5px;
}

/* TABLE */

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#3f4d63;
    color:#fff;
}

th{
    padding:14px;
    font-size:14px;
    font-weight:600;
}

td{
    padding:18px;
    border:1px solid #ddd;
    font-size:14px;
    text-align:center;
}

.btn-aksi {
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
    margin: 0 2px;
}
.btn-lihat { background: #7db8db; }
.btn-edit { background: #f2a13e; }
.btn-hapus { background: #e05252; }
.btn-aksi:hover { opacity: .9; }

.draft-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    justify-content: center;
}
.draft-link i {
    color: #d9534f;
    font-size: 16px;
    flex-shrink: 0;
}
.draft-link span {
    font-size: 13.5px;
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

.empty{
    color:#555;
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
.table-header .search-box {
    display: flex;
    justify-content: flex-end;
    gap: 0 !important;
}

.table-header .search-box input {
    width: 260px;
    padding: 8px 12px;
    border: 1px solid #cccccc;
    border-right: none !important;
    border-radius: 4px 0 0 4px !important;
    height: 38px;
    box-sizing: border-box;
}

.table-header .search-box button {
    background: #4AA361 !important;
    color: #ffffff;
    border: none;
    padding: 0 15px;
    cursor: pointer;
    border-radius: 0 4px 4px 0 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    box-sizing: border-box;
}

.table-header .search-box button:hover {
    background: #3d8b51 !important;
}

table thead th {
    background: #34495e !important;
    color: #ffffff;
    font-weight: 600;
    padding: 12px;
    font-size: 14px;
}

/* MODAL STYLES */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.show {
    display: flex;
}
.modal-box {
    background: #fff;
    width: 500px;
    max-width: 90%;
    border-radius: 6px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    overflow: hidden;
}
.modal-header {
    background: #285aa9;
    color: #fff;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}
.modal-header .btn-close {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
}
.modal-body {
    padding: 20px;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}
.form-group input[type="file"],
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}
.modal-footer {
    padding: 14px 20px;
    background: #f7fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-cancel {
    background: #e2e8f0;
    color: #333;
    border: none;
    padding: 9px 16px;
    border-radius: 4px;
    cursor: pointer;
}
.btn-submit {
    background: #69a86e;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 4px;
    cursor: pointer;
}

</style>


<div class="content">

    <div class="page-title">
        Riwayat Bimbingan Skripsi
    </div>

    <!-- INFO SKRIPSI -->

    <div class="info-card">

        <div class="info-row">
            <div class="info-label">
                Pembimbing Utama
            </div>

            <div class="info-value">
                <?= htmlspecialchars($pembimbingUtama) ?>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Pembimbing Pembantu
            </div>

            <div class="info-value">
                <?= htmlspecialchars($pembimbingPembantu) ?>
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Pembahas
            </div>

            <div class="info-value">
                <?= htmlspecialchars($pembahas) ?>
            </div>
        </div>

        <div class="info-row" style="margin-bottom:0">

            <div class="info-label">
                Judul Skripsi
            </div>

            <div class="info-value">
                <?= htmlspecialchars($judulSkripsi) ?>
            </div>

        </div>

    </div>


    <!-- TABEL RIWAYAT -->

    <div class="table-card">

        <div class="table-header">

            <div class="search-box">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Cari Riwayat Bimbingan">

                <button type="button" id="searchBtn">
                    <i class="fa fa-search"></i>
                </button>
            </div>

            <button class="btn-add" id="btnTambahBimbingan">
                <i class="fa fa-plus"></i>
                Tambah
            </button>

        </div>


        <table>

            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>NPM</th>
                    <th>Nama</th>
                    <th>Draft</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody id="tableBody">

                <?php if(empty($data)): ?>

                <tr class="no-data-row">
                    <td colspan="6" class="empty">
                        Data kosong
                    </td>
                </tr>

                <?php else: ?>
                    <?php foreach($data as $row): 
                        // Fetch the initial forum message (first message) for this bimbingan to pre-populate the edit modal notes
                        $stmtFMsg = $pdo->prepare("SELECT isi FROM forum_bimbingan WHERE bimbingan_id = :b_id ORDER BY id ASC LIMIT 1");
                        $stmtFMsg->execute([':b_id' => $row['id']]);
                        $initialPesan = $stmtFMsg->fetchColumn() ?: '';
                    ?>
                    <tr data-npm="<?= htmlspecialchars(strtolower($row['npm'])) ?>" 
                        data-nama="<?= htmlspecialchars(strtolower($row['nama'])) ?>">
                        <td><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['npm']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td>
                            <a href="/bimbingan-skripsi/public/uploads/draft/<?= htmlspecialchars($row['file_draft']) ?>" class="draft-link" target="_blank" title="<?= htmlspecialchars($row['file_draft']) ?>">
                                <i class="fa-solid fa-file-pdf"></i>
                                <span><?= htmlspecialchars($row['file_draft']) ?></span>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; font-weight: 500; text-align: left; width: fit-content; margin: 0 auto;">
                                <?php if (!empty($row['pembimbing1'])): ?>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="color: #475569;">P1:</span>
                                        <?php if (isset($row['status_pembimbing1']) && $row['status_pembimbing1'] === 'sudah_dibalas'): ?>
                                            <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 1 Sudah Membalas"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 1 Belum Membalas"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($row['pembimbing2'])): ?>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="color: #475569;">P2:</span>
                                        <?php if (isset($row['status_pembimbing2']) && $row['status_pembimbing2'] === 'sudah_dibalas'): ?>
                                            <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 16px;" title="Pembimbing 2 Sudah Membalas"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-circle-exclamation" style="color: #eab308; font-size: 16px;" title="Pembimbing 2 Belum Membalas"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <a href="/bimbingan-skripsi/app/views/mahasiswa/detail_bimbingan.php?id=<?= $row['id'] ?>" class="btn-aksi btn-lihat" title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <?php if (($row['status_pembimbing1'] ?? '') !== 'sudah_dibalas' && ($row['status_pembimbing2'] ?? '') !== 'sudah_dibalas'): ?>
                                <button type="button" class="btn-aksi btn-edit" title="Edit" 
                                        onclick="bukaModalEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['file_draft']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($initialPesan), ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="/bimbingan-skripsi/app/controllers/HapusBimbinganController.php?id=<?= $row['id'] ?>" 
                                   class="btn-aksi btn-hapus" title="Hapus" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus bimbingan ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

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

<!-- MODAL TAMBAH BIMBINGAN -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-cloud-arrow-up"></i> Upload Draft Bimbingan</h3>
            <button type="button" class="btn-close" id="btnCloseModal">&times;</button>
        </div>
        <form action="/bimbingan-skripsi/app/controllers/TambahBimbinganController.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="draft">File Draft (PDF)</label>
                    <input type="file" name="draft" id="draft" accept=".pdf" required>
                </div>
                <div class="form-group">
                    <label for="pesan">Catatan / Pesan Awal</label>
                    <textarea name="pesan" id="pesan" rows="4" placeholder="Tuliskan catatan atau pengantar untuk dosen pembimbing..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="btnCancelModal">Batal</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Unggah Draft</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT BIMBINGAN -->
<div class="modal-overlay" id="modalEditOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Draft Bimbingan</h3>
            <button type="button" class="btn-close" id="btnCloseEditModal">&times;</button>
        </div>
        <form action="/bimbingan-skripsi/app/controllers/EditBimbinganController.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editBimbinganId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="editDraft">File Draft (PDF)</label>
                    <input type="file" name="draft" id="editDraft" accept=".pdf">
                    <small id="editDraftLabel" style="color: #666; display: block; margin-top: 4px;"></small>
                </div>
                <div class="form-group">
                    <label for="editPesan">Catatan / Pesan Awal</label>
                    <textarea name="pesan" id="editPesan" rows="4" placeholder="Tuliskan catatan atau pengantar untuk dosen pembimbing..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="btnCancelEditModal">Batal</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const modalOverlay = document.getElementById('modalOverlay');
    const btnTambah = document.getElementById('btnTambahBimbingan');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancelModal');

    if (btnTambah) {
        btnTambah.addEventListener('click', function() {
            modalOverlay.classList.add('show');
        });
    }

    function closeModal() {
        modalOverlay.classList.remove('show');
    }

    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    // Edit Modal helpers
    const modalEditOverlay = document.getElementById('modalEditOverlay');
    const editBimbinganId  = document.getElementById('editBimbinganId');
    const editPesan        = document.getElementById('editPesan');
    const editDraftLabel   = document.getElementById('editDraftLabel');

    function bukaModalEdit(id, fileDraft, pesan) {
        editBimbinganId.value = id;
        editPesan.value = pesan;
        editDraftLabel.textContent = 'File saat ini: ' + fileDraft;
        modalEditOverlay.classList.add('show');
    }

    function tutupModalEdit() {
        modalEditOverlay.classList.remove('show');
    }

    document.getElementById('btnCloseEditModal').addEventListener('click', tutupModalEdit);
    document.getElementById('btnCancelEditModal').addEventListener('click', tutupModalEdit);

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
            const npm = row.dataset.npm || '';
            const nama = row.dataset.nama || '';
            return npm.includes(keyword) || nama.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="6" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada riwayat bimbingan yang cocok.</td>`;
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

    if (searchInput) {
        searchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
        searchBtn.addEventListener('click', () => { currentPage = 1; renderTable(); });
        rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });
        renderTable();
    }
</script>

<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>',
        timer: 2500,
        showConfirmButton: true,
        confirmButtonColor: '#285aa9'
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

</body>
</html>