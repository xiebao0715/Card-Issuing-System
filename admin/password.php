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
<title>修改密码 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>修改密码</h1>
    </div>

    <div class="detail-card" style="max-width:500px">
        <form onsubmit="return changePassword(event)">
            <div class="form-group">
                <label>当前密码</label>
                <input type="password" id="oldPassword" placeholder="请输入当前密码" required>
            </div>
            <div class="form-group">
                <label>新密码</label>
                <input type="password" id="newPassword" placeholder="请输入新密码（至少6位）" required minlength="6">
            </div>
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" id="confirmPassword" placeholder="请再次输入新密码" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">修改密码</button>
        </form>
    </div>
</div>
</div>

<script>
function changePassword(e){
    e.preventDefault();
    var oldPass = document.getElementById('oldPassword').value;
    var newPass = document.getElementById('newPassword').value;
    var confirmPass = document.getElementById('confirmPassword').value;

    if(newPass !== confirmPass){
        showToast('两次输入的新密码不一致','error');
        return false;
    }
    if(newPass.length < 6){
        showToast('新密码至少6位','error');
        return false;
    }

    fetch('../api.php?action=admin_change_password', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({old_password: oldPass, new_password: newPass})
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){
            showToast('密码修改成功，请重新登录','success');
            setTimeout(function(){ window.location.href = 'index.php'; }, 2000);
        } else {
            showToast(res.msg,'error');
        }
    }).catch(function(){ showToast('修改失败','error'); });
    return false;
}

function showToast(msg, type){
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function(){ toast.classList.add('show'); }, 10);
    setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ toast.remove(); }, 300); }, 3000);
}
</script>
</body>
</html>
