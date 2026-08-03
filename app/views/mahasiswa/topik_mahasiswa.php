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

// Cek apakah mahasiswa sudah disetujui di salah satu topik
$hasApprovedTopic = false;
$approvedTopicDetails = null;
try {
    $stmtApproved = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE mahasiswa_npm = :npm_mahasiswa AND status = 'disetujui'");
    $stmtApproved->execute([':npm_mahasiswa' => $npm_mahasiswa]);
    $hasApprovedTopic = $stmtApproved->fetchColumn() > 0;
    
    if ($hasApprovedTopic) {
        $stmtApprovedTopic = $pdo->prepare("
            SELECT tp.topik, d.nama AS nama_dosen
            FROM minat_topik mt
            JOIN topik_penelitian tp ON mt.topik_id = tp.id
            JOIN dosen d ON REPLACE(tp.nip_dosen, ' ', '') = REPLACE(d.nip, ' ', '')
            WHERE mt.mahasiswa_npm = :npm_mahasiswa AND mt.status = 'disetujui'
            LIMIT 1
        ");
        $stmtApprovedTopic->execute([':npm_mahasiswa' => $npm_mahasiswa]);
        $approvedTopicDetails = $stmtApprovedTopic->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {}

// Tangani proses simpan / update alasan tertarik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topik_id'])) {
    if ($hasApprovedTopic) {
        $_SESSION['swal_error'] = 'Anda tidak dapat mengajukan topik lain karena Anda sudah disetujui untuk salah satu topik penelitian!';
        header('Location: /bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php');
        exit;
    }

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
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }

    .modal-container {
        background: #ffffff;
        border-radius: 16px;
        width: 600px;
        max-width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
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
        max-height: 480px;
        overflow-y: auto;
    }

    /* Detail Review Section */
    .detail-review {
        background: #eef4fb;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 0px;
        border: 1px solid rgba(40, 90, 169, 0.1);
    }

    .detail-review .review-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 6px;
        padding: 8px 0;
        font-size: 13.5px;
        border-bottom: 1px solid rgba(40, 90, 169, 0.05);
    }

    .detail-review .review-row:last-child {
        border-bottom: none;
    }

    .detail-review .review-label {
        color: #285aa9;
        font-weight: 600;
    }

    .detail-review .review-value {
        color: #334155;
        line-height: 1.45;
        text-align: left;
    }

    .modal-input-field {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .modal-input-field:focus {
        border-color: #285aa9;
    }

    textarea.modal-input-field {
        resize: vertical;
        font-family: inherit;
        line-height: 1.5;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #cbd5e1;
    }

    .btn-modal-save {
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
        transition: all 0.2s;
    }

    .btn-modal-save:hover {
        background: #1e4687;
    }

    .modal-footer {
        padding: 16px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

</style>
</head>

<body>

<!-- CONTENT -->

<div class="content">

    <?php if ($approvedTopicDetails): ?>
        <div style="background: #eefaf2; border-left: 4px solid #68a86f; border-top: 1px solid rgba(104, 168, 111, 0.15); border-right: 1px solid rgba(104, 168, 111, 0.15); border-bottom: 1px solid rgba(104, 168, 111, 0.15); border-radius: 4px; padding: 11px 16px; margin-bottom: 20px; color: #1b4332; display: flex; align-items: center; gap: 10px; font-size: 13.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            <i class="fa-solid fa-circle-check" style="font-size: 16px; color: #68a86f;"></i>
            <div>
                <strong>Pengajuan Disetujui!</strong> Anda telah disetujui untuk topik <strong>"<?= htmlspecialchars($approvedTopicDetails['topik']) ?>"</strong> oleh dosen <strong><?= htmlspecialchars($approvedTopicDetails['nama_dosen']) ?></strong>.
            </div>
        </div>
    <?php endif; ?>

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
                    <th style="width: 140px; white-space: nowrap;">Tenggat</th>
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

                    <td style="white-space: nowrap;"><?= !empty($row['tenggat_tanggal']) ? htmlspecialchars($row['tenggat_tanggal']) : '-' ?></td>

                    <td><?= htmlspecialchars($row['kuota']) ?></td>

                    <td>
                        <button type="button" class="btn-detail" 
                                data-id="<?= $row['id'] ?>"
                                data-dosen="<?= htmlspecialchars($row['dosen']) ?>"
                                data-topik="<?= htmlspecialchars($row['judul']) ?>"
                                data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                                data-alasan="<?= htmlspecialchars($alasanVal) ?>"
                                data-full="<?= $isFull ?>"
                                data-expired="<?= $isExpiredStr ?>"
                                data-hasapproved="<?= $hasApprovedTopic ? 'true' : 'false' ?>"
                                data-status="<?= htmlspecialchars($st) ?>">
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
    <div class="modal-container">
        <div class="modal-header">
            <h3>Detail Topik Penelitian</h3>
            <button type="button" class="modal-close" id="btnHeaderClose">&times;</button>
        </div>
        <form id="formDetailTopik" method="POST" action="/bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php">
            <div class="modal-body">
                <input type="hidden" name="topik_id" id="modalTopikId">

                <div class="detail-review">
                    <div class="review-row">
                        <div class="review-label">Dosen</div>
                        <div class="review-value" id="view_dosen">-</div>
                    </div>
                    <div class="review-row">
                        <div class="review-label">Topik</div>
                        <div class="review-value" id="view_topik">-</div>
                    </div>
                    <div class="review-row">
                        <div class="review-label">Deskripsi</div>
                        <div class="review-value" id="view_deskripsi">-</div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <label style="font-weight: 600; color: #285aa9; display: block; margin-bottom: 8px; font-size: 14px;">Alasan Tertarik <span style="color: #ef4444;">*</span></label>
                    <textarea id="modalAlasan" name="alasan" class="modal-input-field" rows="4" required placeholder="Saya tertarik pada topik ini karena..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="btnCloseModal">Tutup</button>
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
    const modalAlasan  = document.getElementById('modalAlasan');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnHeaderClose = document.getElementById('btnHeaderClose');
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
            const hasApproved = this.getAttribute('data-hasapproved') === 'true';
            const status = this.getAttribute('data-status') || '';

            modalTopikId.value = id;
            document.getElementById('view_dosen').textContent = dosen;
            document.getElementById('view_topik').textContent = topik;
            document.getElementById('view_deskripsi').textContent = deskripsi;
            modalAlasan.value = alasan;

            const btnSave = document.querySelector('.btn-modal-save');
            if (status === 'disetujui') {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.5';
                btnSave.style.cursor = 'not-allowed';
                btnSave.innerHTML = '<i class="fa-solid fa-check-double"></i> Disetujui';
                modalAlasan.readOnly = true;
                modalAlasan.placeholder = "Topik ini telah disetujui oleh dosen.";
            } else if (hasApproved) {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.5';
                btnSave.style.cursor = 'not-allowed';
                btnSave.innerHTML = '<i class="fa-solid fa-lock"></i> Terkunci';
                modalAlasan.readOnly = true;
                modalAlasan.placeholder = "Anda tidak dapat mengajukan topik karena sudah disetujui pada topik penelitian lain.";
            } else if (isExpired) {
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
            } else if (status !== '') {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.5';
                btnSave.style.cursor = 'not-allowed';
                btnSave.innerHTML = '<i class="fa-solid fa-check"></i> Sudah Diajukan';
                modalAlasan.readOnly = true;
                modalAlasan.placeholder = "Anda sudah mengajukan minat untuk topik penelitian ini.";
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

    // Form submit confirmation with SweetAlert2
    let isConfirmedSubmit = false;
    formDetailTopik.addEventListener('submit', function(e) {
        if (isConfirmedSubmit) {
            return;
        }
        e.preventDefault();
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Anda akan mengajukan minat pada topik penelitian ini. Pengajuan tidak dapat diubah kembali setelah disimpan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#285aa9',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                isConfirmedSubmit = true;
                formDetailTopik.submit();
            }
        });
    });

    // Tutup Modal via Tombol "Tutup"
    btnCloseModal.addEventListener('click', function() {
        modalOverlay.style.display = 'none';
    });

    // Tutup Modal via Tombol "x" di header
    btnHeaderClose.addEventListener('click', function() {
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
            emptyRow.innerHTML = `<td colspan="8" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada topik yang cocok.</td>`;
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