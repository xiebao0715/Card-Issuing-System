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
<title>订单管理 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>订单管理</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-sm btn-outline" onclick="loadOrders('')" id="filterAll">全部</button>
            <button class="btn btn-sm btn-warning" onclick="loadOrders('paid')" id="filterPaid">待发货</button>
            <button class="btn btn-sm btn-success" onclick="loadOrders('shipped')" id="filterShipped">已发货</button>
            <button class="btn btn-sm btn-danger" onclick="loadOrders('rejected')" id="filterRejected">已拒绝</button>
        </div>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>订单号</th>
                    <th>商品</th>
                    <th>邮箱</th>
                    <th>数量</th>
                    <th>金额</th>
                    <th>支付方式</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="orderList">
                <tr><td colspan="9" style="text-align:center;padding:40px">加载中...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="pagination"></div>
</div>
</div>

<div class="modal-overlay" id="orderDetailModal">
<div class="modal" style="max-width:600px">
    <div class="modal-header">
        <h3>订单详情</h3>
        <button class="modal-close" onclick="closeOrderDetail()">&times;</button>
    </div>
    <div class="modal-body" id="orderDetailBody"></div>
</div>
</div>

<script>
var currentPage = 1;
var currentFilter = '';

var statusMap = {
    'pending': '<span class="status-badge status-pending">待确认</span>',
    'paid': '<span class="status-badge status-paid">待发货</span>',
    'shipped': '<span class="status-badge status-shipped">已发货</span>',
    'rejected': '<span class="status-badge status-rejected">已拒绝</span>'
};

function loadOrders(status, page){
    currentFilter = status || '';
    currentPage = page || 1;
    var url = '../api.php?action=admin_get_orders&page=' + currentPage;
    if(status) url += '&status=' + status;

    fetch(url).then(r => r.json()).then(function(res){
        if(res.code !== 0) return;
        var html = '';
        res.data.list.forEach(function(o){
            html += '<tr>';
            html += '<td style="font-size:12px;font-family:monospace">' + escapeHtml(o.order_no) + '</td>';
            html += '<td>' + escapeHtml(o.product_title || '已删除') + '</td>';
            html += '<td style="font-size:12px">' + escapeHtml(o.email) + '</td>';
            html += '<td>' + o.quantity + '</td>';
            html += '<td>¥' + parseFloat(o.total_price).toFixed(2) + '</td>';
            html += '<td>' + (o.payment_method === 'wechat' ? '微信' : '支付宝') + '</td>';
            html += '<td>' + (statusMap[o.status] || o.status) + '</td>';
            html += '<td style="font-size:12px">' + o.created_at + '</td>';
            html += '<td class="actions">';
            html += '<button class="btn btn-sm btn-outline" onclick="showOrderDetail(' + o.id + ')">详情</button>';
            if(o.status === 'paid'){
                html += '<button class="btn btn-sm btn-success" onclick="shipOrder(' + o.id + ')">发货</button>';
                html += '<button class="btn btn-sm btn-danger" onclick="rejectOrder(' + o.id + ')">拒绝</button>';
            }
            html += '</td>';
            html += '</tr>';
        });
        if(!html) html = '<tr><td colspan="9" style="text-align:center;padding:40px">暂无订单</td></tr>';
        document.getElementById('orderList').innerHTML = html;

        var totalPages = Math.ceil(res.data.total / res.data.pageSize);
        var pagHtml = '';
        if(totalPages > 1){
            for(var i = 1; i <= totalPages; i++){
                if(i === currentPage) pagHtml += '<span class="current">' + i + '</span>';
                else pagHtml += '<a href="javascript:loadOrders(\'' + currentFilter + '\',' + i + ')">' + i + '</a>';
            }
        }
        document.getElementById('pagination').innerHTML = pagHtml;
    });
}

function showOrderDetail(id){
    fetch('../api.php?action=admin_get_orders&page=1')
    .then(r => r.json())
    .then(function(res){
        var o = res.data.list.find(function(x){ return x.id === id; });
        if(!o) return;
        var html = '<div style="line-height:2">';
        html += '<div><strong>订单号：</strong>' + escapeHtml(o.order_no) + '</div>';
        html += '<div><strong>商品：</strong>' + escapeHtml(o.product_title || '已删除') + '</div>';
        html += '<div><strong>邮箱：</strong>' + escapeHtml(o.email) + '</div>';
        html += '<div><strong>数量：</strong>' + o.quantity + '</div>';
        html += '<div><strong>金额：</strong>¥' + parseFloat(o.total_price).toFixed(2) + '</div>';
        html += '<div><strong>支付方式：</strong>' + (o.payment_method === 'wechat' ? '微信' : '支付宝') + '</div>';
        html += '<div><strong>状态：</strong>' + (statusMap[o.status] || o.status) + '</div>';
        html += '<div><strong>下单时间：</strong>' + o.created_at + '</div>';
        if(o.card_content){
            html += '<div style="margin-top:8px"><strong>卡密内容：</strong><div class="card-content-box">' + escapeHtml(o.card_content) + '</div></div>';
        }
        html += '</div>';
        document.getElementById('orderDetailBody').innerHTML = html;
        document.getElementById('orderDetailModal').classList.add('show');
    });
}

function closeOrderDetail(){
    document.getElementById('orderDetailModal').classList.remove('show');
}

function shipOrder(id){
    if(!confirm('确定发货？系统将自动分配卡密并发送邮件通知。')) return;
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = '发货中...';
    btn.style.opacity = '0.6';
    fetch('../api.php?action=admin_ship_order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order_id: id})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast(res.msg,'success'); loadOrders(currentFilter, currentPage); }
        else{ showToast(res.msg,'error'); btn.disabled = false; btn.textContent = '发货'; btn.style.opacity = '1'; }
    }).catch(function(){ showToast('操作失败','error'); btn.disabled = false; btn.textContent = '发货'; btn.style.opacity = '1'; });
}

function rejectOrder(id){
    if(!confirm('确定拒绝该订单？')) return;
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = '处理中...';
    btn.style.opacity = '0.6';
    fetch('../api.php?action=admin_reject_order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order_id: id})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast(res.msg,'success'); loadOrders(currentFilter, currentPage); }
        else{ showToast(res.msg,'error'); btn.disabled = false; btn.textContent = '拒绝'; btn.style.opacity = '1'; }
    }).catch(function(){ showToast('操作失败','error'); btn.disabled = false; btn.textContent = '拒绝'; btn.style.opacity = '1'; });
}

function escapeHtml(str){
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showToast(msg, type){
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function(){ toast.classList.add('show'); }, 10);
    setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ toast.remove(); }, 300); }, 3000);
}

loadOrders('');
</script>
</body>
</html>
