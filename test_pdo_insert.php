<?php
// Test direktnog PDO inserta u users tabelu
$host = '127.0.0.1';
$db   = 'checkin_app'; // iz .env
$user = 'checkin';     // iz .env
$pass = '123456';      // iz .env
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=3307;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $sql = "INSERT INTO users (company_id, username, email, password, name, role, status, created_at, updated_at) VALUES (1, 'pdo_user', 'pdo@test.rs', 'pdo_pass', 'PDO', 'facility_admin', 'active', NOW(), NOW())";
    $pdo->exec($sql);
    echo "Insert uspešan!\n";
} catch (Exception $e) {
    echo "Greška: " . $e->getMessage() . "\n";
}
