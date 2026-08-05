<?php
session_start();

// ==========================================================
// PROTEKSI HALAMAN: hanya akun ber-role dosen yang boleh akses
// (baik sedang beotoritas dosen maupun kaprodi)
// ==========================================================
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: " . BASE_URL . "/");
    exit;
}

$title = 'Dosen - Profil';

// otoritas AKTIF saat ini, bukan role (role selalu 'dosen')
$otoritas_aktif = $_SESSION['otoritas'] ?? 'dosen';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$username = $_SESSION['username'] ?? '';

// Fetch lecturer profile details from database
try {
    $stmtD = $pdo->prepare("SELECT * FROM dosen WHERE REPLACE(nip, ' ', '') = REPLACE(:nip, ' ', '') LIMIT 1");
    $stmtD->execute([':nip' => $username]);
    $dosenDb = $stmtD->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dosenDb = null;
}

$nama = $dosenDb['nama'] ?? ($_SESSION['nama'] ?? '');
$bidang_ilmu = $dosenDb['bidang_ilmu'] ?? 'Belum ditentukan';

$fotoPath = '';
if (!empty($dosenDb['profile_picture'])) {
    $fullPath = dirname(__DIR__, 3) . '/public/uploads/profile/' . $dosenDb['profile_picture'];
    if (file_exists($fullPath) && is_file($fullPath)) {
        $fotoPath = BASE_URL . '/public/uploads/profile/' . $dosenDb['profile_picture'];
    }
}

// Otoritas Kaprodi hanya dimiliki oleh akun Pak Tristiyanto (Kaprodi Ilmu Komputer)
$isKaprodiAccount = (
    strpos(strtolower($nama), 'tristiyanto') !== false ||
    strpos($username, '19810414') !== false ||
    ($_SESSION['otoritas'] ?? '') === 'kaprodi'
);

$daftar_otoritas = [
    'dosen' => 'Dosen Pembimbing',
];

if ($isKaprodiAccount) {
    $daftar_otoritas['kaprodi'] = 'Kepala Program Studi';
}

$profil = [
    'nama'        => $nama,
    'nip'         => $username,
    'bidang_ilmu' => $bidang_ilmu,
    'otoritas'    => $daftar_otoritas[$otoritas_aktif] ?? 'Dosen Pembimbing',
];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';

// sidebar mengikuti otoritas AKTIF
if ($otoritas_aktif === 'kaprodi') {
    require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';
} else {
    require_once __DIR__ . '/../layouts/sidebar_dosen.php';
}
?>

<style>
    /* ================= PROFIL DOSEN (khusus halaman ini) ================= */

    .profil-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: start;
    }

    .profil-left {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .card.table-card {
        border-top: 4px solid #69a86e;
    }

    .card-subtitle {
        font-size: 16px;
        font-weight: 600;
        color: #222;
        margin-bottom: 18px;
    }

    /* --- Foto profil --- */
    .avatar-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 22px;
    }

    .avatar-circle {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: #e2e5ea;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b6bcc6;
        font-size: 56px;
    }

    .avatar-label {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .avatar-label:hover .avatar-overlay {
        opacity: 1;
    }

    .avatar-overlay i {
        color: #ffffff;
        font-size: 26px;
    }

    /* --- Info profil (nama, nip, otoritas) --- */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-row {
        display: grid;
        grid-template-columns: 110px 1fr;
        align-items: center;
        background: #eef4fb;
        border-radius: 5px;
        padding: 11px 14px;
        font-size: 14px;
    }

    .info-row .info-label {
        color: #285aa9;
        font-weight: 600;
    }

    .info-row .info-value {
        color: #333;
    }

    /* --- Pilih otoritas --- */
    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        font-size: 13.5px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
    }

    .form-select,
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 5px;
        font-size: 14px;
        color: #333;
        background: #ffffff;
    }

    .form-select:focus,
    .form-input:focus {
        outline: none;
        border-color: #285aa9;
        box-shadow: 0 0 0 3px rgba(40, 90, 169, 0.12);
    }

    .btn-primary {
        background: #285aa9;
        color: #ffffff;
        border: none;
        padding: 11px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
        width: auto;
    }

    .btn-primary:hover {
        background: #1e4480;
    }

    /* --- Ubah kata sandi: dibungkus kotak biru seperti referensi --- */
    .password-box {
        border: 1.5px solid #4a8fe0;
        border-radius: 6px;
        padding: 18px;
        margin-bottom: 18px;
        background: #f8fbff;
    }

    .password-box .form-group:last-child {
        margin-bottom: 0;
    }

    .password-box label {
        color: #285aa9;
    }

    .alert-info-box {
        margin-top: 4px;
        font-size: 12.5px;
        color: #6b7280;
    }

    @media (max-width: 860px) {
        .profil-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content">

    <h1 class="page-title">Profil Saya</h1>

    <div class="profil-grid">

        <!-- ============ KOLOM KIRI: Info Profil + Pilih Otoritas ============ -->
        <div class="profil-left">

            <div class="card table-card">
                <div class="avatar-wrap">
                    <form action="<?= BASE_URL ?>/app/controllers/DosenController.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="ubah_foto">
                        <label class="avatar-label">
                            <?php if (!empty($fotoPath)): ?>
                                <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto Profil" style="width:130px; height:130px; border-radius:50%; object-fit:cover; border:2px solid #ddd; display:block;">
                            <?php else: ?>
                                <div class="avatar-circle">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-overlay">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <input type="file" name="foto_profile" accept="image/*" onchange="this.form.submit()" style="display: none;">
                        </label>
                    </form>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Nama</div>
                        <div class="info-value"><?= htmlspecialchars($profil['nama']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIP</div>
                        <div class="info-value"><?= htmlspecialchars($profil['nip']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Bidang Ilmu</div>
                        <div class="info-value"><?= htmlspecialchars($profil['bidang_ilmu']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Otoritas</div>
                        <div class="info-value"><?= htmlspecialchars($profil['otoritas']) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-subtitle">Pilih Otoritas</div>

                <form id="formOtoritas" action="<?= BASE_URL ?>/app/controllers/SwitchOtoritasController.php" method="POST">
                    <div class="form-group">
                        <select class="form-select" name="otoritas" id="otoritasSelect">
                            <?php foreach ($daftar_otoritas as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $otoritas_aktif === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">Simpan Otoritas</button>
                </form>
            </div>

        </div>

        <!-- ============ KOLOM KANAN: Ubah Kata Sandi ============ -->
        <div class="card table-card" style="border-top-color: #285aa9;">
            <div class="card-subtitle">
                <i class="fa-solid fa-key"></i> Ubah Kata Sandi
            </div>

            <form id="formPassword" action="<?= BASE_URL ?>/app/controllers/UbahPasswordController.php" method="POST">
                <div class="form-group">
                    <label for="password_lama">Kata Sandi Lama <span style="color: #ef4444;">*</span></label>
                    <input type="password" class="form-input" id="password_lama" name="password_lama" placeholder="Masukkan kata sandi lama" required>
                </div>

                <div class="form-group">
                    <label for="password_baru">Kata Sandi Baru <span style="color: #ef4444;">*</span></label>
                    <input type="password" class="form-input" id="password_baru" name="password_baru" placeholder="Masukkan kata sandi baru" required>
                </div>

                <div class="form-group">
                    <label for="password_konfirmasi">Konfirmasi Kata Sandi <span style="color: #ef4444;">*</span></label>
                    <input type="password" class="form-input" id="password_konfirmasi" name="password_konfirmasi" placeholder="Ulangi kata sandi baru" required>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 10px;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim
                </button>
            </form>
        </div>

    </div>

</div>

<script>
    // ==========================================================
    // formOtoritas: SUDAH terhubung ke SwitchOtoritasController.php,
    // submit apa adanya (tidak di-preventDefault lagi).
    //
    // formPassword: Terhubung ke UbahPasswordController.php via AJAX.
    // ==========================================================

    document.getElementById('formPassword').addEventListener('submit', function (e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);

        Swal.fire({
            title: 'Ubah Kata Sandi?',
            text: "Apakah Anda yakin ingin mengubah kata sandi akun Anda?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#285aa9',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= BASE_URL ?>/app/controllers/UbahPasswordController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Kata sandi akun Anda berhasil diperbarui.',
                            confirmButtonColor: '#285aa9'
                        });
                        form.reset();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message || 'Terjadi kesalahan sistem.',
                            confirmButtonColor: '#285aa9'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: 'Gagal terhubung dengan server.',
                        confirmButtonColor: '#285aa9'
                    });
                });
            }
        });
    });
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

</body>
</html>
