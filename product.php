<?php
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND used = 0) as stock, (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND used = 1) as real_sold FROM products p WHERE p.id = ? AND p.status = 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    header('Location: index.php');
    exit;
}

$faq = getSetting('faq');
$cardUsage = getSetting('card_usage');
$refundPolicy = getSetting('refund_policy');
$serviceWechat = getSetting('service_wechat');
$serviceQq = getSetting('service_qq');
$serviceWechatQrcode = getSetting('service_wechat_qrcode');
$serviceQqQrcode = getSetting('service_qq_qrcode');
$hasService = !empty($serviceWechat) || !empty($serviceQq) || !empty($serviceWechatQrcode) || !empty($serviceQqQrcode);
$smtpConfigured = !empty(getSetting('smtp_host'));
$siteName = getSiteName();
$siteLogo = getSiteLogo();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($product['title']) ?> - <?= htmlspecialchars($siteName) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<header class="header">
<div class="header-inner">
    <a href="index.php" class="logo"><?php if($siteLogo): ?><img src="<?= $siteLogo ?>" alt="" style="height:28px;margin-right:8px;vertical-align:middle"><?php endif; ?><?= htmlspecialchars($siteName) ?></a>
    <nav>
        <a href="index.php">首页</a>
        <a href="query.php">订单查询</a>
    </nav>
</div>
</header>

<main class="container">
<div class="product-detail">
    <div class="detail-card">
        <h1><?= htmlspecialchars($product['title']) ?></h1>
        <div class="price">¥<?= number_format($product['price'], 2) ?></div>
        <div class="desc"><?= htmlspecialchars($product['description']) ?></div>
        <div class="stock-info">库存：<?= $product['stock'] ?> 份 | 已售：<?= intval($product['fake_sold']) + intval($product['real_sold']) ?> 份</div>

        <form id="orderForm" onsubmit="return submitOrder(event)">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

            <div class="form-group">
                <label>收件邮箱</label>
                <input type="email" name="email" id="email" placeholder="请输入接收卡密的邮箱" required>
            </div>

            <?php if ($smtpConfigured): ?>
            <div class="form-group">
                <label>邮箱验证码</label>
                <div class="input-row">
                    <input type="text" name="email_code" id="email_code" placeholder="请输入验证码" maxlength="6" required>
                    <button type="button" id="sendCodeBtn" onclick="sendCode()">发送验证码</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>购买数量</label>
                <div class="quantity-control">
                    <button type="button" class="qty-btn qty-minus" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required onchange="updateTotal()">
                    <button type="button" class="qty-btn qty-plus" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                </div>
                <div style="font-size:12px;color:var(--text-light);margin-top:4px">最多可购买 <?= $product['stock'] ?> 份</div>
            </div>

            <div class="form-group">
                <label>支付方式</label>
                <div class="payment-methods">
                    <label class="pay-option active" id="payWechat" onclick="selectPayment('wechat')">
                        <input type="radio" name="payment_method" value="wechat" checked style="display:none">
                        <i class="fab fa-weixin" style="font-size:22px"></i>
                        <span>微信支付</span>
                    </label>
                    <label class="pay-option" id="payAlipay" onclick="selectPayment('alipay')">
                        <input type="radio" name="payment_method" value="alipay" style="display:none">
                        <i class="fab fa-alipay" style="font-size:22px"></i>
                        <span>支付宝</span>
                    </label>
                </div>
            </div>

            <div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:14px">合计金额</span>
                <span style="font-size:24px;font-weight:700;color:var(--danger)" id="totalPrice">¥<?= number_format($product['price'], 2) ?></span>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                <?= $product['stock'] <= 0 ? '暂无库存' : '提交订单' ?>
            </button>
        </form>
    </div>

    <div class="refund-section">
        <h3>退款方式</h3>
        <p><?= htmlspecialchars($refundPolicy) ?></p>
    </div>

    <?php if($cardUsage): ?>
    <div class="faq-section">
        <h3><i class="fas fa-key" style="color:var(--warning)"></i> 卡密使用方法</h3>
        <div class="faq-content"><?= htmlspecialchars($cardUsage) ?></div>
    </div>
    <?php endif; ?>

    <div class="faq-section">
        <h3>常见问题</h3>
        <div class="faq-content"><?= htmlspecialchars($faq) ?></div>
    </div>

    <?php if($hasService): ?>
    <div class="faq-section">
        <h3><i class="fas fa-headset" style="color:var(--primary)"></i> 联系客服</h3>
        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">
            <?php if($serviceWechat || $serviceWechatQrcode): ?>
            <div style="text-align:center;min-width:160px">
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                    <i class="fab fa-weixin" style="color:#07c160;font-size:20px"></i>
                    <strong>微信客服</strong>
                </div>
                <?php if($serviceWechat): ?>
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                    <span style="font-size:14px;color:var(--text-light)" id="wechatAccount"><?= htmlspecialchars($serviceWechat) ?></span>
                    <button onclick="copyText('wechatAccount')" style="background:none;border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer;color:var(--primary)"><i class="fas fa-copy"></i></button>
                </div>
                <?php endif; ?>
                <?php if($serviceWechatQrcode): ?>
                <img src="<?= $serviceWechatQrcode ?>" alt="微信二维码" style="max-width:160px;border-radius:8px;border:1px solid var(--border)">
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if($serviceQq || $serviceQqQrcode): ?>
            <div style="text-align:center;min-width:160px">
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                    <i class="fab fa-qq" style="color:#12b7f5;font-size:20px"></i>
                    <strong>QQ客服</strong>
                </div>
                <?php if($serviceQq): ?>
                <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                    <span style="font-size:14px;color:var(--text-light)" id="qqAccount"><?= htmlspecialchars($serviceQq) ?></span>
                    <button onclick="copyText('qqAccount')" style="background:none;border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer;color:var(--primary)"><i class="fas fa-copy"></i></button>
                </div>
                <?php endif; ?>
                <?php if($serviceQqQrcode): ?>
                <img src="<?= $serviceQqQrcode ?>" alt="QQ二维码" style="max-width:160px;border-radius:8px;border:1px solid var(--border)">
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>

<div class="modal-overlay" id="paymentModal">
<div class="modal payment-modal">
    <div class="modal-header">
        <h3>扫码支付</h3>
        <button class="modal-close" onclick="cancelOrder()">&times;</button>
    </div>
    <div class="modal-body">
        <div class="qr-container" id="qrContainer">
        </div>
        <div style="text-align:center;margin:8px 0">
            <span style="font-size:20px;font-weight:700;color:var(--danger)" id="payAmount"></span>
        </div>
        <p style="text-align:center;color:var(--text-light);font-size:13px;margin-bottom:8px">请使用<strong id="payMethodText"></strong>扫描上方二维码完成支付</p>
        <p style="text-align:center;color:var(--danger);font-size:12px">付款后请点击"我已付款"，系统将为您创建订单</p>
        <div class="btn-group">
            <button class="btn btn-outline" onclick="cancelOrder()">取消订单</button>
            <button class="btn btn-success" onclick="confirmPaid()">我已付款</button>
        </div>
    </div>
</div>
</div>

<div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></div>

<script>
var unitPrice = <?= $product['price'] ?>;
var smtpConfigured = <?= $smtpConfigured ? 'true' : 'false' ?>;
var pendingOrderData = null;
var emailVerified = false;

function updateTotal(){
    var qty = parseInt(document.getElementById('quantity').value) || 1;
    var max = <?= $product['stock'] ?>;
    if(qty > max) { qty = max; document.getElementById('quantity').value = max; }
    if(qty < 1) { qty = 1; document.getElementById('quantity').value = 1; }
    document.getElementById('totalPrice').textContent = '\u00A5' + (unitPrice * qty).toFixed(2);
}

function changeQty(delta){
    var input = document.getElementById('quantity');
    var qty = parseInt(input.value) || 1;
    qty += delta;
    if(qty < 1) qty = 1;
    var max = <?= $product['stock'] ?>;
    if(qty > max) qty = max;
    input.value = qty;
    updateTotal();
}

function selectPayment(method){
    document.getElementById('payWechat').classList.toggle('active', method === 'wechat');
    document.getElementById('payAlipay').classList.toggle('active', method === 'alipay');
    document.querySelector('input[name="payment_method"][value="' + method + '"]').checked = true;
}

function sendCode(){
    var email = document.getElementById('email').value;
    if(!email){ showToast('请输入邮箱','error'); return; }
    var btn = document.getElementById('sendCodeBtn');
    btn.disabled = true;
    var seconds = 60;
    btn.textContent = seconds + '秒后重发';
    var timer = setInterval(function(){
        seconds--;
        btn.textContent = seconds + '秒后重发';
        if(seconds <= 0){ clearInterval(timer); btn.disabled = false; btn.textContent = '发送验证码'; }
    }, 1000);

    fetch('api.php?action=send_code', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({email: email})
    }).then(r => r.json()).then(function(data){
        if(data.code !== 0){ showToast(data.msg, 'error'); }
        else{ showToast('验证码已发送','success'); }
    }).catch(function(){ showToast('发送失败','error'); });
}

function submitOrder(e){
    e.preventDefault();
    var form = document.getElementById('orderForm');
    var data = {
        product_id: form.product_id.value,
        email: form.email.value,
        email_code: smtpConfigured ? form.email_code.value : '',
        quantity: parseInt(form.quantity.value),
        payment_method: form.payment_method.value
    };

    if(smtpConfigured && !emailVerified){
        var submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = '验证中...';
        fetch('api.php?action=verify_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email: data.email, code: data.email_code})
        }).then(r => r.json()).then(function(res){
            if(res.code !== 0){
                showToast(res.msg, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = '提交订单';
                return;
            }
            emailVerified = true;
            showPaymentModal(data);
            submitBtn.disabled = false;
            submitBtn.textContent = '提交订单';
        }).catch(function(){
            showToast('验证失败','error');
            submitBtn.disabled = false;
            submitBtn.textContent = '提交订单';
        });
    } else {
        showPaymentModal(data);
    }

    return false;
}

function showPaymentModal(data){
    pendingOrderData = data;
    fetch('api.php?action=get_qrcode&type=' + data.payment_method)
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0){ showToast(res.msg, 'error'); return; }
        var container = document.getElementById('qrContainer');
        if(res.data.url){
            container.innerHTML = '<img src="' + res.data.url + '" alt="收款码">';
        } else {
            container.innerHTML = '<p style="color:var(--danger)">管理员暂未上传收款码，请联系客服</p>';
        }
        document.getElementById('payAmount').textContent = '\u00A5' + (unitPrice * data.quantity).toFixed(2);
        var methodIcon = data.payment_method === 'wechat' ? '<i class="fab fa-weixin" style="color:#07c160"></i> 微信' : '<i class="fab fa-alipay" style="color:#1677ff"></i> 支付宝';
        document.getElementById('payMethodText').innerHTML = methodIcon;
        document.getElementById('paymentModal').classList.add('show');
    }).catch(function(){ showToast('获取收款码失败','error'); });
}

function confirmPaid(){
    if(!pendingOrderData) return;
    var paidBtn = document.querySelector('.btn-success');
    var cancelBtn = document.querySelector('.payment-modal .btn-outline');
    paidBtn.disabled = true;
    paidBtn.textContent = '创建订单中...';
    paidBtn.style.opacity = '0.6';
    if(cancelBtn){ cancelBtn.disabled = true; cancelBtn.style.opacity = '0.6'; }
    fetch('api.php?action=create_order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(pendingOrderData)
    }).then(r => r.json()).then(function(data){
        if(data.code !== 0){
            showToast(data.msg, 'error');
            paidBtn.disabled = false;
            paidBtn.textContent = '我已付款';
            paidBtn.style.opacity = '1';
            if(cancelBtn){ cancelBtn.disabled = false; cancelBtn.style.opacity = '1'; }
            return;
        }
        document.getElementById('paymentModal').classList.remove('show');
        showToast('订单创建成功！', 'success');
        setTimeout(function(){
            window.location.href = 'query.php?order_no=' + data.data.order_no;
        }, 1500);
    }).catch(function(){
        showToast('创建订单失败','error');
        paidBtn.disabled = false;
        paidBtn.textContent = '我已付款';
        paidBtn.style.opacity = '1';
        if(cancelBtn){ cancelBtn.disabled = false; cancelBtn.style.opacity = '1'; }
    });
}

function cancelOrder(){
    document.getElementById('paymentModal').classList.remove('show');
    pendingOrderData = null;
}

function showToast(msg, type){
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function(){ toast.classList.add('show'); }, 10);
    setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ toast.remove(); }, 300); }, 3000);
}

function copyText(id){
    var el=document.getElementById(id);
    var text=el.textContent||el.innerText;
    if(navigator.clipboard){navigator.clipboard.writeText(text).then(function(){showToast('已复制到剪贴板','success')}).catch(function(){fallbackCopy(text)})}
    else{fallbackCopy(text)}
}
function fallbackCopy(text){
    var ta=document.createElement('textarea');ta.value=text;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');showToast('已复制到剪贴板','success')}catch(e){showToast('复制失败，请手动复制','error')}document.body.removeChild(ta)
}

document.getElementById('paymentModal').addEventListener('click', function(e){
    if(e.target === this) cancelOrder();
});
</script>
</body>
</html>
