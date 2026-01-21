<?php
require '../config/database.php';

$maker_id = $_GET['maker_id'] ?? 0;

$stmt = $conn->prepare("SELECT id, name, year_from, year_to 
                        FROM car_models 
                        WHERE maker_id = ? 
                        ORDER BY name ASC, year_from ASC, year_to ASC");
$stmt->execute([$maker_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
