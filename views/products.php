<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="搜尋商品...">
        <input type="text" name="category" placeholder="商品分類">
        <select name="sort">
            <option value="newest">最新上架</option>
            <option value="price_asc">價格：低到高</option>
            <option value="price_desc">價格：高到低</option>
            <option value="name_asc">名稱：A → Z</option>
        </select>
        <input type="number" name="min_price" min="0" step="1" placeholder="最低價格">
        <input type="number" name="max_price" min="0" step="1" placeholder="最高價格">
        <label style="display:flex;align-items:center;gap:8px;margin:0;">
            <input type="checkbox" name="in_stock" value="1" style="width:auto;">
            只看有庫存
        </label>
        <button type="submit" class="btn">搜尋</button>
    </form>
    <div id="productMeta" style="margin-top:10px;color:#555;font-weight:600;"></div>
    <div id="productGrid" class="product-grid"></div>
    <div id="productPager" style="display:flex;gap:10px;align-items:center;justify-content:center;margin-top:16px;"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
