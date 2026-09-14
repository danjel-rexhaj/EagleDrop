<?php
require_once __DIR__ . '/env.php';

$host   = env('DB_HOST', 'localhost');
$port   = env('DB_PORT', '3306');
$user   = env('DB_USER', 'root');
$pass   = env('DB_PASS', '');
$dbname = env('DB_NAME', 'myplatform');

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lidhja me databazen deshtoi: " . $e->getMessage());
}
?>
