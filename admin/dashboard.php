<?php
require_once __DIR__ . '/../config.php';
initDB();
requireLogin();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>仪表盘 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>仪表盘</h1>
    </div>

    <div class="stat-cards">
        <div class="stat-card stat-total">
            <div class="stat-label">全部订单</div>
            <div class="stat-value" id="statTotal">-</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-label">待发货</div>
            <div class="stat-value" id="statPending">-</div>
        </div>
        <div class="stat-card stat-shipped">
            <div class="stat-label">已发货</div>
            <div class="stat-value" id="statShipped">-</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">已拒绝</div>
            <div class="stat-value" style="color:var(--danger)" id="statRejected">-</div>
        </div>
    </div>

    <div class="chart-container">
        <h3>近7日订单数据</h3>
        <canvas id="orderChart"></canvas>
    </div>
</div>
</div>

<script>
fetch('../api.php?action=admin_get_stats')
.then(r => r.json())
.then(function(res){
    if(res.code !== 0) return;
    var d = res.data;
    document.getElementById('statTotal').textContent = d.total;
    document.getElementById('statPending').textContent = d.pending;
    document.getElementById('statShipped').textContent = d.shipped;
    document.getElementById('statRejected').textContent = d.rejected;

    var labels = d.chart.map(function(i){ return i.date; });
    var counts = d.chart.map(function(i){ return i.count; });
    var amounts = d.chart.map(function(i){ return i.amount; });

    new Chart(document.getElementById('orderChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '订单数',
                    data: counts,
                    backgroundColor: 'rgba(74,108,247,0.7)',
                    borderColor: 'rgba(74,108,247,1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: '金额(¥)',
                    data: amounts,
                    type: 'line',
                    borderColor: 'rgba(16,185,129,1)',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: '订单数' }, ticks: { stepSize: 1 } },
                y1: { beginAtZero: true, position: 'right', title: { display: true, text: '金额(¥)' }, grid: { drawOnChartArea: false } }
            }
        }
    });
});
</script>
</body>
</html>
