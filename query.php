<?php
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$orderNo = trim($_GET['order_no'] ?? '');
$searchEmail = trim($_GET['email'] ?? '');
$orders = [];

if ($orderNo !== '') {
    $stmt = $db->prepare("SELECT o.*, p.title as product_title FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.order_no = ?");
    $stmt->execute([$orderNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) $orders[] = $result;
} elseif ($searchEmail !== '') {
    $stmt = $db->prepare("SELECT o.*, p.title as product_title FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.email = ? ORDER BY o.created_at DESC");
    $stmt->execute([$searchEmail]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusMap = [
    'pending' => ['待确认', 'status-pending'],
    'paid' => ['已付款', 'status-paid'],
    'shipped' => ['已发货', 'status-shipped'],
    'rejected' => ['已拒绝', 'status-rejected'],
];
$siteName = getSiteName();
$siteLogo = getSiteLogo();
$cardUsage = getSetting('card_usage');
$serviceWechat = getSetting('service_wechat');
$serviceQq = getSetting('service_qq');
$serviceWechatQrcode = getSetting('service_wechat_qrcode');
$serviceQqQrcode = getSetting('service_qq_qrcode');
$hasService = !empty($serviceWechat) || !empty($serviceQq) || !empty($serviceWechatQrcode) || !empty($serviceQqQrcode);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>订单查询 - <?= htmlspecialchars($siteName) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<header class="header">
<div class="header-inner">
    <a href="index.php" class="logo"><?php if($siteLogo): ?><img src="<?= $siteLogo ?>" alt="" style="height:28px;margin-right:8px;vertical-align:middle"><?php endif; ?><?= htmlspecialchars($siteName) ?></a>
    <nav>
        <a href="index.php">首页</a>
        <a href="query.php" class="active">订单查询</a>
        <?php if ($hasService): ?>
        <a href="javascript:void(0)" onclick="showService()" style="color:rgba(255,255,255,0.85)"><i class="fas fa-headset"></i> 客服</a>
        <?php endif; ?>
    </nav>
</div>
</header>

<main class="container" style="max-width:800px">
<div class="detail-card">
    <h2 style="margin-bottom:16px">订单查询</h2>
    <form method="get" style="margin-bottom:16px">
        <div class="form-group">
            <label>订单号查询</label>
            <div class="input-row">
                <input type="text" name="order_no" placeholder="请输入订单号" value="<?= htmlspecialchars($orderNo) ?>">
                <button type="submit" class="btn btn-primary btn-sm">查询</button>
            </div>
        </div>
    </form>
    <form method="get" style="margin-bottom:16px">
        <div class="form-group">
            <label>邮箱查询</label>
            <div class="input-row">
                <input type="email" name="email" placeholder="请输入下单邮箱" value="<?= htmlspecialchars($searchEmail) ?>">
                <button type="submit" class="btn btn-primary btn-sm">查询</button>
            </div>
        </div>
    </form>
</div>

<?php if ($orderNo !== '' || $searchEmail !== ''): ?>
<?php if (empty($orders)): ?>
<div class="detail-card" style="text-align:center;padding:40px">
    <p style="color:var(--text-light)">未找到相关订单</p>
</div>
<?php else: ?>
<?php foreach ($orders as $order): ?>
<div class="order-item">
    <div class="order-header">
        <span class="order-no">订单号：<?= htmlspecialchars($order['order_no']) ?></span>
        <span class="status-badge <?= $statusMap[$order['status']][1] ?>"><?= $statusMap[$order['status']][0] ?></span>
    </div>
    <div style="font-size:14px;margin-bottom:4px"><strong>商品：</strong><?= htmlspecialchars($order['product_title']) ?></div>
    <div style="font-size:13px;color:var(--text-light);margin-bottom:4px"><strong>邮箱：</strong><?= htmlspecialchars($order['email']) ?></div>
    <div style="font-size:13px;color:var(--text-light);margin-bottom:4px"><strong>数量：</strong><?= $order['quantity'] ?> 份</div>
    <div style="font-size:13px;color:var(--text-light);margin-bottom:4px"><strong>金额：</strong>¥<?= number_format($order['total_price'], 2) ?></div>
    <div style="font-size:13px;color:var(--text-light);margin-bottom:4px"><strong>支付方式：</strong><?= $order['payment_method'] === 'wechat' ? '微信支付' : '支付宝' ?></div>
    <div style="font-size:13px;color:var(--text-light);margin-bottom:4px"><strong>下单时间：</strong><?= $order['created_at'] ?></div>
    <?php if ($order['status'] === 'shipped' && $order['card_content']): ?>
    <div style="margin-top:12px">
        <strong style="font-size:14px">卡密内容：</strong>
        <div class="card-content-box"><?= htmlspecialchars($order['card_content']) ?></div>
    </div>
    <?php if ($cardUsage): ?>
    <div style="margin-top:12px;padding:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px">
        <strong style="font-size:13px;color:#92400e"><i class="fas fa-key" style="color:#f59e0b"></i> 卡密使用方法</strong>
        <div style="font-size:12px;color:#78350f;margin-top:6px;white-space:pre-wrap;line-height:1.6"><?= htmlspecialchars($cardUsage) ?></div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($order['status'] === 'rejected'): ?>
    <div style="margin-top:8px;color:var(--danger);font-size:13px">该订单已被拒绝，如有疑问请联系客服。</div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
</main>

<?php if ($hasService): ?>
<div class="modal-overlay" id="serviceModal">
<div class="modal" style="max-width:600px">
    <div class="modal-header">
        <h3><i class="fas fa-headset" style="color:var(--primary)"></i> 联系客服</h3>
        <button class="modal-close" onclick="closeService()">&times;</button>
    </div>
    <div class="modal-body">
        <div class="service-layout">
        <?php if($serviceWechat || $serviceWechatQrcode): ?>
        <div class="service-item">
            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px">
                <i class="fab fa-weixin" style="color:#07c160;font-size:22px"></i>
                <span style="font-size:15px;font-weight:600">微信客服</span>
            </div>
            <?php if($serviceWechat): ?>
            <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                <span style="font-size:14px;color:var(--text-light)" id="wechatAccount"><?= htmlspecialchars($serviceWechat) ?></span>
                <button onclick="copyText('wechatAccount')" style="background:none;border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer;color:var(--primary)"><i class="fas fa-copy"></i> 复制</button>
            </div>
            <?php endif; ?>
            <?php if($serviceWechatQrcode): ?>
            <div style="text-align:center"><img src="<?= $serviceWechatQrcode ?>" alt="微信二维码" style="max-width:180px;border-radius:8px;border:1px solid var(--border)"></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if($serviceQq || $serviceQqQrcode): ?>
        <div class="service-item">
            <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px">
                <i class="fab fa-qq" style="color:#12b7f5;font-size:22px"></i>
                <span style="font-size:15px;font-weight:600">QQ客服</span>
            </div>
            <?php if($serviceQq): ?>
            <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px">
                <span style="font-size:14px;color:var(--text-light)" id="qqAccount"><?= htmlspecialchars($serviceQq) ?></span>
                <button onclick="copyText('qqAccount')" style="background:none;border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer;color:var(--primary)"><i class="fas fa-copy"></i> 复制</button>
            </div>
            <?php endif; ?>
            <?php if($serviceQqQrcode): ?>
            <div style="text-align:center"><img src="<?= $serviceQqQrcode ?>" alt="QQ二维码" style="max-width:180px;border-radius:8px;border:1px solid var(--border)"></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>
</div>
<script>
function showService(){document.getElementById('serviceModal').classList.add('show')}
function closeService(){document.getElementById('serviceModal').classList.remove('show')}
function copyText(id){
    var el=document.getElementById(id);
    var text=el.textContent||el.innerText;
    if(navigator.clipboard){navigator.clipboard.writeText(text).then(function(){showToast('已复制到剪贴板','success')}).catch(function(){fallbackCopy(text)})}
    else{fallbackCopy(text)}
}
function fallbackCopy(text){
    var ta=document.createElement('textarea');ta.value=text;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');showToast('已复制到剪贴板','success')}catch(e){showToast('复制失败，请手动复制','error')}document.body.removeChild(ta)
}
function showToast(msg, type){
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function(){ toast.classList.add('show'); }, 10);
    setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ toast.remove(); }, 300); }, 3000);
}
document.getElementById('serviceModal').addEventListener('click',function(e){if(e.target===this)closeService()});
</script>
<?php endif; ?>

<div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></div>
</body>
</html>
