<?php
include 'includes/header.php';
require 'config/database.php';


$stmt = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
  <h2 class="fw-bold text-center mb-4">🔧 Kategorite e Auto-Pjeseve</h2>

  <div class="row">
    <?php foreach ($categories as $cat): ?>
      <?php

        $stmt = $conn->prepare("SELECT * FROM categories WHERE parent_id = ?");
        $stmt->execute([$cat['id']]);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm text-center category-card">
          <?php if ($cat['image']): ?>
            <img src="assets/uploads/<?= htmlspecialchars($cat['image']) ?>" class="card-img-top p-3" style="height:160px; object-fit:contain;">
          <?php endif; ?>
          <div class="card-body">
            <h5 class="fw-semibold mb-3"><?= htmlspecialchars($cat['name']) ?></h5>
            <ul class="list-unstyled small text-primary">
              <?php foreach ($subs as $sub): ?>
                <li><a href="products.php?category=<?= $sub['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($sub['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
.category-card {
  border: none;
  border-top: 3px solid transparent;
  transition: 0.25s ease-in-out;
}
.category-card:hover {
  border-top-color: #ff6600;
  transform: translateY(-5px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.1);
}
.category-card a:hover { color: #ff6600; }
</style>

<?php include 'includes/footer.php'; ?>
