<?php
require '../config/database.php';
$makers = $conn->query("SELECT id, name FROM car_makers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($makers);
