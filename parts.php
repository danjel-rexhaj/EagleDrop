<?php
require 'config/database.php';

$maker_id = $_GET['maker'] ?? 0;
$model_id = $_GET['model'] ?? 0;
$engine_id = $_GET['engine'] ?? 0;

$stmt = $conn->prepare("
  SELECT e.*, m.name AS model_name, mk.name AS maker_name
  FROM car_engines e
  JOIN car_models m ON e.model_id = m.id
  JOIN car_makers mk ON m.maker_id = mk.id
  WHERE e.id = ?
");
$stmt->execute([$engine_id]);
$engine = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container my-4 text-center">
  <h2 class="fw-bold">
    🧰 Pjeset per <?= htmlspecialchars($engine['maker_name']) ?>
    – <?= htmlspecialchars($engine['model_name']) ?>
  </h2>
  <p class="text-muted">
    Motor: <?= htmlspecialchars($engine['engine_name']) ?>
    (<?= htmlspecialchars($engine['displacement']) ?> <?= htmlspecialchars($engine['fuel_type']) ?>, <?= htmlspecialchars($engine['power_hp']) ?> HP)
  </p>
</div>


<div class="category-scroll d-flex overflow-auto pb-3">
  <?php
  $cats = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($cats as $cat):
  ?>
    <div class="category-item flex-shrink-0 me-3" style="width:230px;">
      <div class="card border-0 shadow-sm text-center h-100"
           onclick="window.location='products.php?category=<?= $cat['id'] ?>&engine=<?= $engine_id ?>'">
        <img src="assets/uploads/<?= htmlspecialchars($cat['image']) ?>" class="card-img-top p-3" style="height:140px;object-fit:contain;">
        <div class="card-body">
          <h6 class="fw-bold"><?= htmlspecialchars($cat['name']) ?></h6>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
