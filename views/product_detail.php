<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card" id="productDetail">
    <p>加載中...</p>
    <?php $product = $product ?? ['product_id' => intval($_GET['product_id'] ?? 0)]; ?>
    <button class="btn btn-primary" id="add-to-cart-btn"
            data-product-id="<?= $product['product_id'] ?>">
       加入購物車
    </button>
</section>
<script>
document.getElementById('add-to-cart-btn').addEventListener('click', function(){
    app.addToCart(this.dataset.productId);
});
</script>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
