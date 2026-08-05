$start = microtime(true);

$pdo = new PDO($dsn, $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Koneksi: " . round(microtime(true) - $start, 3) . " detik";
exit;