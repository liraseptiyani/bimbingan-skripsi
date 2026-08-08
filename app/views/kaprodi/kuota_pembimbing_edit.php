<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: " . BASE_URL . "/");
    exit;
}
$title = 'Edit Dosen';
require_once dirname(__DIR__, 3) . '/app/controllers/DosenController.php';

$nip = $_GET['nip'] ?? '';

// Fetch dosen details space-insensitively
$stmt = $pdo->prepare("SELECT * FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '')");
$stmt->execute([':nip' => $nip]);
$dosen = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dosen) {
    $_SESSION['swal_error'] = 'Dosen tidak ditemukan!';
    header("Location: kuota_pembimbing.php");
    exit;
}

// Fetch user's active otoritas space-insensitively
$stmtUser = $pdo->prepare("SELECT otoritas FROM users WHERE REPLACE(username, ' ', '') = REPLACE(:username, ' ', '')");
$stmtUser->execute([':username' => $nip]);
$userOtoritas = $stmtUser->fetchColumn() ?: 'dosen';

$listOtoritas    = ['Dosen', 'Kaprodi'];
$listUniversitas = ['Universitas Lampung'];
$listFakultas    = ['Fakultas Matematika dan Ilmu Pengetahuan Alam'];
$listProdi       = ['Ilmu Komputer', 'Sistem Informasi'];

include '../layouts/header.php';
include '../layouts/sidebar_kaprodi.php';
include '../layouts/topbar.php';
?>

<style>
    .page-title-back{
        display:flex;
        align-items:center;
        gap:12px;
        font-size:22px;
        font-weight:600;
        margin-bottom:20px;
    }

    .page-title-back a{ color:#285aa9; text-decoration:none; }

    .form-card{ max-width:900px; }

    .form-group{ margin-bottom:16px; }

    .form-group label{
        display:block;
        margin-bottom:6px;
        font-size:14px;
        font-weight:600;
        color:#285aa9;
    }

    .form-group label .req{ color:#e05252; }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select{
        width:100%;
        padding:10px 12px;
        border:1px solid #cccccc;
        border-radius:4px;
        font-size:14px;
        color:#333;
        outline:none;
    }

    .form-group input:focus,
    .form-group select:focus{ border-color:#285aa9; }

    .form-group input[type="file"]{
        width:100%;
        padding:8px;
        border:1px solid #cccccc;
        border-radius:4px;
        font-size:13px;
        background:#fafbfc;
    }

    .form-actions{
        display:flex;
        justify-content:flex-end;
        margin-top:22px;
    }

    .btn-submit{
        background:#285aa9;
        color:#ffffff;
        border:none;
        padding:11px 28px;
        border-radius:4px;
        font-size:14px;
        cursor:pointer;
    }

    .btn-submit:hover{ opacity:.9; }
</style>

<div class="content">
    <div class="page-title-back">
        <a href="kuota_pembimbing.php"><i class="fas fa-chevron-left"></i></a>
        <span>Edit Dosen</span>
    </div>

    <div class="card form-card">
        <form method="post" action="<?= BASE_URL ?>/app/controllers/DosenController.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_dosen">
            <input type="hidden" name="nip_lama" value="<?= htmlspecialchars($nip) ?>">

            <div class="form-group">
                <label>Nama <span class="req">*</span></label>
                <input type="text" name="nama" value="<?= htmlspecialchars($dosen['nama'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>NIP <span class="req">*</span></label>
                <input type="text" name="nip" value="<?= htmlspecialchars($dosen['nip'] ?? $nip) ?>" required>
            </div>

            <div class="form-group">
                <label>Bidang Ilmu <span class="req">*</span></label>
                <input type="text" name="bidang_ilmu" value="<?= htmlspecialchars($dosen['bidang_ilmu'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Otoritas <span class="req">*</span></label>
                <select name="otoritas" required>
                    <?php foreach ($listOtoritas as $o): ?>
                        <option value="<?= htmlspecialchars($o) ?>" <?= strtolower($o) === strtolower($userOtoritas) ? 'selected' : '' ?>><?= htmlspecialchars($o) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Universitas <span class="req">*</span></label>
                <select name="universitas" required>
                    <?php foreach ($listUniversitas as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" <?= $u === ($dosen['universitas'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Fakultas <span class="req">*</span></label>
                <select name="fakultas" required>
                    <?php foreach ($listFakultas as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= $f === ($dosen['fakultas'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Program Studi <span class="req">*</span></label>
                <select name="program_studi" required>
                    <?php foreach ($listProdi as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $p === ($dosen['prodi'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>



            <div class="form-group">
                <label>Kuota Bimbingan <span class="req">*</span></label>
                <input type="number" name="kuota_bimbingan" min="0" value="<?= (int)($dosen['kuota_max'] ?? 10) ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>'
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
