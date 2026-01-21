<link rel="stylesheet" href="https://style.css">
<?php
include 'includes/header.php';
require 'config/database.php';

$q = trim($_GET['q'] ?? '');

if ($q) {
  $stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE title LIKE :q 
       OR description LIKE :q 
       OR keywords LIKE :q 
       OR brand LIKE :q 
       OR fit_model LIKE :q
       OR fit_engine LIKE :q
       OR fit_maker LIKE :q
    ORDER BY created_at DESC
  ");
  $stmt->execute(['q' => "%$q%"]);
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $products = [];
}

?>
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
<div class="container mt-4">
  <h3 class="fw-bold mb-4">🔍 Rezultatet e kerkimit per: "<?= htmlspecialchars($q) ?>"</h3>

<div class="row">
  <?php if (empty($products)): ?>
    <p class="text-muted">Asnje produkt nuk u gjet per kete kerkim.</p>
  <?php else: ?>
    <?php foreach ($products as $p): ?>
      <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
        <div class="card shadow-sm h-100 product-card">
          <img src="assets/uploads/<?= htmlspecialchars($p['image']) ?>"
               class="card-img-top"
               style="height:200px; object-fit:cover; cursor:pointer"
               onclick="window.location='product_details.php?id=<?= $p['id'] ?>'">

          <div class="card-body d-flex flex-column justify-content-between">
            <div>
              <h6 class="text-primary" style="cursor:pointer"
                  onclick="window.location='product_details.php?id=<?= $p['id'] ?>'">
                <?= htmlspecialchars($p['title']) ?>
              </h6>
              <p class="small text-muted product-desc"><?= htmlspecialchars($p['description']) ?></p>

              <h5 class="text-success mb-2">€<?= number_format($p['price'], 2) ?></h5>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-auto">
              <button class="btn btn-sm btn-outline-success" onclick="addToCart(<?= $p['id'] ?>)">
                Add to Cart
              </button>
              <form action="checkout_single.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-outline-primary">Buy Now</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


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

</script>

<style>
.product-card {
    border-radius: 14px;
    transition: 0.2s ease-in-out;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}

.product-img {
    height: 200px;
    width: 100%;
    object-fit: cover;
    cursor: pointer;
}

.product-desc {
    height: 42px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical;
}







@media (max-width: 768px) {

  .category-item {
    flex-direction: column;     
    align-items: flex-start;
  }

  .list-img {
    width: 100%;
    height: 180px;
    margin-bottom: 10px;
  }

  .list-img img {
    max-width: 100%;
    max-height: 100%;
  }

  .list-info {
    width: 100%;
    margin-left: 0 !important;
  }

  .list-actions {
    width: 100%;
    margin-left: 0 !important;
    margin-top: 10px;
  }

  .list-btn {
    width: 100%;
    font-size: 14px;
  }

}



</style>
