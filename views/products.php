<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card products-page-card" style="padding:20px;">
    <div class="products-page-head" style="margin-bottom:14px;">
        <h2 style="margin:0;">商品總覽</h2>
        <p style="margin:6px 0 0;color:var(--muted);">用關鍵字與篩選快速找到想買的商品。</p>
    </div>
    <form id="searchForm" class="search-bar">
        <input type="text" name="keyword" placeholder="搜尋商品...">
        <button type="submit" class="btn">搜尋</button>
        
        <div class="filter-dropdown-wrapper">
            <button type="button" class="filter-toggle-btn btn" id="filterToggle">
                <span>⚙️ 篩選</span>
                <span class="dropdown-arrow">▼</span>
            </button>
            <div id="filterDropdown" class="filter-dropdown-menu">
                <div class="filter-section">
                    <label>商品分類</label>
                    <input type="text" name="category" placeholder="輸入分類名稱">
                </div>
                
                <div class="filter-section">
                    <label>排序方式</label>
                    <select name="sort">
                        <option value="newest">最新上架</option>
                        <option value="price_asc">價格：低到高</option>
                        <option value="price_desc">價格：高到低</option>
                        <option value="name_asc">名稱：A → Z</option>
                    </select>
                </div>
                
                <div class="filter-section">
                    <label>價格範圍</label>
                    <div style="display:flex;gap:8px;">
                        <input type="number" name="min_price" min="0" step="1" placeholder="最低">
                        <span style="align-self:center;">-</span>
                        <input type="number" name="max_price" min="0" step="1" placeholder="最高">
                    </div>
                </div>
                
                <div class="filter-section">
                    <label style="display:flex;align-items:center;gap:8px;margin:0;cursor:pointer;">
                        <input type="checkbox" name="in_stock" value="1" style="width:auto;">
                        <span>只看有庫存</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
    <div id="productMeta" class="product-meta" style="margin-top:10px;color:#555;font-weight:600;"></div>
    <div id="productGrid" class="product-grid"></div>
    <div id="productPager" class="product-pager" style="display:flex;gap:10px;align-items:center;justify-content:center;margin-top:16px;"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
