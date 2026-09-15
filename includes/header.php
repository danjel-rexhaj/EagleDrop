<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    require __DIR__ . '/../config/database.php';

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = $res['total'] ?? 0;
}



$notifCount = 0;
$navProfileImage = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifCount = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $navProfileImage = $stmt->fetchColumn() ?: null;

    // 'default.png' is just the column's DB default value, not a file that
    // actually exists on disk -- treat it as "no photo uploaded yet".
    if ($navProfileImage === 'default.png' || $navProfileImage === 'default_user.png') {
        $navProfileImage = null;
    }
}




?>


<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>EagleDrop</title>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script src="/assets/js/theme.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

</head>
<body>
<nav class="navbar navbar-expand-lg main-nav">
    <div class="container">

    
        <a class="navbar-brand insta-logo" href="/index.php">
            EagleDrop
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNavCollapse" aria-controls="mainNavCollapse"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavCollapse">

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/admin/dashboard.php"
              class="nav-link admin-panel-btn">
                🛠 Admin
            </a>
        <?php endif; ?>

        <form class="d-flex mx-auto search-form position-relative"
            action="/search.php"
            method="GET"
            style="max-width: 500px; flex: 1;">

        <input 
            class="form-control me-2 search-input" 
            type="search" 
            name="q"
            id="searchInput"
            placeholder="Kerko produkt, pjese, ose model makines..."
            autocomplete="off"
        >

        <button class="btn btn-outline-primary search-btn" type="submit">Kerko</button>

       
        <div id="searchResults" class="search-dropdown"></div>
        </form>


     
          <div class="d-flex align-items-center gap-3">

              <a href="/index.php" class="nav-icon">
                  <i class="bi bi-house-door"></i>
              </a>

              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                  <a href="#" onclick="if(window.openSupportWidget){openSupportWidget();}return false;" class="nav-link position-relative">
                      💬 Support

                      <?php if ($notifCount > 0): ?>
                          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                              <?= $notifCount ?>
                          </span>
                      <?php endif; ?>
                  </a>

              <?php elseif (isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff','admin'])): ?>
                  <a href="/support_admin.php" class="nav-link staff position-relative">
                      🧑‍💼 Messages
                      <?php if ($notifCount > 0): ?>
                          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                              <?= $notifCount ?>
                          </span>
                      <?php endif; ?>
                  </a>
              <?php endif; ?>
            <a href="/cart.php" class="nav-icon position-relative">
            <i class="bi bi-cart2"></i>

            <?php if ($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $cartCount ?>
                </span>
            <?php endif; ?>
            </a>


            <a href="/profile.php" class="nav-icon">
                <?php if ($navProfileImage): ?>
                    <img src="/assets/uploads/<?= htmlspecialchars($navProfileImage) ?>" class="nav-avatar-img" alt="Profili">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
            </a>

        
            <button id="themeToggle" class="btn theme-btn">
                🌙
            </button>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="/logout.php" class="logout-btn">Dil</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-primary btn-sm">Login</a>
                <a href="/register.php" class="btn btn-success btn-sm">Regjistrohu</a>
            <?php endif; ?>

        </div>

        </div>

    </div>
</nav>
<style>

.search-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;

  background-color: #1f2225;  
  color: #ffffff;

  border-radius: 10px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.5);

  max-height: 340px;
  overflow-y: auto;
  z-index: 9999;
  display: none;
}

.search-item {
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #2f3336;
  transition: background 0.2s ease;
}

.search-item:hover {
  background-color: #2a2e32;
}

.search-item strong {
  color: #ffffff;
}

.search-item small {
  color: #b0b3b8;
}


body.light-mode .search-dropdown {
  background-color: #ffffff;
  color: #111;
  box-shadow: 0 12px 30px rgba(0,0,0,0.18);
}

body.light-mode .search-item {
  border-bottom: 1px solid #eee;
}

body.light-mode .search-item:hover {
  background-color: #f5f7fa;
}

body.light-mode .search-item strong {
  color: #111;
}

body.light-mode .search-item small {
  color: #777;
}


</style>

<script>
const searchInput = document.getElementById("searchInput");
const searchResults = document.getElementById("searchResults");

if (searchInput) {
  searchInput.addEventListener("keyup", function () {
    const q = this.value.trim();

    if (q.length < 2) {
      searchResults.style.display = "none";
      return;
    }

    fetch("/search_products.php?q=" + encodeURIComponent(q))
      .then(res => res.json())
      .then(data => {
        searchResults.innerHTML = "";

        if (data.length === 0) {
          searchResults.innerHTML =
            `<div class="search-item text-muted">Nuk u gjet asgje</div>`;
        } else {
          data.forEach(p => {
            searchResults.innerHTML += `
              <div class="search-item"
                   onclick="window.location='/product_details.php?id=${p.id}'">
                <strong>${p.title}</strong><br>
                <small>€${p.price}</small>
              </div>
            `;
          });
        }

        searchResults.style.display = "block";
      });
  });

  
  document.addEventListener("click", e => {
    if (!e.target.closest(".search-form")) {
      searchResults.style.display = "none";
    }
  });
}
</script>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
    <?php require __DIR__ . '/chat_widget.php'; ?>
<?php endif; ?>
