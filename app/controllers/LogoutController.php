<?php

session_start();

$_SESSION = [];

session_destroy();

require_once dirname(__DIR__, 2) . '/config/config.php';

header("Location: " . BASE_URL . "/");

exit;