<?php
require_once __DIR__ . '/../config.php';
initDB();

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>后台登录 - <?= htmlspecialchars(getSiteName()) ?></title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="login-container">
<div class="login-card">
    <h1><i class="fas fa-shield-halved"></i> 后台管理</h1>
    <p>请输入管理员密码登录</p>
    <form onsubmit="return login(event)">
        <div class="form-group">
            <label>管理密码</label>
            <input type="password" id="password" placeholder="请输入密码" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">登录</button>
    </form>
</div>
</div>
<script>
function login(e){
    e.preventDefault();
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = '登录中...';
    fetch('../api.php?action=admin_login', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({password: document.getElementById('password').value})
    }).then(r => r.json()).then(function(data){
        if(data.code === 0){ window.location.href = 'dashboard.php'; }
        else{ showToast(data.msg, 'error'); btn.disabled = false; btn.textContent = '登录'; }
    }).catch(function(){ showToast('登录失败','error'); btn.disabled = false; btn.textContent = '登录'; });
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
