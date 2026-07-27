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

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$npm_mahasiswa = str_replace(' ', '', $_SESSION['username'] ?? '');

// Tangani proses simpan / update alasan tertarik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topik_id'])) {
    $topik_id = (int)$_POST['topik_id'];
    $alasan = trim($_POST['alasan'] ?? '');

    if (empty($alasan)) {
        $_SESSION['swal_error'] = 'Harap isi alasan ketertarikan Anda!';
        header('Location: /bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php');
        exit;
    }

    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND mahasiswa_npm = :npm_mahasiswa");
        $stmtCheck->execute([
            ':topik_id' => $topik_id,
            ':npm_mahasiswa' => $npm_mahasiswa
        ]);
        $exists = $stmtCheck->fetchColumn() > 0;

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE minat_topik SET alasan = :alasan, status = 'menunggu' WHERE topik_id = :topik_id AND mahasiswa_npm = :npm_mahasiswa");
            $stmt->execute([
                ':alasan' => $alasan,
                ':topik_id' => $topik_id,
                ':npm_mahasiswa' => $npm_mahasiswa
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO minat_topik (topik_id, mahasiswa_npm, alasan, status) VALUES (:topik_id, :npm_mahasiswa, :alasan, 'menunggu')");
            $stmt->execute([
                ':topik_id' => $topik_id,
                ':npm_mahasiswa' => $npm_mahasiswa,
                ':alasan' => $alasan
            ]);
        }

        $_SESSION['swal_success'] = 'Permohonan minat topik penelitian berhasil disimpan.';
    } catch (PDOException $e) {
        $_SESSION['swal_error'] = 'Gagal menyimpan permohonan: ' . $e->getMessage();
    }

    header('Location: /bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php');
    exit;
}

$stmt = $pdo->query("SELECT tp.*, d.nama AS nama_dosen FROM topik_penelitian tp JOIN dosen d ON REPLACE(tp.nip_dosen, ' ', '') = REPLACE(d.nip, ' ', '') ORDER BY tp.created_at DESC");
$topik_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topik = [];
foreach ($topik_raw as $t) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
    $stmtCount->execute([':topik_id' => $t['id']]);
    $terisi = $stmtCount->fetchColumn();

    $stmtMinat = $pdo->prepare("SELECT status, alasan FROM minat_topik WHERE topik_id = :topik_id AND mahasiswa_npm = :npm_mahasiswa");
    $stmtMinat->execute([
        ':topik_id' => $t['id'],
        ':npm_mahasiswa' => $npm_mahasiswa
    ]);
    $minat = $stmtMinat->fetch(PDO::FETCH_ASSOC);

    $topik[] = [
        'id' => $t['id'],
        'dosen' => $t['nama_dosen'],
        'judul' => $t['topik'],
        'deskripsi' => $t['deskripsi'],
        'tenggat_tanggal' => $t['tenggat_tanggal'] ?: '',
        'kuota' => $terisi . '/' . $t['kuota_max'],
        'kuota_terisi' => (int)$terisi,
        'kuota_max' => (int)$t['kuota_max'],
        'status' => $minat['status'] ?? '',
        'alasan' => $minat['alasan'] ?? ''
    ];
}
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_mahasiswa.php';
include __DIR__ . '/../layouts/topbar.php';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Daftar Topik Penelitian</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#ffffff;
}

/* CONTENT */

.content{
    margin-left:270px;
    margin-top:70px;
    padding:25px;
}

.title{
    font-size:20px;
    font-weight:500;
    margin-bottom:20px;
}

/* CARD */

.card{
    background:#fff;
    border-top:4px solid #68a86f;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
    padding:20px;
}

/* SEARCH */

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

/* TABLE */

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#3f4d63;
    color:white;
}

th{
    padding:14px;
    font-size:14px;
    font-weight:600;
}

td{
    padding:16px 12px;
    border:1px solid #ddd;
    font-size:14px;
    vertical-align:middle;
}

td:nth-child(1),
td:nth-child(5),
td:nth-child(6),
td:nth-child(7){
    text-align:center;
}

.btn-detail {
    background: #6faed3;
    color: white;
    border: none;
    padding: 8px 10px;
    border-radius: 3px;
    cursor: pointer;
}

.btn-detail:hover {
    opacity: 0.9;
}

.badge-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
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

/* ================= MODAL DETAIL TOPIK ================= */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.4);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

.modal-card {
    background: #ffffff;
    width: 680px;
    max-width: 92%;
    border-radius: 6px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    padding: 30px 35px;
    animation: fadeInModal 0.25s ease-out;
}

@keyframes fadeInModal {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-title {
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    color: #222;
    margin-bottom: 25px;
}

.modal-form-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.modal-form-row {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.modal-form-row label {
    width: 150px;
    flex-shrink: 0;
    font-size: 14.5px;
    font-weight: 700;
    color: #5568b2;
    padding-top: 8px;
}

.modal-form-row label span.required {
    color: #e53e3e;
}

.modal-input-field {
    flex: 1;
    width: 100%;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    padding: 9px 12px;
    font-size: 14px;
    color: #333;
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s;
}

.modal-input-field:focus {
    border-color: #285aa9;
}

textarea.modal-input-field {
    resize: vertical;
    font-family: inherit;
    line-height: 1.5;
}

.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
}

.btn-modal-back {
    background: #285aa9;
    color: #ffffff;
    border: none;
    padding: 9px 18px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-modal-back:hover {
    background: #1e4687;
}

.btn-modal-save {
    background: #4ea968;
    color: #ffffff;
    border: none;
    padding: 9px 20px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-modal-save:hover {
    background: #3f9056;
}

</style>
</head>

<body>

<!-- CONTENT -->

<div class="content">

    <div class="title">
        Daftar Topik Penelitian
    </div>

    <div class="card">

        <div class="search">
            <input type="text" id="searchInput" placeholder="Cari Topik Skripsi">
            <button type="button" id="searchBtn">
                <i class="fa fa-search"></i>
            </button>
        </div>

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Dosen</th>
                    <th>Topik</th>
                    <th>Deskripsi</th>
                    <th>Tenggat</th>
                    <th>Kuota</th>
                    <th>Aksi</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody id="tableBody">

            <?php foreach($topik as $i => $row): 
                $st = $row['status'];
                $alasanVal = $row['alasan'];
                $isFull = ($row['kuota_terisi'] >= $row['kuota_max']) ? 'true' : 'false';
                
                // Check if expired
                $isExpired = false;
                if (!empty($row['tenggat_tanggal'])) {
                    $today = date('Y-m-d');
                    if ($today > $row['tenggat_tanggal']) {
                        $isExpired = true;
                    }
                }
                $isExpiredStr = $isExpired ? 'true' : 'false';
            ?>

                <tr data-dosen="<?= htmlspecialchars(strtolower($row['dosen'])) ?>" 
                    data-topik="<?= htmlspecialchars(strtolower($row['judul'])) ?>" 
                    data-deskripsi="<?= htmlspecialchars(strtolower($row['deskripsi'])) ?>">

                    <td><?= $i+1 ?></td>

                    <td><?= htmlspecialchars($row['dosen']) ?></td>

                    <td><?= htmlspecialchars($row['judul']) ?></td>

                    <td><?= htmlspecialchars($row['deskripsi']) ?></td>

                    <td><?= !empty($row['tenggat_tanggal']) ? htmlspecialchars($row['tenggat_tanggal']) : '-' ?></td>

                    <td><?= htmlspecialchars($row['kuota']) ?></td>

                    <td>
                        <button type="button" class="btn-detail" 
                                data-id="<?= $row['id'] ?>"
                                data-dosen="<?= htmlspecialchars($row['dosen']) ?>"
                                data-topik="<?= htmlspecialchars($row['judul']) ?>"
                                data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                                data-alasan="<?= htmlspecialchars($alasanVal) ?>"
                                data-full="<?= $isFull ?>"
                                data-expired="<?= $isExpiredStr ?>">
                            <i class="fa fa-eye"></i>
                        </button>
                    </td>

                    <td>
                        <?php if (strtolower($st) === 'menunggu'): ?>
                            <span class="badge-status status-menunggu">Menunggu</span>
                        <?php elseif (strtolower($st) === 'disetujui'): ?>
                            <span class="badge-status status-disetujui">Disetujui</span>
                        <?php elseif (strtolower($st) === 'ditolak'): ?>
                            <span class="badge-status status-ditolak">Ditolak</span>
                        <?php else: ?>
                            <span class="badge-status" style="background:#e0e0e0; color:#666;">Belum Minat</span>
                        <?php endif; ?>
                    </td>

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

<!-- ================= MODAL DETAIL TOPIK PENELITIAN ================= -->
<div class="modal-overlay" id="modalTopikOverlay">
    <div class="modal-card">
        <h3 class="modal-title">Detail Topik Penelitian</h3>

        <form id="formDetailTopik" method="POST" action="/bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php">
            <input type="hidden" name="topik_id" id="modalTopikId">

            <div class="modal-form-grid">
                <div class="modal-form-row">
                    <label>Dosen</label>
                    <input type="text" id="modalDosen" class="modal-input-field" readonly>
                </div>

                <div class="modal-form-row">
                    <label>Topik</label>
                    <input type="text" id="modalTopik" class="modal-input-field" readonly>
                </div>

                <div class="modal-form-row">
                    <label>Deskripsi</label>
                    <textarea id="modalDeskripsi" class="modal-input-field" rows="2" readonly></textarea>
                </div>

                <div class="modal-form-row">
                    <label>Alasan Tertarik<span class="required">*</span></label>
                    <textarea id="modalAlasan" name="alasan" class="modal-input-field" rows="4" required placeholder="Saya tertarik pada topik ini karena..."></textarea>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-modal-back" id="btnCloseModal">
                    <i class="fa-solid fa-chevron-left"></i> Kembali ke daftar
                </button>

                <button type="submit" class="btn-modal-save">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const modalOverlay = document.getElementById('modalTopikOverlay');
    const modalTopikId = document.getElementById('modalTopikId');
    const modalDosen   = document.getElementById('modalDosen');
    const modalTopik   = document.getElementById('modalTopik');
    const modalDeskripsi = document.getElementById('modalDeskripsi');
    const modalAlasan  = document.getElementById('modalAlasan');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const formDetailTopik = document.getElementById('formDetailTopik');

    // Buka Modal saat tombol Aksi (Mata) diklik
    document.querySelectorAll('.btn-detail').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const dosen = this.getAttribute('data-dosen');
            const topik = this.getAttribute('data-topik');
            const deskripsi = this.getAttribute('data-deskripsi');
            const alasan = this.getAttribute('data-alasan');
            const isFull = this.getAttribute('data-full') === 'true';
            const isExpired = this.getAttribute('data-expired') === 'true';

            modalTopikId.value = id;
            modalDosen.value = dosen;
            modalTopik.value = topik;
            modalDeskripsi.value = deskripsi;
            modalAlasan.value = alasan;

            const btnSave = document.querySelector('.btn-modal-save');
            if (isExpired) {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.5';
                btnSave.style.cursor = 'not-allowed';
                btnSave.innerHTML = '<i class="fa-solid fa-ban"></i> Tenggat Lewat';
                modalAlasan.readOnly = true;
                modalAlasan.placeholder = "Pendaftaran topik ini sudah ditutup karena melewati tenggat tanggal.";
            } else if (isFull) {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.5';
                btnSave.style.cursor = 'not-allowed';
                btnSave.innerHTML = '<i class="fa-solid fa-ban"></i> Kuota Penuh';
                modalAlasan.readOnly = true;
                modalAlasan.placeholder = "Kuota topik bimbingan dosen ini sudah penuh.";
            } else {
                btnSave.disabled = false;
                btnSave.style.opacity = '1';
                btnSave.style.cursor = 'pointer';
                btnSave.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan';
                modalAlasan.readOnly = false;
                modalAlasan.placeholder = "Saya tertarik pada topik ini karena...";
            }

            modalOverlay.style.display = 'flex';
        });
    });

    // Tutup Modal via Tombol "Kembali ke daftar"
    btnCloseModal.addEventListener('click', function() {
        modalOverlay.style.display = 'none';
    });

    // Tutup Modal jika area diluar card diklik
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
        }
    });

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
            const dosen = row.dataset.dosen || '';
            const topik = row.dataset.topik || '';
            const deskripsi = row.dataset.deskripsi || '';
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
            emptyRow.innerHTML = `<td colspan="7" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada topik yang cocok.</td>`;
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

    searchInput.addEventListener('input', () => { currentPage = 1; renderTable(); });
    searchBtn.addEventListener('click', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();
</script>

<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Disimpan!',
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
        title: 'Gagal!',
        text: '<?= htmlspecialchars($_SESSION['swal_error']) ?>'
    });
</script>
<?php unset($_SESSION['swal_error']); endif; ?>

</body>
</html>