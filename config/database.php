<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "myplatform";

try {
    $conn = new PDO("mysql: host=$host; dbname=$dbname;", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lidhja me databazen deshtoi: " . $e->getMessage());
}
?>
