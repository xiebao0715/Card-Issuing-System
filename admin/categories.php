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
<title>分类管理 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>分类管理</h1>
        <button class="btn btn-primary" onclick="showAddModal()"><i class="fas fa-plus"></i> 添加分类</button>
    </div>

    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>分类名称</th>
                    <th>排序</th>
                    <th>商品数量</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="categoryList">
                <tr><td colspan="5" style="text-align:center;padding:40px">加载中...</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal-overlay" id="addModal">
<div class="modal" style="max-width:400px">
    <div class="modal-header">
        <h3>添加分类</h3>
        <button class="modal-close" onclick="closeAddModal()">&times;</button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label>分类名称</label>
            <input type="text" id="catName" placeholder="请输入分类名称" maxlength="50">
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeAddModal()">取消</button>
        <button class="btn btn-primary" onclick="addCategory()">添加</button>
    </div>
</div>
</div>

<script>
loadCategories();

function loadCategories(){
    fetch('../api.php?action=admin_get_categories')
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0){ showToast(res.msg,'error'); return; }
        var html = '';
        if(res.data.length === 0){
            html = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light)">暂无分类，点击上方按钮添加</td></tr>';
        } else {
            res.data.forEach(function(c){
                html += '<tr>';
                html += '<td>' + c.id + '</td>';
                html += '<td><strong>' + escapeHtml(c.name) + '</strong></td>';
                html += '<td>' + c.sort_order + '</td>';
                html += '<td id="catCount_' + c.id + '">-</td>';
                html += '<td class="actions">';
                html += '<button class="btn btn-sm btn-danger" onclick="deleteCategory(' + c.id + ',\'' + escapeHtml(c.name) + '\')"><i class="fas fa-trash"></i> 删除</button>';
                html += '</td>';
                html += '</tr>';
            });
        }
        document.getElementById('categoryList').innerHTML = html;
        res.data.forEach(function(c){
            fetch('../api.php?action=admin_get_products&page=1')
            .then(r => r.json())
            .then(function(pRes){
                if(pRes.code === 0){
                    var count = pRes.data.list.filter(function(p){ return p.category_id == c.id; }).length;
                    var el = document.getElementById('catCount_' + c.id);
                    if(el) el.textContent = count;
                }
            });
        });
    }).catch(function(err){ showToast('加载失败','error'); });
}

function showAddModal(){
    document.getElementById('catName').value = '';
    document.getElementById('addModal').classList.add('show');
    document.getElementById('catName').focus();
}

function closeAddModal(){
    document.getElementById('addModal').classList.remove('show');
}

function addCategory(){
    var name = document.getElementById('catName').value.trim();
    if(!name){ showToast('请输入分类名称','error'); return; }
    fetch('../api.php?action=admin_add_category', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({name: name})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast('分类添加成功','success'); closeAddModal(); loadCategories(); }
        else{ showToast(res.msg,'error'); }
    }).catch(function(){ showToast('添加失败','error'); });
}

function deleteCategory(id, name){
    if(!confirm('确定删除分类"' + name + '"？\n该分类下的商品将变为无分类状态。')) return;
    fetch('../api.php?action=admin_delete_category', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){ showToast('分类删除成功','success'); loadCategories(); }
        else{ showToast(res.msg,'error'); }
    }).catch(function(){ showToast('删除失败','error'); });
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

document.getElementById('catName').addEventListener('keydown', function(e){
    if(e.key === 'Enter') addCategory();
});
</script>
</body>
</html>
