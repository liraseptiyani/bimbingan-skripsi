<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: " . BASE_URL . "/");
    exit;
}
$title = 'Tambah Distribusi Mahasiswa';

// Fetch all registered lecturers from database
$stmtDosen = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
$listDosen = $stmtDosen->fetchAll(PDO::FETCH_COLUMN);

// Fetch all registered students from database
$stmtMhs = $pdo->query("SELECT * FROM mahasiswa ORDER BY nama ASC");
$listMahasiswa = $stmtMhs->fetchAll(PDO::FETCH_ASSOC);

// Pre-generate select options for JavaScript to use when adding rows
$mhsOptionsHtml = '<option value="" disabled selected>Pilih Mahasiswa</option>';
foreach ($listMahasiswa as $m) {
    $mhsOptionsHtml .= '<option value="' . htmlspecialchars($m['npm'] . '|' . $m['nama']) . '">' . htmlspecialchars($m['nama']) . ' (' . htmlspecialchars($m['npm']) . ')</option>';
}

$p1OptionsHtml = '<option value="" disabled selected>Pilih Pembimbing 1</option>';
foreach ($listDosen as $namaDosen) {
    $p1OptionsHtml .= '<option value="' . htmlspecialchars($namaDosen) . '">' . htmlspecialchars($namaDosen) . '</option>';
}

$p2OptionsHtml = '<option value="" selected>Pilih Pembimbing 2 (Opsional)</option>';
foreach ($listDosen as $namaDosen) {
    $p2OptionsHtml .= '<option value="' . htmlspecialchars($namaDosen) . '">' . htmlspecialchars($namaDosen) . '</option>';
}

$pb1OptionsHtml = '<option value="" disabled selected>Pilih Pembahas 1</option>';
foreach ($listDosen as $namaDosen) {
    $pb1OptionsHtml .= '<option value="' . htmlspecialchars($namaDosen) . '">' . htmlspecialchars($namaDosen) . '</option>';
}

$pb2OptionsHtml = '<option value="" selected>Pilih Pembahas 2 (Opsional)</option>';
foreach ($listDosen as $namaDosen) {
    $pb2OptionsHtml .= '<option value="' . htmlspecialchars($namaDosen) . '">' . htmlspecialchars($namaDosen) . '</option>';
}

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<!-- Select2 CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .form-card {
        max-width: 100%;
        margin-top: 16px;
    }

    /* Table form styling aligned with judulTable style */
    .form-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    .form-table th {
        background: #34495e;
        color: #ffffff;
        font-weight: 600;
        padding: 12px 10px;
        font-size: 13.5px;
        text-align: left;
        border: 1px solid #cbd5e1;
    }

    .form-table td {
        padding: 10px 8px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    /* Table row inputs styling */
    .form-table select,
    .form-table input[type="text"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 13px;
        color: #333;
        outline: none;
        background: #ffffff;
        box-sizing: border-box;
    }

    .form-table select:focus,
    .form-table input[type="text"]:focus {
        border-color: #285aa9;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 22px;
        padding-top: 16px;
        border-top: 1px solid #eef0f3;
    }

    .form-actions-buttons {
        display: flex;
        gap: 10px;
    }

    .form-actions a,
    .form-actions button,
    .btn-action-trigger {
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .btn-kembali { background: #64748b; color: #ffffff; }
    .btn-kembali:hover { opacity: .9; color: #ffffff; }

    .btn-simpan { background: #3fae4e; color: #ffffff; }
    .btn-simpan:hover { opacity: .9; }

    .btn-tambah-baris { background: #e7eef9; color: #285aa9; border: 1px solid rgba(40, 90, 169, 0.2); }
    .btn-tambah-baris:hover { background: #285aa9; color: #ffffff; }

    .btn-hapus-baris {
        background: #fee2e2;
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.15);
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-hapus-baris:hover {
        background: #ef4444;
        color: #ffffff;
    }

    /* Custom Select2 premium overrides to match template */
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cccccc !important;
        border-radius: 4px !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #333 !important;
        font-size: 13px !important;
        padding-left: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 6px !important;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }
    .select2-results__option {
        font-size: 13px !important;
        padding: 8px 10px !important;
    }
    .select2-search__field {
        border: 1px solid #cccccc !important;
        border-radius: 4px !important;
        outline: none !important;
        padding: 6px 8px !important;
        font-size: 13px !important;
    }
</style>

<div class="content">
    <!-- Header with a clean back chevron next to the title -->
    <div style="display: flex; align-items: center; margin-bottom: 12px; gap: 12px;">
        <a href="distribusi_mahasiswa.php" style="color: #333; font-size: 18px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; transition: all 0.2s;" title="Kembali ke Daftar" onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div>
            <h1 class="page-title" style="margin: 0; display: inline-block;">Tambah Distribusi Mahasiswa</h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Plotting pembimbing dan pembahas skripsi secara massal/sekaligus untuk beberapa mahasiswa.</p>
        </div>
    </div>

    <div class="card form-card">
        <form method="post" action="../../controllers/DistribusiMahasiswaController.php">
            <input type="hidden" name="action" value="tambah_distribusi">

            <div style="overflow-x: auto;">
                <table class="form-table" id="plottingTable">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">No</th>
                            <th style="width: 220px;">Mahasiswa *</th>
                            <th>Judul Skripsi (Opsional)</th>
                            <th style="width: 190px;">Pembimbing 1 *</th>
                            <th style="width: 190px;">Pembimbing 2</th>
                            <th style="width: 190px;">Pembahas 1 *</th>
                            <th style="width: 190px;">Pembahas 2</th>
                            <th style="width: 60px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="plottingTableBody">
                        <!-- Initial Row -->
                        <tr>
                            <td class="row-number" style="text-align: center; font-weight: 600;">1</td>
                            <td>
                                <select name="npm[]" required class="searchable-select">
                                    <?= $mhsOptionsHtml ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="judul_skripsi[]" placeholder="Belum ada judul / isi jika ada">
                            </td>
                            <td>
                                <select name="pembimbing1[]" required class="searchable-select">
                                    <?= $p1OptionsHtml ?>
                                </select>
                            </td>
                            <td>
                                <select name="pembimbing2[]" class="searchable-select">
                                    <?= $p2OptionsHtml ?>
                                </select>
                            </td>
                            <td>
                                <select name="pembahas1[]" required class="searchable-select">
                                    <?= $pb1OptionsHtml ?>
                                </select>
                            </td>
                            <td>
                                <select name="pembahas2[]" class="searchable-select">
                                    <?= $pb2OptionsHtml ?>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-hapus-baris" onclick="hapusBaris(this)" title="Hapus Baris">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <div>
                    <button type="button" class="btn-action-trigger btn-tambah-baris" onclick="tambahBaris()">
                        <i class="fa-solid fa-circle-plus"></i> Tambah Mahasiswa
                    </button>
                </div>
                <div class="form-actions-buttons">
                    <a href="distribusi_mahasiswa.php" class="btn-kembali"><i class="fas fa-chevron-left"></i> Kembali</a>
                    <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Distribusi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- jQuery and Select2 JS CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // HTML blocks generated in PHP
    const mhsOptions = `<?= $mhsOptionsHtml ?>`;
    const p1Options = `<?= $p1OptionsHtml ?>`;
    const p2Options = `<?= $p2OptionsHtml ?>`;
    const pb1Options = `<?= $pb1OptionsHtml ?>`;
    const pb2Options = `<?= $pb2OptionsHtml ?>`;

    // Initialize Select2 on document ready
    $(document).ready(function() {
        $('.searchable-select').select2({
            width: '100%',
            dropdownAutoWidth: true
        });
    });

    function tambahBaris() {
        const tbody = document.getElementById('plottingTableBody');
        if (tbody.rows.length >= 100) {
            Swal.fire({
                icon: 'warning',
                title: 'Batas Maksimum',
                text: 'Demi kestabilan server, batas maksimal penginputan adalah 100 mahasiswa sekali simpan.'
            });
            return;
        }
        const nextNum = tbody.rows.length + 1;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="row-number" style="text-align: center; font-weight: 600;">${nextNum}</td>
            <td>
                <select name="npm[]" required class="searchable-select">
                    ${mhsOptions}
                </select>
            </td>
            <td>
                <input type="text" name="judul_skripsi[]" placeholder="Belum ada judul / isi jika ada">
            </td>
            <td>
                <select name="pembimbing1[]" required class="searchable-select">
                    ${p1Options}
                </select>
            </td>
            <td>
                <select name="pembimbing2[]" class="searchable-select">
                    ${p2Options}
                </select>
            </td>
            <td>
                <select name="pembahas1[]" required class="searchable-select">
                    ${pb1Options}
                </select>
            </td>
            <td>
                <select name="pembahas2[]" class="searchable-select">
                    ${pb2Options}
                </select>
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn-hapus-baris" onclick="hapusBaris(this)" title="Hapus Baris">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        updateRowNumbers();

        // Initialize Select2 on the newly added select elements
        $(tr).find('.searchable-select').select2({
            width: '100%',
            dropdownAutoWidth: true
        });
    }

    function hapusBaris(button) {
        const tbody = document.getElementById('plottingTableBody');
        if (tbody.rows.length === 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Harus menyisakan minimal satu baris plotting!'
            });
            return;
        }
        
        // Destroy select2 instances first before removing row to prevent memory leaks
        const row = button.closest('tr');
        $(row).find('.searchable-select').select2('destroy');
        
        row.remove();
        updateRowNumbers();
    }

    function updateRowNumbers() {
        const tbody = document.getElementById('plottingTableBody');
        Array.from(tbody.rows).forEach((tr, index) => {
            tr.querySelector('.row-number').textContent = index + 1;
        });
    }
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
