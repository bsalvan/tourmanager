<?php
// Afficher toutes les erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. Lecture du fichier .env...\n";
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    die("❌ Erreur : Le fichier .env est introuvable dans " . __DIR__ . "\n");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
    if ($name && $value) {
        $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

// Récupération des variables (adapte les noms si tes clés dans le .env sont différentes)
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

echo "Paramètres lus :\n";
echo "- Host : $host\n";
echo "- Port : $port\n";
echo "- DB   : $db\n";
echo "- User : $user\n";
echo "- Pass : " . ($pass ? '*** (Défini)' : '(Vide)') . "\n\n";

echo "2. Tentative de connexion PDO...\n";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Succès total ! La connexion PDO via le .env fonctionne parfaitement !\n";
} catch (PDOException $e) {
    echo "❌ Échec de la connexion PDO : " . $e->getMessage() . "\n";
}
