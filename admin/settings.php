<?php
require_once __DIR__ . '/../config.php';
initDB();
requireLogin();
$siteName = getSiteName();
$siteLogo = getSiteLogo();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>系统设置 - <?= htmlspecialchars($siteName) ?> 后台</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="admin-layout">
<?php include 'sidebar.php'; ?>
<div class="admin-content">
    <div class="page-header">
        <h1>系统设置</h1>
    </div>

    <div class="settings-tabs">
        <a class="settings-tab active" onclick="switchTab('site')" id="tabSite"><i class="fas fa-globe"></i> 站点</a>
        <a class="settings-tab" onclick="switchTab('marquee')" id="tabMarquee"><i class="fas fa-text-width"></i> 滚动字幕</a>
        <a class="settings-tab" onclick="switchTab('content')" id="tabContent"><i class="fas fa-file-lines"></i> 内容</a>
        <a class="settings-tab" onclick="switchTab('service')" id="tabService"><i class="fas fa-headset"></i> 客服</a>
        <a class="settings-tab" onclick="switchTab('email')" id="tabEmail"><i class="fas fa-envelope"></i> 邮件</a>
    </div>

    <div class="settings-panel show" id="panelSite">
        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fas fa-globe" style="color:var(--primary)"></i> 站点设置</h3>
            <div class="form-group">
                <label>站点名称</label>
                <input type="text" id="sSiteName" placeholder="请输入站点名称">
            </div>
            <div class="form-group">
                <label>站点 LOGO</label>
                <div class="qrcode-upload" id="logoUpload" onclick="document.getElementById('logoFile').click()">
                    <div id="logoPreview">
                        <p style="font-size:30px;margin-bottom:8px;color:var(--primary)"><i class="fas fa-image"></i></p>
                        <p>点击上传站点 LOGO</p>
                        <p style="font-size:12px">支持 JPG/PNG/GIF/WEBP/SVG，不超过2MB</p>
                    </div>
                </div>
                <input type="file" id="logoFile" accept="image/*" style="display:none" onchange="uploadLogo(this)">
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['site_name'])">保存站点设置</button>
        </div>
    </div>

    <div class="settings-panel" id="panelMarquee">
        <div class="detail-card" style="margin-bottom:24px">
            <h3 style="margin-bottom:16px"><i class="fas fa-bell" style="color:#e74c3c"></i> 滚动字幕 1（商品须知）</h3>
            <div class="form-group">
                <label>滚动文字内容</label>
                <input type="text" id="sMarquee1Text" placeholder="如：商品须知">
            </div>
            <div class="form-group">
                <label>文字颜色</label>
                <div style="display:flex;align-items:center;gap:12px">
                    <input type="color" id="sMarquee1Color" value="#e74c3c" style="width:50px;height:36px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
                    <span id="marquee1ColorLabel" style="font-size:13px;color:var(--text-light)">#e74c3c</span>
                </div>
            </div>
            <div class="form-group">
                <label>详情页内容（支持图文混合）</label>
                <div class="rich-editor">
                    <div class="editor-toolbar">
                        <button type="button" onclick="editorCmd('bold')" title="加粗"><i class="fas fa-bold"></i></button>
                        <button type="button" onclick="editorCmd('italic')" title="斜体"><i class="fas fa-italic"></i></button>
                        <button type="button" onclick="editorCmd('underline')" title="下划线"><i class="fas fa-underline"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="editorCmd('formatBlock','<h3>')" title="标题"><i class="fas fa-heading"></i></button>
                        <button type="button" onclick="editorCmd('formatBlock','<p>')" title="正文"><i class="fas fa-paragraph"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="editorCmd('insertUnorderedList')" title="无序列表"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="editorCmd('insertOrderedList')" title="有序列表"><i class="fas fa-list-ol"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="uploadContentImage('editorNotice')" title="插入图片"><i class="fas fa-image"></i></button>
                        <input type="file" id="editorNoticeImgFile" accept="image/*" style="display:none" onchange="doUploadContentImage(this,'editorNotice')">
                    </div>
                    <div class="editor-body" id="editorNotice" contenteditable="true"></div>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveMarqueeSettings(1)">保存滚动字幕1设置</button>
        </div>

        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fas fa-rotate" style="color:#4a6cf7"></i> 滚动字幕 2（充值流程）</h3>
            <div class="form-group">
                <label>滚动文字内容</label>
                <input type="text" id="sMarquee2Text" placeholder="如：充值流程">
            </div>
            <div class="form-group">
                <label>文字颜色</label>
                <div style="display:flex;align-items:center;gap:12px">
                    <input type="color" id="sMarquee2Color" value="#4a6cf7" style="width:50px;height:36px;border:1px solid var(--border);border-radius:6px;cursor:pointer;padding:2px">
                    <span id="marquee2ColorLabel" style="font-size:13px;color:var(--text-light)">#4a6cf7</span>
                </div>
            </div>
            <div class="form-group">
                <label>详情页内容（支持图文混合）</label>
                <div class="rich-editor">
                    <div class="editor-toolbar">
                        <button type="button" onclick="editorCmd('bold','','editorRecharge')" title="加粗"><i class="fas fa-bold"></i></button>
                        <button type="button" onclick="editorCmd('italic','','editorRecharge')" title="斜体"><i class="fas fa-italic"></i></button>
                        <button type="button" onclick="editorCmd('underline','','editorRecharge')" title="下划线"><i class="fas fa-underline"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="editorCmd('formatBlock','<h3>','editorRecharge')" title="标题"><i class="fas fa-heading"></i></button>
                        <button type="button" onclick="editorCmd('formatBlock','<p>','editorRecharge')" title="正文"><i class="fas fa-paragraph"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="editorCmd('insertUnorderedList','','editorRecharge')" title="无序列表"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="editorCmd('insertOrderedList','','editorRecharge')" title="有序列表"><i class="fas fa-list-ol"></i></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" onclick="uploadContentImage('editorRecharge')" title="插入图片"><i class="fas fa-image"></i></button>
                        <input type="file" id="editorRechargeImgFile" accept="image/*" style="display:none" onchange="doUploadContentImage(this,'editorRecharge')">
                    </div>
                    <div class="editor-body" id="editorRecharge" contenteditable="true"></div>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveMarqueeSettings(2)">保存滚动字幕2设置</button>
        </div>
    </div>

    <div class="settings-panel" id="panelContent">
        <div class="detail-card" style="margin-bottom:24px">
            <h3 style="margin-bottom:16px"><i class="fas fa-bullhorn" style="color:var(--warning)"></i> 公告设置</h3>
            <div class="form-group">
                <label>弹窗公告内容</label>
                <textarea id="sAnnouncement" rows="4" placeholder="留空则不显示公告"></textarea>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['announcement'])">保存公告</button>
        </div>

        <div class="detail-card" style="margin-bottom:24px">
            <h3 style="margin-bottom:16px"><i class="fas fa-circle-question" style="color:var(--primary)"></i> 常见问题</h3>
            <div class="form-group">
                <label>常见问题说明</label>
                <textarea id="sFaq" rows="6" placeholder="商品详情页底部显示的常见问题"></textarea>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['faq'])">保存常见问题</button>
        </div>

        <div class="detail-card" style="margin-bottom:24px">
            <h3 style="margin-bottom:16px"><i class="fas fa-key" style="color:var(--warning)"></i> 卡密使用方法</h3>
            <div class="form-group">
                <label>卡密使用方法说明</label>
                <textarea id="sCardUsage" rows="4" placeholder="用户收到卡密后如何使用的说明"></textarea>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['card_usage'])">保存卡密使用方法</button>
        </div>

        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fas fa-rotate-left" style="color:var(--danger)"></i> 退款方式</h3>
            <div class="form-group">
                <label>退款说明</label>
                <textarea id="sRefundPolicy" rows="3" placeholder="商品详情页底部显示的退款说明"></textarea>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['refund_policy'])">保存退款说明</button>
        </div>
    </div>

    <div class="settings-panel" id="panelService">
        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fas fa-headset" style="color:var(--primary)"></i> 客服设置</h3>
            <div class="form-group">
                <label><i class="fab fa-weixin" style="color:#07c160"></i> 微信号</label>
                <input type="text" id="sServiceWechat" placeholder="请输入客服微信号">
            </div>
            <div class="form-group">
                <label>微信好友二维码</label>
                <div class="qrcode-upload" id="serviceWechatQrUpload" onclick="document.getElementById('serviceWechatQrFile').click()">
                    <div id="serviceWechatQrPreview">
                        <p style="font-size:30px;margin-bottom:8px;color:#07c160"><i class="fab fa-weixin"></i></p>
                        <p>点击上传微信好友二维码</p>
                        <p style="font-size:12px">支持 JPG/PNG/GIF/WEBP，不超过2MB</p>
                    </div>
                </div>
                <input type="file" id="serviceWechatQrFile" accept="image/*" style="display:none" onchange="uploadServiceWechatQr(this)">
            </div>
            <div class="form-group">
                <label><i class="fab fa-qq" style="color:#12b7f5"></i> QQ号</label>
                <input type="text" id="sServiceQq" placeholder="请输入客服QQ号">
            </div>
            <div class="form-group">
                <label>QQ好友二维码</label>
                <div class="qrcode-upload" id="serviceQqQrUpload" onclick="document.getElementById('serviceQqQrFile').click()">
                    <div id="serviceQqQrPreview">
                        <p style="font-size:30px;margin-bottom:8px;color:#12b7f5"><i class="fab fa-qq"></i></p>
                        <p>点击上传QQ好友二维码</p>
                        <p style="font-size:12px">支持 JPG/PNG/GIF/WEBP，不超过2MB</p>
                    </div>
                </div>
                <input type="file" id="serviceQqQrFile" accept="image/*" style="display:none" onchange="uploadServiceQqQr(this)">
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['service_wechat','service_qq'])">保存客服设置</button>
        </div>
    </div>

    <div class="settings-panel" id="panelEmail">
        <div class="detail-card">
            <h3 style="margin-bottom:16px"><i class="fas fa-envelope" style="color:var(--success)"></i> 邮件服务 (SMTP)</h3>
            <p style="font-size:12px;color:var(--text-light);margin-bottom:12px">配置后支持邮箱验证码和订单通知，不配置则跳过验证码</p>
            <div class="form-group">
                <label>SMTP 服务器</label>
                <input type="text" id="sSmtpHost" placeholder="如：smtp.qq.com">
            </div>
            <div class="form-group">
                <label>SMTP 端口</label>
                <input type="text" id="sSmtpPort" placeholder="465" value="465">
            </div>
            <div class="form-group">
                <label>SMTP 用户名</label>
                <input type="text" id="sSmtpUser" placeholder="邮箱地址">
            </div>
            <div class="form-group">
                <label>SMTP 密码</label>
                <input type="password" id="sSmtpPass" placeholder="授权码">
            </div>
            <div class="form-group">
                <label>发件人地址</label>
                <input type="text" id="sSmtpFrom" placeholder="与用户名相同的邮箱地址">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="sSmtpSsl" value="1" checked>
                    启用 SSL/TLS
                </label>
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveSettings(['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_ssl'])">保存邮件设置</button>
        </div>
    </div>
</div>
</div>

<script>
loadSettings();

document.getElementById('sMarquee1Color').addEventListener('input',function(){
    document.getElementById('marquee1ColorLabel').textContent = this.value;
});
document.getElementById('sMarquee2Color').addEventListener('input',function(){
    document.getElementById('marquee2ColorLabel').textContent = this.value;
});

function switchTab(name){
    document.querySelectorAll('.settings-tab').forEach(function(t){t.classList.remove('active')});
    document.querySelectorAll('.settings-panel').forEach(function(p){p.classList.remove('show')});
    document.getElementById('tab'+name.charAt(0).toUpperCase()+name.slice(1)).classList.add('active');
    document.getElementById('panel'+name.charAt(0).toUpperCase()+name.slice(1)).classList.add('show');
}

function loadSettings(){
    fetch('../api.php?action=admin_get_settings')
    .then(r => r.json())
    .then(function(res){
        if(res.code !== 0) return;
        document.getElementById('sSiteName').value = res.data.site_name || '';
        document.getElementById('sAnnouncement').value = res.data.announcement || '';
        document.getElementById('sFaq').value = res.data.faq || '';
        document.getElementById('sCardUsage').value = res.data.card_usage || '';
        document.getElementById('sRefundPolicy').value = res.data.refund_policy || '';
        document.getElementById('sServiceWechat').value = res.data.service_wechat || '';
        document.getElementById('sServiceQq').value = res.data.service_qq || '';
        document.getElementById('sSmtpHost').value = res.data.smtp_host || '';
        document.getElementById('sSmtpPort').value = res.data.smtp_port || '465';
        document.getElementById('sSmtpUser').value = res.data.smtp_user || '';
        document.getElementById('sSmtpPass').value = res.data.smtp_pass || '';
        document.getElementById('sSmtpFrom').value = res.data.smtp_from || '';
        document.getElementById('sSmtpSsl').checked = res.data.smtp_ssl === '1';
        document.getElementById('sMarquee1Text').value = res.data.marquee_1_text || '';
        document.getElementById('sMarquee1Color').value = res.data.marquee_1_color || '#e74c3c';
        document.getElementById('marquee1ColorLabel').textContent = res.data.marquee_1_color || '#e74c3c';
        document.getElementById('sMarquee2Text').value = res.data.marquee_2_text || '';
        document.getElementById('sMarquee2Color').value = res.data.marquee_2_color || '#4a6cf7';
        document.getElementById('marquee2ColorLabel').textContent = res.data.marquee_2_color || '#4a6cf7';
        document.getElementById('editorNotice').innerHTML = fixImgSrc(res.data.notice_content || '', true);
        document.getElementById('editorRecharge').innerHTML = fixImgSrc(res.data.recharge_content || '', true);
        if(res.data.site_logo){
            document.getElementById('logoPreview').innerHTML = '<img src="../' + res.data.site_logo + '" alt="LOGO" style="max-height:80px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        }
        if(res.data.service_wechat_qrcode){
            document.getElementById('serviceWechatQrPreview').innerHTML = '<img src="../' + res.data.service_wechat_qrcode + '" alt="微信二维码" style="max-height:160px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        }
        if(res.data.service_qq_qrcode){
            document.getElementById('serviceQqQrPreview').innerHTML = '<img src="../' + res.data.service_qq_qrcode + '" alt="QQ二维码" style="max-height:160px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        }
    });
}

function editorCmd(cmd, value, editorId){
    var id = editorId || 'editorNotice';
    document.getElementById(id).focus();
    document.execCommand(cmd, false, value || null);
}

function fixImgSrc(html, toAdmin){
    return html.replace(/src="([^"]*)"/g, function(match, src){
        if(toAdmin){
            if(src.indexOf('uploads/') === 0) return 'src="../' + src + '"';
        } else {
            if(src.indexOf('../uploads/') === 0) return 'src="' + src.substring(3) + '"';
        }
        return match;
    });
}

function uploadContentImage(editorId){
    var fileInputId = editorId + 'ImgFile';
    document.getElementById(fileInputId).click();
}

function doUploadContentImage(input, editorId){
    var file = input.files[0];
    if(!file) return;
    var formData = new FormData();
    formData.append('image', file);
    fetch('../api.php?action=admin_upload_content_image', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(function(res){
        if(res.code === 0){
            var editor = document.getElementById(editorId);
            editor.focus();
            var img = '<img src="../' + res.data.url + '" style="max-width:100%;height:auto;border-radius:6px;margin:8px 0" alt="">';
            document.execCommand('insertHTML', false, img);
            showToast('图片插入成功','success');
        } else {
            showToast(res.msg,'error');
        }
        input.value = '';
    }).catch(function(){
        showToast('图片上传失败','error');
        input.value = '';
    });
}

function saveMarqueeSettings(num){
    var data = {};
    if(num === 1){
        data.marquee_1_text = document.getElementById('sMarquee1Text').value;
        data.marquee_1_color = document.getElementById('sMarquee1Color').value;
        data.notice_content = fixImgSrc(document.getElementById('editorNotice').innerHTML, false);
    } else {
        data.marquee_2_text = document.getElementById('sMarquee2Text').value;
        data.marquee_2_color = document.getElementById('sMarquee2Color').value;
        data.recharge_content = fixImgSrc(document.getElementById('editorRecharge').innerHTML, false);
    }
    fetch('../api.php?action=admin_save_settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(function(res){
        if(res.code === 0) showToast('保存成功','success');
        else showToast(res.msg,'error');
    });
}

function uploadLogo(input){
    var file = input.files[0];
    if(!file) return;
    var formData = new FormData();
    formData.append('logo', file);
    document.getElementById('logoUpload').style.opacity = '0.5';
    fetch('../api.php?action=admin_upload_logo', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(function(res){
        document.getElementById('logoUpload').style.opacity = '1';
        if(res.code === 0){
            showToast('LOGO上传成功','success');
            document.getElementById('logoPreview').innerHTML = '<img src="../' + res.data.url + '" alt="LOGO" style="max-height:80px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        } else { showToast(res.msg,'error'); }
        input.value = '';
    }).catch(function(){
        document.getElementById('logoUpload').style.opacity = '1';
        showToast('上传失败','error');
        input.value = '';
    });
}

function uploadServiceWechatQr(input){
    var file = input.files[0];
    if(!file) return;
    var formData = new FormData();
    formData.append('qrcode', file);
    document.getElementById('serviceWechatQrUpload').style.opacity = '0.5';
    fetch('../api.php?action=admin_upload_service_wechat', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(function(res){
        document.getElementById('serviceWechatQrUpload').style.opacity = '1';
        if(res.code === 0){
            showToast('微信二维码上传成功','success');
            document.getElementById('serviceWechatQrPreview').innerHTML = '<img src="../' + res.data.url + '" alt="微信二维码" style="max-height:160px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        } else { showToast(res.msg,'error'); }
        input.value = '';
    }).catch(function(){
        document.getElementById('serviceWechatQrUpload').style.opacity = '1';
        showToast('上传失败','error');
        input.value = '';
    });
}

function uploadServiceQqQr(input){
    var file = input.files[0];
    if(!file) return;
    var formData = new FormData();
    formData.append('qrcode', file);
    document.getElementById('serviceQqQrUpload').style.opacity = '0.5';
    fetch('../api.php?action=admin_upload_service_qq', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(function(res){
        document.getElementById('serviceQqQrUpload').style.opacity = '1';
        if(res.code === 0){
            showToast('QQ二维码上传成功','success');
            document.getElementById('serviceQqQrPreview').innerHTML = '<img src="../' + res.data.url + '" alt="QQ二维码" style="max-height:160px"><p style="color:var(--success);margin-top:8px;font-size:13px">已上传（点击更换）</p>';
        } else { showToast(res.msg,'error'); }
        input.value = '';
    }).catch(function(){
        document.getElementById('serviceQqQrUpload').style.opacity = '1';
        showToast('上传失败','error');
        input.value = '';
    });
}

function saveSettings(fields){
    var data = {};
    fields.forEach(function(f){
        if(f === 'smtp_ssl'){
            data[f] = document.getElementById('sSmtpSsl').checked ? '1' : '0';
        } else {
            var map = {
                'site_name': 'sSiteName',
                'announcement': 'sAnnouncement',
                'faq': 'sFaq',
                'card_usage': 'sCardUsage',
                'refund_policy': 'sRefundPolicy',
                'service_wechat': 'sServiceWechat',
                'service_qq': 'sServiceQq',
                'smtp_host': 'sSmtpHost',
                'smtp_port': 'sSmtpPort',
                'smtp_user': 'sSmtpUser',
                'smtp_pass': 'sSmtpPass',
                'smtp_from': 'sSmtpFrom',
                'smtp_ssl': 'sSmtpSsl'
            };
            var el = document.getElementById(map[f]);
            if(el) data[f] = el.value;
        }
    });
    fetch('../api.php?action=admin_save_settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(function(res){
        if(res.code === 0) showToast('保存成功','success');
        else showToast(res.msg,'error');
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
