<?php
$current = basename($_SERVER['PHP_SELF']);
$pengajuanJudulActive = in_array($current, [
    'pengajuan_judul.php',
    'pengajuan_riwayat.php',
    'proses_pengajuan.php',
    'riwayat_detail.php'
]);
?>

<style>
    /* SUBMENU */
    .submenu {
        display: none;
        background: #ececec;
        border-radius: 4px;
        margin-bottom: 10px;
        padding: 8px;
        margin-left: 20px;
    }

    .submenu.show {
        display: block;
    }

    .submenu a {
        padding: 12px 18px;
        margin-bottom: 5px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #222;
        text-decoration: none;
        border-radius: 4px;
        transition: .2s;
    }

    .submenu a:hover {
        background: #285aa9;
        color: white;
    }

    .submenu a.active {
        background: #285aa9;
        color: white;
    }

    .submenu a.active i {
        color: white;
    }

    /* ARROW */
    .arrow {
        margin-left: auto;
        transition: 0.3s;
    }

    .arrow.rotate {
        transform: rotate(180deg);
    }

    .menu .menu-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 14px 18px;
        text-decoration: none;
        color: #222;
        border-radius: 4px;
        margin-bottom: 5px;
        font-size: 15px;
        border: none;
        background: none;
        cursor: pointer;
        transition: .2s;
    }

    .menu .menu-btn:hover {
        background: #285aa9;
        color: white;
    }

    .menu .menu-btn.active {
        background: #285aa9;
        color: white;
    }

    .menu .menu-btn.active i {
        color: white;
    }
</style>

<div class="sidebar">

    <div class="logo">
        <img src="<?= BASE_URL ?>/public/img/Logo_UnivLampung.png" alt="Logo">
        <h3>UNIVERSITAS LAMPUNG</h3>
    </div>

    <div class="menu">

        <a href="<?= BASE_URL ?>/app/views/kaprodi/dashboard.php"
           class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            Dashboard
        </a>

        <a href="<?= BASE_URL ?>/app/views/kaprodi/monitoring_progres.php"
           class="<?= $current == 'monitoring_progres.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            Monitoring Progres
        </a>

        <a href="<?= BASE_URL ?>/app/views/kaprodi/kuota_pembimbing.php"
           class="<?= $current == 'kuota_pembimbing.php' ? 'active' : '' ?>">
            <i class="fas fa-list-check"></i>
            Kuota Pembimbing
        </a>

        <a href="<?= BASE_URL ?>/app/views/kaprodi/topik_penelitian.php"
           class="<?= $current == 'topik_penelitian.php' ? 'active' : '' ?>">
            <i class="fas fa-book-bookmark"></i>
            Topik Penelitian
        </a>

        <!-- PENGAJUAN JUDUL DROPDOWN -->
        <button class="menu-btn <?= $pengajuanJudulActive ? 'active' : '' ?>" 
                onclick="toggleSubmenu('submenuJudul', 'arrowJudul')">
            <i class="fa-solid fa-file-signature"></i>
            Pengajuan Judul
            <i id="arrowJudul" class="fa-solid fa-chevron-down arrow <?= $pengajuanJudulActive ? 'rotate' : '' ?>"></i>
        </button>

        <!-- SUBMENU PENGAJUAN JUDUL -->
        <div id="submenuJudul" class="submenu <?= $pengajuanJudulActive ? 'show' : '' ?>">
            <a href="<?= BASE_URL ?>/app/views/kaprodi/pengajuan_judul.php"
               class="<?= in_array($current, ['pengajuan_judul.php', 'proses_pengajuan.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
                Persetujuan Judul
            </a>
            <a href="<?= BASE_URL ?>/app/views/kaprodi/pengajuan_riwayat.php"
               class="<?= in_array($current, ['pengajuan_riwayat.php', 'riwayat_detail.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
                Riwayat SK Judul
            </a>
        </div>

        <a href="<?= BASE_URL ?>/app/views/kaprodi/distribusi_mahasiswa.php"
           class="<?= $current == 'distribusi_mahasiswa.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i>
            Distribusi Mahasiswa
        </a>

    </div>

</div>

<script>
function toggleSubmenu(id, arrowId) {
    const submenu = document.getElementById(id);
    const arrow = document.getElementById(arrowId);
    if (submenu && arrow) {
        submenu.classList.toggle('show');
        arrow.classList.toggle('rotate');
    }
}
</script>