<?php
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$search = trim($_GET['search'] ?? '');
$categoryId = intval($_GET['category'] ?? 0);

$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT p.*, (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND used = 0) as stock, (SELECT COUNT(*) FROM cards WHERE product_id = p.id AND used = 1) as real_sold FROM products p WHERE p.status = 1";
$params = [];
if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}
if ($search !== '') {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$announcement = getSetting('announcement');
$siteName = getSiteName();
$siteLogo = getSiteLogo();
$serviceWechat = getSetting('service_wechat');
$serviceQq = getSetting('service_qq');
$serviceWechatQrcode = getSetting('service_wechat_qrcode');
$serviceQqQrcode = getSetting('service_qq_qrcode');
$hasService = !empty($serviceWechat) || !empty($serviceQq) || !empty($serviceWechatQrcode) || !empty($serviceQqQrcode);
$marquee1Text = getSetting('marquee_1_text');
$marquee1Color = getSetting('marquee_1_color') ?: '#e74c3c';
$marquee2Text = getSetting('marquee_2_text');
$marquee2Color = getSetting('marquee_2_color') ?: '#4a6cf7';
$hasMarquee1 = !empty($marquee1Text);
$hasMarquee2 = !empty($marquee2Text);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<header class="header">
<div class="header-inner">
    <a href="index.php" class="logo"><?php if($siteLogo): ?><img src="<?= $siteLogo ?>" alt="" style="height:28px;margin-right:8px;vertical-align:middle"><?php endif; ?><?= htmlspecialchars($siteName) ?></a>
    <nav>
        <a href="index.php" class="active">首页</a>
        <a href="query.php">订单查询</a>
        <?php if ($announcement): ?>
        <a href="javascript:void(0)" onclick="showAnnouncement()" style="color:rgba(255,255,255,0.85)"><i class="fas fa-bullhorn"></i> 公告</a>
        <?php endif; ?>
        <?php if ($hasService): ?>
        <a href="javascript:void(0)" onclick="showService()" style="color:rgba(255,255,255,0.85)"><i class="fas fa-headset"></i> 客服</a>
        <?php endif; ?>
    </nav>
</div>
</header>

<main class="container">
<div class="search-bar">
    <form method="get" style="display:flex;gap:8px;width:100%">
        <input type="text" name="search" placeholder="搜索商品..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">搜索</button>
    </form>
</div>

<?php if ($hasMarquee1 || $hasMarquee2): ?>
<div class="marquee-wrap">
    <?php if ($hasMarquee1): ?>
    <a href="content.php?type=notice" class="marquee-item" style="--mc:<?= htmlspecialchars($marquee1Color) ?>">
        <span class="marquee-label">商品须知：</span>
        <div class="marquee-viewport"><div class="marquee-track">
            <span class="marquee-text"><?= htmlspecialchars($marquee1Text) ?></span>
            <span class="marquee-text"><?= htmlspecialchars($marquee1Text) ?></span>
        </div></div>
    </a>
    <?php endif; ?>
    <?php if ($hasMarquee2): ?>
    <a href="content.php?type=recharge" class="marquee-item" style="--mc:<?= htmlspecialchars($marquee2Color) ?>">
        <span class="marquee-label">充值流程：</span>
        <div class="marquee-viewport"><div class="marquee-track">
            <span class="marquee-text"><?= htmlspecialchars($marquee2Text) ?></span>
            <span class="marquee-text"><?= htmlspecialchars($marquee2Text) ?></span>
        </div></div>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="categories">
    <a href="index.php" class="cat-btn <?= $categoryId === 0 ? 'active' : '' ?>">全部</a>
    <?php foreach ($categories as $cat): ?>
    <a href="index.php?category=<?= $cat['id'] ?>" class="cat-btn <?= $categoryId === $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
<div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    <p>暂无商品</p>
</div>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $p): ?>
    <a href="product.php?id=<?= $p['id'] ?>" class="product-card">
        <div class="card-body">
            <div class="card-title"><?= htmlspecialchars($p['title']) ?></div>
            <div class="card-desc"><?= htmlspecialchars($p['description']) ?></div>
            <div class="card-footer">
                <span class="price">¥<?= number_format($p['price'], 2) ?><small>/份</small></span>
                <span class="sold-info">已售 <?= intval($p['fake_sold']) + intval($p['real_sold']) ?></span>
                <span class="stock">库存 <?= $p['stock'] ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</main>

<div class="modal-overlay" id="announcementModal">
<div class="modal">
    <div class="modal-header">
        <h3>公告</h3>
        <button class="modal-close" onclick="closeAnnouncement()">&times;</button>
    </div>
    <div class="modal-body">
        <div style="white-space:pre-wrap;line-height:1.8"><?= htmlspecialchars($announcement) ?></div>
    </div>
</div>
</div>

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
<?php endif; ?>

<div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></div>

<script>
function showAnnouncement(){document.getElementById('announcementModal').classList.add('show')}
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
function closeAnnouncement(){
    document.getElementById('announcementModal').classList.remove('show');
    localStorage.setItem('announcement_seen','1');
}
document.getElementById('announcementModal').addEventListener('click',function(e){if(e.target===this)closeAnnouncement()});
<?php if ($hasService): ?>
document.getElementById('serviceModal').addEventListener('click',function(e){if(e.target===this)closeService()});
<?php endif; ?>
<?php if ($announcement): ?>
if(!localStorage.getItem('announcement_seen')){
    document.getElementById('announcementModal').classList.add('show');
}
<?php endif; ?>
</script>
</body>
</html>
