<?php

// Tentukan BASE_URL secara dinamis berdasarkan path project terhadap DOCUMENT_ROOT
$project_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

$base_url = '';
if (!empty($doc_root) && strpos(strtolower($project_root), strtolower($doc_root)) === 0) {
    $base_url = substr($project_root, strlen($doc_root));
}
$base_url = rtrim($base_url, '/');

define('BASE_URL', $base_url);