<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="Search products...">
        <input type="text" name="category" placeholder="Category">
        <button type="submit" class="btn">Search</button>
    </form>
    <div id="productGrid" class="product-grid"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
