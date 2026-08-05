<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: /");
    exit;
}
$title = 'Distribusi Mahasiswa';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Auto-create/alter table column and seed if empty
try {
    // 1. Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS distribusi_mahasiswa (
        npm VARCHAR(50) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        judul_skripsi TEXT NOT NULL,
        pembimbing1 VARCHAR(255) NOT NULL,
        pembimbing2 VARCHAR(255) DEFAULT NULL,
        pembahas1 VARCHAR(255) NOT NULL,
        pembahas2 VARCHAR(255) DEFAULT NULL,
        nomor_sk VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Seed data if table is empty (disabled to allow total database clearing)
    /*
    $countQuery = $pdo->query("SELECT COUNT(*) FROM distribusi_mahasiswa");
    $count = $countQuery ? $countQuery->fetchColumn() : 0;
    if ($count == 0) {
        $dummyList = [
            ['npm' => '2217051121', 'nama' => 'Aimar Abie Pasah', 'judul' => 'Sistem Pendukung Keputusan Pemilihan Topik Skripsi Menggunakan Metode SAW', 'pembimbing1' => 'Muhaqiqin, S.Kom., M.T.I', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'Rahman Taufik, M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1421/UN26.17.06/Tema/2025'],
            ['npm' => '2217051151', 'nama' => 'LIRA SEPTIYANI', 'judul' => 'Implementasi Sistem Informasi Bimbingan Skripsi Berbasis Web Pada Program Studi S1 Ilmu Komputer Universitas Lampung', 'pembimbing1' => 'Tristiyanto, S.Kom., M.I.S., Ph.D', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'Dwi Sakethi, S.Si., M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1439/UN26.17.06/Tema/2025'],
            ['npm' => '2267051002', 'nama' => 'KYLA NISRINA ANGGRAHINI', 'judul' => 'Analisis dan Perancangan Antarmuka Pengguna Sistem Bimbingan Skripsi', 'pembimbing1' => 'Muhaqiqin, S.Kom., M.T.I', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'Yunda Heningtyas, M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1440/UN26.17.06/Tema/2025'],
            ['npm' => '2257051015', 'nama' => 'Intan Maghfirah', 'judul' => 'Penerapan Metode Deep Learning pada Deteksi Objek Digital', 'pembimbing1' => 'BAMBANG HERMANTO, S.Kom., M.Cs', 'pembimbing2' => 'Dhella Amelia, M.Kom.', 'pembahas1' => 'Dr. Aristoteles, S.Si., M.Si', 'pembahas2' => '', 'nomor_sk' => 'No: 1441/UN26.17.06/Tema/2025'],
            ['npm' => '2257051019', 'nama' => 'Rini Puspita Wati', 'judul' => 'Sistem Prediksi Kelulusan Mahasiswa dengan Algoritma Naive Bayes', 'pembimbing1' => 'Dwi Sakethi, M.Kom.', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'RICO ANDRIAN, S.Si., M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1442/UN26.17.06/Tema/2025'],
            ['npm' => '2217051146', 'nama' => 'FERNANDA PRANATA', 'judul' => 'Pengembangan Game Edukasi Sejarah Lampung Menggunakan Unity', 'pembimbing1' => 'BAMBANG HERMANTO, S.Kom., M.Cs', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'FEBI EKA FEBRIANSYAH, M.T', 'pembahas2' => '', 'nomor_sk' => 'No: 1443/UN26.17.06/Tema/2025'],
            ['npm' => '2217051041', 'nama' => 'Safira Aulia', 'judul' => 'Sistem Keamanan Jaringan Komputer Menggunakan Honeypot', 'pembimbing1' => 'Tristiyanto, S.Kom., M.I.S., Ph.D', 'pembimbing2' => 'Muhaqiqin, S.Kom., M.T.I', 'pembahas1' => 'Rahman Taufik, M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1444/UN26.17.06/Tema/2025'],
            ['npm' => '2217051045', 'nama' => 'DEWI INTAN NABILA', 'judul' => 'Optimasi Rute Distribusi Menggunakan Algoritma Genetika', 'pembimbing1' => 'Dwi Sakethi, M.Kom.', 'pembimbing2' => 'Muhammad Ikhsan, S.Kom., M.Cs.', 'pembahas1' => 'Yunda Heningtyas, M.Kom', 'pembahas2' => '', 'nomor_sk' => 'No: 1445/UN26.17.06/Tema/2025'],
            ['npm' => '2217051018', 'nama' => 'Adilla Aulia Desriyanti', 'judul' => 'Pemanfaatan Natural Language Processing pada Chatbot Akademik', 'pembimbing1' => 'Tristiyanto, S.Kom., M.I.S., Ph.D', 'pembimbing2' => 'Yulya Muharmi, M.Kom.', 'pembahas1' => 'Dr. Aristoteles, S.Si., M.Si', 'pembahas2' => '', 'nomor_sk' => 'No: 1446/UN26.17.06/Tema/2025'],
            ['npm' => '22170511101', 'nama' => 'Rizki Mahesa', 'judul' => 'Pengembangan Aplikasi Monitoring Kinerja Dosen', 'pembimbing1' => 'Muhaqiqin, S.Kom., M.T.I', 'pembimbing2' => 'Ridho Sholehurrohman, M. Mat', 'pembahas1' => 'FEBI EKA FEBRIANSYAH, M.T', 'pembahas2' => '', 'nomor_sk' => 'No: 1447/UN26.17.06/Tema/2025']
        ];

        // Seed distribusi_mahasiswa table
        $insertQuery = "INSERT INTO distribusi_mahasiswa (npm, nama, judul_skripsi, pembimbing1, pembimbing2, pembahas1, pembahas2, nomor_sk) VALUES (:npm, :nama, :judul, :p1, :p2, :pb1, :pb2, :sk)";
        $stmtInsert = $pdo->prepare($insertQuery);
        foreach ($dummyList as $item) {
            $stmtInsert->execute([
                ':npm' => $item['npm'],
                ':nama' => $item['nama'],
                ':judul' => $item['judul'],
                ':p1' => $item['pembimbing1'],
                ':p2' => !empty($item['pembimbing2']) ? $item['pembimbing2'] : null,
                ':pb1' => $item['pembahas1'],
                ':pb2' => !empty($item['pembahas2']) ? $item['pembahas2'] : null,
                ':sk' => $item['nomor_sk']
            ]);
        }
    }
    */
} catch (PDOException $e) {
    // Fail silently
}

// Fetch from database
$cari = trim($_GET['cari'] ?? '');
$daftarMahasiswa = [];
try {
    if (!empty($cari)) {
        $sql = "SELECT dm.*, pj.id AS pengajuan_id FROM distribusi_mahasiswa dm
                LEFT JOIN (
                    SELECT DISTINCT ON (mahasiswa_npm) id, mahasiswa_npm 
                    FROM pengajuan_judul 
                    WHERE status = 'disetujui' 
                    ORDER BY mahasiswa_npm, id DESC
                ) pj ON REPLACE(pj.mahasiswa_npm, ' ', '') = REPLACE(dm.npm, ' ', '')
                WHERE dm.npm LIKE :cari 
                   OR dm.nama LIKE :cari 
                   OR dm.pembimbing1 LIKE :cari 
                   OR dm.pembimbing2 LIKE :cari 
                ORDER BY dm.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cari' => '%' . $cari . '%']);
        $daftarMahasiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT dm.*, pj.id AS pengajuan_id FROM distribusi_mahasiswa dm
                LEFT JOIN (
                    SELECT DISTINCT ON (mahasiswa_npm) id, mahasiswa_npm 
                    FROM pengajuan_judul 
                    WHERE status = 'disetujui' 
                    ORDER BY mahasiswa_npm, id DESC
                ) pj ON REPLACE(pj.mahasiswa_npm, ' ', '') = REPLACE(dm.npm, ' ', '')
                ORDER BY dm.created_at DESC";
        $stmt = $pdo->query($sql);
        $daftarMahasiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Fallback empty list
}

// Gather unique angkatans from $daftarMahasiswa
$daftar_angkatan = [];
foreach ($daftarMahasiswa as $m) {
    $angk = substr($m['npm'], 0, 2); // e.g. "22"
    if (strlen($angk) === 2 && is_numeric($angk)) {
        $daftar_angkatan[] = "20" . $angk; // e.g. "2022"
    }
}
$daftar_angkatan = array_unique($daftar_angkatan);
$daftar_angkatan = array_filter($daftar_angkatan);
sort($daftar_angkatan);

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<style>
    /* Card top green border */
    .card {
        border-top: 4px solid #69a86e;
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
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-right: none;
        border-radius: 4px 0 0 4px;
        font-size: 14px;
        outline: none;
        height: 38px;
        box-sizing: border-box;
    }

    .table-toolbar .search button {
        background: #4AA361;
        color: white;
        border: none;
        padding: 0 16px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        height: 38px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-toolbar .search button:hover {
        background: #3d8b51;
    }

    .btn-tambah {
        background: #4AA361;
        color: #ffffff;
        border: none;
        padding: 0 20px;
        border-radius: 4px;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
        height: 38px;
        box-sizing: border-box;
    }

    .btn-tambah:hover {
        background: #3d8b51;
        color: #ffffff;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 10px;
    }

    table.tabel-distribusi {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
        margin-top: 0;
    }

    table.tabel-distribusi thead th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
        border: none;
        text-align: left;
    }

    table.tabel-distribusi tbody td {
        padding: 12px 16px;
        font-size: 14px;
        border-bottom: 1px solid #eef0f3;
        vertical-align: middle;
    }

    table.tabel-distribusi tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    .aksi-group {
        display: flex;
        gap: 6px;
        justify-content: center;
    }

    .btn-aksi {
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 4px;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        font-size: 12px;
    }

    .btn-lihat { background: #7db8db; }
    .btn-lihat:hover { opacity: .9; }

    .btn-edit { background: #f2a13e; }
    .btn-edit:hover { opacity: .9; }

    .btn-hapus { background: #e05252; }
    .btn-hapus:hover { opacity: .9; }

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
        width: 100%;
    }
</style>

<div class="content">
    <div class="page-title">Daftar Distribusi Mahasiswa</div>

    <div class="card">
        <div class="table-toolbar">
            <div class="filter-angkatan-wrap">
                <select class="filter-angkatan" id="filterAngkatan">
                    <option value="">Filter Angkatan</option>
                    <?php foreach ($daftar_angkatan as $angkatan): ?>
                        <option value="<?= $angkatan ?>"><?= $angkatan ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <div class="search">
                    <input type="text" id="searchInput" placeholder="Cari Mahasiswa">
                    <button type="button" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                <a href="distribusi_mahasiswa_add.php" class="btn-tambah"><i class="fas fa-plus"></i> Tambah</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="tabel-distribusi">
            <thead>
                <tr>
                    <th style="width:120px;">NPM</th>
                    <th>Nama</th>
                    <th>Pembimbing 1</th>
                    <th>Pembimbing 2</th>
                    <th style="width:150px;">SK Penetapan</th>
                    <th class="text-center" style="width:110px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($daftarMahasiswa as $m): ?>
                <tr data-npm="<?= htmlspecialchars($m['npm']) ?>" 
                    data-nama="<?= htmlspecialchars(strtolower($m['nama'])) ?>" 
                    data-pembimbing1="<?= htmlspecialchars(strtolower($m['pembimbing1'])) ?>" 
                    data-pembimbing2="<?= htmlspecialchars(strtolower($m['pembimbing2'] ?? '')) ?>" 
                    data-angkatan="<?= htmlspecialchars("20" . substr($m['npm'], 0, 2)) ?>">
                    <td><?= htmlspecialchars($m['npm']) ?></td>
                    <td><?= htmlspecialchars($m['nama']) ?></td>
                    <td><?= htmlspecialchars($m['pembimbing1']) ?></td>
                    <td><?= htmlspecialchars($m['pembimbing2'] ?: '-') ?></td>
                    <td>
                        <?php 
                        $fullSk = $m['nomor_sk'];
                        $shortSk = strlen($fullSk) > 15 ? substr($fullSk, 0, 14) . '...' : $fullSk;
                        if (!empty($m['pengajuan_id'])): 
                        ?>
                            <a href="cetak_sk.php?id=<?= $m['pengajuan_id'] ?>" target="_blank" style="color: #285aa9; text-decoration: none; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;" title="<?= htmlspecialchars($fullSk) ?>">
                                <i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 14px;"></i>
                                <?= htmlspecialchars($shortSk) ?>
                            </a>
                        <?php else: ?>
                            <span style="font-family: monospace; font-weight: 600; color: #64748b; font-size: 13px;" title="<?= htmlspecialchars($fullSk) ?>"><?= htmlspecialchars($shortSk) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="aksi-group">
                            <button type="button" class="btn-aksi btn-lihat" title="Lihat Detail" onclick='bukaModalDetail(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="distribusi_mahasiswa_edit.php?npm=<?= urlencode($m['npm']) ?>" class="btn-aksi btn-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="../../controllers/DistribusiMahasiswaController.php?action=hapus_distribusi&npm=<?= urlencode($m['npm']) ?>" class="btn-aksi btn-hapus" title="Hapus" onclick="return confirm('Hapus distribusi mahasiswa ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

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

<!-- MODAL DETAIL DISTRIBUSI MAHASISWA -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Detail Distribusi Mahasiswa</h3>
            <button class="modal-close" onclick="closeAllModals()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-review">
                <div class="review-row">
                    <div class="review-label">Nama Mahasiswa</div>
                    <div class="review-value" id="view_nama">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">NPM</div>
                    <div class="review-value" id="view_npm">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">Judul Skripsi</div>
                    <div class="review-value" id="view_judul">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">Pembimbing 1</div>
                    <div class="review-value" id="view_pembimbing1">-</div>
                </div>
                <div class="review-row" id="row_pembimbing2">
                    <div class="review-label">Pembimbing 2</div>
                    <div class="review-value" id="view_pembimbing2">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">Pembahas 1</div>
                    <div class="review-value" id="view_pembahas1">-</div>
                </div>
                <div class="review-row" id="row_pembahas2">
                    <div class="review-label">Pembahas 2</div>
                    <div class="review-value" id="view_pembahas2">-</div>
                </div>
                <div class="review-row">
                    <div class="review-label">Nomor SK</div>
                    <div class="review-value" id="view_nomor_sk">-</div>
                </div>
                <div class="review-row" id="row_file_sk">
                    <div class="review-label">File SK</div>
                    <div class="review-value" id="view_file_sk">-</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeAllModals()">Tutup</button>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>',
        showConfirmButton: false,
        timer: 2000
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

<script>
    // Search + Filter + Pagination
    const searchInput      = document.getElementById('searchInput');
    const searchBtn        = document.getElementById('searchBtn');
    const filterAngkatan   = document.getElementById('filterAngkatan');
    const rowsPerPageSel   = document.getElementById('rowsPerPage');
    const tableBody        = document.getElementById('tableBody');
    const paginationInfo   = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentPage = 1;

    function getFilteredRows() {
        const keyword = searchInput.value.trim().toLowerCase();
        const angkatan = filterAngkatan.value;

        return allRows.filter(row => {
            const npm = row.dataset.npm.toLowerCase();
            const nama = row.dataset.nama;
            const p1 = row.dataset.pembimbing1;
            const p2 = row.dataset.pembimbing2;
            const rowAngkatan = row.dataset.angkatan;

            const matchKeyword = npm.includes(keyword) || 
                                 nama.includes(keyword) || 
                                 p1.includes(keyword) || 
                                 p2.includes(keyword);
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
            emptyRow.innerHTML = `<td colspan="6" style="text-align:center; color:#94a3b8; padding:22px !important;">Tidak ada data distribusi yang cocok.</td>`;
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
    filterAngkatan.addEventListener('change', () => { currentPage = 1; renderTable(); });
    rowsPerPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });

    renderTable();

    // Modal Action detail
    const modalOverlay = document.getElementById('modalOverlay');

    function bukaModalDetail(m) {
        document.getElementById('view_nama').textContent = m.nama;
        document.getElementById('view_npm').textContent = m.npm;
        document.getElementById('view_judul').textContent = m.judul_skripsi;
        document.getElementById('view_pembimbing1').textContent = m.pembimbing1;
        
        const rowP2 = document.getElementById('row_pembimbing2');
        if (m.pembimbing2 && m.pembimbing2.trim() !== '') {
            rowP2.style.display = 'grid';
            document.getElementById('view_pembimbing2').textContent = m.pembimbing2;
        } else {
            rowP2.style.display = 'none';
        }

        document.getElementById('view_pembahas1').textContent = m.pembahas1;

        const rowPb2 = document.getElementById('row_pembahas2');
        if (m.pembahas2 && m.pembahas2.trim() !== '') {
            rowPb2.style.display = 'grid';
            document.getElementById('view_pembahas2').textContent = m.pembahas2;
        } else {
            rowPb2.style.display = 'none';
        }

        document.getElementById('view_nomor_sk').textContent = m.nomor_sk;

        const rowFileSk = document.getElementById('row_file_sk');
        const viewFileSk = document.getElementById('view_file_sk');
        if (m.pengajuan_id) {
            rowFileSk.style.display = 'grid';
            viewFileSk.innerHTML = `<a href="cetak_sk.php?id=${m.pengajuan_id}" target="_blank" style="background: #eef4fb; color: #285aa9; border: 1px solid rgba(40, 90, 169, 0.2); padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; text-decoration: none; width: fit-content;"><i class="fa-solid fa-file-pdf" style="color: #dc2626;"></i> Cetak / Unduh SK PDF</a>`;
        } else {
            rowFileSk.style.display = 'none';
        }

        modalOverlay.style.display = 'flex';
    }

    function closeAllModals() {
        modalOverlay.style.display = 'none';
    }
</script>

<?php include '../layouts/footer.php'; ?>
