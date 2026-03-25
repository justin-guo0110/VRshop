/* 幸运转盘系统 */

// 创建转盘HTML
function createLuckyWheelHTML() {
    return `
    <div id="luckyWheelModal" class="lucky-wheel-modal" style="display:none;">
        <div class="lucky-wheel-container">
            <div class="lucky-wheel-header">
                <h2>🎉 幸运转盘</h2>
                <button class="close-btn" onclick="closeLuckyWheel()">&times;</button>
            </div>
            
            <div class="lucky-wheel-content">
                <div class="spin-info" id="spinInfo">
                    <p>加载中...</p>
                </div>
                
                <div class="wheel-canvas">
                    <svg id="luckyWheelSvg" width="320" height="320" viewBox="0 0 320 320" style="filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));">
                        <g id="wheelGroup">
                            <!-- 8个奖项扇形 -->
                        </g>
                        <!-- 中心圆 -->
                        <circle cx="160" cy="160" r="35" fill="white" stroke="#333" stroke-width="2"/>
                        <text x="160" y="165" text-anchor="middle" font-size="14" font-weight="bold" fill="#333">转</text>
                    </svg>
                    <!-- 转盘指针 -->
                    <div class="wheel-pointer"></div>
                </div>
                
                <div class="spin-button-area">
                    <button id="spinBtn" class="btn btn-primary btn-lg" onclick="performSpin()" style="width:100%;padding:15px;font-size:16px;border-radius:8px;">
                        立即转盘
                    </button>
                </div>
                
                <div id="spinResult" class="spin-result" style="display:none;text-align:center;margin-top:15px;">
                    <h3 id="resultTitle">恭喜获奖！</h3>
                    <p id="resultMsg" style="margin:10px 0;font-size:14px;color:#666;"></p>
                    <div id="resultCoupon" style="display:none;background:#fef3c7;border:1px solid #fcd34d;padding:10px;border-radius:6px;margin:10px 0;">
                        <p style="margin:5px 0;font-size:12px;color:#92400e;">优惠券代码</p>
                        <p id="couponCode" style="margin:5px 0;font-size:16px;font-weight:bold;color:#b45309;font-family:monospace;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
}

// 初始化转盘（加载信息并绘制）
async function initLuckyWheel() {
    try {
        const response = await api.get('../api/lucky_wheel.php?action=get_spin_info');
        updateSpinInfo(response);
        drawWheel(response.prizes);
    } catch (error) {
        console.error('初始化转盘失败:', error);
        document.getElementById('spinInfo').innerHTML = '<p style="color:red;">加载失败，请刷新重试</p>';
    }
}

// 更新转盘信息显示
function updateSpinInfo(data) {
    const info = document.getElementById('spinInfo');
    if (!info) return;
    
    const remaining = data.remaining_spins || 0;
    let infoHtml = `
        <div class="spin-stats">
            <div class="stat-item">
                <span class="stat-label">已消费</span>
                <span class="stat-value">¥${data.total_spent.toFixed(2)}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">转盘次数</span>
                <span class="stat-value">${data.spins_today}/${data.spin_count}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">剩余次数</span>
                <span class="stat-value" style="color:${remaining > 0 ? '#10b981' : '#ef4444'};">${remaining}</span>
            </div>
        </div>
    `;
    
    if (!data.can_spin) {
        infoHtml += '<p style="text-align:center;color:#666;font-size:13px;margin-top:10px;">今日转盘次数已用完 💤</p>';
    } else {
        infoHtml += `<p style="text-align:center;color:#10b981;font-size:13px;margin-top:10px;">✨ 还可转盘${remaining}次！</p>`;
    }
    
    info.innerHTML = infoHtml;
    
    // 更新按钮状态
    const btn = document.getElementById('spinBtn');
    if (btn) {
        btn.disabled = !data.can_spin;
        btn.textContent = data.can_spin ? '立即转盘' : '今日已转完';
    }
}

// 绘制转盘
function drawWheel(prizes) {
    const svg = document.getElementById('luckyWheelSvg') || document.querySelector('svg[id="luckyWheelSvg"]');
    if (!svg) return;
    
    const wheelGroup = svg.querySelector('#wheelGroup');
    if (!wheelGroup) return;
    
    wheelGroup.innerHTML = ''; // 清空之前的条目
    
    const centerX = 160;
    const centerY = 160;
    const radius = 140;
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    
    prizes.forEach((prize, index) => {
        const startAngle = (index * 360 / prizes.length) * Math.PI / 180;
        const endAngle = ((index + 1) * 360 / prizes.length) * Math.PI / 180;
        
        const x1 = centerX + radius * Math.cos(startAngle);
        const y1 = centerY + radius * Math.sin(startAngle);
        const x2 = centerX + radius * Math.cos(endAngle);
        const y2 = centerY + radius * Math.sin(endAngle);
        
        const largeArc = Math.PI > (endAngle - startAngle) ? 0 : 1;
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M ${centerX} ${centerY} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`);
        path.setAttribute('fill', colors[index % colors.length]);
        path.setAttribute('stroke', 'white');
        path.setAttribute('stroke-width', '2');
        
        wheelGroup.appendChild(path);
        
        // 添加文本标签
        const midAngle = (startAngle + endAngle) / 2;
        const textRadius = radius * 0.65;
        const textX = centerX + textRadius * Math.cos(midAngle);
        const textY = centerY + textRadius * Math.sin(midAngle);
        
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', textX);
        text.setAttribute('y', textY);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('dominant-baseline', 'middle');
        text.setAttribute('font-size', '12');
        text.setAttribute('font-weight', 'bold');
        text.setAttribute('fill', 'white');
        text.setAttribute('pointer-events', 'none');
        // 旋转文本以适应扇形
        text.setAttribute('transform', `rotate(${(midAngle * 180 / Math.PI)}, ${textX}, ${textY})`);
        text.textContent = prize.name;
        
        wheelGroup.appendChild(text);
    });
}

// 执行转盘抽奖
async function performSpin() {
    const btn = document.getElementById('spinBtn');
    if (!btn || btn.disabled) return;
    
    btn.disabled = true;
    btn.textContent = '转动中...';
    
    try {
        const response = await api.post('../api/lucky_wheel.php?action=spin', {});
        
        if (response.success) {
            const prizeCount = 8;
            const prizeIndex = Number(response.prize_index || 0);
            const segmentAngle = 360 / prizeCount;
            const targetSegmentCenter = (prizeIndex * segmentAngle) + (segmentAngle / 2);
            const pointerAngle = 270;
            const offset = pointerAngle - targetSegmentCenter;
            const extraTurns = (4 + Math.floor(Math.random() * 2)) * 360;
            const jitter = (Math.random() * 8) - 4;
            const totalDegree = extraTurns + offset + jitter;

            const wheelGroup = document.getElementById('wheelGroup')?.parentElement;
            if (wheelGroup) {
                wheelGroup.style.transition = 'transform 3s cubic-bezier(0.17, 0.67, 0.2, 1)';
                wheelGroup.style.transform = `rotate(${totalDegree}deg)`;
                await new Promise(resolve => setTimeout(resolve, 3000));
            }

            showSpinResult(response);
            // 刷新转盘信息
            await new Promise(resolve => setTimeout(resolve, 1000));
            initLuckyWheel();
        } else {
            showSpinError(response.error || '转盘失败，请稍后重试');
        }
    } catch (error) {
        console.error('转盘错误:', error);
        showSpinError('转盘发生错误，请稍后重试');
    } finally {
        btn.disabled = false;
        btn.textContent = '立即转盘';
    }
}

// 显示转盘结果
function showSpinResult(data) {
    const resultDiv = document.getElementById('spinResult');
    if (!resultDiv) return;
    
    resultDiv.style.display = 'block';
    document.getElementById('resultTitle').textContent = data.prize.name + ' 🎁';
    
    let msg = '';
    if (data.prize.discount_type === 'percent') {
        msg = `获得${data.prize.value}%折扣优惠券`;
    } else if (data.prize.discount_type === 'fixed') {
        msg = `获得¥${data.prize.value}折扣优惠券`;
    } else if (data.prize.discount_type === 'bonus') {
        msg = '获得额外一次转盘机会！';
    }
    
    document.getElementById('resultMsg').textContent = msg;
    
    if (data.coupon) {
        const couponDiv = document.getElementById('resultCoupon');
        couponDiv.style.display = 'block';
        document.getElementById('couponCode').textContent = data.coupon.coupon_code;
    } else {
        document.getElementById('resultCoupon').style.display = 'none';
    }
}

// 显示转盘错误
function showSpinError(errorMsg) {
    const resultDiv = document.getElementById('spinResult');
    if (!resultDiv) return;
    
    resultDiv.style.display = 'block';
    document.getElementById('resultTitle').textContent = '转盘失败';
    document.getElementById('resultMsg').textContent = errorMsg;
    document.getElementById('resultCoupon').style.display = 'none';
}

// 打开转盘弹窗
function openLuckyWheel() {
    const modal = document.getElementById('luckyWheelModal');
    if (!modal) {
        const container = document.body;
        container.insertAdjacentHTML('beforeend', createLuckyWheelHTML());
        addLuckyWheelStyles();
    }
    
    const modal2 = document.getElementById('luckyWheelModal');
    if (modal2) {
        modal2.style.display = 'flex';
        initLuckyWheel();
    }
}

// 关闭转盘弹窗
function closeLuckyWheel() {
    const modal = document.getElementById('luckyWheelModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 添加转盘CSS样式
function addLuckyWheelStyles() {
    const styleId = 'luckyWheelStyles';
    if (document.getElementById(styleId)) return;
    
    const styles = document.createElement('style');
    styles.id = styleId;
    styles.textContent = `
    .lucky-wheel-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 12px;
        z-index: 10000;
        backdrop-filter: blur(4px);
    }
    
    .lucky-wheel-container {
        background: white;
        border-radius: 16px;
        padding: 16px;
        width: 90%;
        max-width: 380px;
        max-height: calc(100vh - 24px);
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .lucky-wheel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 8px;
    }
    
    .lucky-wheel-header h2 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    
    .close-btn:hover {
        background: #f0f0f0;
    }
    
    .lucky-wheel-content {
        text-align: center;
    }
    
    .spin-info {
        background: #f9f9f9;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .spin-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }
    
    .stat-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .stat-label {
        font-size: 12px;
        color: #999;
    }
    
    .stat-value {
        font-size: 16px;
        font-weight: bold;
        color: #333;
    }
    
    .wheel-canvas {
        position: relative;
        width: min(72vw, 250px);
        height: min(72vw, 250px);
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #luckyWheelSvg {
        max-width: 100%;
        height: auto;
    }
    
    .wheel-pointer {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-top: 16px solid #FF6B6B;
        z-index: 10;
    }
    
    .spin-button-area {
        margin-bottom: 10px;
    }
    
    .spin-result {
        background: #f0f9ff;
        border: 2px solid #0ea5e9;
        border-radius: 8px;
        padding: 16px;
        margin-top: 12px;
    }
    
    .spin-result h3 {
        margin: 0 0 8px 0;
        color: #0284c7;
        font-size: 18px;
    }
    
    @media (max-width: 480px) {
        .lucky-wheel-container {
            padding: 14px;
            width: 95%;
        }
        
        .lucky-wheel-header h2 {
            font-size: 17px;
        }
        
        .wheel-canvas {
            width: min(72vw, 220px);
            height: min(72vw, 220px);
        }
        
        #luckyWheelSvg {
            width: 100%;
            height: 100%;
        }
    }
    `;
    document.head.appendChild(styles);
}

// 在登入成功后显示转盘
function showLuckyWheelAfterLogin() {
    // 延迟1秒后显示，让用户有时间看到欢迎消息
    setTimeout(() => {
        openLuckyWheel();
    }, 1000);
}
