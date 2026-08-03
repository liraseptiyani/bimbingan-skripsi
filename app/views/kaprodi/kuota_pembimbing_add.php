<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen' || ($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: /bimbingan-skripsi/");
    exit;
}
$title = 'Tambah Dosen';

// ================== DUMMY DATA DROPDOWN (sementara) ==================
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
    .form-group input[type="password"],
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
        <span>Tambah Dosen</span>
    </div>

    <div class="card form-card">
        <form method="post" action="../../controllers/DosenController.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="tambah_dosen">

            <div class="form-group">
                <label>Nama <span class="req">*</span></label>
                <input type="text" name="nama" required>
            </div>

            <div class="form-group">
                <label>NIP <span class="req">*</span></label>
                <input type="text" name="nip" required>
            </div>

            <div class="form-group">
                <label>Bidang Ilmu <span class="req">*</span></label>
                <input type="text" name="bidang_ilmu" required>
            </div>

            <div class="form-group">
                <label>Password <span class="req">*</span></label>
                <div style="position: relative;">
                    <input type="password" name="password" id="inputPassword" required style="padding-right: 40px;">
                    <i class="fa-solid fa-eye-slash" onclick="togglePasswordVisibility('inputPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777;"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password <span class="req">*</span></label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" id="inputConfirmPassword" required style="padding-right: 40px;">
                    <i class="fa-solid fa-eye-slash" onclick="togglePasswordVisibility('inputConfirmPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777;"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Otoritas <span class="req">*</span></label>
                <select name="otoritas" required>
                    <option value="" disabled selected>Pilih Otoritas</option>
                    <?php foreach ($listOtoritas as $o): ?>
                        <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Universitas <span class="req">*</span></label>
                <select name="universitas" required>
                    <option value="" disabled>Pilih Universitas</option>
                    <?php foreach ($listUniversitas as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" selected><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Fakultas <span class="req">*</span></label>
                <select name="fakultas" required>
                    <option value="" disabled selected>Pilih Fakultas</option>
                    <?php foreach ($listFakultas as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Program Studi <span class="req">*</span></label>
                <select name="program_studi" required>
                    <option value="" disabled selected>Pilih Program Studi</option>
                    <?php foreach ($listProdi as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Foto Profile</label>
                <input type="file" name="foto_profile" accept="image/*">
            </div>

            <div class="form-group">
                <label>Kuota Bimbingan <span class="req">*</span></label>
                <input type="number" name="kuota_bimbingan" min="0" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Register</button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePasswordVisibility(fieldId, iconEl) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            iconEl.classList.remove('fa-eye-slash');
            iconEl.classList.add('fa-eye');
        } else {
            field.type = 'password';
            iconEl.classList.remove('fa-eye');
            iconEl.classList.add('fa-eye-slash');
        }
    }
</script>
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
