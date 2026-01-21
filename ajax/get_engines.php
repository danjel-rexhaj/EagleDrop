<?php
require '../config/database.php';

$model_id = $_GET['model_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT id, engine_name, fuel_type, displacement, power_hp, year_from, year_to 
    FROM car_engines 
    WHERE model_id = ? 
    ORDER BY 
      CASE 
        WHEN fuel_type = 'Diesel' THEN 1
        WHEN fuel_type = 'Petrol' THEN 2
        WHEN fuel_type = 'Hybrid' THEN 3
        WHEN fuel_type = 'Electric' THEN 4
        ELSE 5
      END,
      power_hp ASC
");
$stmt->execute([$model_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
