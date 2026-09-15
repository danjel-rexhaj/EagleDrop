<?php
session_start();
require 'config/database.php';
include 'includes/header.php';
?>

<style>

body {
  background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
              url('assets/uploads/background-car.jpg') no-repeat center center fixed;
  background-size: cover;
}




.filter-box {
  max-width: 850px;
  margin: 8vh auto;
  background: #fff;
  border-radius: 20px;
  padding: 40px 30px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.filter-box:hover {
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
  transform: translateY(-3px);
}


.filter-box h4 {
  font-weight: 700;
  color: #212529;
  font-size: 1.4rem;
  margin-bottom: 25px;
  letter-spacing: 0.5px;
}

.filter-box h4 .emoji {
  font-size: 1.6rem;
  margin-right: 5px;
}


.filter-box select {
  min-width: 200px;
  border-radius: 10px;
  border: 1px solid #ced4da;
  padding: 10px 14px;
  transition: all 0.2s ease;
}

.filter-box select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}


#filterSearch {
  padding: 10px 20px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.3s ease;
}

#filterSearch:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

#filterSearch:not(:disabled):hover {
  transform: scale(1.05);
}


@media (max-width: 768px) {
  .filter-box {
    margin: 5vh auto;
    padding: 25px;
  }
  .filter-box select {
    min-width: 100%;
  }
}
</style>

<div class="filter-box text-center">
  <h4><span class="emoji">🔧</span> Gjej pjeset sipas makines tende</h4>

  <form id="carFilter" class="d-flex flex-wrap justify-content-center gap-3">
    <select id="maker" name="maker" class="form-select">
      <option value="">Zgjidh marken</option>
      <?php
      $makers = $conn->query("SELECT * FROM car_makers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($makers as $m) {
          echo "<option value='{$m['id']}'>{$m['name']}</option>";
      }
      ?>
    </select>

    <select id="model" name="model" class="form-select" disabled>
      <option value="">Zgjidh modelin</option>
    </select>

    <select id="engine" name="engine" class="form-select" disabled>
      <option value="">Zgjidh motorin</option>
    </select>

    <button type="button" id="filterSearch" class="btn btn-primary shadow-sm" disabled>
      🔍 Kerko pjese
    </button>
  </form>
</div>

<div class="container trust-badges-wrap">
  <div class="row g-3 text-center">

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">🔒</div>
        <div class="trust-title">Pagesa te sigurta</div>
        <div class="trust-sub">Mbrojtur nga Stripe</div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">💶</div>
        <div class="trust-title">Pa kosto te fshehura</div>
        <div class="trust-sub">Çmimi qe shikon, ate paguan</div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">🚚</div>
        <div class="trust-title">Dergese e shpejte</div>
        <div class="trust-sub">Ne te gjithe Shqiperine</div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">↩️</div>
        <div class="trust-title">Kthim i lehte</div>
        <div class="trust-sub">Kontakto Support-in</div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">✅</div>
        <div class="trust-title">Pjese te verifikuara</div>
        <div class="trust-sub">Cilesi e kontrolluar</div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
      <div class="trust-badge">
        <div class="trust-icon">💬</div>
        <div class="trust-title">Support gjithmone aktiv</div>
        <div class="trust-sub">Pergjigje brenda minutash</div>
      </div>
    </div>

  </div>
</div>

<style>
.trust-badges-wrap {
  max-width: 1000px;
  margin: 0 auto 6vh;
}

.trust-badge {
  background: rgba(255,255,255,0.92);
  border-radius: 14px;
  padding: 18px 10px;
  height: 100%;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  transition: transform 0.2s ease;
}

.trust-badge:hover {
  transform: translateY(-3px);
}

.trust-icon {
  font-size: 1.8rem;
  margin-bottom: 6px;
}

.trust-title {
  font-weight: 700;
  font-size: 0.85rem;
  color: #212529;
}

.trust-sub {
  font-size: 0.75rem;
  color: #6c757d;
  margin-top: 2px;
}
</style>

<script>

document.addEventListener("DOMContentLoaded", () => {
  const maker = document.getElementById("maker");
  const model = document.getElementById("model");
  const engine = document.getElementById("engine");
  const searchBtn = document.getElementById("filterSearch");

  maker.addEventListener("change", async () => {
    const makerId = maker.value;
    model.innerHTML = '<option value="">Zgjidh modelin</option>';
    engine.innerHTML = '<option value="">Zgjidh motorin</option>';
    model.disabled = true;
    engine.disabled = true;
    searchBtn.disabled = true;

    if (!makerId) return;

    try {
      const res = await fetch(`ajax/get_models.php?maker_id=${makerId}`);
      const data = await res.json();

      if (data.length > 0) {
        data.forEach(m => {
          model.innerHTML += `<option value="${m.id}">${m.name} (${m.year_from}-${m.year_to})</option>`;
        });
        model.disabled = false;
      }
    } catch (err) {
      console.error("Gabim gjate marrjes se modeleve:", err);
    }
  });

  model.addEventListener("change", async () => {
    const modelId = model.value;
    engine.innerHTML = '<option value="">Zgjidh motorin</option>';
    engine.disabled = true;
    searchBtn.disabled = true;

    if (!modelId) return;

    try {
      const res = await fetch(`ajax/get_engines.php?model_id=${modelId}`);
      const data = await res.json();

      if (data.length > 0) {
        const groups = {};
        data.forEach(e => {
          if (!groups[e.fuel_type]) groups[e.fuel_type] = [];
          groups[e.fuel_type].push(e);
        });

        const order = ['Diesel', 'Petrol', 'Hybrid', 'Electric'];
        order.forEach(type => {
          if (groups[type]) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = type;
            groups[type].forEach(e => {
              const option = document.createElement('option');
              option.value = e.id;
              option.textContent = `${e.engine_name} - ${e.displacement} ${e.fuel_type} (${e.power_hp} HP)`;
              optgroup.appendChild(option);
            });
            engine.appendChild(optgroup);
          }
        });

        engine.disabled = false;
      }
    } catch (err) {
      console.error("Gabim gjate marrjes se motoreve:", err);
    }
  });

  engine.addEventListener("change", () => {
    searchBtn.disabled = !engine.value;
  });

  searchBtn.addEventListener("click", () => {
    const engineId = engine.value;
    if (engineId) {
      const makerId = maker.value;
      const modelId = model.value;
      const makerName = maker.options[maker.selectedIndex].text;
      const modelName = model.options[model.selectedIndex].text;
      const engineName = engine.options[engine.selectedIndex].text;

      document.getElementById('searchLoadingOverlay').classList.add('show');

      window.location.href = `products.php?maker_id=${makerId}&model_id=${modelId}&engine_id=${engineId}&maker=${encodeURIComponent(makerName)}&model=${encodeURIComponent(modelName)}&engine=${encodeURIComponent(engineName)}`;
    }
  });
});
</script>

<div id="searchLoadingOverlay" class="search-loading-overlay">
    <div class="spinner-border text-light" role="status"></div>
    <p class="mt-3 text-light fw-semibold">Duke gjetur pjeset...</p>
</div>

<style>
.search-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(15, 17, 20, 0.85);
}

.search-loading-overlay.show {
    display: flex;
}
</style>

<?php include 'includes/footer.php'; ?>

<?php include 'includes/footer.php'; ?>
