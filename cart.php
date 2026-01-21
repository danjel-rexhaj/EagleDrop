<?php
session_start();
require 'config/database.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    die("Duhet te jeni te loguar.");
}

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("
    SELECT cart_items.product_id, cart_items.quantity,
           products.title, products.price, products.image, products.description
    FROM cart_items
    JOIN products ON products.id = cart_items.product_id
    WHERE cart_items.user_id = ?
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container mt-3">
    <div class="container mt-3">
        <button class="back-btn" onclick="goBack()">
            <span class="arrow">←</span>
            <span>Kthehu</span>
        </button>
    </div>
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

<?php if (!$items): ?>


    <div class="container mt-4">
        <h3>Shporta juaj eshte bosh.</h3>
    </div>

<?php else: ?>

<div class="container mt-4">
    <div class="row">

        <div class="col-md-8">
            <h3 class="mb-3">Shporta juaj</h3>

            <?php foreach ($items as $p): 
                $itemTotal = $p['quantity'] * $p['price'];
            ?>

            <div class="cart-item d-flex p-3 mb-3 border rounded">

              
                <div class="me-3">
                    <img src="assets/uploads/<?= $p['image'] ?>" 
                         style="width:120px; height:120px; object-fit:cover;">
                </div>

               
                <div class="flex-grow-1">
                    <h5><?= $p['title'] ?></h5>
                    <p class="text-muted small"><?= $p['description'] ?></p>

                    <div class="d-flex align-items-center gap-3 mt-2">

                      
                        <form action="cart_update.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">

                            <select name="quantity" class="form-select form-select-sm" 
                                onchange="this.form.submit()" style="width:70px;">
                                <?php for($i=1; $i<=10; $i++): ?>
                                    <option value="<?= $i ?>" <?= $p['quantity'] == $i ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <input type="hidden" name="action" value="updateQty">
                        </form>

                      
                        <form action="cart_update.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                            <input type="hidden" name="action" value="remove">
                            <button class="btn btn-link text-danger p-0">Hiqe</button>
                        </form>

                    </div>
                </div>

           
                <div class="text-end">
                    <p class="fw-bold">€<?= number_format($p['price'], 2) ?></p>
                    <p class="text-muted">Totali: €<?= number_format($itemTotal, 2) ?></p>
                </div>

            </div>
            <?php endforeach; ?>

        </div>

       
        <div class="col-md-4">

            <?php
            $subtotal = 0;
            foreach ($items as $p) {
                $subtotal += $p['price'] * $p['quantity'];
            }
            $shipping = 10;
            $total = $subtotal + $shipping;
            ?>

            <div class="card p-3">
            <h5 class="mb-3">Permbledhje</h5>

           
            <?php foreach ($items as $p): 
                $lineTotal = $p['price'] * $p['quantity'];
            ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span>
                        <?= $p['title'] ?>
                        <small class="text-muted">(x<?= $p['quantity'] ?>)</small>
                    </span>
                    <span>€<?= number_format($lineTotal, 2) ?></span>
                </div>
            <?php endforeach; ?>

            <hr>

          
            <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <span>€<?= number_format($subtotal, 2) ?></span>
            </div>

           
            <div class="d-flex justify-content-between">
                <span>Shipping:</span>
                <span class="text-success">€10</span>
            </div>

            <hr>

         
            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total:</span>
                <span>€<?= number_format($total, 2) ?></span>
            </div>

            <form action="checkout_cart.php" method="POST">
                <button class="btn btn-primary mt-3 w-100">
                    Go to checkout
                </button>
            </form>
        </div>


        </div>

    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<style>
    


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

@media (max-width: 768px) {
  .product-title-link {
    font-size: 16px;
  }
}


</style>