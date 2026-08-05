<?php
session_start();

// ==========================================================
// PROTEKSI HALAMAN: hanya akun ber-role mahasiswa yang boleh akses
// ==========================================================
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'mahasiswa') {
    header("Location: " . BASE_URL . "/");
    exit;
}

$title = 'Mahasiswa - Profil';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$npmMhs = $_SESSION['username'] ?? '';
$namaMhs = $_SESSION['nama'] ?? '';

// Extract Angkatan from NPM (e.g. 2217051151 -> Angkatan 2022)
$prefix = substr(trim($npmMhs), 0, 2);
$angkatanMhs = is_numeric($prefix) ? '20' . $prefix : '2022';

// Semester logic mapping
$semesterMhs = 8;
if ($angkatanMhs == '2020') $semesterMhs = 12;
elseif ($angkatanMhs == '2021') $semesterMhs = 10;
elseif ($angkatanMhs == '2022') $semesterMhs = 8;
elseif ($angkatanMhs == '2023') $semesterMhs = 6;
elseif ($angkatanMhs == '2024') $semesterMhs = 4;
elseif ($angkatanMhs == '2025') $semesterMhs = 2;

// Fetch active profile picture
$fotoPath = '';
try {
    $stmtM = $pdo->prepare("SELECT profile_picture FROM mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmtM->execute([':npm' => $npmMhs]);
    $mhsDb = $stmtM->fetch(PDO::FETCH_ASSOC);
    if (!empty($mhsDb['profile_picture'])) {
        $fullPath = dirname(__DIR__, 3) . '/public/uploads/profile/' . $mhsDb['profile_picture'];
        if (file_exists($fullPath) && is_file($fullPath)) {
            $fotoPath = BASE_URL . '/public/uploads/profile/' . $mhsDb['profile_picture'];
        }
    }
} catch (PDOException $e) {}

// Fetch active thesis title, advisors, and examiners from distribusi_mahasiswa
$judulSkripsi = '-';
$pembimbing1 = '-';
$pembimbing2 = '-';
$pembahas = '-';
try {
    $stmt = $pdo->prepare("SELECT * FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '') LIMIT 1");
    $stmt->execute([':npm' => $npmMhs]);
    $dist = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dist) {
        $judulSkripsi = $dist['judul_skripsi'] ?: '-';
        $pembimbing1 = $dist['pembimbing1'] ?: '-';
        $pembimbing2 = $dist['pembimbing2'] ?: '-';
        
        $p1 = $dist['pembahas1'] ?? '';
        $p2 = $dist['pembahas2'] ?? '';
        if (!empty($p1) && !empty($p2)) {
            $pembahas = $p1 . ' / ' . $p2;
        } elseif (!empty($p1)) {
            $pembahas = $p1;
        } elseif (!empty($p2)) {
            $pembahas = $p2;
        } else {
            $pembahas = '-';
        }
    }
} catch (PDOException $e) {}

// Dynamic Initials Avatar
$words = explode(' ', trim($namaMhs));
$initials = '';
$count = 0;
foreach ($words as $w) {
    if (!empty($w) && $count < 2) {
        $initials .= strtoupper($w[0]);
        $count++;
    }
}

$profil = [
    'nama'         => $namaMhs,
    'npm'          => $npmMhs,
    'pembimbing1'  => $pembimbing1,
    'pembimbing2'  => $pembimbing2,
    'pembahas'     => $pembahas,
    'judul'        => $judulSkripsi
];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_mahasiswa.php';
?>

<style>
    /* ================= PROFIL MAHASISWA (khusus halaman ini) ================= */

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

    /* --- Info profil (nama, npm, dsb) --- */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-row {
        display: grid;
        grid-template-columns: 140px 1fr;
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

    /* --- Form sandi --- */
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

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 5px;
        font-size: 14px;
        color: #333;
        background: #ffffff;
    }

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

    @media (max-width: 860px) {
        .profil-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="content">

    <h1 class="page-title">Profil Saya</h1>

    <div class="profil-grid">

        <!-- ============ KOLOM KIRI: Informasi Profil Lengkap ============ -->
        <div class="card table-card">
            <div class="avatar-wrap">
                <form action="<?= BASE_URL ?>/app/controllers/MahasiswaController.php" method="POST" enctype="multipart/form-data">
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
                    <div class="info-label">NPM</div>
                    <div class="info-value"><?= htmlspecialchars($profil['npm']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pembimbing 1</div>
                    <div class="info-value"><?= htmlspecialchars($profil['pembimbing1']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pembimbing 2</div>
                    <div class="info-value"><?= htmlspecialchars($profil['pembimbing2']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Dosen Pembahas</div>
                    <div class="info-value"><?= htmlspecialchars($profil['pembahas']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Judul Skripsi</div>
                    <div class="info-value" style="font-style: italic;"><?= htmlspecialchars($profil['judul']) ?></div>
                </div>
            </div>
        </div>

        <!-- ============ KOLOM KANAN: Ubah Kata Sandi ============ -->
        <div class="card table-card" style="border-top-color: #285aa9;">
            <div class="card-subtitle">
                <i class="fa-solid fa-key"></i> Ubah Kata Sandi
            </div>

            <form id="formPassword">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('formPassword').addEventListener('submit', function (e) {
        e.preventDefault();
        
        const form = this;
        const passwordBaru = document.getElementById('password_baru').value;
        const passwordKonfirmasi = document.getElementById('password_konfirmasi').value;

        if (passwordBaru !== passwordKonfirmasi) {
            Swal.fire({
                icon: 'warning',
                title: 'Kata Sandi Tidak Cocok',
                text: 'Konfirmasi kata sandi baru Anda tidak cocok!',
                confirmButtonColor: '#285aa9'
            });
            return;
        }

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
                const btnSubmit = form.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

                fetch('<?= BASE_URL ?>/app/controllers/UbahPasswordController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim';

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
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim';

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

<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: '<?= htmlspecialchars($_SESSION['swal_success']) ?>',
        confirmButtonColor: '#285aa9'
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

<?php if (isset($_SESSION['swal_error'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '<?= htmlspecialchars($_SESSION['swal_error']) ?>',
        confirmButtonColor: '#285aa9'
    });
</script>
<?php unset($_SESSION['swal_error']); endif; ?>

</body>
</html>
