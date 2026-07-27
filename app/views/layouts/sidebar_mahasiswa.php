<?php
$current = basename($_SERVER['PHP_SELF']);

$topikDosenActive = in_array($current, [
    'topik_mahasiswa.php',
    'kuota_dosen.php',
    'detail_topik.php',
    'detail_dosen.php'
]);

$pengajuanJudulActive = in_array($current, [
    'pengajuan_judul.php',
    'pengajuan_riwayat.php'
]);

// Check if student has been assigned a supervisor and title
$sidebar_is_distributed = false;
if (isset($_SESSION['username']) && ($_SESSION['role'] ?? '') === 'mahasiswa') {
    $sidebar_npm = $_SESSION['username'];
    // Open a connection if $pdo is not defined yet
    if (!isset($pdo)) {
        require_once dirname(__DIR__, 3) . '/config/koneksi.php';
    }
    if (isset($pdo)) {
        try {
            $stmtSidebar = $pdo->prepare("SELECT COUNT(*) FROM distribusi_mahasiswa WHERE REPLACE(npm, ' ', '') = REPLACE(:npm, ' ', '')");
            $stmtSidebar->execute([':npm' => $sidebar_npm]);
            $sidebar_is_distributed = ((int)$stmtSidebar->fetchColumn() > 0);
        } catch (PDOException $e) {}
    }
} else {
    // If not a student account (e.g. Kaprodi simulating), allow access
    $sidebar_is_distributed = true;
}
?>

<style>

.sidebar{
    position:fixed;
    left:0;
    top:0;
    width:270px;
    height:100vh;
    background:#F5F5F5;
    border-right:1px solid #e3e3e3;
    overflow-y:auto;
}

.logo{
    background:#fff;
    padding:18px;
    display:flex;
    align-items:center;
    gap:15px;
    border-bottom:1px solid #e3e3e3;
}

.logo img{
    width:45px;
}

.logo h3{
    color:#1f57a8;
    font-size:15px;
    font-weight:600;
    white-space:nowrap;
}

.menu{
    padding:12px;
}

.menu a,
.menu .menu-btn{
    width:100%;
    display:flex;
    align-items:center;
    gap:15px;
    padding:14px 18px;
    text-decoration:none;
    color:#222;
    border-radius:4px;
    margin-bottom:5px;
    font-size:15px;
    border:none;
    background:none;
    cursor:pointer;
    transition:.2s;
}

.menu a:hover,
.menu .menu-btn:hover{
    background:#285aa9;
    color:white;
}

.menu a.active,
.menu .menu-btn.active{
    background:#285aa9;
    color:white;
}

.menu .menu-btn.active i{
    color:white;
}

/* SUBMENU */

.submenu{
    display:none;
    background:#ececec;
    border-radius:4px;
    margin-bottom:10px;
    padding:8px;
}

.submenu.show{
    display:block;
}

.submenu a{
    padding:12px 18px;
    margin-bottom:5px;
    font-size:14px;
}

.submenu a.active{
    background:#285aa9;
    color:white;
}

.submenu a.active i{
    color:white;
}

/* ARROW */

.arrow{
    margin-left:auto;
    transition:0.3s;
}

.arrow.rotate{
    transform:rotate(180deg);
}

</style>

<div class="sidebar">

    <!-- LOGO -->
    <div class="logo">
        <img src="/bimbingan-skripsi/public/img/Logo_UnivLampung.png">
        <h3>UNIVERSITAS LAMPUNG</h3>
    </div>

    <div class="menu">

        <!-- DASHBOARD -->

        <a href="/bimbingan-skripsi/app/views/mahasiswa/dashboard.php"
           class="<?= $current=='dashboard.php' ? 'active' : '' ?>">

            <i class="fa-solid fa-house"></i>
            Dashboard

        </a>

        <!-- TOPIK & DOSEN -->

        <button
            class="menu-btn <?= $topikDosenActive ? 'active' : '' ?>"
            onclick="toggleMenu()">

            <i class="fa-solid fa-book"></i>

            Topik & Dosen

            <i id="arrow"
               class="fa-solid fa-chevron-down arrow
               <?= $topikDosenActive ? 'rotate' : '' ?>">
            </i>

        </button>

        <!-- SUBMENU -->

        <div
            id="submenu"
            class="submenu <?= $topikDosenActive ? 'show' : '' ?>">

           <a href="/bimbingan-skripsi/app/views/mahasiswa/topik_mahasiswa.php"
            class="<?= $current=='topik_mahasiswa.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
             Daftar Topik Penelitian

            </a>

            <a href="/bimbingan-skripsi/app/views/mahasiswa/kuota_dosen.php"
            class="<?= ($current=='kuota_dosen.php' || $current=='detail_dosen.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
                Daftar Dosen
            </a>

        </div>

        <!-- PENGAJUAN JUDUL -->

        <button
            class="menu-btn <?= $pengajuanJudulActive ? 'active' : '' ?>"
            onclick="toggleSubmenu('submenuJudul', 'arrowJudul')">
            <i class="fa-solid fa-file-signature"></i>
            Pengajuan Judul
            <i id="arrowJudul"
               class="fa-solid fa-chevron-down arrow
               <?= $pengajuanJudulActive ? 'rotate' : '' ?>">
            </i>
        </button>

        <!-- SUBMENU PENGAJUAN JUDUL -->
        <div
            id="submenuJudul"
            class="submenu <?= $pengajuanJudulActive ? 'show' : '' ?>">

            <a href="/bimbingan-skripsi/app/views/mahasiswa/pengajuan_judul.php"
               class="<?= $current=='pengajuan_judul.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
                Alur & Formulir
            </a>

            <a href="/bimbingan-skripsi/app/views/mahasiswa/pengajuan_riwayat.php"
               class="<?= $current=='pengajuan_riwayat.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-angle-right"></i>
                Riwayat Pengajuan
            </a>

        </div>

        <!-- BIMBINGAN -->

        <?php if ($sidebar_is_distributed): ?>
            <a href="/bimbingan-skripsi/app/views/mahasiswa/bimbingan.php"
            class="<?= $current=='bimbingan.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-graduation-cap"></i>
                Bimbingan
            </a>
        <?php else: ?>
            <button class="menu-btn" onclick="alertLockedBimbingan()" style="opacity: 0.6; cursor: not-allowed; display: flex; align-items: center; gap: 15px; width: 100%; text-align: left; padding: 14px 18px; color: #444; border: none; background: none; font-size: 15px; font-weight: 500;">
                <i class="fa-solid fa-lock"></i>
                Bimbingan <span style="font-size: 10px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #475569; margin-left: auto; text-transform: uppercase;">Locked</span>
            </button>
        <?php endif; ?>

    </div>

</div>

<script>

function toggleMenu(){
    const submenu = document.getElementById('submenu');
    const arrow = document.getElementById('arrow');
    submenu.classList.toggle('show');
    arrow.classList.toggle('rotate');
}

function toggleSubmenu(id, arrowId){
    const submenu = document.getElementById(id);
    const arrow = document.getElementById(arrowId);
    submenu.classList.toggle('show');
    arrow.classList.toggle('rotate');
}

function alertLockedBimbingan() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Akses Terkunci',
            text: 'Fitur bimbingan belum aktif. Anda harus menunggu Kaprodi melakukan plotting Dosen Pembimbing dan Judul Skripsi terlebih dahulu.',
            confirmButtonColor: '#285aa9'
        });
    } else {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = () => {
            Swal.fire({
                icon: 'warning',
                title: 'Akses Terkunci',
                text: 'Fitur bimbingan belum aktif. Anda harus menunggu Kaprodi melakukan plotting Dosen Pembimbing dan Judul Skripsi terlebih dahulu.',
                confirmButtonColor: '#285aa9'
            });
        };
        document.head.appendChild(script);
    }
}

</script>