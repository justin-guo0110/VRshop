<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="搜尋商品...">
        <input type="text" name="category" placeholder="商品分類">
        <button type="submit" class="btn">搜尋</button>
    </form>
    <div id="productGrid" class="product-grid"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
