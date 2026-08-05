<?php
$current = basename($_SERVER['PHP_SELF']);

$topikActive = in_array($current, [
    'topik_penelitian.php',
    'detail_topik.php'
]);

$bimbinganActive = in_array($current, [
    'bimbingan.php',
    'detail_bimbingan.php'
]);
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
    transition:.3s;
}

.arrow.rotate{
    transform:rotate(180deg);
}

</style>

<div class="sidebar">

    <!-- LOGO -->
    <div class="logo">
        <img src="<?= BASE_URL ?>/public/img/Logo_UnivLampung.png">
        <h3>UNIVERSITAS LAMPUNG</h3>
    </div>

    <div class="menu">

        <!-- DASHBOARD -->

        <a href="<?= BASE_URL ?>/app/views/dosen/dashboard.php"
           class="<?= $current=='dashboard.php' ? 'active' : '' ?>">

            <i class="fa-solid fa-house"></i>
            Dashboard

        </a>

        <!-- TOPIK PENELITIAN -->

        <a href="<?= BASE_URL ?>/app/views/dosen/topik_penelitian.php"
           class="<?= $topikActive ? 'active' : '' ?>">

            <i class="fa-solid fa-book"></i>
            Topik Penelitian

        </a>

        <!-- BIMBINGAN -->

        <a href="<?= BASE_URL ?>/app/views/dosen/bimbingan.php"
           class="<?= $bimbinganActive ? 'active' : '' ?>">

            <i class="fa-solid fa-graduation-cap"></i>
            Bimbingan

        </a>

    </div>

</div>