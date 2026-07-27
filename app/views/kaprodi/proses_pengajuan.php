<?php
session_start();

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    header("Location: /bimbingan-skripsi/");
    exit;
}

if (($_SESSION['otoritas'] ?? '') !== 'kaprodi') {
    header("Location: /bimbingan-skripsi/app/views/dosen/dashboard.php");
    exit;
}

require_once dirname(__DIR__, 3) . '/config/koneksi.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: /bimbingan-skripsi/app/views/kaprodi/pengajuan_judul.php");
    exit;
}

// Fetch submission details
try {
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_judul WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$p || $p['status'] !== 'menunggu') {
        header("Location: /bimbingan-skripsi/app/views/kaprodi/pengajuan_judul.php");
        exit;
    }
} catch (PDOException $e) {
    header("Location: /bimbingan-skripsi/app/views/kaprodi/pengajuan_judul.php");
    exit;
}

// Fetch list of dosen for dropdowns
try {
    $stmtD = $pdo->query("SELECT nama FROM dosen ORDER BY nama ASC");
    $daftar_dosen = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_dosen = [];
}

$title = "Proses Pengajuan Judul";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/topbar.php';
require_once __DIR__ . '/../layouts/sidebar_kaprodi.php';

function renderDocCard($filename, $label, $isDocx = false) {
    if (!$filename) {
        return '
        <div class="doc-card" style="border-left: 4px solid #ef4444;">
            <div class="doc-info">
                <div class="doc-icon ' . ($isDocx ? 'word' : 'pdf') . '">
                    <i class="fa-solid ' . ($isDocx ? 'fa-file-word' : 'fa-file-pdf') . '"></i>
                </div>
                <div class="doc-meta">
                    <span class="doc-name">' . htmlspecialchars($label) . '</span>
                    <span class="doc-desc" style="color: #ef4444; font-style: italic; font-weight: 500;">Berkas belum diunggah / opsional kosong</span>
                </div>
            </div>
        </div>';
    }
    
    $files = explode(',', $filename);
    $output = '';
    foreach ($files as $index => $file) {
        $fileClean = trim($file);
        $path = "/bimbingan-skripsi/public/uploads/persyaratan/" . $fileClean;
        $fileExt = strtolower(pathinfo($fileClean, PATHINFO_EXTENSION));
        $fileIsDocx = in_array($fileExt, ['docx', 'doc']);
        $iconClass = $fileIsDocx ? 'word' : 'pdf';
        $icon = $fileIsDocx ? 'fa-file-word' : 'fa-file-pdf';
        $itemLabel = htmlspecialchars($label) . (count($files) > 1 ? ' [' . ($index + 1) . ']' : '');
        
        $output .= '
        <div class="doc-card" style="border-left: 4px solid ' . ($fileIsDocx ? '#3b82f6' : '#ef4444') . ';">
            <div class="doc-info">
                <div class="doc-icon ' . $iconClass . '">
                    <i class="fa-solid ' . $icon . '"></i>
                </div>
                <div class="doc-meta">
                    <span class="doc-name">' . $itemLabel . '</span>
                    <span class="doc-desc">' . htmlspecialchars($fileClean) . '</span>
                </div>
            </div>
            <a href="' . $path . '" target="_blank" class="btn-view-doc">
                <i class="fa-solid fa-eye"></i> Tinjau
            </a>
        </div>';
    }
    return $output;
}
?>

<style>
    .page-title-back {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 600;
        color: #222;
        text-decoration: none;
        transition: color 0.2s;
    }
    .page-title-back i {
        font-size: 18px;
        color: #444;
        transition: color 0.2s;
    }
    .page-title-back:hover {
        color: #285aa9;
    }
    .page-title-back:hover i {
        color: #285aa9;
    }
    
    .layout-grid {
        display: grid;
        grid-template-columns: 1.3fr 0.7fr;
        gap: 24px;
        align-items: start;
        margin-bottom: 40px;
    }

    .sub-grid-top {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    
    @media (max-width: 1024px) {
        .layout-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .sub-grid-top {
            grid-template-columns: 1fr !important;
        }
    }
    
    .card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        padding: 24px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title i {
        color: #285aa9;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .detail-label {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 500;
    }
    .detail-value {
        font-size: 14.5px;
        color: #1e293b;
        font-weight: 600;
    }
    
    .doc-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .doc-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .doc-card:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
    .doc-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .doc-icon {
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
    }
    .doc-icon.pdf {
        background: #fef2f2;
        color: #ef4444;
    }
    .doc-icon.word {
        background: #eff6ff;
        color: #3b82f6;
    }
    .doc-meta {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    .doc-name {
        font-size: 12.5px;
        font-weight: 600;
        color: #1e293b;
    }
    .doc-desc {
        font-size: 10.5px;
        color: #64748b;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-view-doc {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        transition: all 0.2s;
    }
    .btn-view-doc:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }
    
    .decision-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 10px;
    }
    .decision-tab {
        flex: 1;
        text-align: center;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 700;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .decision-tab.active-setuju {
        background: #22c55e;
        color: #ffffff;
    }
    .decision-tab.active-tolak {
        background: #ef4444;
        color: #ffffff;
    }
    
    .decision-panel {
        display: none;
    }
    .decision-panel.active {
        display: block;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        font-size: 13.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }
    .form-group select,
    .form-group input[type="text"],
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        color: #334155;
        background: #ffffff;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-group select:focus,
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #285aa9;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }
    .btn-cancel {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-cancel:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .btn-save-decision {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        background: #285aa9;
        border: 1px solid #285aa9;
        color: #ffffff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-save-decision:hover:not(:disabled) {
        background: #1e4480;
        border-color: #1e4480;
    }
    .btn-save-decision:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>

<div class="content">

    <a href="pengajuan_judul.php" class="page-title-back" style="margin-bottom: 24px;">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Proses Pengajuan Judul</span>
    </a>

    <!-- TOP ROW: PROFILE & TITLES -->
    <div class="sub-grid-top">
        <!-- Student profile card -->
        <div class="card" style="margin-bottom: 0;">
            <h3 class="card-title"><i class="fa-solid fa-graduation-cap"></i> Profil Mahasiswa</h3>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div class="detail-item">
                    <span class="detail-label">Nama Lengkap</span>
                    <span class="detail-value"><?= htmlspecialchars($p['mahasiswa_nama']) ?></span>
                </div>
                <div class="detail-item" style="border-top: 1px solid #f1f5f9; padding-top: 10px;">
                    <span class="detail-label">NPM</span>
                    <span class="detail-value"><?= htmlspecialchars($p['mahasiswa_npm']) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Proposed titles card -->
        <div class="card" style="margin-bottom: 0;">
            <h3 class="card-title"><i class="fa-solid fa-book-bookmark"></i> Judul yang Diajukan</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Judul Utama</label>
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.4; max-height: 56px; overflow-y: auto;">
                        <?= htmlspecialchars($p['judul']) ?>
                    </div>
                </div>
                
                <?php if (!empty($p['judul_alternatif'])): ?>
                    <div class="form-group" style="margin-bottom: 0; margin-top: 4px;">
                        <label style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Judul Alternatif</label>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 600; color: #64748b; line-height: 1.4; font-style: italic; max-height: 56px; overflow-y: auto;">
                            <?= htmlspecialchars($p['judul_alternatif']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BOTTOM ROW: WORKSPACE GRID -->
    <div class="layout-grid">
    
        <!-- LEFT COLUMN: DECISION FORM -->
        <div>
            <!-- Keputusan Kaprodi Card -->
            <div class="card" style="margin-bottom: 0;">
                <h3 class="card-title"><i class="fa-solid fa-gavel"></i> Keputusan Kaprodi</h3>
                
                <form id="formProsesPengajuan">
                    <input type="hidden" name="id" id="pengajuan_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="action" id="action_field" value="setujui">
                    
                    <div class="decision-tabs">
                        <div class="decision-tab active-setuju" id="tabSetuju" onclick="switchDecision('setujui')">
                            <i class="fa-solid fa-circle-check"></i> Setujui Judul
                        </div>
                        <div class="decision-tab" id="tabTolak" onclick="switchDecision('tolak')">
                            <i class="fa-solid fa-circle-xmark"></i> Tolak Judul
                        </div>
                    </div>
                    
                    <!-- PANEL: SETUJUI -->
                    <div class="decision-panel active" id="panelSetuju">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Judul yang Disetujui <span style="color:#ef4444;">*</span></label>
                            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                                <label style="font-weight: normal; color: #1e293b; display: flex; align-items: flex-start; gap: 8px; cursor: pointer; line-height: 1.4;">
                                    <input type="radio" name="judul_disetujui_pilihan" value="utama" checked style="cursor: pointer; margin-top: 3px;"> 
                                    <span><strong>Judul Utama:</strong> <br><span style="font-size: 13px; color: #334155;"><?= htmlspecialchars($p['judul']) ?></span></span>
                                </label>
                                
                                <?php if (!empty($p['judul_alternatif'])): ?>
                                    <label style="font-weight: normal; color: #1e293b; display: flex; align-items: flex-start; gap: 8px; cursor: pointer; line-height: 1.4; margin-top: 4px; border-top: 1px solid #f1f5f9; padding-top: 8px;">
                                        <input type="radio" name="judul_disetujui_pilihan" value="alternatif" style="cursor: pointer; margin-top: 3px;"> 
                                        <span><strong>Judul Alternatif:</strong> <br><span style="font-size: 13px; color: #64748b; font-style: italic;"><?= htmlspecialchars($p['judul_alternatif']) ?></span></span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pembimbing1">Dosen Pembimbing 1 <span style="color:#ef4444;">*</span></label>
                            <select id="pembimbing1" name="pembimbing1">
                                <option value="">-- Pilih Pembimbing 1 --</option>
                                <?php foreach ($daftar_dosen as $d): ?>
                                    <option value="<?= htmlspecialchars($d['nama']) ?>"><?= htmlspecialchars($d['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="pembimbing2">Dosen Pembimbing 2</label>
                            <select id="pembimbing2" name="pembimbing2">
                                <option value="">-- Pilih Pembimbing 2 (Boleh Kosong) --</option>
                                <?php foreach ($daftar_dosen as $d): ?>
                                    <option value="<?= htmlspecialchars($d['nama']) ?>"><?= htmlspecialchars($d['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="pembahas1">Dosen Pembahas 1 (Opsional)</label>
                            <select id="pembahas1" name="pembahas1">
                                <option value="">-- Pilih Dosen Pembahas 1 --</option>
                                <?php foreach ($daftar_dosen as $d): ?>
                                    <option value="<?= htmlspecialchars($d['nama']) ?>"><?= htmlspecialchars($d['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="pembahas2">Dosen Pembahas 2 (Opsional)</label>
                            <select id="pembahas2" name="pembahas2">
                                <option value="">-- Pilih Dosen Pembahas 2 --</option>
                                <?php foreach ($daftar_dosen as $d): ?>
                                    <option value="<?= htmlspecialchars($d['nama']) ?>"><?= htmlspecialchars($d['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- PANEL: TOLAK -->
                    <div class="decision-panel" id="panelTolak">
                        <div class="form-group">
                            <label for="alasan">Alasan Penolakan <span style="color:#ef4444;">*</span></label>
                            <textarea id="alasan" name="alasan" rows="5" placeholder="Tuliskan secara detail alasan penolakan judul skripsi mahasiswa agar mahasiswa dapat memperbaikinya..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <a href="pengajuan_judul.php" class="btn-cancel">Batal</a>
                        <button type="submit" class="btn-save-decision" id="btnSubmitForm">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Keputusan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- RIGHT COLUMN: REQUIREMENT DOCUMENTS -->
        <div>
            <div class="card" style="position: sticky; top: 24px; margin-bottom: 0;">
                <h3 class="card-title"><i class="fa-solid fa-folder-open"></i> Dokumen Persyaratan</h3>
                <p style="color: #64748b; font-size: 12px; margin-top: -8px; margin-bottom: 14px;">Silakan tinjau kelayakan akademis mahasiswa melalui berkas berikut.</p>
                
                <div class="doc-list">
                    <?= renderDocCard($p['file_transkrip'], 'Transkrip Akademik (PDF)') ?>
                    <?= renderDocCard($p['file_ktm'], 'KTM / Kartu Tanda Mahasiswa (PDF)') ?>
                    <?= renderDocCard($p['file_form_tema'], 'Form Pengajuan Tema (PDF)') ?>
                    <?= renderDocCard($p['file_bukti_ukt'], 'Bukti Pembayaran UKT (PDF)') ?>
                    <?= renderDocCard($p['file_krs_terakhir'], 'KRS Terakhir (PDF)') ?>
                    <?= renderDocCard($p['file_form_verifikasi'], 'Form Verifikasi Berkas (PDF)') ?>
                    <?= renderDocCard($p['file_bukti_acc'], 'Bukti Acc Judul Dosen (PDF)') ?>
                    <?= renderDocCard($p['file_form_penetapan'], 'Form Penetapan Tema (DOCX)', true) ?>
                    <?= renderDocCard($p['file_bab1'], 'Bab 1 Utama (DOCX)', true) ?>
                    <?= renderDocCard($p['file_bab1_alt'], 'Bab 1 Alternatif (DOCX)', true) ?>
                </div>
            </div>
        </div>
        
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const formProses = document.getElementById('formProsesPengajuan');
    const actionField = document.getElementById('action_field');
    const tabSetuju = document.getElementById('tabSetuju');
    const tabTolak = document.getElementById('tabTolak');
    const panelSetuju = document.getElementById('panelSetuju');
    const panelTolak = document.getElementById('panelTolak');
    const btnSubmitForm = document.getElementById('btnSubmitForm');

    function switchDecision(type) {
        if (type === 'setujui') {
            actionField.value = 'setujui';
            tabSetuju.classList.add('active-setuju');
            tabTolak.classList.remove('active-tolak');
            panelSetuju.classList.add('active');
            panelTolak.classList.remove('active');
        } else {
            actionField.value = 'tolak';
            tabSetuju.classList.remove('active-setuju');
            tabTolak.classList.add('active-tolak');
            panelSetuju.classList.remove('active');
            panelTolak.classList.add('active');
        }
    }

    formProses.onsubmit = function(e) {
        e.preventDefault();
        const action = actionField.value;

        // Perform validations
        if (action === 'setujui') {
            const p1 = document.getElementById('pembimbing1').value;
            const p2 = document.getElementById('pembimbing2').value;
            const pb1 = document.getElementById('pembahas1').value;

            if (p1 === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Formulir Kurang Lengkap',
                    text: 'Mohon lengkapi Dosen Pembimbing 1!',
                    confirmButtonColor: '#285aa9'
                });
                return;
            }
            if (p2 !== "" && p1 === p2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dosen Pembimbing Sama',
                    text: 'Pembimbing 1 dan Pembimbing 2 tidak boleh orang yang sama!',
                    confirmButtonColor: '#285aa9'
                });
                return;
            }
            if (pb1 !== "" && (p1 === pb1 || p2 === pb1)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dosen Pembahas Merangkap',
                    text: 'Dosen Pembahas tidak boleh merangkap sebagai Pembimbing mahasiswa tersebut!',
                    confirmButtonColor: '#285aa9'
                });
                return;
            }
        } else {
            const alasan = document.getElementById('alasan').value.trim();
            if (alasan === "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan Kosong',
                    text: 'Harap berikan alasan penolakan judul skripsi mahasiswa!',
                    confirmButtonColor: '#285aa9'
                });
                return;
            }
        }

        const formData = new FormData(formProses);

        Swal.fire({
            title: 'Simpan Keputusan?',
            text: "Apakah Anda yakin ingin memproses keputusan pengajuan ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#285aa9',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btnSubmitForm.disabled = true;
                btnSubmitForm.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                fetch('/bimbingan-skripsi/app/controllers/PengajuanJudulController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btnSubmitForm.disabled = false;
                    btnSubmitForm.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Keputusan';

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Keputusan Disimpan',
                            text: 'Pengajuan judul telah sukses diproses.',
                            confirmButtonColor: '#285aa9'
                        }).then(() => {
                            window.location.href = 'pengajuan_judul.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: data.message || 'Terjadi kesalahan sistem.',
                            confirmButtonColor: '#285aa9'
                        });
                    }
                })
                .catch(err => {
                    btnSubmitForm.disabled = false;
                    btnSubmitForm.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Keputusan';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: 'Gagal menghubungi server.',
                        confirmButtonColor: '#285aa9'
                    });
                });
            }
        });
    };
</script>

</body>
</html>
