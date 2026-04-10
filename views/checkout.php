<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: ./login.php');
    exit;
}
?>

<?php require_once __DIR__ . '/layout_header.php'; ?>


<div class="checkout-page">
    <section class="card checkout-progress-card" aria-label="結帳流程進度">
        <ol class="checkout-progress" role="list">
            <li class="checkout-progress-step is-done">
                <span class="step-dot">1</span>
                <div class="step-text">
                    <strong>選擇要購買的商品</strong>
                    <small>已於購物車勾選商品與數量</small>
                </div>
            </li>
            <li class="checkout-progress-step is-active">
                <span class="step-dot">2</span>
                <div class="step-text">
                    <strong>填寫配送與付款</strong>
                    <small>地址、配送、付款方式</small>
                </div>
            </li>
            <li class="checkout-progress-step">
                <span class="step-dot">3</span>
                <div class="step-text">
                    <strong>完成下單</strong>
                    <small>送出訂單並檢視結果</small>
                </div>
            </li>
        </ol>
    </section>

    <div class="checkout-layout">
        <div class="checkout-main">
            <section class="card checkout-section">
                <h2>結帳</h2>
                <p class="checkout-note">請確認本次要結帳的商品與配送資訊。</p>
                <div class="checkout-items-list" id="checkoutCart"></div>
            </section>

            <section class="card checkout-section">
                <h3>選擇送貨地址</h3>
                <div class="address-options" id="checkoutAddresses">
                    <p>載入中...</p>
                </div>
                <a class="btn add-address-btn" href="./profile.php#addressSection">+ 新增地址</a>
            </section>

            <section class="card checkout-section">
                <h3>選擇送貨方式</h3>
                <div class="options-group">
                    <label class="option-item">
                        <input type="radio" name="shipping_method" value="宅配" checked>
                        <span><strong>宅配</strong><small>3~5 個工作天</small></span>
                    </label>

                    <label class="option-item">
                        <input type="radio" name="shipping_method" value="超商取貨">
                        <span><strong>超商取貨</strong><small>2~3 個工作天</small></span>
                    </label>
                </div>
                <p class="checkout-tip">部分商品可能不支援超商取貨。</p>
            </section>

            <section class="card checkout-section" id="pickupStoreSection" style="display:none;">
                <h3>選擇超商門市</h3>
                <p class="checkout-note" style="margin-bottom:12px;">可先到官方地圖查門市，再填入門市資訊。</p>
                <div class="pickup-store-links">
                    <a class="btn btn-secondary btn-sm" href="https://emap.pcsc.com.tw/" target="_blank" rel="noopener noreferrer">7-ELEVEN 地圖(手動)</a>
                    <a class="btn btn-secondary btn-sm" href="https://www.family.com.tw/Marketing/Map/" target="_blank" rel="noopener noreferrer">全家地圖(手動)</a>
                    <button type="button" class="btn btn-secondary btn-sm" id="clearPickupStoreBtn">清除常用門市</button>
                </div>
                <div class="pickup-store-form">
                    <label>
                        <span>超商品牌</span>
                        <select id="pickupStoreBrand">
                            <option value="">請選擇</option>
                            <option value="7-ELEVEN">7-ELEVEN</option>
                            <option value="全家">全家</option>
                        </select>
                    </label>
                    <label>
                        <span>門市代碼</span>
                        <input type="text" id="pickupStoreId" placeholder="例如：153482" maxlength="20">
                    </label>
                    <label>
                        <span>門市名稱</span>
                        <input type="text" id="pickupStoreName" placeholder="例如：復興店" maxlength="80">
                    </label>
                    <label>
                        <span>門市地址（選填）</span>
                        <input type="text" id="pickupStoreAddress" placeholder="例如：臺北市大安區復興南路一段100號" maxlength="180">
                    </label>
                </div>
            </section>

            <section class="card checkout-section">
                <h3>選擇支付方式</h3>
                <div class="options-group">
                    <label class="option-item">
                        <input type="radio" name="payment_method" value="信用卡" checked>
                        <span><strong>信用卡</strong><small>立即支付</small></span>
                    </label>

                    <label class="option-item">
                        <input type="radio" name="payment_method" value="貨到付款">
                        <span><strong>貨到付款</strong><small>送達時付款</small></span>
                    </label>
                </div>
                <p class="checkout-tip">貨到付款可能會有額外手續費。</p>
            </section>

            <section class="card checkout-section" id="creditCardSection">
                <h3>信用卡資訊</h3>
                <div class="pickup-store-form">
                    <label>
                        <span>持卡人姓名</span>
                        <input type="text" id="cardHolderName" placeholder="例如：CHEN DA MING" maxlength="50" autocomplete="cc-name">
                    </label>
                    <label>
                        <span>卡號</span>
                        <input type="text" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="23" autocomplete="cc-number" inputmode="numeric">
                    </label>
                    <div class="card-inline-fields">
                        <label>
                            <span>到期日</span>
                            <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5" autocomplete="cc-exp" inputmode="numeric">
                        </label>
                        <label>
                            <span>安全碼</span>
                            <input type="password" id="cardCvv" placeholder="CVV" maxlength="4" autocomplete="cc-csc" inputmode="numeric">
                        </label>
                    </div>
                    <label class="card-save-option">
                        <input type="checkbox" id="saveCardInfo" value="1">
                        <span>記住卡片資訊（不儲存安全碼）</span>
                    </label>
                    <button type="button" class="btn btn-secondary btn-sm checkout-inline-action" id="clearSavedCardBtn">清除已記住卡片</button>
                </div>
            </section>

            <section class="card checkout-section">
                <h3>🎟️ 優惠券</h3>
                <select id="checkoutCouponSelect" class="coupon-select">
                    <option value="">載入中…</option>
                </select>
                <div class="message" id="checkoutCouponMessage"></div>
            </section>

            <section class="card checkout-section">
                <div class="checkout-actions">
                    <button class="btn btn-place-order" id="placeOrderBtn">確認下單</button>
                </div>
                <div class="message" id="checkoutMessage"></div>
            </section>
        </div>

        <aside class="checkout-sidebar">
            <section class="card checkout-summary-card" id="checkoutTotals">
                <h3>訂單摘要</h3>
                <div class="totals-section">
                    <div class="total-row">
                        <span>商品小計</span>
                        <strong id="checkoutSubtotal">$0</strong>
                    </div>
                    <div class="total-row">
                        <span>送貨費</span>
                        <strong id="checkoutShippingFee">$0</strong>
                    </div>
                    <div id="freeShippingHint" class="free-shipping-hint" style="display:none;"></div>
                    <div class="total-row">
                        <span>支付手續費</span>
                        <strong id="checkoutPaymentFee">$0</strong>
                    </div>
                    <div class="total-row" id="checkoutPromoDiscountRow" style="display:none;">
                        <span>活動優惠</span>
                        <strong id="checkoutPromoDiscount">-$0</strong>
                    </div>
                    <div class="total-row" id="checkoutCouponDiscountRow" style="display:none;">
                        <span>優惠券折抵</span>
                        <strong id="checkoutCouponDiscount">-$0</strong>
                    </div>
                    <div class="total-row" id="checkoutDiscountRow" style="display:none;">
                        <span>總折扣</span>
                        <strong id="checkoutDiscount">-$0</strong>
                    </div>
                    <div class="total-row grand-total">
                        <span>應付總額</span>
                        <strong id="checkoutGrandTotal">$0</strong>
                    </div>
                </div>
                <div id="checkoutPromotionTips" class="checkout-promo-list"></div>
                <p id="checkoutEta" class="checkout-eta"></p>
            </section>
        </aside>
    </div>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
