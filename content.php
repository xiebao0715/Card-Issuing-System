<?php
require_once __DIR__ . '/config.php';
initDB();

$type = $_GET['type'] ?? '';
if (!in_array($type, ['notice', 'recharge'])) {
    header('Location: index.php');
    exit;
}

$siteName = getSiteName();
$siteLogo = getSiteLogo();
$title = $type === 'notice' ? getSetting('marquee_1_text') : getSetting('marquee_2_text');
if (empty($title)) {
    $title = $type === 'notice' ? '商品须知' : '充值流程';
}
$content = $type === 'notice' ? getSetting('notice_content') : getSetting('recharge_content');
$announcement = getSetting('announcement');
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
<title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($siteName) ?></title>
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
<div class="content-page">
    <div class="content-page-header">
        <a href="index.php" class="content-back"><i class="fas fa-arrow-left"></i> 返回首页</a>
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="content-page-body">
        <?php if (empty($content)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <p>暂无内容</p>
        </div>
        <?php else: ?>
        <div class="rich-content"><?= $content ?></div>
        <?php endif; ?>
    </div>
</div>
</main>

<?php if ($announcement): ?>
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
<?php endif; ?>

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
function closeAnnouncement(){document.getElementById('announcementModal').classList.remove('show')}
function copyText(id){
    var el=document.getElementById(id);
    var text=el.textContent||el.innerText;
    if(navigator.clipboard){navigator.clipboard.writeText(text).then(function(){showToast('已复制到剪贴板','success')}).catch(function(){fallbackCopy(text)})}
    else{fallbackCopy(text)}
}
function fallbackCopy(text){
    var ta=document.createElement('textarea');ta.value=text;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');showToast('已复制到剪贴板','success')}catch(e){showToast('复制失败，请手动复制','error')}document.body.removeChild(ta)
}
<?php if ($hasService): ?>
document.getElementById('serviceModal').addEventListener('click',function(e){if(e.target===this)closeService()});
<?php endif; ?>
<?php if ($announcement): ?>
document.getElementById('announcementModal').addEventListener('click',function(e){if(e.target===this)closeAnnouncement()});
<?php endif; ?>
</script>
</body>
</html>
