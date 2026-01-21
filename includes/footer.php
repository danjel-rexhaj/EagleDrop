<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


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
