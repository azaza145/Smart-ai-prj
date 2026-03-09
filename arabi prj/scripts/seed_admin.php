<?php
/**
 * Create initial admin user if none exists. Run from project root: php scripts/seed_admin.php
 */
require_once __DIR__ . '/../vendor/autoload.php';

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    App\Core\DB::loadEnv($envPath);
}
App\Core\DB::loadEnv(dirname(__DIR__) . '/env.example');

$pdo = App\Core\DB::getInstance();
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit(0);
}
$hash = password_hash('Admin123!', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES ('Admin', 'admin@smartrecruit.local', ?, 'admin', 'active')")->execute([$hash]);
echo "Admin user created: admin@smartrecruit.local / Admin123!\n";
exit(0);
