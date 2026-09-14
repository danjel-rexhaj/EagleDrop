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


/* Markat e disponueshme per kete kategori/automjet, para se te aplikohet filtri i markes */
$brandStmt = $conn->prepare("$sql AND brand IS NOT NULL AND brand <> '' ORDER BY brand ASC");
$brandStmt->execute($params);
$availableBrands = array_values(array_unique(array_column($brandStmt->fetchAll(PDO::FETCH_ASSOC), 'brand')));


$brand = trim($_GET['brand'] ?? '');

if ($brand !== '') {
    $sql .= " AND brand = ?";
    $params[] = $brand;
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
    if (window.history.length > 1) {
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

    <?php if (!empty($availableBrands)): ?>
    <form method="GET" id="brandFilterBar" class="brand-filter-bar d-flex align-items-center flex-wrap gap-2 mb-4">
        <input type="hidden" name="category" value="<?= htmlspecialchars($category_id) ?>">
        <input type="hidden" name="include_sub" value="<?= htmlspecialchars($include_sub) ?>">
        <input type="hidden" name="maker" value="<?= htmlspecialchars($maker) ?>">
        <input type="hidden" name="model" value="<?= htmlspecialchars($model) ?>">
        <input type="hidden" name="model_id" value="<?= htmlspecialchars($model_id) ?>">
        <input type="hidden" name="engine" value="<?= htmlspecialchars($engine) ?>">

        <span class="brand-filter-icon">⚙️</span>
        <label for="brandSelect" class="fw-semibold small mb-0">Marka:</label>
        <select id="brandSelect" name="brand" class="form-select form-select-sm w-auto" onchange="filterByBrand(this.value)">
            <option value="">Te gjitha markat</option>
            <?php foreach ($availableBrands as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= $brand === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($brand !== ''): ?>
            <a href="?category=<?= urlencode($category_id) ?>&include_sub=<?= urlencode($include_sub) ?>&maker=<?= urlencode($maker) ?>&model=<?= urlencode($model) ?>&model_id=<?= urlencode($model_id) ?>&engine=<?= urlencode($engine) ?>"
               class="clear-filter-btn ms-1" onclick="event.preventDefault(); loadCategory(this.href);">✕ Fshij filtrin</a>
        <?php endif; ?>
    </form>
    <?php endif; ?>

<div id="productsList">
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
</div>

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


.brand-filter-bar {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 14px;
  padding: 14px 18px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.brand-filter-icon {
  font-size: 15px;
}

.brand-filter-bar label {
  color: #495057;
}

.brand-filter-bar select {
  border-radius: 8px;
  min-width: 180px;
  border-color: rgba(0,0,0,0.15);
}

.brand-filter-bar select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
}

.clear-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 12px;
  border-radius: 20px;
  background: rgba(220,53,69,0.1);
  color: #dc3545 !important;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none !important;
  transition: background-color .2s;
}

.clear-filter-btn:hover {
  background: rgba(220,53,69,0.2);
}

#productsList {
  transition: opacity .15s ease-in-out;
}

body:not(.light-mode) .brand-filter-bar {
  background: linear-gradient(145deg, #1f2225, #1b1e21);
  border-color: rgba(90,160,255,0.25);
}

body:not(.light-mode) .brand-filter-bar label {
  color: #e4e6eb;
}

body:not(.light-mode) .brand-filter-bar select {
  background-color: #16181b;
  color: #e4e6eb;
  border-color: rgba(90,160,255,0.35);
}

body:not(.light-mode) .clear-filter-btn {
  background: rgba(255,138,138,0.15);
  color: #ff8a8a !important;
}

body:not(.light-mode) .clear-filter-btn:hover {
  background: rgba(255,138,138,0.25);
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

async function loadCategory(url, pushState = true) {
  const list = document.getElementById('productsList');
  if (list) list.style.opacity = '0.4';

  try {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
    const html = await res.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');

    const newList = doc.getElementById('productsList');
    const currentList = document.getElementById('productsList');
    if (newList && currentList) {
      currentList.replaceWith(newList);
    }

    const newFilterBar = doc.getElementById('brandFilterBar');
    const currentFilterBar = document.getElementById('brandFilterBar');
    if (newFilterBar && currentFilterBar) {
      currentFilterBar.replaceWith(newFilterBar);
    }

    if (pushState) {
      history.pushState({}, '', url);
    }
  } catch (e) {
    window.location.href = url;
  }
}

function filterByBrand(brand) {
  const url = new URL(window.location.href);
  if (brand) {
    url.searchParams.set('brand', brand);
  } else {
    url.searchParams.delete('brand');
  }
  loadCategory(url.toString());
}

window.addEventListener('popstate', function () {
  loadCategory(window.location.href, false);
});

</script>


