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


            <div class="col-md-6 d-flex justify-content-center">
                <img src="assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                    class="img-fluid rounded"
                    style="max-height: 420px; object-fit: contain;">
            </div>


            <div class="col-md-6">

                <h2 class="fw-bold mb-3"><?= htmlspecialchars($product['title']) ?></h2>

                <p class="text-muted" style="font-size: 1.05rem; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </p>

                <h3 class="text-success fw-bold mb-4">
                    €<?= number_format($product['price'], 2) ?>
                </h3>


                <div class="d-flex gap-3">


                 
                <button onclick="addToCart(<?= $product['id'] ?>)" 
                        class="btn btn-outline-success list-btn btn-lg-custom">
                    Add to Cart
                </button>

                <form action="checkout_single.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button class="btn btn-outline-primary list-btn btn-lg-custom">
                        Buy Now
                    </button>
                </form>


                </div>


            </div>

        </div>

    </div>

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
  padding: 15px 21px;
  font-size: 18px;
  font-weight: 400;
  border-radius: 10px;
}

</style>



