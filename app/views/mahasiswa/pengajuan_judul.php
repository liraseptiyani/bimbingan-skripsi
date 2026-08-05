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

$title = "Mahasiswa - Formulir Pengajuan Judul";

$isMahasiswaAccount = (($_SESSION['role'] ?? '') === 'mahasiswa');
$npmMhs  = $isMahasiswaAccount ? ($_SESSION['username'] ?? '2217051151') : '2217051151';

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

// Fetch all dosen for proposals
try {
    $stmtD = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
    $daftar_dosen = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_dosen = [];
}

// Check if student has a pending submission
$hasPending = false;
try {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_judul WHERE mahasiswa_npm = :npm AND status = 'menunggu'");
    $stmtCheck->execute([':npm' => $npmMhs]);
    $hasPending = ((int)$stmtCheck->fetchColumn() > 0);
} catch (PDOException $e) {}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_mahasiswa.php';
?>

<style>
    .card.guideline-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
        border-top: 4px solid #285aa9;
    }

    .step-timeline {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 18px;
        position: relative;
    }

    .step-timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 10px;
        bottom: 10px;
        width: 2px;
        background: #e2e8f0;
    }

    .step-item {
        display: flex;
        gap: 16px;
        position: relative;
        align-items: flex-start;
    }

    .step-number {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #e7eef9;
        color: #285aa9;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        z-index: 1;
    }

    .step-item.active .step-number {
        background: #285aa9;
        color: #ffffff;
    }

    .step-content {
        padding-top: 6px;
    }

    .step-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .step-desc {
        font-size: 13.5px;
        color: #64748b;
        line-height: 1.5;
    }

    /* Form Card */
    .card.form-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        border-top: 4px solid #69a86e;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        color: #334155;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }

    .form-group input[type="file"] {
        padding: 8px 10px;
        background: #f8fafc;
        cursor: pointer;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #285aa9;
    }

    .btn-submit {
        background: #285aa9;
        color: #ffffff;
        border: none;
        padding: 11px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
    }

    .btn-submit:hover:not(:disabled) {
        background: #1e4480;
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .warning-pending {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 16px;
        color: #b45309;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .warning-pending i {
        font-size: 20px;
    }
</style>

<div class="content">

    <div style="margin-bottom: 24px;">
        <h1 class="page-title" style="margin: 0;">Pengajuan Judul Skripsi</h1>
    </div>

    <!-- PETUNJUK ALUR -->
    <div class="card guideline-card">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-circle-info" style="color: #285aa9;"></i>
            Alur Pengusulan Judul Skripsi Mandiri
        </h3>
        
        <div class="step-timeline">
            
            <div class="step-item">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Verifikasi Kelayakan Berkas Fisik</div>
                    <div class="step-desc">
                        Pastikan semua dokumen kelengkapan fisik Anda telah diperiksa dan disetujui oleh Loket Jurusan Ilmu Komputer.
                    </div>
                </div>
            </div>

            <div class="step-item active">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Pengunggahan Persyaratan Digital & Usulan Judul</div>
                    <div class="step-desc">
                        Lengkapi semua berkas scan PDF/DOCX yang wajib di bawah ini dan input rencana judul usulan Anda.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- FORM PENDAFTARAN LOKAL -->
    <div class="card form-card">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-pen-to-square" style="color: #69a86e;"></i>
            Form Pengusulan Judul & Berkas Persyaratan
        </h3>

        <?php if ($hasPending): ?>
            <div class="warning-pending">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Perhatian!</strong> Anda memiliki pengajuan judul yang saat ini sedang menunggu persetujuan Kaprodi. Silakan pantau perkembangannya pada menu <strong>Riwayat Pengajuan</strong>. Anda tidak dapat mengirim usulan baru sampai usulan saat ini diproses.
                </div>
            </div>
        <?php endif; ?>

        <form id="formRegistrasiJudul" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajukan">
            
            <div class="form-grid">
                <!-- TEXT INPUTS -->
                <div class="form-group">
                    <label for="judul">Judul <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="judul" name="judul" required placeholder="Tuliskan judul skripsi utama yang diajukan" <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="judul_alternatif">Judul Alternatif</label>
                    <input type="text" id="judul_alternatif" name="judul_alternatif" placeholder="Tuliskan judul alternatif jika ada" <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <!-- FILE INPUTS -->
                <div class="form-group">
                    <label for="file_transkrip">Transkrip Akademik <span style="color: #ef4444;">* (Format PDF - Min 100 SKS & IPK > 2.00)</span></label>
                    <input type="file" id="file_transkrip" name="file_transkrip" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_ktm">KTM <span style="color: #ef4444;">* (Format PDF)</span></label>
                    <input type="file" id="file_ktm" name="file_ktm" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_form_tema">Form Pengajuan Tema <span style="color: #ef4444;">* (Format PDF)</span></label>
                    <input type="file" id="file_form_tema" name="file_form_tema" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_bukti_ukt">Bukti Pembayaran UKT dari Semester 1 <span style="color: #ef4444;">* (Format PDF)</span></label>
                    <input type="file" id="file_bukti_ukt" name="file_bukti_ukt" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_krs_terakhir">KRS Terakhir <span style="color: #ef4444;">* (Format PDF)</span></label>
                    <input type="file" id="file_krs_terakhir" name="file_krs_terakhir" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_form_verifikasi">Form Verifikasi Berkas <span style="color: #ef4444;">* (Format PDF)</span></label>
                    <input type="file" id="file_form_verifikasi" name="file_form_verifikasi" accept="application/pdf" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_bukti_acc">Bukti Acc Judul dengan Dosen Pembimbing (jika ada) <span style="color: #ef4444;">(Format PDF)</span></label>
                    <input type="file" id="file_bukti_acc" name="file_bukti_acc" accept="application/pdf" <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_form_penetapan">Form Penetapan Tema Penelitian <span style="color: #ef4444;">* (Format DOCX)</span></label>
                    <input type="file" id="file_form_penetapan" name="file_form_penetapan" accept=".docx,.doc,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_bab1">Halaman Judul & Bab 1 (Utama) <span style="color: #ef4444;">* (Format DOCX)</span></label>
                    <input type="file" id="file_bab1" name="file_bab1" accept=".docx,.doc,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required <?= $hasPending ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="file_bab1_alt">Halaman Judul & Bab 1 (Alternatif) <span style="color: #ef4444;">(Format DOCX)</span></label>
                    <input type="file" id="file_bab1_alt" name="file_bab1_alt" accept=".docx,.doc,application/vnd.openxmlformats-officedocument.wordprocessingml.document" <?= $hasPending ? 'disabled' : '' ?>>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-submit" id="btnSubmitForm" <?= $hasPending ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-paper-plane"></i> Kirim Usulan Judul & Berkas
                </button>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const formRegistrasiJudul = document.getElementById('formRegistrasiJudul');

    if (formRegistrasiJudul) {
        formRegistrasiJudul.onsubmit = function(e) {
            e.preventDefault();

            const formData = new FormData(formRegistrasiJudul);

            Swal.fire({
                title: 'Kirim Usulan Judul?',
                text: "Pastikan seluruh berkas persyaratan wajib telah diunggah dengan benar!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#285aa9',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const btnSubmit = document.getElementById('btnSubmitForm');
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';

                    fetch('<?= BASE_URL ?>/app/controllers/PengajuanJudulController.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Usulan Judul & Berkas';

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pengiriman Berhasil',
                                text: 'Judul dan berkas persyaratan Anda berhasil dikirim ke Kaprodi.',
                                confirmButtonColor: '#285aa9'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Pendaftaran Gagal',
                                text: data.message || 'Terjadi kesalahan sistem.',
                                confirmButtonColor: '#285aa9'
                            });
                        }
                    })
                    .catch(err => {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Usulan Judul & Berkas';

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
</script>

</body>
</html>
