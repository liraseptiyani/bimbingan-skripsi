<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

// Strict Dosen check
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'dosen') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "VERIFIKASI POST received: " . print_r($_POST, true) . "Session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $statusInput = strtolower(trim($_POST['status'] ?? ''));

    if ($action === 'verifikasi' && $id > 0) {
        $statusMap = [
            'disetujui' => 'disetujui',
            'ditolak' => 'ditolak',
            'menunggu' => 'menunggu'
        ];

        $status = $statusMap[$statusInput] ?? 'menunggu';

        try {
            // Get info about applicant and topic ownership
            $stmtCheck = $pdo->prepare("
                SELECT tp.nip_dosen, tp.kuota_max, mt.topik_id, mt.status AS current_status, mt.mahasiswa_npm
                FROM minat_topik mt
                JOIN topik_penelitian tp ON mt.topik_id = tp.id
                WHERE mt.id = :id
            ");
            $stmtCheck->execute([':id' => $id]);
            $info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                echo json_encode(['success' => false, 'message' => 'Data pengajuan tidak ditemukan!']);
                exit;
            }

            // Verify that the logged in lecturer owns the topic
            if (str_replace(' ', '', $info['nip_dosen']) !== str_replace(' ', '', $_SESSION['username'])) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki hak untuk memverifikasi topik ini!']);
                exit;
            }

            // Checks when changing status to 'disetujui'
            if ($status === 'disetujui') {
                // Check if this student is already approved for another topic
                $stmtCheckOther = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM minat_topik 
                    WHERE mahasiswa_npm = :npm AND status = 'disetujui' AND id != :id
                ");
                $stmtCheckOther->execute([':npm' => $info['mahasiswa_npm'], ':id' => $id]);
                if ($stmtCheckOther->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Mahasiswa ini sudah disetujui untuk topik penelitian lain!']);
                    exit;
                }

                // Check quota
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
                $stmtCount->execute([':topik_id' => $info['topik_id']]);
                $currentApproved = (int)$stmtCount->fetchColumn();

                if ($currentApproved >= (int)$info['kuota_max']) {
                    echo json_encode(['success' => false, 'message' => 'Kuota topik sudah penuh!']);
                    exit;
                }
            }

            // Update status in minat_topik
            $stmt = $pdo->prepare("UPDATE minat_topik SET status = :status WHERE id = :id");
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);

            // Post-approval actions (auto-reject others)
            if ($status === 'disetujui') {
                // Auto-reject other pending applications of this student
                $stmtRejectOthersMhs = $pdo->prepare("
                    UPDATE minat_topik 
                    SET status = 'ditolak' 
                    WHERE mahasiswa_npm = :npm AND id != :id AND status = 'menunggu'
                ");
                $stmtRejectOthersMhs->execute([
                    ':npm' => $info['mahasiswa_npm'],
                    ':id' => $id
                ]);

                // Auto-reject other applicants if the quota is now full
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM minat_topik WHERE topik_id = :topik_id AND status = 'disetujui'");
                $stmtCount->execute([':topik_id' => $info['topik_id']]);
                $currentApproved = (int)$stmtCount->fetchColumn();

                if ($currentApproved >= (int)$info['kuota_max']) {
                    // Update all other pending applicants to 'ditolak'
                    $stmtRejectOthers = $pdo->prepare("
                        UPDATE minat_topik 
                        SET status = 'ditolak' 
                        WHERE topik_id = :topik_id AND id != :id AND status = 'menunggu'
                    ");
                    $stmtRejectOthers->execute([
                        ':topik_id' => $info['topik_id'],
                        ':id' => $id
                    ]);
                }
            }
            file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "VERIFIKASI Query successful for status '$status' on ID $id.\n", FILE_APPEND);

            echo json_encode(['success' => true]);
            exit;

        } catch (PDOException $e) {
            file_put_contents(dirname(__DIR__, 2) . '/db_log.txt', "VERIFIKASI PDOException: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);
exit;
