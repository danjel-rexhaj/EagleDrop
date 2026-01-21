<link rel="stylesheet" href="assets/css/style.css">
<?php
include 'includes/header.php';
require 'config/database.php';

$products = [];

$maker     = trim($_GET['maker']  ?? '');
$model     = trim($_GET['model']  ?? '');
$engine    = trim($_GET['engine'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$model_id = intval($_GET['model_id'] ?? 0); // <= FIX

$query = "SELECT * FROM products WHERE 1";
$params = [];

/* Filtrim sipas kategorise */
if ($category_id) {
    $query .= " AND category_id = ?";
    $params[] = $category_id;
}

/* Filtrim sipas maker */
if ($maker !== '') {
    $query .= " AND fit_maker LIKE ?";
    $params[] = "%$maker%";
}

/* Filtrim sipas model */
if ($model !== '') {
    // heq vitet (p.sh "W212 (2009-2016)" → "W212")
    $cleanModel = trim(strtok($model, '('));

    $query .= " AND fit_model LIKE ?";
    $params[] = "%$cleanModel%";
}

/* Filtrim sipas motorrit */
if ($engine !== '') {
    // heq HP nga motori
    $cleanEngine = trim(strtok($engine, '('));

    $query .= " AND fit_engine LIKE ?";
    $params[] = "%$cleanEngine%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-3">
    <button class="back-btn" onclick="goBack()">
        <span class="arrow">←</span>
        <span>Kthehu</span>
    </button>
</div>

<link rel="stylesheet" href="assets/css/style.css">

<script>
function goBack() {
    if (document.referrer) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}
</script>



<!-- ============================= -->
<!--     KATEGORITË E PJESËVE     -->
<!-- ============================= -->
<div class="category-header text-center my-4">
    <?php if (empty($_GET['maker']) || empty($_GET['model'])): ?>
        <h2 class="fw-bold">
            <span class="emoji">🔧</span> Find your car
        </h2>
        <p class="text-muted">Select your vehicle’s <strong>maker</strong> and <strong>model</strong> to view the top part categories.</p>
    <?php else: ?>
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-4 my-3">
            <div class="text-center text-md-start">
                <h2 class="fw-bold mb-2">
                    <span class="emoji">🔧</span> Top parts categories for 
                    <span class="text-highlight"><?= htmlspecialchars($_GET['maker'] . ' ' . $_GET['model']) ?></span>
                </h2>
                <p class="text-muted mb-0">Choose a category below to explore all compatible parts.</p>
            </div>

            <div class="car-wrapper">
                <img src="assets/uploads/universalphotocar.jpeg" 
                    alt="Car image"
                    class="car-illustration">
            </div>
        </div>
    <?php endif; ?>
</div>

    <div class="category-scroll-wrapper position-relative">

  <!-- SHIGJETA MAJTAS -->
  <button class="cat-arrow left" onclick="scrollCategories(-1)">
    ‹
  </button>

  <!-- KATEGORITË -->
  <div class="category-scroll d-flex overflow-auto pb-3">
    <!-- KËTU MBETET KODI YT I KATEGORIVE PA NDRYSHIM -->

<?php
// Merr kategorite prind (pa parent_id)
$stmt = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY id ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $cat):

    // Merr nenkategorite
    $stmt2 = $conn->prepare("SELECT id, name FROM categories WHERE parent_id = ? ORDER BY id ASC");
    $stmt2->execute([$cat['id']]);
    $subs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $imageBase = !empty($cat['image']) ? pathinfo($cat['image'], PATHINFO_FILENAME) : $cat['name'];
    $imageName = preg_replace('/\s+/', '_', trim($imageBase));
    $imageName = str_replace(['&', '(', ')', '-', "'", ',', '.', '/'], '_', $imageName);

    $possiblePaths = [
        "assets/uploads/" . strtolower($imageName) . ".png",
        "assets/uploads/" . ucfirst($imageName) . ".png",
        "assets/uploads/" . strtolower($imageName) . ".jpg",
        "assets/uploads/" . ucfirst($imageName) . ".jpg"
    ];

    $imagePath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $imagePath = $path;
            break;
        }
    }

    if (empty($imagePath)) {
        $imagePath = "assets/uploads/" . $imageName . ".png";
    }
?>

    <div class="category-item flex-shrink-0 me-3" style="width:230px;">
        <div class="card border-0 shadow-sm h-100 text-center category-card"
             style="cursor:pointer;"
             onclick="window.location='category.php?category=<?=$cat['id']?>&include_sub=1&maker_id=<?=urlencode($_GET['maker_id'] ?? '')?>&model_id=<?=urlencode($_GET['model_id'] ?? '')?>&engine_id=<?=urlencode($_GET['engine_id'] ?? '')?>&maker=<?=urlencode($_GET['maker'] ?? '')?>&model=<?=urlencode($_GET['model'] ?? '')?>&engine=<?=urlencode($_GET['engine']??'')?>'">

            <img src="<?= htmlspecialchars($imagePath) ?>" 
                 alt="<?= htmlspecialchars($cat['name']) ?>"
                 class="card-img-top p-3"
                 style="height:140px; object-fit:contain;">

            <div class="card-body text-start">

                <!-- TITULLI I KATEGORISË KRYESORE – I RREGULLUAR -->
                <h6 class="fw-bold text-center mb-2">
                    <a href="category.php?category=<?=urlencode($cat['id'])?>&include_sub=1&maker_id=<?=urlencode($_GET['maker_id'] ?? '')?>&model_id=<?=urlencode($_GET['model_id'] ?? '')?>&engine_id=<?=urlencode($_GET['engine_id'] ?? '')?>&maker=<?=urlencode($_GET['maker']?? '')?>&model=<?=urlencode($_GET['model']?? '')?>&engine=<?=urlencode($_GET['engine']?? '')?>"onclick="event.stopPropagation();"class="text-decoration-none text-dark"><?= htmlspecialchars($cat['name']) ?>
                    </a>
                </h6>

                <?php if ($subs): ?>
                    <ul class="list-unstyled text-center small mb-2">

                        <?php foreach ($subs as $sub): ?>
                            <li class="mb-1">

                            <!-- LINKU I NËNKATEGORISË – I RREGULLUAR -->
                            <a href="category.php?category=<?=urlencode($sub['id'])?>&maker_id=<?=urlencode($_GET['maker_id'] ?? '')?>&model_id=<?=urlencode($_GET['model_id'] ?? '')?>&engine_id=<?=urlencode($_GET['engine_id'] ?? '')?>&maker=<?=urlencode($_GET['maker'] ?? '')?>&model=<?=urlencode($_GET['model'] ?? '')?>&engine=<?=urlencode($_GET['engine'] ?? '')?>"class="text-decoration-none text-primary"onclick="event.stopPropagation();"><?= htmlspecialchars($sub['name'])?>
                            </a>

                            </li>
                        <?php endforeach; ?>

                    </ul>
                <?php else: ?>
                    <p class="text-muted small text-center mb-0">Pa nenkategori</p>
                <?php endif; ?>

            </div>
        </div>
    </div>

<?php endforeach; ?>
  </div>

  <!-- SHIGJETA DJATHTAS -->
  <button class="cat-arrow right" onclick="scrollCategories(1)">
    ›
  </button>

</div>

    </div>
    
<?php if (!empty($products)): ?>
    <div class="row g-4">
        <?php foreach ($products as $p): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">

                <div class="card shadow-sm product-card h-100 p-2">

                    <!-- Foto e madhe -->
                    <div class="product-img-wrap"
                         onclick="window.location='product_details.php?id=<?= $p['id'] ?>'">
                        <img src="assets/uploads/<?= htmlspecialchars($p['image']) ?>"
                             class="product-img">
                    </div>

                    <div class="card-body d-flex flex-column">

                        <!-- Titulli -->
                        <h6 class="fw-bold text-primary mb-1"
                            style="cursor:pointer"
                            onclick="window.location='product_details.php?id=<?= $p['id'] ?>'">
                            <?= htmlspecialchars($p['title']) ?>
                        </h6>

                        <!-- Pershkrimi -->
                        <p class="small text-muted product-desc">
                            <?= htmlspecialchars($p['description']) ?>
                        </p>

                        <!-- Çmimi -->
                        <h5 class="text-success fw-bold mb-3">
                            €<?= number_format($p['price'], 2) ?>
                        </h5>

                        <!-- Butonat -->
                        <div class="d-flex gap-2 mt-auto">
                            <button class="btn btn-success btn-sm w-50"
                                    onclick="addToCart(<?= $p['id'] ?>)">
                                🛒 Add to Cart
                            </button>

                            <form action="checkout_single.php"
                                  method="POST" class="w-50">
                                <input type="hidden"
                                       name="product_id"
                                       value="<?= $p['id'] ?>">
                                <button class="btn btn-primary btn-sm w-100">
                                    ⚡ Buy Now
                                </button>
                            </form>
                        </div>

                    </div>

                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php
// ============================
// FALLBACK: Merr produkte te pershtatshme per modelin / motorin
// ============================

$fallback_sql = "SELECT * FROM products WHERE 1";
$fallback_params = [];

// Filtrim sipas modelit
if ($model !== '') {
    $fallback_sql .= " AND (fit_model LIKE ? OR fit_model_id = ?)";
    $fallback_params[] = "%$model%";
    $fallback_params[] = $model_id;
    
}

// Filtrim sipas motorrit
if ($engine !== '') {
    $fallback_sql .= " AND (fit_engine LIKE ? OR fit_engine IS NULL)";
    $fallback_params[] = "%$engine%";
}

// Nese s’ka asnje filter (rast shume i rralle), mos e boshatis faqen
if (empty($fallback_params)) {
    $fallback_sql .= " LIMIT 12";
}

// Randomizo produktet
$fallback_sql .= " ORDER BY RAND() LIMIT 12";

$stmt_fb = $conn->prepare($fallback_sql);
$stmt_fb->execute($fallback_params);
$fallback_products = $stmt_fb->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="text-center py-4 compatible-title">
    <h5>ℹ Produkte qe i pershtaten automjetit tuaj.</h5>
</div>

<div class="row">
<?php foreach ($fallback_products as $p): ?>
  <div class="col-lg-3 col-md-4 col-sm-6 mb-4">

    <div class="card shadow-sm h-100 product-card product-click p-2"
         data-href="product_details.php?id=<?= $p['id'] ?>">

      <img src="assets/uploads/<?= htmlspecialchars($p['image']) ?>"
           class="card-img-top"
           style="height:200px; object-fit:cover">

      <div class="card-body d-flex flex-column justify-content-between">

        <div>
          <h6 class="text-primary">
            <?= htmlspecialchars($p['title']) ?>
          </h6>

          <p class="small text-muted short-description">
            <?= htmlspecialchars($p['description']) ?>
          </p>

          <h5 class="text-success mb-2">
            €<?= number_format($p['price'], 2) ?>
          </h5>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-auto">
          <button class="btn btn-sm btn-outline-success"
                  onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>)">
            Add to Cart
          </button>

          <form action="checkout_single.php" method="POST"
                onClick="event.stopPropagation();">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <button class="btn btn-sm btn-outline-primary">
              Buy Now
            </button>
          </form>
        </div>

      </div>
    </div>

  </div>
<?php endforeach; ?>
</div>


<script>

function addToCart(id) {
  fetch("add_to_cart.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "product_id=" + encodeURIComponent(id)
  })
  .then(res => res.json())
  .then(data => {

    if (!data.success) {
      alert(data.error || "Gabim gjate shtimit ne shporte.");
      return;
    }

 
    let badge = document.querySelector(".bi-cart2").nextElementSibling;

    if (!badge) {
      const span = document.createElement("span");
      span.className =
        "position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger";
      span.textContent = data.cart_count;
      document.querySelector(".bi-cart2").parentElement.appendChild(span);
    } else {
      badge.textContent = data.cart_count;
    }

  
    let toast = new bootstrap.Toast(document.getElementById("cartToast"));
    toast.show();
  })
  .catch(() => {

    let toast = new bootstrap.Toast(document.getElementById("cartToast"));
    toast.show();
  });
}


document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".product-click").forEach(card => {
    card.addEventListener("click", function () {
      const url = this.dataset.href;
      if (url) {
        window.location.href = url;
      }
    });
  });
});


function scrollCategories(direction) {
  const container = document.querySelector('.category-scroll');
  const scrollAmount = 320; 

  container.scrollBy({
    left: direction * scrollAmount,
    behavior: 'smooth'
  });
}
</script>

<style>

.car-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-illustration {
  width: 85px;
  transition: 0.2s;
}

.car-illustration:hover {
  transform: scale(1.05);
}


.product-card {
  border-radius: 14px;
  transition: 0.2s ease-in-out;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}


.product-img-wrap {
  height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: 12px;
  cursor: pointer;
}

.product-img {
  max-height: 210px;
  object-fit: contain;
}


.product-desc {
  height: 42px;
  overflow: hidden;
  text-overflow: ellipsis;
}


body:not(.light-mode) .product-card,
body:not(.light-mode) .category-card {
  background-color: #1f2225 !important;
  color: #e4e6eb;
  border: 1px solid rgba(90,160,255,0.25);
}

body:not(.light-mode) .product-card h6,
body:not(.light-mode) .category-card h6 {
  color: #9ec5fe !important;
}

body:not(.light-mode) .product-card p,
body:not(.light-mode) .category-card p,
body:not(.light-mode) .category-card li {
  color: #cfd2d6 !important;
}

body:not(.light-mode) .product-card .text-success {
  color: #4ade80 !important;
}

body:not(.light-mode) .product-card:hover,
body:not(.light-mode) .category-card:hover {
  box-shadow: 0 0 0 2px rgba(90,160,255,0.25);
}

.compatible-title {
  color: #6c757d;
}

body:not(.light-mode) .compatible-title {
  color: #f1f3f5;
}


.category-scroll-wrapper {
  position: relative;
  width: 100%;
}

.category-scroll-viewport {
  overflow: hidden;
  width: 100%;
}

.category-scroll {
  display: flex;
  gap: 16px;
  scroll-behavior: smooth;
  padding: 6px 4px;
}

.category-item {
  flex: 0 0 230px;
}


.cat-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;

  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: none;

  font-size: 26px;
  font-weight: bold;
  cursor: pointer;

  display: flex;
  align-items: center;
  justify-content: center;
  transition: 0.25s ease;
}

.cat-arrow.left { left: -18px; }
.cat-arrow.right { right: -18px; }

body.light-mode .cat-arrow {
  background: #ffffff;
  color: #111;
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

body:not(.light-mode) .cat-arrow {
  background: #1f2225;
  color: #9ec5fe;
  box-shadow: 0 0 0 2px rgba(90,160,255,0.35);
}

.cat-arrow:hover {
  transform: translateY(-50%) scale(1.1);
}

@media (max-width: 768px) {
  .cat-arrow {
    display: none;
  }
}

</style>
