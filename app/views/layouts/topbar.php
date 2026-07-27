<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =====================
// Tentukan link Profil sesuai role yang sedang login
// =====================
$role = $_SESSION['role'] ?? '';
$otoritasAktif = $_SESSION['otoritas'] ?? $role;

switch ($role) {
    case 'mahasiswa':
        $profil_link = '/bimbingan-skripsi/app/views/mahasiswa/profil.php';
        break;

    case 'dosen':
    case 'kaprodi':
        // dosen dan kaprodi memakai halaman profil yang sama (folder dosen)
        $profil_link = '/bimbingan-skripsi/app/views/dosen/profil.php';
        break;

    default:
        $profil_link = '#';
}

// Tentukan Nama Pengguna di Topbar sesuai Otoritas Aktif
if ($otoritasAktif === 'mahasiswa') {
    $topbarDisplayName = ($role === 'mahasiswa') ? ($_SESSION['nama'] ?? 'LIRA SEPTIYANI') : 'LIRA SEPTIYANI';
} else {
    $topbarDisplayName = $_SESSION['nama'] ?? '';
}
?>

<div class="topbar">

    <div class="topbar-left">

        <i class="fa-solid fa-bars" id="sidebarToggle" style="cursor: pointer;"></i>

        <h2>Sistem Bimbingan Skripsi</h2>

    </div>

    <div class="topbar-right">

        <div class="user-dropdown">

            <button class="user-btn" onclick="toggleDropdown(event)">

                <i class="fa-solid fa-user"></i>

                <?= htmlspecialchars($topbarDisplayName); ?>

                <i class="fa-solid fa-caret-down"></i>

            </button>

            <div class="dropdown-menu" id="dropdownMenu">

                <a href="<?= $profil_link ?>">
                    <i class="fa-solid fa-user"></i>
                    Profil
                </a>

                <a href="/bimbingan-skripsi/app/controllers/LogoutController.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>

            </div>

        </div>

    </div>

</div>

<script>

function toggleDropdown(e) {

    e.stopPropagation();

    document.getElementById("dropdownMenu").classList.toggle("show");

}

window.onclick = function () {

    document.getElementById("dropdownMenu").classList.remove("show");

}

</script>
