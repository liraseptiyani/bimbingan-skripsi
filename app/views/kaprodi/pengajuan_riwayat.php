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
    header("Location: " . BASE_URL . "/");
    exit;
}

$title = 'Kaprodi - Riwayat SK Judul';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch all dosen for dropdowns
try {
    $stmtD = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
    $daftar_dosen = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_dosen = [];
}

// Fetch processed submissions only (disetujui or ditolak)
// Sorted by Nomor SK (newest/latest approved on top)
try {
    $sql = "SELECT p.*, dm.nomor_sk 
            FROM pengajuan_judul p
            LEFT JOIN distribusi_mahasiswa dm ON REPLACE(p.mahasiswa_npm, ' ', '') = REPLACE(dm.npm, ' ', '')
            WHERE p.status != 'menunggu'
            ORDER BY 
              CASE WHEN p.status = 'disetujui' THEN 0 ELSE 1 END,
              dm.nomor_sk DESC, 
              p.tanggal_persetujuan DESC,
              p.id DESC";
    $stmtP = $pdo->query($sql);
    $pengajuan_list = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pengajuan_list = [];
}

// Fetch all distributions to match for approved view
$distribusi_list = [];
try {
    $stmtDist = $pdo->query("SELECT * FROM distribusi_mahasiswa");
    $dists = $stmtDist->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dists as $d) {
        $normNpm = strtolower(preg_replace('/[^a-z0-9]/', '', $d['npm']));
        $distribusi_list[$normNpm] = $d;
    }
} catch (PDOException $e) {}

// Gather unique angkatans for filter
$daftar_angkatan = [];
foreach ($pengajuan_list as $p) {
    $prefix = substr(trim($p['mahasiswa_npm']), 0, 2);
    if (is_numeric($prefix)) {
        $daftar_angkatan[] = "20" . $prefix;
    }
}
$daftar_angkatan = array_unique($daftar_angkatan);
$daftar_angkatan = array_filter($daftar_angkatan);
rsort($daftar_angkatan);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
?>

<style>
    /* Card Styles */
    .card.table-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        border-top: 4px solid #69a86e;
        margin-bottom: 28px;
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
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        color: #334155;
        background: #fff;
        min-width: 170px;
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

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 20px;
    }

    table#judulTable {
        width: 100%;
        min-width: 1200px;
        border-collapse: collapse;
        margin-top: 16px;
    }

    table#judulTable th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        text-align: left;
    }

    table#judulTable td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }

    table#judulTable tbody tr {
        transition: all 0.2s ease;
    }

    table#judulTable tbody tr:hover {
        background: #f8fafc;
    }

    .npm-badge {
        font-family: monospace;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    /* Badge status capsule */
    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 30px;
        text-transform: capitalize;
        text-align: center;
    }
    .badge-status.disetujui {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid rgba(4, 120, 87, 0.15);
    }
    .badge-status.ditolak {
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid rgba(185, 28, 28, 0.15);
    }

    .btn-action-detail {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid rgba(30, 64, 175, 0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        width: 110px;
        justify-content: center;
        box-sizing: border-box;
        text-decoration: none;
    }
    .btn-action-detail:hover {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.15);
    }

    .btn-action-print {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid rgba(22, 101, 52, 0.15);
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        width: 110px;
        justify-content: center;
        box-sizing: border-box;
        text-decoration: none;
    }
    .btn-action-print:hover {
        background: #10b981;
        color: #ffffff;
        border-color: #10b981;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.15);
    }

    .btn-lihat {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid rgba(71, 85, 105, 0.2);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-lihat:hover {
        background: #cbd5e1;
        transform: translateY(-1px);
    }

    /* Modal Overlay & Card Container */
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
        z-index: 1000;
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
        margin-bottom: 20px;
        border: 1px solid rgba(40, 90, 169, 0.1);
    }

    .detail-review .review-row {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 6px;
        padding: 4px 0;
        font-size: 13.5px;
    }

    .detail-review .review-label {
        color: #285aa9;
        font-weight: 600;
    }

    .detail-review .review-value {
        color: #334155;
        line-height: 1.45;
    }

    /* Pagination */
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

    /* Decision details block */
    .decision-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #10b981;
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 13px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        color: #334155;
        text-align: left;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
    }

    .decision-info .d-row {
        display: flex;
        justify-content: flex-start;
        align-items: flex-start;
        gap: 12px;
    }

    .decision-info .d-row span:last-child {
        font-weight: 500;
        color: #1e293b;
        text-align: left;
        flex: 1;
        white-space: nowrap;
    }

    .decision-info .d-label {
        font-weight: 600;
        color: #475569;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex: 0 0 95px;
        white-space: nowrap;
    }

    .btn-lihat-berkas:hover {
        background: #285aa9 !important;
        color: #ffffff !important;
        border-color: #285aa9 !important;
    }

    /* Modal Footer and Buttons Styling */
    .modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        gap: 12px;
        box-sizing: border-box;
    }

    .btn-cancel {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    .btn-print-sk {
        background: #285aa9;
        border: 1px solid #285aa9;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-print-sk:hover {
        background: #1e4480;
        border-color: #1e4480;
    }

    @media (max-width: 480px) {
        .modal-footer {
            flex-direction: column-reverse;
            align-items: stretch;
            padding: 14px 18px;
        }
        .btn-cancel, .btn-print-sk, #modal_print_action, #modal_print_action a {
            width: 100% !important;
            box-sizing: border-box;
        }
    }
</style>

<div class="content">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 class="page-title" style="margin: 0;">Riwayat SK Judul Skripsi</h1>
        </div>
    </div>

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
                <input type="text" id="searchInput" placeholder="Cari NPM, nama, judul...">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="judulTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center; white-space: nowrap;">No</th>
                    <th style="width: 120px; white-space: nowrap;">NPM</th>
                    <th style="width: 200px; white-space: nowrap;">Nama Mahasiswa</th>
                    <th>Judul Skripsi</th>
                    <th style="width: 280px;">Hasil Keputusan Kaprodi / Nomor SK</th>
                    <th style="width: 120px; text-align: center; white-space: nowrap;">Status</th>
                    <th style="width: 150px; text-align: center; white-space: nowrap;">Berkas</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($pengajuan_list)): ?>
                    <tr class="no-data-row">
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 22px;">Belum ada riwayat judul yang selesai diproses.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengajuan_list as $i => $p): 
                        $prefix = substr(trim($p['mahasiswa_npm']), 0, 2);
                        $angkatan = is_numeric($prefix) ? "20" . $prefix : "";
                        $normNpm = strtolower(preg_replace('/[^a-z0-9]/', '', $p['mahasiswa_npm']));
                        $hasDist = isset($distribusi_list[$normNpm]);
                        $distData = $hasDist ? $distribusi_list[$normNpm] : null;
                    ?>
                        <tr data-keyword="<?= strtolower($p['mahasiswa_npm'] . ' ' . $p['mahasiswa_nama'] . ' ' . $p['judul'] . ' ' . ($p['judul_alternatif'] ?? '')) ?>"
                            data-angkatan="<?= $angkatan ?>">
                             <td style="text-align: center; white-space: nowrap;"><?= $i + 1 ?></td>
                             <td style="white-space: nowrap;"><span class="npm-badge"><?= htmlspecialchars($p['mahasiswa_npm']) ?></span></td>
                             <td style="white-space: nowrap; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($p['mahasiswa_nama']) ?></td>
                             <td>
                                 <?php 
                                 $isApproved = ($p['status'] === 'disetujui');
                                 $approvedType = $p['judul_disetujui'] ?? 'utama';
                                 $displayTitle = ($isApproved && $approvedType === 'alternatif') ? $p['judul_alternatif'] : $p['judul'];
                                 ?>
                                 <div style="font-weight: 600; color: #1e293b;">
                                     <?= htmlspecialchars($displayTitle) ?>
                                 </div>
                                 <?php if (!$isApproved && !empty($p['judul_alternatif'])): ?>
                                     <div style="font-size: 13px; color: #64748b; margin-top: 4px; font-style: italic;">
                                         Alternatif: <?= htmlspecialchars($p['judul_alternatif']) ?>
                                     </div>
                                 <?php endif; ?>
                             </td>
                             <td>
                                 <?php if ($p['status'] === 'disetujui'): ?>
                                     <div class="decision-info">
                                          <div class="d-row">
                                              <span class="d-label">Pembimbing 1</span>
                                              <span><?= htmlspecialchars($p['pembimbing1'] ?? $distData['pembimbing1'] ?? '-') ?></span>
                                          </div>
                                          <?php 
                                          $p2_val = $p['pembimbing2'] ?? $distData['pembimbing2'] ?? '';
                                          if (!empty($p2_val) && $p2_val !== '-'): 
                                          ?>
                                              <div class="d-row">
                                                  <span class="d-label">Pembimbing 2</span>
                                                  <span><?= htmlspecialchars($p2_val) ?></span>
                                              </div>
                                          <?php endif; ?>
                                          <?php
                                          $pb2_val = $p['pembahas2'] ?? $distData['pembahas2'] ?? '';
                                          $pb1_label = (!empty($pb2_val) && $pb2_val !== '-') ? 'Pembahas 1' : 'Pembahas';
                                          ?>
                                          <div class="d-row">
                                              <span class="d-label"><?= $pb1_label ?></span>
                                              <span><?= htmlspecialchars($p['pembahas1'] ?? $distData['pembahas1'] ?? '-') ?></span>
                                          </div>
                                          <?php if (!empty($pb2_val) && $pb2_val !== '-'): ?>
                                              <div class="d-row">
                                                  <span class="d-label">Pembahas 2</span>
                                                  <span><?= htmlspecialchars($pb2_val) ?></span>
                                              </div>
                                          <?php endif; ?>
                                          <div class="d-row" style="margin-top: 4px; border-top: 1px dashed #e2e8f0; padding-top: 6px;">
                                             <span class="d-label">Nomor SK</span>
                                             <span style="font-family: monospace; font-weight: 600; color: #166534; background: #f0fdf4; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(22, 101, 52, 0.1);"><?= htmlspecialchars($p['nomor_sk'] ?? $distData['nomor_sk'] ?? '-') ?></span>
                                         </div>
                                     </div>
                                  <?php elseif ($p['status'] === 'ditolak'): ?>
                                      <div style="color: #ef4444; font-size: 13px; font-style: italic; background: #fee2e2; border: 1px solid rgba(185,28,28,0.1); border-radius: 8px; padding: 10px 14px;">
                                          <strong>Alasan Penolakan:</strong><br>
                                          <?= !empty($p['alasan']) ? htmlspecialchars($p['alasan']) : 'Tidak ada alasan rinci yang dicantumkan.' ?>
                                      </div>
                                  <?php else: ?>
                                      <span style="color: #64748b; font-style: italic; font-size: 13px;">Sedang diverifikasi di loket/Kaprodi.</span>
                                  <?php endif; ?>
                             </td>
                             <td style="text-align: center; white-space: nowrap;">
                                 <span class="badge-status <?= $p['status'] ?>"><?= $p['status'] ?></span>
                             </td>
                             <td style="text-align: center;">
                                 <div style="display: flex; flex-direction: column; gap: 6px; align-items: center; justify-content: center;">
                                     <button class="btn-action-detail" type="button" onclick='bukaModalDetail(<?= json_encode($p) ?>, <?= json_encode($distData) ?>)'>
                                         <i class="fa-solid fa-folder-open"></i> Detail
                                     </button>
                                     <?php if ($p['status'] === 'disetujui'): ?>
                                         <a href="cetak_sk.php?id=<?= $p['id'] ?>" target="_blank" class="btn-action-print">
                                             <i class="fa-solid fa-print"></i> Cetak SK
                                         </a>
                                     <?php endif; ?>
                                 </div>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

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

<!-- MODAL WORKFLOW KEPUTUSAN KAPRODI -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Detail Riwayat Pengajuan</h3>
            <button class="modal-close" onclick="closeAllModals()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Info Usulan -->
            <div class="detail-review">
                <div class="review-row">
                    <div class="review-label">Mahasiswa</div>
                    <div class="review-value" id="view_nama_mhs">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">NPM</div>
                    <div class="review-value" id="view_npm_mhs">-</div>
                </div>
                <div class="review-row" id="row_judul_lama" style="display: none;">
                    <div class="review-label">Judul Lama (Disetujui)</div>
                    <div class="review-value" id="view_judul_lama" style="text-decoration: line-through; color: #64748b;">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label" id="label_judul_utama">Judul Utama</div>
                    <div class="review-value" id="view_judul">-</div>
                </div>
                <div class="review-row" id="row_judul_alt">
                    <div class="review-label" id="label_judul_alt">Judul Alternatif</div>
                    <div class="review-value" id="view_judul_alternatif">-</div>
                </div>
                <div class="review-row" id="row_deskripsi">
                    <div class="review-label">Rencana Riset</div>
                    <div class="review-value" id="view_deskripsi">-</div>
                </div>
                
                <!-- BERKAS UPLOAD PERSYARATAN -->
                <div style="margin-top: 14px; border-top: 1px dashed rgba(40, 90, 169, 0.2); padding-top: 10px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 13px; color: #285aa9; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Berkas Kelengkapan</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12.5px;">
                        <div id="btn_transkrip"></div>
                        <div id="btn_ktm"></div>
                        <div id="btn_form_tema"></div>
                        <div id="btn_bukti_ukt"></div>
                        <div id="btn_krs_terakhir"></div>
                        <div id="btn_form_verifikasi"></div>
                        <div id="btn_bukti_acc"></div>
                        <div id="btn_form_penetapan"></div>
                        <div id="btn_bab1"></div>
                        <div id="btn_bab1_alt"></div>
                    </div>
                </div>
            </div>

            <!-- Info Read-Only Detail -->
            <div>
                <h4 style="margin: 0 0 10px 0; color:#334155; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">Detail Hasil Keputusan</h4>
                <div class="detail-review" style="background:#f8fafc; border-color:#cbd5e1;">
                    <div class="review-row">
                        <div class="review-label">Status Akhir</div>
                        <div class="review-value"><span id="view_status_akhir" class="badge-status">-</span></div>
                    </div>
                    <div class="review-row" id="row_p1_akhir">
                        <div class="review-label">Pembimbing 1</div>
                        <div class="review-value" id="view_p1_akhir">-</div>
                    </div>
                    <div class="review-row" id="row_p2_akhir">
                        <div class="review-label">Pembimbing 2</div>
                        <div class="review-value" id="view_p2_akhir">-</div>
                    </div>
                    <div class="review-row" id="row_pb_akhir">
                        <div class="review-label" id="label_pb_akhir">Pembahas</div>
                        <div class="review-value" id="view_pb_akhir">-</div>
                    </div>
                    <div class="review-row" id="row_pb2_akhir" style="display: none;">
                        <div class="review-label">Pembahas 2</div>
                        <div class="review-value" id="view_pb2_akhir">-</div>
                    </div>
                    <div class="review-row" id="row_sk_akhir">
                        <div class="review-label">Nomor SK</div>
                        <div class="review-value" id="view_sk_akhir">-</div>
                    </div>
                    <div class="review-row" id="row_alasan_akhir">
                        <div class="review-label" id="label_alasan_akhir">Alasan Tolak</div>
                        <div class="review-value" id="view_alasan_akhir" style="color:#ef4444; font-style:italic;">-</div>
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeAllModals()">Tutup</button>
            <div id="modal_print_action"></div>
        </div>
    </div>
</div>

<script>
    const modalOverlay = document.getElementById('modalOverlay');

    // Helper to generate file links
    function makeDocLink(filename, label, isDocx = false) {
        if (!filename) return `<span style="color: #94a3b8; font-style: italic;"><i class="fa-solid fa-minus"></i> ${label} (Tidak ada)</span>`;
        
        // Check if there are multiple comma-separated files
        const files = filename.split(',');
        if (files.length > 1) {
            let links = [];
            files.forEach((file, index) => {
                const path = `<?= BASE_URL ?>/public/uploads/persyaratan/${file.trim()}`;
                const fileExt = file.trim().split('.').pop().toLowerCase();
                const fileIsDocx = ['docx', 'doc'].includes(fileExt);
                const icon = fileIsDocx ? 'fa-file-word' : 'fa-file-pdf';
                const color = fileIsDocx ? '#2563eb' : '#dc2626';
                links.push(`<a href="${path}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; color: ${color}; font-weight: 600; text-decoration: none; margin-bottom: 4px;"><i class="fa-solid ${icon}"></i> ${label} [${index + 1}]</a>`);
            });
            return `<div style="display: flex; flex-direction: column; gap: 4px;">${links.join('')}</div>`;
        } else {
            const fileExt = filename.trim().split('.').pop().toLowerCase();
            const fileIsDocx = ['docx', 'doc'].includes(fileExt);
            const icon = fileIsDocx ? 'fa-file-word' : 'fa-file-pdf';
            const color = fileIsDocx ? '#2563eb' : '#dc2626';
            const path = `<?= BASE_URL ?>/public/uploads/persyaratan/${filename}`;
            return `<a href="${path}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; color: ${color}; font-weight: 600; text-decoration: none;"><i class="fa-solid ${icon}"></i> ${label}</a>`;
        }
    }

    function bukaModalDetail(p, dist) {
        document.getElementById('view_nama_mhs').textContent = p.mahasiswa_nama;
        document.getElementById('view_npm_mhs').textContent = p.mahasiswa_npm;
        
        // Show Judul and highlight which one is approved
        const altRow = document.getElementById('row_judul_alt');
        const rowJudulLama = document.getElementById('row_judul_lama');
        const labelJudulUtama = document.getElementById('label_judul_utama');
        const labelJudulAlt = document.getElementById('label_judul_alt');

        if (p.status === 'disetujui') {
            labelJudulUtama.textContent = 'Judul Skripsi';
            const displayTitle = (p.judul_disetujui === 'alternatif') ? p.judul_alternatif : p.judul;
            document.getElementById('view_judul').textContent = displayTitle;
            altRow.style.display = 'none';
            rowJudulLama.style.display = 'none';
        } else {
            labelJudulUtama.textContent = 'Judul Skripsi';
            document.getElementById('view_judul').textContent = p.judul;
            if (p.judul_alternatif) {
                altRow.style.display = 'grid';
                labelJudulAlt.textContent = 'Judul Alternatif';
                document.getElementById('view_judul_alternatif').textContent = p.judul_alternatif;
            } else {
                altRow.style.display = 'none';
            }
            if (p.judul_lama) {
                rowJudulLama.style.display = 'grid';
                document.getElementById('view_judul_lama').textContent = p.judul_lama;
            } else {
                rowJudulLama.style.display = 'none';
            }
        }
        
        const deskripsiRow = document.getElementById('row_deskripsi');
        if (p.deskripsi && p.deskripsi.trim() !== '') {
            deskripsiRow.style.display = 'grid';
            document.getElementById('view_deskripsi').textContent = p.deskripsi;
        } else {
            deskripsiRow.style.display = 'none';
        }

        // Setup status akhir pill
        const statusAkhirEl = document.getElementById('view_status_akhir');
        statusAkhirEl.textContent = p.status;
        statusAkhirEl.className = 'badge-status ' + p.status;

        // Fill documents buttons
        document.getElementById('btn_transkrip').innerHTML = makeDocLink(p.file_transkrip, 'Transkrip Nilai');
        document.getElementById('btn_ktm').innerHTML = makeDocLink(p.file_ktm, 'KTM');
        document.getElementById('btn_form_tema').innerHTML = makeDocLink(p.file_form_tema, 'Form Pengajuan Tema');
        document.getElementById('btn_bukti_ukt').innerHTML = makeDocLink(p.file_bukti_ukt, 'Bukti Pembayaran UKT');
        document.getElementById('btn_krs_terakhir').innerHTML = makeDocLink(p.file_krs_terakhir, 'KRS Terakhir');
        document.getElementById('btn_form_verifikasi').innerHTML = makeDocLink(p.file_form_verifikasi, 'Form Verifikasi Berkas');
        document.getElementById('btn_bukti_acc').innerHTML = makeDocLink(p.file_bukti_acc, 'Bukti ACC Dosen');
        document.getElementById('btn_form_penetapan').innerHTML = makeDocLink(p.file_form_penetapan, 'Form Penetapan Tema', true);
        document.getElementById('btn_bab1').innerHTML = makeDocLink(p.file_bab1, 'Bab 1 (Utama)', true);
        document.getElementById('btn_bab1_alt').innerHTML = makeDocLink(p.file_bab1_alt, 'Bab 1 (Alternatif)', true);

        // Show/hide sub rows
        if (p.status === 'disetujui') {
            document.getElementById('row_p1_akhir').style.display = 'grid';
            document.getElementById('row_sk_akhir').style.display = 'grid';
            document.getElementById('row_alasan_akhir').style.display = 'none';

            const p1_val = p.pembimbing1 || (dist ? dist.pembimbing1 : '-') || '-';
            const p2_val = p.pembimbing2 || (dist ? dist.pembimbing2 : '-') || '-';
            const pb1_val = p.pembahas1 || (dist ? dist.pembahas1 : '-') || '-';
            const pb2_val = p.pembahas2 || (dist ? dist.pembahas2 : '-') || '-';

            document.getElementById('view_p1_akhir').textContent = p1_val;

            if (p2_val && p2_val !== '-') {
                document.getElementById('row_p2_akhir').style.display = 'grid';
                document.getElementById('view_p2_akhir').textContent = p2_val;
            } else {
                document.getElementById('row_p2_akhir').style.display = 'none';
            }

            if (pb2_val && pb2_val !== '-') {
                document.getElementById('label_pb_akhir').textContent = 'Pembahas 1';
                document.getElementById('row_pb_akhir').style.display = 'grid';
                document.getElementById('row_pb2_akhir').style.display = 'grid';
                document.getElementById('view_pb2_akhir').textContent = pb2_val;
            } else {
                document.getElementById('label_pb_akhir').textContent = 'Pembahas';
                document.getElementById('row_pb_akhir').style.display = 'grid';
                document.getElementById('row_pb2_akhir').style.display = 'none';
            }
            document.getElementById('view_pb_akhir').textContent = pb1_val;
            document.getElementById('view_sk_akhir').textContent = p.nomor_sk || (dist ? dist.nomor_sk : '-') || '-';
        } else {
            document.getElementById('row_p1_akhir').style.display = 'none';
            document.getElementById('row_p2_akhir').style.display = 'none';
            document.getElementById('row_pb_akhir').style.display = 'none';
            document.getElementById('row_pb2_akhir').style.display = 'none';
            document.getElementById('row_sk_akhir').style.display = 'none';
            document.getElementById('row_alasan_akhir').style.display = 'grid';
            document.getElementById('view_alasan_akhir').textContent = p.alasan || '-';

            const labelAlasanAkhir = document.getElementById('label_alasan_akhir');
            if (p.status === 'revisi') {
                labelAlasanAkhir.textContent = 'Catatan Revisi';
                document.getElementById('view_alasan_akhir').style.color = '#7e22ce';
            } else {
                labelAlasanAkhir.textContent = 'Alasan Tolak';
                document.getElementById('view_alasan_akhir').style.color = '#ef4444';
            }
        }

        const printActionDiv = document.getElementById('modal_print_action');
        if (p.status === 'disetujui') {
            printActionDiv.innerHTML = `<a href="cetak_sk.php?id=${p.id}" target="_blank" class="btn-print-sk"><i class="fa-solid fa-print"></i> Cetak SK</a>`;
        } else {
            printActionDiv.innerHTML = '';
        }

        modalOverlay.style.display = 'flex';
    }

    function closeAllModals() {
        modalOverlay.style.display = 'none';
    }

    // Table Search, Filter and Pagination
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const filterAngkatan = document.getElementById('filterAngkatan');
    const rowsPerPageSel = document.getElementById('rowsPerPage');
    const tableBody = document.getElementById('tableBody');
    const allRows = Array.from(tableBody.querySelectorAll('tr:not(.no-data-row)'));
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        const angkatan = filterAngkatan.value;

        return allRows.filter(row => {
            const rowKeyword = row.dataset.keyword;
            const rowAngkatan = row.dataset.angkatan;

            const matchKeyword = rowKeyword.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="7" style="text-align: center; color: #94a3b8; padding: 22px;">Tidak ada pengajuan riwayat yang cocok.</td>`;
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

        paginationButtons.appendChild(makeBtn('&laquo;', currentPage === 1, () => { currentPage = 1; renderTable(); }));
        paginationButtons.appendChild(makeBtn('&lsaquo;', currentPage === 1, () => { currentPage--; renderTable(); }));

        for (let i = 1; i <= totalPages; i++) {
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
