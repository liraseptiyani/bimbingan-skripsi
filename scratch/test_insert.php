<?php
require_once dirname(__DIR__) . '/config/koneksi.php';

try {
    $npm = '2217051151';
    $nama = 'Lira Septiyani';
    $judul = 'Test Judul Skripsi';
    $judul_alternatif = 'Test Judul Alternatif';
    $deskripsi = '';
    $pembimbing1 = null;
    $pembimbing2 = null;
    $file_transkrip = 'test_transkrip.pdf';
    $file_ktm = 'test_ktm.pdf';
    $file_form_tema = 'test_form_tema.pdf';
    $file_bukti_ukt = 'test_bukti_ukt.pdf';
    $file_krs_terakhir = 'test_krs_terakhir.pdf';
    $file_form_verifikasi = 'test_form_verifikasi.pdf';
    $file_bukti_acc = null;
    $file_form_penetapan = 'test_form_penetapan.docx';
    $file_bab1 = 'test_bab1.docx';
    $file_bab1_alt = null;

    $stmtInsert = $pdo->prepare("
        INSERT INTO pengajuan_judul (
            mahasiswa_npm, mahasiswa_nama, judul, judul_alternatif, deskripsi, pembimbing1, pembimbing2, status, 
            file_transkrip, file_ktm, file_form_tema, file_bukti_ukt, file_krs_terakhir, file_form_verifikasi, 
            file_bukti_acc, file_form_penetapan, file_bab1, file_bab1_alt
        )
        VALUES (
            :npm, :nama, :judul, :judul_alt, :deskripsi, :p1, :p2, 'menunggu', 
            :f_transkrip, :f_ktm, :f_form_tema, :f_bukti_ukt, :f_krs_terakhir, :f_form_verifikasi, 
            :f_bukti_acc, :f_form_penetapan, :f_bab1, :f_bab1_alt
        )
    ");
    $res = $stmtInsert->execute([
        ':npm' => $npm,
        ':nama' => $nama,
        ':judul' => $judul,
        ':judul_alt' => $judul_alternatif ?: null,
        ':deskripsi' => $deskripsi,
        ':p1' => $pembimbing1,
        ':p2' => $pembimbing2 ?: null,
        ':f_transkrip' => $file_transkrip,
        ':f_ktm' => $file_ktm,
        ':f_form_tema' => $file_form_tema,
        ':f_bukti_ukt' => $file_bukti_ukt,
        ':f_krs_terakhir' => $file_krs_terakhir,
        ':f_form_verifikasi' => $file_form_verifikasi,
        ':f_bukti_acc' => $file_bukti_acc,
        ':f_form_penetapan' => $file_form_penetapan,
        ':f_bab1' => $file_bab1,
        ':f_bab1_alt' => $file_bab1_alt
    ]);

    if ($res) {
        echo "INSERT BERHASIL!\n";
        // Clean up
        $pdo->exec("DELETE FROM pengajuan_judul WHERE judul = 'Test Judul Skripsi'");
        echo "CLEANUP BERHASIL!\n";
    } else {
        echo "INSERT GAGAL TANPA EXCEPTION\n";
    }
} catch (Exception $e) {
    echo "EX-ERROR: " . $e->getMessage() . "\n";
}
