<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: " . BASE_URL . "/");
    exit;
}
$title = 'Edit Distribusi Mahasiswa';

$npm = $_GET['npm'] ?? '';
$mhs = null;
$listDosen = [];

// Fetch all registered lecturers from database
try {
    $stmtDosen = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
    $listDosen = $stmtDosen->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fail silently
}

if (!empty($npm)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE npm = :npm");
        $stmt->execute([':npm' => $npm]);
        $mhs = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fail silently
    }
}

if (!$mhs) {
    $_SESSION['swal_error'] = 'Data mahasiswa tidak ditemukan!';
    header("Location: distribusi_mahasiswa.php");
    exit;
}

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<!-- Select2 CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .form-card { max-width: 950px; }

    .form-group {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 16px;
    }

    .form-group label {
        width: 160px;
        flex-shrink: 0;
        font-size: 14px;
        font-weight: 600;
        color: #285aa9;
        padding-top: 10px;
    }

    .form-group label .req { color: #e05252; }

    .form-group input[type="text"],
    .form-group select {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 14px;
        color: #333;
        outline: none;
        background: #ffffff;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus { border-color: #285aa9; }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
        padding-top: 16px;
        border-top: 1px solid #eef0f3;
    }

    .form-actions a,
    .form-actions button {
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-kembali { background: #64748b; color: #ffffff; }
    .btn-kembali:hover { opacity: .9; color: #ffffff; }

    .btn-simpan { background: #3fae4e; color: #ffffff; }
    .btn-simpan:hover { opacity: .9; }

    /* Custom Select2 premium overrides */
    .select2-container {
        flex: 1 !important;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cccccc !important;
        border-radius: 4px !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #333 !important;
        font-size: 13.5px !important;
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
        font-size: 13.5px !important;
        padding: 8px 10px !important;
    }
    .select2-search__field {
        border: 1px solid #cccccc !important;
        border-radius: 4px !important;
        outline: none !important;
        padding: 6px 8px !important;
        font-size: 13.5px !important;
    }
</style>

<div class="content">
    <!-- Header with a clean back chevron next to the title -->
    <div style="display: flex; align-items: center; margin-bottom: 20px; gap: 12px;">
        <a href="distribusi_mahasiswa.php" style="color: #333; font-size: 18px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; transition: all 0.2s;" title="Kembali ke Daftar" onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div>
            <h1 class="page-title" style="margin: 0; display: inline-block;">Edit Distribusi Mahasiswa</h1>
        </div>
    </div>

    <div class="card form-card">
        <form method="post" action="<?= BASE_URL ?>/app/controllers/DistribusiMahasiswaController.php">
            <input type="hidden" name="action" value="edit_distribusi">
            <input type="hidden" name="npm_lama" value="<?= htmlspecialchars($npm) ?>">

            <div class="form-group">
                <label>Nama <span class="req">*</span></label>
                <input type="text" name="nama" value="<?= htmlspecialchars($mhs['nama']) ?>" required>
            </div>

            <div class="form-group">
                <label>NPM <span class="req">*</span></label>
                <input type="text" name="npm" value="<?= htmlspecialchars($npm) ?>" required>
            </div>

            <div class="form-group">
                <label>Judul Skripsi</label>
                <input type="text" name="judul_skripsi" value="<?= htmlspecialchars($mhs['judul_skripsi'] ?? '') ?>" placeholder="Belum ada judul / isi jika ada">
            </div>

            <div class="form-group">
                <label>Pembimbing 1 <span class="req">*</span></label>
                <select name="pembimbing1" required class="searchable-select">
                    <option value="" disabled>Pilih Pembimbing 1</option>
                    <?php foreach ($listDosen as $namaDosen): ?>
                        <option value="<?= htmlspecialchars($namaDosen) ?>" <?= ($mhs['pembimbing1'] ?? '') === $namaDosen ? 'selected' : '' ?>><?= htmlspecialchars($namaDosen) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pembimbing 2</label>
                <select name="pembimbing2" class="searchable-select">
                    <option value="">Pilih Pembimbing 2 (Optional)</option>
                    <?php foreach ($listDosen as $namaDosen): ?>
                        <option value="<?= htmlspecialchars($namaDosen) ?>" <?= ($mhs['pembimbing2'] ?? '') === $namaDosen ? 'selected' : '' ?>><?= htmlspecialchars($namaDosen) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pembahas 1 <span class="req">*</span></label>
                <select name="pembahas1" required class="searchable-select">
                    <option value="" disabled>Pilih Pembahas 1</option>
                    <?php foreach ($listDosen as $namaDosen): ?>
                        <option value="<?= htmlspecialchars($namaDosen) ?>" <?= ($mhs['pembahas1'] ?? '') === $namaDosen ? 'selected' : '' ?>><?= htmlspecialchars($namaDosen) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Pembahas 2</label>
                <select name="pembahas2" class="searchable-select">
                    <option value="">Pilih Pembahas 2 (Boleh Kosong)</option>
                    <?php foreach ($listDosen as $namaDosen): ?>
                        <option value="<?= htmlspecialchars($namaDosen) ?>" <?= ($mhs['pembahas2'] ?? '') === $namaDosen ? 'selected' : '' ?>><?= htmlspecialchars($namaDosen) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nomor SK <span class="req">*</span></label>
                <input type="text" name="nomor_sk" value="<?= htmlspecialchars($mhs['nomor_sk'] ?? '') ?>" required>
            </div>

            <div class="form-actions">
                <a href="distribusi_mahasiswa.php" class="btn-kembali"><i class="fas fa-chevron-left"></i> Kembali ke daftar</a>
                <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- jQuery and Select2 JS CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.searchable-select').select2({
            width: '100%',
            dropdownAutoWidth: true
        });
    });
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
