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
