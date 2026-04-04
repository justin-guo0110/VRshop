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

<section class="card products-page-card" style="padding:20px;">
    <div class="products-page-head" style="margin-bottom:14px;">
        <h2 style="margin:0;">商品總覽</h2>
        <p style="margin:6px 0 0;color:var(--muted);">用關鍵字與篩選快速找到想買的商品。</p>
    </div>

    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="搜尋商品...">
        <input type="hidden" name="category" value="">
        <button type="submit" class="btn">搜尋</button>
    </form>

    <!-- 分類篩選按鈕 -->
    <div class="category-filter-bar">
        <button class="category-filter-btn active" data-category="">全部類別</button>
        <?php foreach ($categories as $category): ?>
            <button class="category-filter-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div id="productMeta" class="product-meta" style="margin-top:10px;color:#555;font-weight:600;"></div>
    <div id="productGrid" class="product-grid"></div>
    <div id="productPager" class="product-pager" style="display:flex;gap:10px;align-items:center;justify-content:center;margin-top:16px;"></div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>