<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php require_once __DIR__ . '/../api/db.php'; ?>

<?php
$conn = get_db();
$categories = [];

$sql = "SELECT DISTINCT category
        FROM products
        WHERE category IS NOT NULL
          AND category <> ''
        ORDER BY category ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<section class="card products-page-card">
    <div class="products-page-head">
        <h2>商品總覽</h2>
        <p>用關鍵字與篩選快速找到想買的商品。</p>
    </div>

    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="搜尋商品...">
        <input type="hidden" name="category" value="">
        <button type="submit" class="btn">搜尋</button>
    </form>

    <section class="category-filter-section" aria-label="商品分類篩選">
        <div class="category-filter-head">
            <h3>商品分類</h3>
            <p>快速切換飲品、零食與醬料分類</p>
        </div>

        <div class="category-filter-bar">
            <button class="category-filter-btn active" data-category="">全部類別</button>
            <?php foreach ($categories as $category): ?>
                <button class="category-filter-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                    <?php echo htmlspecialchars($category); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="productMeta" class="product-meta"></div>
    <div id="productGrid" class="product-grid"></div>
    <div id="productPager" class="product-pager"></div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>