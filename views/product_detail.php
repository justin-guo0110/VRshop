<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php $product = $product ?? ['product_id' => intval($_GET['product_id'] ?? 0)]; ?>

<section class="card" id="productDetail" data-product-id="<?= $product['product_id'] ?>">
    <p>加載中...</p>
</section>

<section class="card" id="productInfoBlocks" style="display:none;">
    <h3 style="margin-top:0;">購買須知</h3>
    <div class="grid two-cols">
        <div>
            <p style="margin:0 0 6px;font-weight:700;">運送</p>
            <p style="margin:0;color:#666;">宅配約 1-3 個工作天；超商取貨依門市到貨為準。</p>
        </div>
        <div>
            <p style="margin:0 0 6px;font-weight:700;">退換貨</p>
            <p style="margin:0;color:#666;">商品到貨 7 天內可申請退換貨（需保留完整包裝與配件）。</p>
        </div>
        <div>
            <p style="margin:0 0 6px;font-weight:700;">保固</p>
            <p style="margin:0;color:#666;">依商品/原廠規範提供保固；如有疑問請聯繫客服。</p>
        </div>
        <div>
            <p style="margin:0 0 6px;font-weight:700;">提醒</p>
            <p style="margin:0;color:#666;">部分產品需相容設備或特定規格，購買前請確認需求。</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
