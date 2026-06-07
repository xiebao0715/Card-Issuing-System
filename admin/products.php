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
<title>商品管理 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>商品管理</h1>
        <button class="btn btn-primary" onclick="showProductModal()">+ 添加商品</button>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>标题</th>
                    <th>分类</th>
                    <th>价格</th>
                    <th>可用库存</th>
                    <th>总卡密</th>
                    <th>真实已售</th>
                    <th>伪已售</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="productList">
                <tr><td colspan="10" style="text-align:center;padding:40px">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal-overlay" id="productModal">
<div class="modal" style="max-width:600px">
    <div class="modal-header">
        <h3 id="productModalTitle">添加商品</h3>
        <button class="modal-close" onclick="closeProductModal()">&times;</button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="editProductId" value="0">
        <div class="form-group">
            <label>商品标题</label>
            <input type="text" id="pTitle" placeholder="请输入商品标题">
        </div>
        <div class="form-group">
            <label>商品描述</label>
            <textarea id="pDesc" rows="3" placeholder="请输入商品描述"></textarea>
        </div>
        <div class="form-group">
            <label>价格 (¥)</label>
            <input type="number" id="pPrice" step="0.01" min="0.01" placeholder="0.00">
        </div>
        <div class="form-group">
            <label>分类</label>
            <select id="pCategory">
                <option value="0">无分类</option>
            </select>
        </div>
        <div class="form-group">
            <label>状态</label>
            <select id="pStatus">
                <option value="1">上架</option>
                <option value="0">下架</option>
            </select>
        </div>
        <div class="form-group">
            <label>伪已售数量</label>
            <input type="number" id="pFakeSold" min="0" value="0" placeholder="设置后用户端显示的已售数会加上此数值">
            <p style="font-size:12px;color:var(--text-light);margin-top:4px">用户端已售 = 真实已售 + 伪已售</p>
        </div>
        <div class="form-group">
            <label>卡密添加（每个卡密换行）</label>
            <textarea id="pCards" rows="5" placeholder="每行一个卡密&#10;卡密1&#10;卡密2&#10;卡密3"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeProductModal()">取消</button>
        <button class="btn btn-primary" onclick="saveProduct()">保存</button>
    </div>
</div>
</div>

<div class="modal-overlay" id="cardsModal">
<div class="modal" style="max-width:700px">
    <div class="modal-header">
        <h3>卡密管理</h3>
        <button class="modal-close" onclick="closeCardsModal()">&times;</button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label>追加卡密（每个卡密换行）</label>
            <textarea id="addCardsInput" rows="4" placeholder="每行一个卡密"></textarea>
        </div>
        <button class="btn btn-primary btn-sm" onclick="addCards()" style="margin-bottom:16px">追加卡密</button>
        <h4 style="margin-bottom:8px">已有卡密</h4>
        <div id="cardsList" style="max-height:400px;overflow-y:auto"></div>
    </div>
</div>
</div>

<script>
var currentProductId = 0;

function loadProducts(){
    fetch('../api.php?action=admin_get_products')
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0) return;
        var html = '';
        res.data.list.forEach(function(p){
            html += '<tr>';
            html += '<td>' + p.id + '</td>';
            html += '<td>' + escapeHtml(p.title) + '</td>';
            html += '<td>' + escapeHtml(p.category_name || '无') + '</td>';
            html += '<td>¥' + parseFloat(p.price).toFixed(2) + '</td>';
            html += '<td>' + p.stock + '</td>';
            html += '<td>' + p.total_cards + '</td>';
            html += '<td>' + p.real_sold + '</td>';
            html += '<td>' + (parseInt(p.fake_sold) > 0 ? '<span style="color:var(--warning);font-weight:600">' + p.fake_sold + '</span>' : '0') + '</td>';
            html += '<td>' + (p.status == 1 ? '<span class="status-badge status-shipped">上架</span>' : '<span class="status-badge status-rejected">下架</span>') + '</td>';
            html += '<td class="actions">';
            html += '<button class="btn btn-sm btn-outline" onclick="showCards(' + p.id + ')">卡密</button>';
            html += '<button class="btn btn-sm btn-primary" onclick="editProduct(' + p.id + ')">编辑</button>';
            html += '<button class="btn btn-sm btn-danger" onclick="deleteProduct(' + p.id + ')">删除</button>';
            html += '</td>';
            html += '</tr>';
        });
        if(!html) html = '<tr><td colspan="10" style="text-align:center;padding:40px">暂无商品</td></tr>';
        document.getElementById('productList').innerHTML = html;
    });
}

function loadCategories(){
    fetch('../api.php?action=admin_get_categories')
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0) return;
        var sel = document.getElementById('pCategory');
        sel.innerHTML = '<option value="0">无分类</option>';
        res.data.forEach(function(c){
            sel.innerHTML += '<option value="' + c.id + '">' + escapeHtml(c.name) + '</option>';
        });
    });
}

function showProductModal(id){
    document.getElementById('editProductId').value = 0;
    document.getElementById('pTitle').value = '';
    document.getElementById('pDesc').value = '';
    document.getElementById('pPrice').value = '';
    document.getElementById('pCategory').value = '0';
    document.getElementById('pStatus').value = '1';
    document.getElementById('pFakeSold').value = '0';
    document.getElementById('pCards').value = '';
    document.getElementById('productModalTitle').textContent = '添加商品';
    document.getElementById('productModal').classList.add('show');
    loadCategories();
}

function editProduct(id){
    fetch('../api.php?action=admin_get_products&page=1')
    .then(r => r.json())
    .then(function(res){
        var p = res.data.list.find(function(x){ return x.id === id; });
        if(!p) return;
        loadCategories();
        setTimeout(function(){
            document.getElementById('editProductId').value = p.id;
            document.getElementById('pTitle').value = p.title;
            document.getElementById('pDesc').value = p.description;
            document.getElementById('pPrice').value = p.price;
            document.getElementById('pCategory').value = p.category_id;
            document.getElementById('pStatus').value = p.status;
            document.getElementById('pFakeSold').value = p.fake_sold || 0;
            document.getElementById('pCards').value = '';
            document.getElementById('productModalTitle').textContent = '编辑商品';
            document.getElementById('productModal').classList.add('show');
        }, 200);
    });
}

function closeProductModal(){
    document.getElementById('productModal').classList.remove('show');
}

function saveProduct(){
    var data = {
        id: parseInt(document.getElementById('editProductId').value),
        title: document.getElementById('pTitle').value,
        description: document.getElementById('pDesc').value,
        price: parseFloat(document.getElementById('pPrice').value),
        category_id: parseInt(document.getElementById('pCategory').value),
        status: parseInt(document.getElementById('pStatus').value),
        fake_sold: parseInt(document.getElementById('pFakeSold').value) || 0,
        cards: document.getElementById('pCards').value
    };
    if(!data.title){ showToast('请输入标题','error'); return; }
    if(!data.price || data.price <= 0){ showToast('请输入有效价格','error'); return; }

    fetch('../api.php?action=admin_save_product', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast('保存成功','success'); closeProductModal(); loadProducts(); }
        else{ showToast(res.msg,'error'); }
    });
}

function deleteProduct(id){
    if(!confirm('确定删除该商品？关联的卡密也会被删除！')) return;
    fetch('../api.php?action=admin_delete_product', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast('删除成功','success'); loadProducts(); }
        else{ showToast(res.msg,'error'); }
    });
}

function showCards(productId){
    currentProductId = productId;
    document.getElementById('addCardsInput').value = '';
    loadCards(productId);
    document.getElementById('cardsModal').classList.add('show');
}

function closeCardsModal(){
    document.getElementById('cardsModal').classList.remove('show');
    currentProductId = 0;
}

function loadCards(productId){
    fetch('../api.php?action=admin_get_product_cards&product_id=' + productId)
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0) return;
        var html = '';
        res.data.forEach(function(c){
            var statusBadge = c.used ? '<span class="status-badge status-shipped">已用</span>' : '<span class="status-badge status-pending">可用</span>';
            var deleteBtn = c.used ? '' : '<button class="btn btn-sm btn-danger" onclick="deleteCard(' + c.id + ')">删除</button>';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;border-bottom:1px solid var(--border)">';
            html += '<div style="flex:1;font-family:monospace;font-size:13px;word-break:break-all">' + escapeHtml(c.content) + '</div>';
            html += '<div style="display:flex;align-items:center;gap:8px;margin-left:12px">' + statusBadge + deleteBtn + '</div>';
            html += '</div>';
        });
        if(!html) html = '<div style="text-align:center;padding:20px;color:var(--text-light)">暂无卡密</div>';
        document.getElementById('cardsList').innerHTML = html;
    });
}

function addCards(){
    var cards = document.getElementById('addCardsInput').value;
    if(!cards.trim()){ showToast('请输入卡密','error'); return; }
    fetch('../api.php?action=admin_add_cards', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({product_id: currentProductId, cards: cards})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast(res.msg,'success'); document.getElementById('addCardsInput').value = ''; loadCards(currentProductId); loadProducts(); }
        else{ showToast(res.msg,'error'); }
    });
}

function deleteCard(id){
    if(!confirm('确定删除该卡密？')) return;
    fetch('../api.php?action=admin_delete_card', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast('删除成功','success'); loadCards(currentProductId); loadProducts(); }
        else{ showToast(res.msg,'error'); }
    });
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

loadProducts();
</script>
</body>
</html>
