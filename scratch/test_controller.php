<?php
// Mock PHP environment for testing PengajuanJudulController.php
session_start();
$_SESSION['username'] = '2217051151';
$_SESSION['role'] = 'mahasiswa';
$_SESSION['nama'] = 'Lira Septiyani';

// Mock $_POST
$_POST['action'] = 'ajukan';
$_POST['judul'] = 'Sistem Informasi Bimbingan Skripsi S1 Ilmu Komputer';
$_POST['judul_alternatif'] = 'Pengembangan Web Bimbingan Skripsi';

// Create a dummy temp file to mock upload
$tempFileTranskrip = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileTranskrip, '%PDF-1.4 ... dummy transkrip');

$tempFileKtm = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileKtm, '%PDF-1.4 ... dummy ktm');

$tempFileTema = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileTema, '%PDF-1.4 ... dummy tema');

$tempFileUkt = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileUkt, '%PDF-1.4 ... dummy ukt');

$tempFileKrs = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileKrs, '%PDF-1.4 ... dummy krs');

$tempFileVerifikasi = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempFileVerifikasi, '%PDF-1.4 ... dummy verifikasi');

$tempFilePenetapan = tempnam(sys_get_temp_dir(), 'docx');
file_put_contents($tempFilePenetapan, 'docx dummy content');

$tempFileBab1 = tempnam(sys_get_temp_dir(), 'docx');
file_put_contents($tempFileBab1, 'docx dummy content');

// Mock $_FILES
$_FILES = [
    'file_transkrip' => [
        'name' => 'transkrip.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileTranskrip,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileTranskrip),
    ],
    'file_ktm' => [
        'name' => 'ktm.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileKtm,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileKtm),
    ],
    'file_form_tema' => [
        'name' => 'form_tema.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileTema,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileTema),
    ],
    'file_bukti_ukt' => [
        'name' => 'bukti_ukt.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileUkt,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileUkt),
    ],
    'file_krs_terakhir' => [
        'name' => 'krs.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileKrs,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileKrs),
    ],
    'file_form_verifikasi' => [
        'name' => 'verifikasi.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tempFileVerifikasi,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileVerifikasi),
    ],
    'file_form_penetapan' => [
        'name' => 'penetapan.docx',
        'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'tmp_name' => $tempFilePenetapan,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFilePenetapan),
    ],
    'file_bab1' => [
        'name' => 'bab1.docx',
        'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'tmp_name' => $tempFileBab1,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempFileBab1),
    ],
];

// Set request method to POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Enable error reporting to capture any warning or notice
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include the controller
ob_start();
include dirname(__DIR__) . '/app/controllers/PengajuanJudulController.php';
$output = ob_get_clean();

echo "CONTROLLER OUTPUT:\n";
echo $output . "\n";

// Clean up temp files
@unlink($tempFileTranskrip);
@unlink($tempFileKtm);
@unlink($tempFileTema);
@unlink($tempFileUkt);
@unlink($tempFileKrs);
@unlink($tempFileVerifikasi);
@unlink($tempFilePenetapan);
@unlink($tempFileBab1);
