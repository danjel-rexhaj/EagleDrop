<footer class="site-footer">
    <div class="container py-5">
        <div class="row gy-4">

            <div class="col-12 col-md-4">
                <h5 class="footer-brand mb-2">EagleDrop</h5>
                <p class="footer-tagline">
                    Platforma jote per te gjetur pjese kembimi per çdo automjet — shpejt, thjesht dhe me çmime pa kosto te fshehura.
                </p>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Kompania</h6>
                <ul class="footer-links">
                    <li><a href="index.php">Ballina</a></li>
                    <li><a href="support.php">Kontakt</a></li>
                    <li><a href="mailto:eagledrop19@gmail.com">eagledrop19@gmail.com</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Ndihme</h6>
                <ul class="footer-links">
                    <li><a href="support.php">Si funksionon</a></li>
                    <li><a href="cart.php">Shporta ime</a></li>
                    <li><a href="payment_history.php">Historiku i pagesave</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Ligjore</h6>
                <ul class="footer-links">
                    <li><a href="#">Privatësia</a></li>
                    <li><a href="#">Termat e Perdorimit</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="footer-heading">Llogaria</h6>
                <ul class="footer-links">
                    <li><a href="login.php">Kyçu</a></li>
                    <li><a href="register.php">Regjistrohu</a></li>
                    <li><a href="profile.php">Profili im</a></li>
                </ul>
            </div>

        </div>

        <hr class="footer-divider">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="footer-copy">© <?= date('Y') ?> EagleDrop. Te gjitha te drejtat e rezervuara.</span>
            <span class="footer-copy">Bere me ❤ ne Shqiperi</span>
        </div>
    </div>
</footer>

<style>
.site-footer {
    margin-top: auto;
    padding-top: 40px;
    background: #14171a;
    color: #c6cad2;
    border-top: 1px solid rgba(255,255,255,0.08);
}

body.light-mode .site-footer {
    background: #f8f9fa;
    color: #495057;
    border-top: 1px solid #e9ecef;
}

.footer-brand {
    color: #fff;
    font-weight: 700;
    font-size: 1.3rem;
}

body.light-mode .footer-brand {
    color: #14171a;
}

.footer-tagline {
    font-size: 0.9rem;
    max-width: 320px;
    line-height: 1.5;
    color: #9aa0a6;
}

body.light-mode .footer-tagline {
    color: #6c757d;
}

.footer-heading {
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 14px;
}

body.light-mode .footer-heading {
    color: #14171a;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.footer-links a {
    color: #9aa0a6;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.15s ease;
}

.footer-links a:hover {
    color: #4c5fff;
}

body.light-mode .footer-links a {
    color: #6c757d;
}

.footer-divider {
    border-color: rgba(255,255,255,0.08);
    margin: 10px 0 20px;
}

body.light-mode .footer-divider {
    border-color: #e9ecef;
}

.footer-copy {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>

<script>
document.addEventListener("click", function (e) {
  const card = e.target.closest(".product-click, .category-click");

  if (!card) return;

  
  if (e.target.closest("button, a, form")) return;

  const url = card.dataset.href;
  if (url) {
    window.location.href = url;
  }
});
</script>



</body>
</html>
