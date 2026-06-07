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
<title>收款码管理 - <?= htmlspecialchars(getSiteName()) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>收款码管理</h1>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px">
        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fab fa-weixin" style="color:#07c160"></i> 微信收款码</h3>
            <div class="qrcode-upload" id="wechatUpload" onclick="document.getElementById('wechatFile').click()">
                <div id="wechatPreview">
                    <p style="font-size:40px;margin-bottom:8px;color:#07c160"><i class="fas fa-mobile-screen-button"></i></p>
                    <p>点击上传微信收款码</p>
                    <p style="font-size:12px">支持 JPG/PNG/GIF/WEBP，不超过2MB</p>
                </div>
            </div>
            <input type="file" id="wechatFile" accept="image/*" style="display:none" onchange="uploadQRCode('wechat', this)">
        </div>

        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fab fa-alipay" style="color:#1677ff"></i> 支付宝收款码</h3>
            <div class="qrcode-upload" id="alipayUpload" onclick="document.getElementById('alipayFile').click()">
                <div id="alipayPreview">
                    <p style="font-size:40px;margin-bottom:8px;color:#1677ff"><i class="fas fa-mobile-screen-button"></i></p>
                    <p>点击上传支付宝收款码</p>
                    <p style="font-size:12px">支持 JPG/PNG/GIF/WEBP，不超过2MB</p>
                </div>
            </div>
            <input type="file" id="alipayFile" accept="image/*" style="display:none" onchange="uploadQRCode('alipay', this)">
        </div>
    </div>
</div>
</div>

<script>
loadQRCodes();

function loadQRCodes(){
    fetch('../api.php?action=admin_get_settings')
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0) return;
        if(res.data.wechat_qrcode){
            document.getElementById('wechatPreview').innerHTML = '<img src="../' + res.data.wechat_qrcode + '" alt="微信收款码"><p style="color:var(--success);margin-top:8px">已上传（点击更换）</p>';
        }
        if(res.data.alipay_qrcode){
            document.getElementById('alipayPreview').innerHTML = '<img src="../' + res.data.alipay_qrcode + '" alt="支付宝收款码"><p style="color:var(--success);margin-top:8px">已上传（点击更换）</p>';
        }
    });
}

function uploadQRCode(type, input){
    var file = input.files[0];
    if(!file) return;
    var formData = new FormData();
    formData.append('qrcode', file);
    formData.append('type', type);

    var btn = type === 'wechat' ? 'wechatUpload' : 'alipayUpload';
    document.getElementById(btn).style.opacity = '0.5';

    fetch('../api.php?action=admin_upload_qrcode', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(function(res){
        document.getElementById(btn).style.opacity = '1';
        if(res.code === 0){
            showToast('上传成功','success');
            loadQRCodes();
        } else {
            showToast(res.msg,'error');
        }
        input.value = '';
    }).catch(function(){
        document.getElementById(btn).style.opacity = '1';
        showToast('上传失败','error');
        input.value = '';
    });
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
