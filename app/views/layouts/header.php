<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 604800);
    ini_set('session.gc_maxlifetime', 604800);
    session_start();
}

// Refresh cookie lifetime
$params = session_get_cookie_params();
setcookie(session_name(), session_id(), time() + 604800, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= $title ?? 'Sistem Informasi Bimbingan Skripsi' ?></title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#ffffff;
}

/* =====================================================
   SIDEBAR
===================================================== */

.sidebar{
    position:fixed;
    top:0 !important;
    left:0;
    width:270px;
    height:100vh !important;
    background:#F5F5F5;
    border-right:1px solid #ddd;
    z-index:10000;
    transition: all 0.3s ease !important;
}

.logo{
    background:#ffffff;
    height: 70px !important;
    padding: 0 18px !important;
    display:flex;
    align-items:center;
    gap:15px;
    border-bottom:1px solid #ddd;
}

.logo img{
    width:45px;
}

.logo h3{
    color:#285aa9;
    font-size:15px;
    white-space:nowrap;
}

/* =====================================================
   MENU
===================================================== */

.menu{
    padding:12px;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 18px;
    text-decoration:none;
    color:#222;
    border-radius:4px;
    margin-bottom:5px;
    font-size:15px;
    transition:.25s;
}

.menu a:hover{
    background:#e7eef9;
}

.menu a.active{
    background:#285aa9;
    color:#ffffff;
}

.submenu{
    margin-left:20px;
    background:#ececec;
    border-radius:4px;
    padding:8px;
}

.submenu a{
    padding:12px;
    margin-bottom:3px;
}

/* TOPBAR */

.topbar{
    position:fixed;
    top:0;
    left:270px !important;
    right:0;
    height:70px;

    background:#285aa9;
    color:#ffffff;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:0 25px;

    z-index:9999;
    transition: all 0.3s ease !important;
}

.topbar-left{
    display:flex;
    align-items:center;
    gap:18px;
}

.topbar-left i{
    font-size:20px;
}

.topbar-left h2{
    font-size:17px;
    font-weight:400;
}

.topbar-right{
    display:flex;
    align-items:center;
}

/*USER DROPDOWN*/

.user-dropdown{
    position:relative;
}

.user-btn{
    background:transparent;
    border:none;
    color:#ffffff;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    font-size:15px;
}

.user-btn:hover{
    opacity:.9;
}

.dropdown-menu{

    position:absolute;
    top:48px;
    right:0;
    width:190px;
    background:#ffffff;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 5px 15px rgba(0,0,0,.18);
    display:none;
    z-index:99999;
}

.dropdown-menu.show{
    display:block;
}

.dropdown-menu a{

    display:flex;
    align-items:center;
    gap:12px;
    padding:13px 16px;
    text-decoration:none;
    color:#333333;
    transition:.2s;
}

.dropdown-menu a:hover{
    background:#eef4fb;
    color:#285aa9;

}

/*CONTENT*/

.content{
    margin-left:270px;
    margin-top:70px;
    padding:25px;
    transition: all 0.3s ease !important;
}

/* Collapse States */
/* Collapse States */
body.sidebar-collapsed .sidebar {
    width: 70px !important;
    overflow-x: hidden !important;
}

body.sidebar-collapsed .topbar {
    left: 70px !important;
}

body.sidebar-collapsed .content {
    margin-left: 70px !important;
}

/* Sidebar Internal Elements when Collapsed */
body.sidebar-collapsed .sidebar .logo {
    padding: 0 !important;
    justify-content: center !important;
    gap: 0 !important;
}

body.sidebar-collapsed .sidebar .logo img {
    margin: 0 !important;
    width: 35px !important;
    transition: all 0.3s ease;
}

body.sidebar-collapsed .sidebar .logo h3 {
    display: none !important;
}

body.sidebar-collapsed .sidebar .menu {
    padding: 12px 6px !important;
}

body.sidebar-collapsed .sidebar .menu a,
body.sidebar-collapsed .sidebar .menu button,
body.sidebar-collapsed .sidebar .menu .menu-btn {
    font-size: 0 !important;
    padding: 14px 0 !important;
    justify-content: center !important;
    gap: 0 !important;
    width: 100% !important;
}

body.sidebar-collapsed .sidebar .menu a i,
body.sidebar-collapsed .sidebar .menu button i,
body.sidebar-collapsed .sidebar .menu .menu-btn i {
    font-size: 18px !important;
    margin: 0 !important;
}

body.sidebar-collapsed .sidebar .menu button i.arrow,
body.sidebar-collapsed .sidebar .menu .menu-btn i.arrow {
    display: none !important;
}

body.sidebar-collapsed .sidebar .submenu {
    display: none !important;
}

/* Hover Expand behavior when sidebar is collapsed */
body.sidebar-collapsed .sidebar:hover {
    width: 270px !important;
    box-shadow: 10px 0 30px rgba(0,0,0,0.15) !important;
}

body.sidebar-collapsed .sidebar:hover .logo {
    padding: 0 18px !important;
    justify-content: flex-start !important;
    gap: 15px !important;
}

body.sidebar-collapsed .sidebar:hover .logo img {
    width: 45px !important;
}

body.sidebar-collapsed .sidebar:hover .logo h3 {
    display: block !important;
}

body.sidebar-collapsed .sidebar:hover .menu {
    padding: 12px !important;
}

body.sidebar-collapsed .sidebar:hover .menu a,
body.sidebar-collapsed .sidebar:hover .menu button,
body.sidebar-collapsed .sidebar:hover .menu .menu-btn {
    font-size: 15px !important;
    padding: 14px 18px !important;
    justify-content: flex-start !important;
    gap: 15px !important;
}

body.sidebar-collapsed .sidebar:hover .menu a i,
body.sidebar-collapsed .sidebar:hover .menu button i,
body.sidebar-collapsed .sidebar:hover .menu .menu-btn i {
    font-size: 15px !important;
}

body.sidebar-collapsed .sidebar:hover .menu button i.arrow,
body.sidebar-collapsed .sidebar:hover .menu .menu-btn i.arrow {
    display: inline-block !important;
}

body.sidebar-collapsed .sidebar:hover .submenu.show {
    display: block !important;
}

body.sidebar-collapsed .sidebar:hover .submenu a {
    font-size: 14px !important;
    padding: 12px 18px !important;
    justify-content: flex-start !important;
    gap: 10px !important;
}

.page-title{
    font-size:22px;
    font-weight:600;
    margin-bottom:20px;
}

/* =====================================================
   CARD
===================================================== */

.card{
    background:#ffffff;
    border-top:4px solid #69a86e;
    border-left:1px solid #e2e8f0;
    border-right:1px solid #e2e8f0;
    border-bottom:1px solid #e2e8f0;
    box-shadow:0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    padding:24px;
    border-radius:16px;
}

/* =====================================================
   TABLE
===================================================== */

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#3f4d63;
    color:#ffffff;
    padding:12px;
    font-size:14px;
    text-align:left;
}

table td{
    padding:14px 12px;
    border:1px solid #dddddd;
    font-size:14px;
}

table tbody tr:nth-child(even){
    background:#f5f7fa;
}

/* =====================================================
   BUTTON
===================================================== */

.btn-view{
    background:#7db8db;
    color:#ffffff;
    border:none;
    padding:8px 12px;
    cursor:pointer;
}

.btn-view:hover{
    opacity:.9;
}

/* SEARCH */

.search{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-bottom:20px;
}

.search input{
    width:300px;
    padding:10px;
    border:1px solid #cccccc;
    border-radius:4px;
}

.search button{
    background:#69a86e;
    color:#ffffff;
    border:none;
    padding:10px 15px;
    cursor:pointer;
    border-radius:4px;
}

.search button:hover{
    opacity:.9;
}

</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebarToggle = document.getElementById("sidebarToggle");
    if (sidebarToggle) {
        if (localStorage.getItem("sidebar-collapsed") === "true") {
            document.body.classList.add("sidebar-collapsed");
        }
        sidebarToggle.addEventListener("click", function() {
            document.body.classList.toggle("sidebar-collapsed");
            localStorage.setItem("sidebar-collapsed", document.body.classList.contains("sidebar-collapsed"));
        });
    }
});
</script>
</head>
<body>