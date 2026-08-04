<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 604800);
    ini_set('session.gc_maxlifetime', 604800);
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {

        // Login
        $sql = "
            SELECT *
            FROM users
            WHERE username = :username
            AND password = :password
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':username' => $username,
            ':password' => $password
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            // Remember me cookies (only username is saved for security)
            if (isset($_POST['remember'])) {
                setcookie('remember_username', $username, time() + 3600 * 24 * 30, "/");
                setcookie('remember_checked', '1', time() + 3600 * 24 * 30, "/");
            } else {
                setcookie('remember_username', '', time() - 3600, "/");
                setcookie('remember_checked', '', time() - 3600, "/");
            }

            // =====================
            // Session dasar
            // =====================
            // role     = jenis akun (mahasiswa / dosen) -> nentuin tabel data
            // otoritas = tampilan aktif saat ini (mahasiswa / dosen / kaprodi) -> nentuin dashboard

            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['otoritas'] = $user['otoritas'];


            if ($user['role'] == 'mahasiswa') {

                $stmtNama = $pdo->prepare("
                    SELECT nama
                    FROM mahasiswa
                    WHERE npm = :npm
                ");

                $stmtNama->execute([
                    ':npm' => $user['username']
                ]);

                $data = $stmtNama->fetch(PDO::FETCH_ASSOC);

                $_SESSION['nama'] = $data['nama'] ?? $user['username'];

            }

            elseif ($user['role'] == 'dosen') {

                // dosen dengan otoritas kaprodi tetap ambil nama dari tabel dosen
                $stmtNama = $pdo->prepare("
                    SELECT nama
                    FROM dosen
                    WHERE nip = :nip
                ");

                $stmtNama->execute([
                    ':nip' => $user['username']
                ]);

                $data = $stmtNama->fetch(PDO::FETCH_ASSOC);

                $_SESSION['nama'] = $data['nama'] ?? $user['username'];

            }

            else {

                $_SESSION['nama'] = $user['username'];

            }

            switch ($user['otoritas']) {

                case 'mahasiswa':
                    header("Location: /app/views/mahasiswa/dashboard.php");
                    exit;       

                case 'dosen':
                    header("Location: /app/views/dosen/dashboard.php");
                    exit;

                case 'kaprodi':
                    header("Location: /app/views/kaprodi/dashboard.php");
                    exit;
            }

        }

        $_SESSION['error'] = "Username atau Password salah.";

        header("Location: /");

        exit;

    } catch (PDOException $e) {

        die($e->getMessage());

    }

}
