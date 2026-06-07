<?php
require_once __DIR__ . '/../config.php';
initDB();
requireLogin();

$current = basename($_SERVER['PHP_SELF'], '.php');
$siteName = getSiteName();
$siteLogo = getSiteLogo();
$menu = [
    ['dashboard', '仪表盘', 'fa-chart-pie'],
    ['products', '商品管理', 'fa-box'],
    ['categories', '分类管理', 'fa-tags'],
    ['orders', '订单管理', 'fa-clipboard-list'],
    ['qrcodes', '收款码管理', 'fa-qrcode'],
    ['settings', '系统设置', 'fa-cog'],
    ['password', '修改密码', 'fa-key'],
];
?>
<button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <?php if($siteLogo): ?><img src="../<?= $siteLogo ?>" alt="" style="height:32px;margin-bottom:6px"><?php endif; ?>
        <h2><?= htmlspecialchars($siteName) ?></h2>
        <div style="font-size:12px;color:rgba(255,255,255,0.5)">管理后台</div>
    </div>
    <nav>
        <?php foreach ($menu as $item): ?>
        <a href="<?= $item[0] ?>.php" class="<?= $current === $item[0] ? 'active' : '' ?>"><i class="fas <?= $item[2] ?>"></i> <?= $item[1] ?></a>
        <?php endforeach; ?>
        <a href="logout.php" style="margin-top:20px;color:rgba(255,255,255,0.5)"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
    </nav>
</aside>
<script>
function toggleSidebar(){
    document.querySelector('.admin-sidebar').classList.toggle('sidebar-open');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
