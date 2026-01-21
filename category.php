<?php
require 'config/database.php';
include 'includes/header.php';



$category_id = intval($_GET['category'] ?? 0);
$include_sub = intval($_GET['include_sub'] ?? 0);

$maker     = trim($_GET['maker']  ?? '');
$model     = trim($_GET['model']  ?? '');
$engine    = trim($_GET['engine'] ?? '');

$products = [];


if ($category_id <= 0) {
    die("Gabim: category_id mungon!");
}


$category_ids = [$category_id];

if ($include_sub) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $subs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($subs)) {
        $category_ids = array_merge($category_ids, $subs);
    }
}


$placeholders = implode(',', array_fill(0, count($category_ids), '?'));

$sql = "SELECT * FROM products 
        WHERE category_id IN ($placeholders)";

$params = $category_ids;


if ($maker !== '') {
    $sql .= " AND fit_maker LIKE ?";
    $params[] = "%$maker%";
}


$model_id = intval($_GET['model_id'] ?? 0);

if ($model !== '') {
    $sql .= " AND (fit_model LIKE ? OR fit_model_id = ?)";
    $params[] = "%$model%";
    $params[] = $model_id;
}


if ($engine !== '') {
    $sql .= " AND (fit_engine LIKE ? OR fit_engine IS NULL)";
    $params[] = "%$engine%";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$catName = $stmt->fetchColumn() ?: "Kategori";
?>
<link rel="stylesheet" href="assets/css/style.css">
 <div class="container mt-3">
    <button class="back-btn" onclick="goBack()">
        <span class="arrow">←</span>
        <span>Kthehu</span>
    </button>
</div>

<script>
function goBack() {
    if (document.referrer) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}
</script>
<div class="container mt-5">
    <h2 class="fw-bold mb-4 text-center">
        🔧 Produkte per kategorine: <?= htmlspecialchars($catName) ?>
    </h2>

<?php if (!empty($products)): ?>
<div class="category-list">

<?php foreach ($products as $p): ?>
  <div class="category-item d-flex align-items-center p-3 mb-3 w-100 shadow-sm"
     onclick="goToProduct(<?= $p['id'] ?>)">

    <div class="list-img"
         onclick="window.location='product_details.php?id=<?= $p['id'] ?>'">
      <img src="assets/uploads/<?= htmlspecialchars($p['image']) ?>" alt="">
    </div>

    <div class="list-info flex-grow-1 ms-3">
      <h6 class="fw-bold mb-1">
        <a href="product_details.php?id=<?= $p['id'] ?>"
          class="product-title-link">
          <?= htmlspecialchars($p['title']) ?>
        </a>
      </h6>


      <p class="small text-muted mb-0">
        <?= htmlspecialchars($p['description']) ?>
      </p>
    </div>

    <div class="list-actions text-end ms-3">
      <div class="fw-bold text-success mb-2">
        €<?= number_format($p['price'], 2) ?>
      </div>

      <button class="btn btn-sm btn-outline-success list-btn"
        onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>)">
        Add to Cart
      </button>


      <form action="checkout_single.php" method="POST"
      onclick="event.stopPropagation();">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <button class="btn btn-sm btn-outline-primary list-btn">
          Buy Now
        </button>
      </form>


    </div>

  </div>
<?php endforeach; ?>

</div>
<?php else: ?>
  <div class="text-center text-muted py-5">
    <h4>🚫 Nuk u gjeten produkte per kete kategori per kete makine.</h4>
  </div>
<?php endif; ?>

<style>

.category-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}



.category-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 16px;

  background-color: #ffffff;
  border: 1.5px solid rgba(13,110,253,0.35);
  border-radius: 14px;

  padding: 16px;
  cursor: pointer;

  transition: background-color .25s, border-color .25s, box-shadow .25s;
}


.category-item:hover {
  background-color: #f8faff;
  border-color: rgba(13,110,253,0.85);
  box-shadow: 0 0 0 2px rgba(13,110,253,0.15);
}


.list-img {
  width: 140px;
  min-width: 140px;
  height: 100px;

  display: flex;
  align-items: center;
  justify-content: center;
}

.list-img img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}



.list-info {
  flex-grow: 1;
  min-width: 0;
  color: white;
}

.product-title-link {
  color: #0d6efd;
  font-weight: 600;
  text-decoration: none;
}

.product-title-link:hover {
  text-decoration: underline;
}


.list-actions {
  width: 140px;
  min-width: 140px;

  display: flex;
  flex-direction: column;
  gap: 6px;
}

.list-btn {
  width: 100%;
  padding: 6px 10px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 6px;
}


body:not(.light-mode) .category-item {
  background: linear-gradient(145deg, #1f2225, #1b1e21);
  border-color: rgba(90,160,255,0.35);
  color: #e4e6eb;
}

body:not(.light-mode) .category-item:hover {
  background: linear-gradient(145deg, #262a2e, #1f2326);
  border-color: rgba(90,160,255,0.9);
  box-shadow: 0 0 0 2px rgba(90,160,255,0.25);
}


body:not(.light-mode) .list-img {
  background-color: #111315;
  border-radius: 10px;
}

body:not(.light-mode) .list-img img {
  filter: drop-shadow(0 4px 10px rgba(0,0,0,.6));
}


body:not(.light-mode) .product-title-link {
  color: #9ec5fe;
}

body:not(.light-mode) .product-title-link:hover {
  color: #cfe2ff;
}

body:not(.light-mode) .category-item p {
  color: #cfd2d6;
}


body:not(.light-mode) .category-item .text-success {
  color: #4ade80 !important;
  font-weight: 700;
}


body:not(.light-mode) .btn-outline-success {
  border-color: #4ade80;
  color: #4ade80;
}

body:not(.light-mode) .btn-outline-success:hover {
  background-color: #4ade80;
  color: #0f172a;
}

body:not(.light-mode) .btn-outline-primary {
  border-color: #60a5fa;
  color: #60a5fa;
}

body:not(.light-mode) .btn-outline-primary:hover {
  background-color: #60a5fa;
  color: #0f172a;
}



@media (max-width: 768px) {
  .category-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .list-img {
    width: 100%;
    height: 180px;
  }

  .list-actions {
    width: 100%;
    margin-top: 10px;
  }

  .list-btn {
    font-size: 14px;
  }
}
</style>


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
      showCartToast(data.error || "Gabim gjate shtimit ne shporte");
      return;
    }

    const cartIcon = document.querySelector(".bi-cart2");
    if (cartIcon) {
      let badge = cartIcon.nextElementSibling;
      if (!badge || !badge.classList.contains("badge")) {
        badge = document.createElement("span");
        badge.className =
          "position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger";
        cartIcon.parentElement.appendChild(badge);
      }
      badge.textContent = data.cart_count;
    }

    showCartToast("🛒 Produkti u shtua ne shporte");
  })
  .catch(() => {
    showCartToast("⚠️ Gabim rrjeti");
  });
}

function showCartToast(message) {
  const toastEl = document.getElementById("cartToast");
  if (!toastEl) return;

  const body = toastEl.querySelector(".toast-body");
  if (body) body.textContent = message;

  new bootstrap.Toast(toastEl).show();
}

function goToProduct(id) {
  window.location.href = "product_details.php?id=" + id;
}

</script>


