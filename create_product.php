<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$categories = $conn->query("SELECT id, name FROM categories WHERE id >= 10 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$carMakers = $conn->query("SELECT id, name FROM car_makers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$carModels = $conn->query("SELECT id, maker_id, name, year_from, year_to FROM car_models ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title         = $_POST['title'];
    $desc          = $_POST['description'];
    $price         = $_POST['price'];
    $user_id       = $_SESSION['user_id'];

    $category_id   = $_POST['category_id'] ?? null;
    $fit_maker     = $_POST['fit_maker_text'] ?? null;  
    $fit_model     = $_POST['fit_model_text'] ?? null;  
    $fit_model_id  = $_POST['fit_model_id'] ?? null;
    $brand         = $_POST['brand'] ?? null;
    $keywords      = $_POST['keywords'] ?? null;


    $imgName = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "assets/uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $imgName = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imgName);
    }

    $stmt = $conn->prepare("
        INSERT INTO products (
            user_id, title, description, price, image,
            category_id, fit_maker, fit_model, fit_model_id,
            brand, keywords, created_at
        ) VALUES (
            :user_id, :title, :description, :price, :image,
            :category_id, :fit_maker, :fit_model, :fit_model_id,
            :brand, :keywords, NOW()
        )
    ");

    $stmt->execute([
        ':user_id'      => $user_id,
        ':title'        => $title,
        ':description'  => $desc,
        ':price'        => $price,
        ':image'        => $imgName,
        ':category_id'  => $category_id,
        ':fit_maker'    => $fit_maker,
        ':fit_model'    => $fit_model,
        ':fit_model_id' => $fit_model_id,
        ':brand'        => $brand,
        ':keywords'     => $keywords,
    ]);

    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

include "includes/header.php";
?>
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ Produkti u publikua me sukses!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-plus-circle"></i> Krijo nje produkt te ri</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="productForm">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Titulli *</label>
                            <input type="text" class="form-control" name="title" placeholder="P.sh. Sensor oksigjeni Golf 5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pershkrimi</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Detaje te plota te produktit..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Çmimi (€) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-primary">Kategoria *</label>
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <option value="">Zgjidh kategorine</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?> (ID: <?= $cat['id'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-success">Prodhuesi i makines</label>
                                <select class="form-select" name="fit_maker_text" id="fit_maker" required>
                                    <option value="">Zgjidh prodhues</option>
                                    <?php foreach($carMakers as $maker): ?>
                                        <option value="<?= htmlspecialchars($maker['name']) ?>" data-id="<?= $maker['id'] ?>">
                                            <?= $maker['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-success">Modeli i makines</label>
                                <select class="form-select" name="fit_model_text" id="fit_model" required>
                                    <option value="">Zgjidh modelin</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold text-success">Model ID</label>
                                <input type="text" class="form-control" name="fit_model_id" id="fit_model_id" readonly>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Brand</label>
                                <input type="text" class="form-control" name="brand" placeholder="P.sh. Bosch, Mann, Febi">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Keywords</label>
                                <input type="text" class="form-control" name="keywords" id="keywords" placeholder="Auto-gjenerohet">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Foto e produktit</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div class="form-text">Zgjidh nje foto te qarte te produktit (max 5MB)</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left"></i> Anulo
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Publiko produktin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fitMaker = document.getElementById('fit_maker');
    const fitModel = document.getElementById('fit_model');
    const fitModelId = document.getElementById('fit_model_id');
    const keywords = document.getElementById('keywords');
    const categoryId = document.getElementById('category_id');
    const titleInput = document.querySelector('input[name="title"]');
    const descriptionInput = document.querySelector('textarea[name="description"]');

    fitMaker.addEventListener('change', function() {
        const makerName = this.value;
        const makerId = this.selectedOptions[0]?.dataset.id;
        fitModel.innerHTML = '<option value="">Ngarkimi...</option>';
        
        if (makerId) {
            const models = <?= json_encode($carModels) ?>;
            const makerModels = models.filter(model => model.maker_id == makerId);
            
            fitModel.innerHTML = '<option value="">Zgjidh modelin</option>';
            makerModels.forEach(model => {
                const modelText = `${model.name} (${model.year_from}-${model.year_to})`;
                fitModel.innerHTML += `<option value="${modelText}" data-id="${model.id}">
                    ${modelText}
                </option>`;
            });
            
            fitModelId.value = '';
            updateKeywords();
        } else {
            fitModel.innerHTML = '<option value="">Zgjidh modelin</option>';
            fitModelId.value = '';
            keywords.value = '';
        }
    });

    fitModel.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        fitModelId.value = selectedOption.dataset.id || '';
        updateKeywords();
    });

    function updateKeywords() {
        const title = titleInput.value.trim();
        const description = descriptionInput.value.trim();
        const catText = categoryId.options[categoryId.selectedIndex]?.text || '';
        const makerText = fitMaker.value || '';
        const modelText = fitModel.value || '';
        
        let keywordParts = [];
        
        if (title) keywordParts.push(title);
        
        if (description) {
            const descWords = description.split(' ').slice(0, 4).join(' ');
            keywordParts.push(descWords);
        }
        
        if (catText) {
            const cleanCat = catText.replace(/\(ID:.*?\)/, '').trim();
            keywordParts.push(cleanCat);
        }
        
        if (makerText) keywordParts.push(makerText);
        if (modelText) keywordParts.push(modelText);
        
        if (keywordParts.length > 0) {
            keywords.value = keywordParts.join(' ').substring(0, 200);
        }
    }


    categoryId.addEventListener('change', updateKeywords);
    titleInput.addEventListener('input', updateKeywords);
    descriptionInput.addEventListener('input', updateKeywords);
    fitMaker.addEventListener('change', updateKeywords);
    fitModel.addEventListener('change', updateKeywords);
});


setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) alert.remove();
}, 3000);

</script>


<?php include "includes/footer.php"; ?>
