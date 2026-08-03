<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: /bimbingan-skripsi/");
    exit;
}
$title = 'Kuota Pembimbing';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

require_once dirname(__DIR__, 3) . '/app/controllers/DosenController.php';

// Fetch all dosen
$stmt = $pdo->query("SELECT * FROM dosen ORDER BY nama ASC");
$daftarDosenRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$daftarDosen = [];
$no = 1;
foreach ($daftarDosenRaw as $d) {
    // Calculate terisi dynamically from distribusi_mahasiswa (only active guidance: status_bimbingan != 'selesai')
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM distribusi_mahasiswa WHERE (pembimbing1 = :nama OR pembimbing2 = :nama) AND (status_bimbingan IS NULL OR status_bimbingan != 'selesai')");
    $stmtCount->execute([':nama' => $d['nama']]);
    $terisi = $stmtCount->fetchColumn();

    $daftarDosen[] = [
        'no' => $no++,
        'nama' => $d['nama'],
        'nip' => $d['nip'],
        'bidang' => $d['bidang_ilmu'] ?? '',
        'terisi' => (int)$terisi,
        'maks' => (int)($d['kuota_max'] ?? 10)
    ];
}

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<style>
    /* Halaman ini pakai class global .content dan .card dari header.php.
       CSS di bawah cuma tambahan khusus untuk elemen yang belum ada di global. */

    .kuota-toolbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:18px;
    }

    .kuota-search{
        display:flex;
        gap: 0;
        max-width:340px;
        width:100%;
    }

    .kuota-search input{
        flex:1;
        padding:10px 12px;
        border:1px solid #cccccc;
        border-right:none;
        border-radius:4px 0 0 4px;
        font-size:14px;
        outline:none;
    }

    .kuota-search button{
        border:none;
        background:#4AA361;
        color:#ffffff;
        padding:0 16px;
        border-radius:0 4px 4px 0;
        cursor:pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .kuota-search button:hover{ background:#3d8b51; }

    .btn-tambah{
        background:#4AA361;
        color:#ffffff;
        border:none;
        padding:10px 20px;
        border-radius:4px;
        font-size:14px;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:8px;
        white-space:nowrap;
    }

    .btn-tambah:hover{ background:#3d8b51; color:#ffffff; }

    /* Override sebagian style table global khusus utk tabel ini */
    table.tabel-dosen td,
    table.tabel-dosen th{
        border:none;
        border-bottom:1px solid #eef0f3;
    }

    table.tabel-dosen thead th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
    }

    table.tabel-dosen th.text-center,
    table.tabel-dosen td.text-center{ text-align:center; }

    table.tabel-dosen tbody tr:nth-child(even){ background:#f5f7fa; }

    .kuota-text{ font-weight:600; color:#333; }

    .aksi-group{ display:flex; gap:6px; justify-content:center; }

    .btn-aksi{
        width:28px;
        height:28px;
        border:none;
        border-radius:4px;
        color:#ffffff;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        text-decoration:none;
        font-size:12px;
    }

    .btn-edit{ background:#f2a13e; }
    .btn-edit:hover{ background:#d68b2c; }

    .btn-hapus{ background:#e05252; }
    .btn-hapus:hover{ background:#c73f3f; }

    .kuota-footer{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:16px;
        font-size:13px;
        color:#555;
    }

    .hal-info{
        font-size: 13.5px;
        color: #1e3a8a;
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 6px 12px;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }

    .baris-select{
        padding:6px 12px;
        border:1px solid #cccccc;
        border-radius:4px;
        font-size:13px;
    }

    .pagination{ display:flex; gap:6px; }

    .pagination button{
        width:30px;
        height:30px;
        border:1px solid #cccccc;
        background:#ffffff;
        border-radius:4px;
        cursor:pointer;
        font-size:13px;
        color:#555;
    }

    .pagination button:disabled{
        background:#eeeeee;
        color:#aaaaaa;
        cursor:not-allowed;
    }

    .pagination button.active{
        background:#285aa9;
        color:#ffffff;
        border-color:#285aa9;
    }
</style>

<div class="content">
    <div class="page-title">Daftar Dosen</div>

    <div class="card">
        <div class="kuota-toolbar">
            <div class="kuota-search">
                <input type="text" id="searchInput" placeholder="Cari Dosen">
                <button type="button" id="searchBtn"><i class="fas fa-search"></i></button>
            </div>
            <a href="kuota_pembimbing_add.php" class="btn-tambah"><i class="fas fa-plus"></i> Tambah</a>
        </div>

        <table class="tabel-dosen">
            <thead>
                <tr>
                    <th class="text-center" style="width:50px;">No</th>
                    <th>Nama Dosen</th>
                    <th>NIP</th>
                    <th>Bidang Ilmu</th>
                    <th class="text-center" style="width:80px;">Kuota</th>
                    <th class="text-center" style="width:90px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($daftarDosen as $d): ?>
                <tr data-nama="<?= htmlspecialchars(strtolower($d['nama'])) ?>" 
                    data-nip="<?= htmlspecialchars(strtolower($d['nip'])) ?>" 
                    data-bidang="<?= htmlspecialchars(strtolower($d['bidang'])) ?>">
                    <td class="text-center"><?= $d['no'] ?></td>
                    <td><?= htmlspecialchars($d['nama']) ?></td>
                    <td><?= htmlspecialchars($d['nip']) ?></td>
                    <td><?= htmlspecialchars($d['bidang']) ?></td>
                    <td class="text-center" style="vertical-align: middle;">
                        <?php if ($d['terisi'] > $d['maks']): ?>
                            <span class="badge-status ditolak" style="font-family: monospace; font-size: 12.5px; font-weight: 700; padding: 4px 10px; border-radius: 30px; display: inline-flex; align-items: center; gap: 4px;" title="Melebihi Kuota Maksimum!">
                                <i class="fa-solid fa-triangle-exclamation"></i> <?= $d['terisi'] . '/' . $d['maks'] ?>
                            </span>
                        <?php elseif ($d['terisi'] == $d['maks']): ?>
                            <span class="badge-status" style="font-family: monospace; font-size: 12.5px; font-weight: 700; padding: 4px 10px; border-radius: 30px; display: inline-flex; align-items: center; gap: 4px; background: #fffbeb; color: #b45309; border: 1px solid rgba(180, 83, 9, 0.25);" title="Kuota Penuh">
                                <i class="fa-solid fa-circle-exclamation"></i> <?= $d['terisi'] . '/' . $d['maks'] ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-status disetujui" style="font-family: monospace; font-size: 12.5px; font-weight: 700; padding: 4px 10px; border-radius: 30px; display: inline-flex; align-items: center; gap: 4px;" title="Kuota Aman">
                                <i class="fa-solid fa-circle-check"></i> <?= $d['terisi'] . '/' . $d['maks'] ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="aksi-group">
                            <a href="kuota_pembimbing_edit.php?nip=<?= urlencode($d['nip']) ?>" class="btn-aksi btn-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="../../controllers/DosenController.php?action=hapus_dosen&nip=<?= urlencode($d['nip']) ?>" class="btn-aksi btn-hapus" title="Hapus" onclick="return confirm('Hapus dosen ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="kuota-footer">
            <div class="hal-info" id="paginationInfo">Hal 1/1 (0 data)</div>
            <select class="baris-select" id="rowsPerPage">
                <option value="10">10 baris</option>
                <option value="25">25 baris</option>
                <option value="50">50 baris</option>
            </select>
            <div class="pagination" id="paginationButtons">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            const nip = row.dataset.nip || '';
            const bidang = row.dataset.bidang || '';
            return nama.includes(keyword) || nip.includes(keyword) || bidang.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="6" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada data dosen yang cocok.</td>`;
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

<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>',
        timer: 2000,
        showConfirmButton: false
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

<?php if (isset($_SESSION['swal_error'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '<?= htmlspecialchars($_SESSION['swal_error']) ?>'
    });
</script>
<?php unset($_SESSION['swal_error']); endif; ?>

<?php include '../layouts/footer.php'; ?>
