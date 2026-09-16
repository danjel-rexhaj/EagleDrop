<?php
require 'config/database.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div class='container mt-5'><h3>Produkti nuk u gjet!</h3></div>";
    include 'includes/footer.php';
    exit;
}

/* Ndaj pershkrimin ne tekst te lire dhe specifikime tip "Emri: Vlera" */
$descLines = preg_split('/\r\n|\r|\n/', trim($product['description'] ?? ''));
$descText = [];
$specs = [];
foreach ($descLines as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    if (preg_match('/^([^:]{2,40}):\s*(.+)$/u', $line, $m)) {
        $specs[] = [trim($m[1]), trim($m[2])];
    } else {
        $descText[] = $line;
    }
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
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}
</script>

<style>
.product-card-wrapper {
    background: #ffffff;
    padding: 35px;
    border-radius: 16px;
    color:black;
    box-shadow: 0 8px 28px rgba(0,0,0,0.12);
}
</style>

<div class="container mt-5 product-page">

    <div class="product-card-wrapper">

        <div class="row align-items-start g-4">


            <div class="col-12 col-md-6 d-flex justify-content-center">
                <img src="assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                    class="img-fluid rounded"
                    style="max-height: 420px; object-fit: contain;">
            </div>


            <div class="col-12 col-md-6">

                <h2 class="fw-bold mb-3"><?= htmlspecialchars($product['title']) ?></h2>

                <h3 class="text-success fw-bold mb-3">
                    €<?= number_format($product['price'], 2) ?>
                </h3>

                <?php if ($descText): ?>
                    <p class="text-muted product-free-text">
                        <?= nl2br(htmlspecialchars(implode("\n", $descText))) ?>
                    </p>
                <?php endif; ?>

                <?php if ($specs): ?>
                    <table class="table table-sm spec-table mb-4">
                        <tbody>
                            <?php foreach ($specs as [$specKey, $specValue]): ?>
                                <tr>
                                    <th><?= htmlspecialchars($specKey) ?></th>
                                    <td><?= htmlspecialchars($specValue) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>


                <div class="d-flex flex-wrap gap-3">



                <button onclick="addToCart(<?= $product['id'] ?>)"
                        class="btn btn-outline-success list-btn btn-lg-custom">
                    🛒 Add to Cart
                </button>

                <form action="checkout_single.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button class="btn list-btn btn-lg-custom btn-buy-now">
                        ⚡ Buy Now
                    </button>
                </form>


                </div>


            </div>

        </div>

    </div>

    <?php
    $relatedProducts = [];
    if (!empty($product['category_id'])) {
        $stmtRel = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 8");
        $stmtRel->execute([$product['category_id'], $product['id']]);
        $relatedProducts = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    }
    if (!$relatedProducts) {
        $stmtRel = $conn->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 8");
        $stmtRel->execute([$product['id']]);
        $relatedProducts = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>

    <?php if ($relatedProducts): ?>
    <div class="related-title mt-5 mb-3">
        <h5>Produkte te ngjashme</h5>
    </div>
    <div class="row g-3 g-lg-4">
        <?php foreach ($relatedProducts as $rp):
            $rpTitle = mb_strlen($rp['title']) > 34 ? mb_substr($rp['title'], 0, 34) . '…' : $rp['title'];
        ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="product_details.php?id=<?= $rp['id'] ?>" class="related-card-link">
                    <div class="card h-100 related-card">
                        <div class="related-card-img">
                            <img src="assets/uploads/<?= htmlspecialchars($rp['image']) ?>" loading="lazy" alt="">
                        </div>
                        <div class="card-body">
                            <h6 class="related-card-title"><?= htmlspecialchars($rpTitle) ?></h6>
                            <div class="related-card-price">€<?= number_format($rp['price'], 2) ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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


        if (!data || typeof data !== "object") {
            alert("Gabim ne pergjigjen e serverit.");
            return;
        }

        if (data.success) {


            let badge = document.querySelector(".bi-cart2").nextElementSibling;
            if (!badge) {
                const span = document.createElement("span");
                span.className = "position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger";
                span.textContent = data.cart_count;
                document.querySelector(".bi-cart2").parentElement.appendChild(span);
            } else {
                badge.textContent = data.cart_count;
            }


            let toast = new bootstrap.Toast(document.getElementById("cartToast"));
            toast.show();

        } else {
            alert(data.error || "Gabim gjate shtimit ne shporte.");
        }
    })
}
</script>

<?php include 'includes/footer.php'; ?>
<style>


.product-card-wrapper {
    background: #ffffff;
    padding: 35px;
    border-radius: 16px;
    color: #000;
    box-shadow: 0 8px 28px rgba(0,0,0,0.12);
}

body:not(.light-mode) .product-card-wrapper {
    background: linear-gradient(145deg, #1f2225, #1b1e21);
    color: #e4e6eb;
    box-shadow: 0 10px 30px rgba(0,0,0,0.6);
}


body:not(.light-mode) .product-card-wrapper h2 {
    color: #9ec5fe;
}


body:not(.light-mode) .product-card-wrapper p {
    color: #cfd2d6;
}


body:not(.light-mode) .product-card-wrapper .text-success {
    color: #4ade80 !important;
}


body:not(.light-mode) .product-card-wrapper img {
    background-color: #111315;
    border-radius: 12px;
}

.btn-lg-custom {
  padding: 13px 26px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 30px;
  transition: transform .18s ease, box-shadow .18s ease;
}

.btn-lg-custom:hover {
  transform: translateY(-2px);
}

.btn-outline-success.btn-lg-custom:hover {
  box-shadow: 0 8px 18px rgba(25, 135, 84, 0.25);
}

.btn-buy-now {
  background: linear-gradient(135deg, #4d7fff, #2f5fe0);
  color: #fff;
  border: none;
  box-shadow: 0 6px 16px rgba(45, 95, 224, 0.35);
}

.btn-buy-now:hover {
  color: #fff;
  box-shadow: 0 10px 22px rgba(45, 95, 224, 0.45);
}

.related-title h5 {
  font-weight: 700;
  color: #495057;
}

body:not(.light-mode) .related-title h5 {
  color: #e4e6eb;
}

.related-card-link {
  text-decoration: none;
}

.related-card {
  border-radius: 14px;
  border: 1px solid #e9ecef;
  padding: 10px;
  transition: transform .2s ease, box-shadow .2s ease;
}

.related-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 22px rgba(0,0,0,0.12);
}

.related-card-img {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #f1f3f5;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 8px;
}

.related-card-img img {
  max-height: 80px;
  max-width: 88%;
  object-fit: contain;
}

.related-card .card-body {
  padding: 0;
}

.related-card-title {
  font-size: 0.82rem;
  font-weight: 600;
  color: #212529;
  height: 2.1em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 6px;
}

.related-card-price {
  font-size: 0.88rem;
  font-weight: 700;
  color: #198754;
}

body:not(.light-mode) .related-card {
  background-color: #1f2225;
  border-color: rgba(90,160,255,0.25);
}

body:not(.light-mode) .related-card-title {
  color: #e4e6eb;
}

body:not(.light-mode) .related-card-price {
  color: #4ade80;
}

.product-free-text {
  font-size: 1.02rem;
  line-height: 1.6;
}

.spec-table {
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.08);
}

.spec-table th,
.spec-table td {
  padding: 10px 14px;
  vertical-align: middle;
  font-size: 0.92rem;
}

.spec-table th {
  width: 45%;
  color: #6c757d;
  font-weight: 600;
  background: rgba(0,0,0,0.03);
}

.spec-table tbody tr:nth-child(odd) {
  background: rgba(0,0,0,0.015);
}

body:not(.light-mode) .spec-table {
  border-color: rgba(90,160,255,0.2);
}

body:not(.light-mode) .spec-table th {
  color: #9ec5fe;
  background: rgba(90,160,255,0.08);
}

body:not(.light-mode) .spec-table td {
  color: #cfd2d6;
}

body:not(.light-mode) .spec-table tbody tr:nth-child(odd) {
  background: rgba(255,255,255,0.02);
}

</style>



