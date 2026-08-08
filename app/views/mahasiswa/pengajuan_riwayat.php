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

$title = "Mahasiswa - Riwayat Pengajuan Judul";

$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch past submissions
try {
    $sql = "SELECT p.*, 
                   d1.nama AS nama_p1, 
                   d2.nama AS nama_p2, 
                   db1.nama AS nama_pb1, 
                   db2.nama AS nama_pb2 
            FROM pengajuan_judul p
            LEFT JOIN dosen d1 ON p.pembimbing1 = d1.nip
            LEFT JOIN dosen d2 ON p.pembimbing2 = d2.nip
            LEFT JOIN dosen db1 ON p.pembahas1 = db1.nip
            LEFT JOIN dosen db2 ON p.pembahas2 = db2.nip
            WHERE p.mahasiswa_npm = :npm 
            ORDER BY p.id DESC";
    $stmtP = $pdo->prepare($sql);
    $stmtP->execute([':npm' => $npmMhs]);
    $pengajuan_list = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pengajuan_list = [];
}

// Fetch distribution to match SK numbers and actual assigned pembimbing
$distData = null;
try {
    $sqlDist = "SELECT dm.*, 
                       d1.nama AS nama_p1, 
                       d2.nama AS nama_p2, 
                       db1.nama AS nama_pb1, 
                       db2.nama AS nama_pb2 
                FROM distribusi_mahasiswa dm
                LEFT JOIN dosen d1 ON dm.pembimbing1 = d1.nip
                LEFT JOIN dosen d2 ON dm.pembimbing2 = d2.nip
                LEFT JOIN dosen db1 ON dm.pembahas1 = db1.nip
                LEFT JOIN dosen db2 ON dm.pembahas2 = db2.nip
                WHERE REPLACE(dm.npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1";
    $stmtDist = $pdo->prepare($sqlDist);
    $stmtDist->execute([':npm' => $npmMhs]);
    $distData = $stmtDist->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_mahasiswa.php';
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
        min-width: 900px;
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
    .badge-status.menunggu {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid rgba(217, 119, 6, 0.15);
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
    
    .btn-action-perbaikan {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid rgba(180, 83, 9, 0.15);
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
    .btn-action-perbaikan:hover {
        background: #d97706;
        color: #ffffff;
        border-color: #d97706;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.15);
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

    .modal-footer {
        padding: 16px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
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
            <h1 class="page-title" style="margin: 0;">Riwayat Pengajuan Judul</h1>
        </div>
    </div>

    <div class="card table-card">
        <div class="table-toolbar">
            <div class="search">
                <input type="text" id="searchInput" placeholder="Cari judul skripsi...">
                <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <?php 
            $hasApproved = false;
            $approvedSub = null;
            $hasPending = false;
            if (!empty($pengajuan_list)) {
                foreach ($pengajuan_list as $p_row) {
                    if ($p_row['status'] === 'menunggu') {
                        $hasPending = true;
                    }
                    if ($p_row['status'] === 'disetujui' && !$approvedSub) {
                        $hasApproved = true;
                        $approvedSub = $p_row;
                    }
                }
            }
            if ($hasApproved && !$hasPending):
            ?>
                <button class="btn-action-perbaikan" type="button" onclick="bukaModalPerbaikan(<?= htmlspecialchars(json_encode($approvedSub), ENT_QUOTES, 'UTF-8') ?>)" style="padding: 9px 18px; font-size: 13.5px; width: auto; border-radius: 6px; font-weight: 700; background: #f59e0b; color: white; border: 1px solid #d97706; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2); cursor: pointer; transition: all 0.2s;">
                    <i class="fa-solid fa-pen-to-square"></i> Perbaikan Judul
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table id="judulTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center; white-space: nowrap;">No</th>
                        <th>Judul Skripsi</th>
                        <th style="width: 150px; white-space: nowrap;">Tanggal Pengajuan</th>
                        <th style="width: 120px; text-align: center; white-space: nowrap;">Status</th>
                        <th style="width: 280px;">Hasil Keputusan Kaprodi / Nomor SK</th>
                        <th style="width: 150px; text-align: center; white-space: nowrap;">Berkas</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($pengajuan_list)): ?>
                        <tr class="no-data-row">
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 22px;">Belum ada riwayat pengajuan judul.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengajuan_list as $i => $p): ?>
                            <tr data-keyword="<?= strtolower($p['judul'] . ' ' . ($p['judul_alternatif'] ?? '') . ' ' . $p['status']) ?>">
                                <td style="text-align: center; white-space: nowrap;"><?= $i + 1 ?></td>
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
                                <td style="white-space: nowrap; color: #475569; font-size: 13px;">
                                    <i class="fa-regular fa-calendar" style="margin-right: 5px; color: #285aa9;"></i>
                                    <?= date('d M Y', strtotime($p['created_at'])) ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <span class="badge-status <?= $p['status'] ?>"><?= $p['status'] ?></span>
                                </td>
                                <td>
                                     <?php if ($p['status'] === 'disetujui'): ?>
                                         <div class="decision-info">
                                             <div class="d-row">
                                                 <span class="d-label">Pembimbing 1</span>
                                                 <span><?= htmlspecialchars($p['nama_p1'] ?? $distData['nama_p1'] ?? $p['pembimbing1'] ?? $distData['pembimbing1'] ?? '-') ?></span>
                                             </div>
                                             <?php 
                                             $p2_val = $p['nama_p2'] ?? $distData['nama_p2'] ?? $p['pembimbing2'] ?? $distData['pembimbing2'] ?? '';
                                             if (!empty($p2_val) && $p2_val !== '-'): 
                                             ?>
                                                 <div class="d-row">
                                                     <span class="d-label">Pembimbing 2</span>
                                                     <span><?= htmlspecialchars($p2_val) ?></span>
                                                 </div>
                                             <?php endif; ?>
                                             <?php
                                             $pb2_val = $p['nama_pb2'] ?? $distData['nama_pb2'] ?? $p['pembahas2'] ?? $distData['pembahas2'] ?? '';
                                             $pb1_label = (!empty($pb2_val) && $pb2_val !== '-') ? 'Pembahas 1' : 'Pembahas';
                                             ?>
                                             <div class="d-row">
                                                 <span class="d-label"><?= $pb1_label ?></span>
                                                 <span><?= htmlspecialchars($p['nama_pb1'] ?? $distData['nama_pb1'] ?? $p['pembahas1'] ?? $distData['pembahas1'] ?? '-') ?></span>
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
                                <td style="text-align: center;">
                                    <div style="display: flex; flex-direction: column; gap: 6px; align-items: center; justify-content: center;">
                                        <button class="btn-action-detail" type="button" onclick="bukaModalDetail(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($distData), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fa-solid fa-folder-open"></i> Detail
                                        </button>
                                        <?php if ($p['status'] === 'disetujui'): ?>
                                            <a href="../kaprodi/cetak_sk.php?id=<?= $p['id'] ?>" target="_blank" class="btn-action-print">
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

<!-- MODAL DETAIL BERKAS MAHASISWA -->
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

<!-- MODAL PERBAIKAN JUDUL MAHASISWA -->
<div class="modal-overlay" id="modalPerbaikanOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Ajukan Perbaikan Judul</h3>
            <button class="modal-close" onclick="closeAllModals()">&times;</button>
        </div>
        <form id="formPerbaikanJudul">
            <div class="modal-body">
                <input type="hidden" name="id" id="perbaikan_id">
                <input type="hidden" name="action" value="perbaikan">
                
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13.5px; color: #b45309; line-height: 1.45;">
                    <i class="fa-solid fa-circle-info"></i> 
                    Tinjau judul lama Anda di bawah ini dan masukkan usulan judul baru yang diinginkan. Berkas persyaratan Anda sebelumnya akan tetap digunakan.
                </div>

                <!-- Judul Lama (Read-Only) -->
                <div class="form-group" style="margin-bottom: 16px; text-align: left;">
                    <label style="display: block; font-weight: 600; font-size: 13.5px; color: #64748b; margin-bottom: 6px;">Judul Lama</label>
                    <div id="perbaikan_judul_lama_val" style="background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 13.5px; color: #475569; line-height: 1.4; text-decoration: line-through;">
                        -
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px; text-align: left;">
                    <label for="perbaikan_judul" style="display: block; font-weight: 600; font-size: 13.5px; color: #334155; margin-bottom: 6px;">Judul Baru <span style="color:#ef4444;">*</span></label>
                    <textarea id="perbaikan_judul" name="judul" rows="3" required style="width:100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" placeholder="Masukkan judul skripsi baru..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAllModals()">Batal</button>
                <button type="submit" class="btn-save-decision" id="btnSubmitPerbaikan" style="background: #f59e0b; border: 1px solid #f59e0b; color: white; padding: 10px 18px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Perbaikan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Table search & pagination
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const rowsPerPageSel = document.getElementById('rowsPerPage');
    const tableBody = document.getElementById('tableBody');
    const allRows = Array.from(tableBody.querySelectorAll('tr:not(.no-data-row)'));
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    let currentPage = 1;

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

            const p1_val = p.nama_p1 || (dist ? dist.nama_p1 : '') || p.pembimbing1 || (dist ? dist.pembimbing1 : '-') || '-';
            const p2_val = p.nama_p2 || (dist ? dist.nama_p2 : '') || p.pembimbing2 || (dist ? dist.pembimbing2 : '-') || '-';
            const pb1_val = p.nama_pb1 || (dist ? dist.nama_pb1 : '') || p.pembahas1 || (dist ? dist.pembahas1 : '-') || '-';
            const pb2_val = p.nama_pb2 || (dist ? dist.nama_pb2 : '') || p.pembahas2 || (dist ? dist.pembahas2 : '-') || '-';

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
            printActionDiv.innerHTML = `<a href="../kaprodi/cetak_sk.php?id=${p.id}" target="_blank" class="btn-print-sk"><i class="fa-solid fa-print"></i> Cetak SK</a>`;
        } else {
            printActionDiv.innerHTML = '';
        }

        modalOverlay.style.display = 'flex';
    }

    function closeAllModals() {
        modalOverlay.style.display = 'none';
        modalPerbaikanOverlay.style.display = 'none';
    }

    const modalPerbaikanOverlay = document.getElementById('modalPerbaikanOverlay');
    const formPerbaikanJudul = document.getElementById('formPerbaikanJudul');
    const btnSubmitPerbaikan = document.getElementById('btnSubmitPerbaikan');

    function bukaModalPerbaikan(p) {
        document.getElementById('perbaikan_id').value = p.id;
        
        // Show old titles as read-only comparison: use approved title only
        const oldApprovedTitle = (p.judul_disetujui === 'alternatif' && p.judul_alternatif) ? p.judul_alternatif : p.judul;
        document.getElementById('perbaikan_judul_lama_val').textContent = oldApprovedTitle;
        
        // Keep new inputs empty by default for student to enter their revised titles
        document.getElementById('perbaikan_judul').value = '';
        
        modalPerbaikanOverlay.style.display = 'flex';
    }

    if (formPerbaikanJudul) {
        formPerbaikanJudul.onsubmit = function(e) {
            e.preventDefault();
            const judul = document.getElementById('perbaikan_judul').value.trim();
            if (judul === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Judul Utama Kosong',
                    text: 'Harap masukkan judul utama baru!',
                    confirmButtonColor: '#285aa9'
                });
                return;
            }

            const formData = new FormData(formPerbaikanJudul);

            Swal.fire({
                title: 'Kirim Perbaikan Judul?',
                text: "Apakah Anda yakin ingin mengajukan perubahan judul ini ke Kaprodi?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#285aa9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    btnSubmitPerbaikan.disabled = true;
                    btnSubmitPerbaikan.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

                    fetch('<?= BASE_URL ?>/app/controllers/PengajuanJudulController.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnSubmitPerbaikan.disabled = false;
                        btnSubmitPerbaikan.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Perbaikan';

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Perbaikan Terkirim',
                                text: 'Perbaikan judul Anda berhasil diajukan dan status kembali menjadi menunggu.',
                                confirmButtonColor: '#285aa9'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Pengiriman Gagal',
                                text: data.message || 'Terjadi kesalahan sistem.',
                                confirmButtonColor: '#285aa9'
                            });
                        }
                    })
                    .catch(err => {
                        btnSubmitPerbaikan.disabled = false;
                        btnSubmitPerbaikan.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Perbaikan';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error Koneksi',
                            text: 'Gagal terhubung dengan server.',
                            confirmButtonColor: '#285aa9'
                        });
                    });
                }
            });
        };
    }

    modalPerbaikanOverlay.addEventListener('click', function(e) {
        if (e.target === modalPerbaikanOverlay) {
            modalPerbaikanOverlay.style.display = 'none';
        }
    });

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        return allRows.filter(row => row.dataset.keyword.includes(keyword));
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
            emptyRow.innerHTML = `<td colspan="6" style="text-align: center; color: #94a3b8; padding: 22px;">Tidak ada pengajuan judul yang cocok.</td>`;
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
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();

    // Tutup Modal jika area diluar card diklik
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
        }
    });
</script>

</body>
</html>
