<?php
require_once __DIR__ . '/config.php';
initDB();

$db = getDB();
$action = $_GET['action'] ?? '';

try {
switch ($action) {
    case 'get_content':
        $type = $_GET['type'] ?? '';
        if (!in_array($type, ['notice', 'recharge'])) {
            jsonOutput(['code' => 1, 'msg' => '无效的内容类型'], 400);
        }
        $key = $type === 'notice' ? 'notice_content' : 'recharge_content';
        $title = $type === 'notice' ? getSetting('marquee_1_text') : getSetting('marquee_2_text');
        $content = getSetting($key);
        jsonOutput(['code' => 0, 'data' => ['title' => $title ?: ($type === 'notice' ? '商品须知' : '充值流程'), 'content' => $content]]);
        break;

    case 'send_code':
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOutput(['code' => 1, 'msg' => '请输入有效的邮箱地址'], 400);
        }
        if (empty(getSetting('smtp_host'))) {
            jsonOutput(['code' => 1, 'msg' => '邮件服务未配置，请联系管理员'], 400);
        }
        if (sendVerificationCode($email)) {
            jsonOutput(['code' => 0, 'msg' => '验证码已发送']);
        } else {
            jsonOutput(['code' => 1, 'msg' => '验证码发送失败，请检查邮箱或联系管理员'], 500);
        }
        break;

    case 'get_qrcode':
        $type = $_GET['type'] ?? '';
        if (!in_array($type, ['wechat', 'alipay'])) {
            jsonOutput(['code' => 1, 'msg' => '无效的支付方式'], 400);
        }
        $key = $type === 'wechat' ? 'wechat_qrcode' : 'alipay_qrcode';
        $url = getSetting($key);
        if (empty($url)) {
            jsonOutput(['code' => 0, 'data' => ['url' => '']]);
        } else {
            jsonOutput(['code' => 0, 'data' => ['url' => $url]]);
        }
        break;

    case 'verify_code':
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $code = trim($input['code'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOutput(['code' => 1, 'msg' => '请输入有效的邮箱'], 400);
        if (empty($code)) jsonOutput(['code' => 1, 'msg' => '请输入验证码'], 400);
        if (verifyCode($email, $code)) {
            jsonOutput(['code' => 0, 'msg' => '验证成功']);
        } else {
            jsonOutput(['code' => 1, 'msg' => '验证码错误或已过期'], 400);
        }
        break;

    case 'create_order':
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = intval($input['product_id'] ?? 0);
        $email = trim($input['email'] ?? '');
        $quantity = intval($input['quantity'] ?? 1);
        $paymentMethod = trim($input['payment_method'] ?? '');

        if ($productId <= 0) jsonOutput(['code' => 1, 'msg' => '无效的商品'], 400);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOutput(['code' => 1, 'msg' => '请输入有效的邮箱'], 400);
        if (!in_array($paymentMethod, ['wechat', 'alipay'])) jsonOutput(['code' => 1, 'msg' => '请选择支付方式'], 400);
        if ($quantity < 1) jsonOutput(['code' => 1, 'msg' => '数量不能小于1'], 400);

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) jsonOutput(['code' => 1, 'msg' => '商品不存在或已下架'], 404);

        $stock = getProductStock($productId);
        if ($stock < $quantity) jsonOutput(['code' => 1, 'msg' => '库存不足，当前库存' . $stock], 400);

        $orderNo = generateOrderNo();
        $totalPrice = $product['price'] * $quantity;

        $stmt = $db->prepare("INSERT INTO orders (order_no, product_id, email, quantity, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
        $stmt->execute([$orderNo, $productId, $email, $quantity, $totalPrice, $paymentMethod]);

        $paymentName = $paymentMethod === 'wechat' ? '微信支付' : '支付宝';
        $orderBody = "<h2>下单成功通知</h2>";
        $orderBody .= "<p>您已成功提交订单！</p>";
        $orderBody .= "<table style='border-collapse:collapse;width:100%;max-width:400px'>";
        $orderBody .= "<tr><td style='padding:8px;border:1px solid #eee;font-weight:bold'>订单号</td><td style='padding:8px;border:1px solid #eee'>" . $orderNo . "</td></tr>";
        $orderBody .= "<tr><td style='padding:8px;border:1px solid #eee;font-weight:bold'>商品</td><td style='padding:8px;border:1px solid #eee'>" . htmlspecialchars($product['title']) . "</td></tr>";
        $orderBody .= "<tr><td style='padding:8px;border:1px solid #eee;font-weight:bold'>数量</td><td style='padding:8px;border:1px solid #eee'>" . $quantity . " 份</td></tr>";
        $orderBody .= "<tr><td style='padding:8px;border:1px solid #eee;font-weight:bold'>金额</td><td style='padding:8px;border:1px solid #eee;color:#e74c3c;font-weight:bold'>¥" . number_format($totalPrice, 2) . "</td></tr>";
        $orderBody .= "<tr><td style='padding:8px;border:1px solid #eee;font-weight:bold'>支付方式</td><td style='padding:8px;border:1px solid #eee'>" . $paymentName . "</td></tr>";
        $orderBody .= "</table>";
        $orderBody .= "<p style='margin-top:16px;color:#666'>管理员确认付款后将尽快发货，届时卡密将发送至本邮箱。</p>";
        $orderBody .= "<p style='color:#999;font-size:12px'>您可使用订单号 <strong>" . $orderNo . "</strong> 在订单查询页面查询进度。</p>";
        sendMail($email, "下单成功 - 订单号 $orderNo", $orderBody);

        jsonOutput(['code' => 0, 'msg' => '订单创建成功', 'data' => ['order_no' => $orderNo]]);
        break;

    case 'admin_login':
        $input = json_decode(file_get_contents('php://input'), true);
        $password = $input['password'] ?? '';
        $hash = getSetting('admin_password');
        if (password_verify($password, $hash)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            jsonOutput(['code' => 0, 'msg' => '登录成功']);
        } else {
            jsonOutput(['code' => 1, 'msg' => '密码错误'], 401);
        }
        break;

    case 'admin_change_password':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $oldPass = $input['old_password'] ?? '';
        $newPass = $input['new_password'] ?? '';
        if (empty($newPass) || strlen($newPass) < 6) jsonOutput(['code' => 1, 'msg' => '新密码至少6位'], 400);
        $hash = getSetting('admin_password');
        if (!password_verify($oldPass, $hash)) jsonOutput(['code' => 1, 'msg' => '原密码错误'], 400);
        setSetting('admin_password', password_hash($newPass, PASSWORD_DEFAULT));
        jsonOutput(['code' => 0, 'msg' => '密码修改成功']);
        break;

    case 'admin_get_stats':
        requireLogin();
        $total = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $pending = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();
        $shipped = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
        $rejected = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'rejected'")->fetchColumn();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total_price),0) as total FROM orders WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $chartData[] = [
                'date' => date('m/d', strtotime($date)),
                'count' => intval($row['cnt']),
                'amount' => floatval($row['total'])
            ];
        }
        jsonOutput(['code' => 0, 'data' => ['total' => $total, 'pending' => $pending, 'shipped' => $shipped, 'rejected' => $rejected, 'chart' => $chartData]]);
        break;

    case 'admin_get_products':
        requireLogin();
        $page = max(1, intval($_GET['page'] ?? 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $total = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

        $products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT $pageSize OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$p) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$p['id']]);
            $p['stock'] = intval($stmt->fetchColumn());
            $stmt = $db->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ?");
            $stmt->execute([$p['id']]);
            $p['total_cards'] = intval($stmt->fetchColumn());
            $stmt = $db->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 1");
            $stmt->execute([$p['id']]);
            $p['real_sold'] = intval($stmt->fetchColumn());
        }
        unset($p);

        jsonOutput(['code' => 0, 'data' => ['list' => $products, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]]);
        break;

    case 'admin_save_product':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $price = floatval($input['price'] ?? 0);
        $categoryId = intval($input['category_id'] ?? 0);
        $status = intval($input['status'] ?? 1);
        $fakeSold = intval($input['fake_sold'] ?? 0);
        $cards = trim($input['cards'] ?? '');

        if (empty($title)) jsonOutput(['code' => 1, 'msg' => '请输入商品标题'], 400);
        if ($price <= 0) jsonOutput(['code' => 1, 'msg' => '价格必须大于0'], 400);

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE products SET title=?, description=?, price=?, category_id=?, status=?, fake_sold=? WHERE id=?");
            $stmt->execute([$title, $description, $price, $categoryId, $status, $fakeSold, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO products (title, description, price, category_id, status, fake_sold) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $price, $categoryId, $status, $fakeSold]);
            $id = $db->lastInsertId();
        }

        if (!empty($cards)) {
            $cardList = array_filter(array_map('trim', explode("\n", $cards)));
            $stmt = $db->prepare("INSERT INTO cards (product_id, content) VALUES (?, ?)");
            foreach ($cardList as $card) {
                if ($card !== '') $stmt->execute([$id, $card]);
            }
        }

        jsonOutput(['code' => 0, 'msg' => '保存成功']);
        break;

    case 'admin_add_cards':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = intval($input['product_id'] ?? 0);
        $cards = trim($input['cards'] ?? '');
        if ($productId <= 0) jsonOutput(['code' => 1, 'msg' => '无效的商品ID'], 400);
        if (empty($cards)) jsonOutput(['code' => 1, 'msg' => '请输入卡密'], 400);
        $cardList = array_filter(array_map('trim', explode("\n", $cards)));
        $stmt = $db->prepare("INSERT INTO cards (product_id, content) VALUES (?, ?)");
        $count = 0;
        foreach ($cardList as $card) {
            if ($card !== '') { $stmt->execute([$productId, $card]); $count++; }
        }
        jsonOutput(['code' => 0, 'msg' => "成功添加 {$count} 个卡密"]);
        break;

    case 'admin_delete_product':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonOutput(['code' => 1, 'msg' => '无效的商品ID'], 400);
        $db->prepare("DELETE FROM cards WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        jsonOutput(['code' => 0, 'msg' => '删除成功']);
        break;

    case 'admin_get_product_cards':
        requireLogin();
        $productId = intval($_GET['product_id'] ?? 0);
        if ($productId <= 0) jsonOutput(['code' => 1, 'msg' => '无效的商品ID'], 400);
        $stmt = $db->prepare("SELECT * FROM cards WHERE product_id = ? ORDER BY used ASC, created_at DESC");
        $stmt->execute([$productId]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonOutput(['code' => 0, 'data' => $cards]);
        break;

    case 'admin_delete_card':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonOutput(['code' => 1, 'msg' => '无效的卡密ID'], 400);
        $db->prepare("DELETE FROM cards WHERE id = ? AND used = 0")->execute([$id]);
        jsonOutput(['code' => 0, 'msg' => '删除成功']);
        break;

    case 'admin_get_orders':
        requireLogin();
        $page = max(1, intval($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? '';
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $where = '';
        $params = [];
        if (in_array($status, ['pending', 'paid', 'shipped', 'rejected'])) {
            $where = " WHERE o.status = ?";
            $params[] = $status;
        }

        $total = $db->prepare("SELECT COUNT(*) FROM orders o" . $where);
        $total->execute($params);
        $totalCount = $total->fetchColumn();

        $sql = "SELECT o.*, p.title as product_title FROM orders o LEFT JOIN products p ON o.product_id = p.id $where ORDER BY o.created_at DESC LIMIT $pageSize OFFSET $offset";
        if (!empty($params)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            $stmt = $db->query($sql);
        }
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonOutput(['code' => 0, 'data' => ['list' => $orders, 'total' => $totalCount, 'page' => $page, 'pageSize' => $pageSize]]);
        break;

    case 'admin_ship_order':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = intval($input['order_id'] ?? 0);
        if ($orderId <= 0) jsonOutput(['code' => 1, 'msg' => '无效的订单ID'], 400);

        $stmt = $db->prepare("SELECT o.*, p.title as product_title FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.id = ? AND o.status = 'paid'");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) jsonOutput(['code' => 1, 'msg' => '订单不存在或状态不符'], 400);

        $stock = getProductStock($order['product_id']);
        if ($stock < $order['quantity']) jsonOutput(['code' => 1, 'msg' => '库存不足，无法发货。当前库存：' . $stock], 400);

        $limitQty = intval($order['quantity']);
        $stmt = $db->prepare("SELECT * FROM cards WHERE product_id = ? AND used = 0 LIMIT $limitQty");
        $stmt->execute([$order['product_id']]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($cards) < $order['quantity']) jsonOutput(['code' => 1, 'msg' => '可用卡密不足'], 400);

        $cardContents = [];
        $db->beginTransaction();
        try {
            foreach ($cards as $card) {
                $db->prepare("UPDATE cards SET used = 1, order_id = ? WHERE id = ?")->execute([$orderId, $card['id']]);
                $cardContents[] = $card['content'];
            }
            $cardStr = implode("\n", $cardContents);
            $db->prepare("UPDATE orders SET status = 'shipped', card_content = ?, updated_at = NOW() WHERE id = ?")->execute([$cardStr, $orderId]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonOutput(['code' => 1, 'msg' => '发货失败：' . $e->getMessage()], 500);
        }

        sendOrderNotification($order['email'], $order['order_no'], $cardStr, $order['product_title']);
        jsonOutput(['code' => 0, 'msg' => '发货成功，卡密已发送至客户邮箱']);
        break;

    case 'admin_reject_order':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = intval($input['order_id'] ?? 0);
        if ($orderId <= 0) jsonOutput(['code' => 1, 'msg' => '无效的订单ID'], 400);
        $db->prepare("UPDATE orders SET status = 'rejected', updated_at = NOW() WHERE id = ? AND status = 'paid'")->execute([$orderId]);
        jsonOutput(['code' => 0, 'msg' => '已拒绝该订单']);
        break;

    case 'admin_upload_qrcode':
        requireLogin();
        $type = $_POST['type'] ?? '';
        if (!in_array($type, ['wechat', 'alipay'])) jsonOutput(['code' => 1, 'msg' => '无效的类型'], 400);

        if (!isset($_FILES['qrcode']) || $_FILES['qrcode']['error'] !== UPLOAD_ERR_OK) {
            jsonOutput(['code' => 1, 'msg' => '请选择图片文件'], 400);
        }

        $file = $_FILES['qrcode'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) jsonOutput(['code' => 1, 'msg' => '仅支持 JPG/PNG/GIF/WEBP 格式'], 400);
        if ($file['size'] > 2 * 1024 * 1024) jsonOutput(['code' => 1, 'msg' => '图片不能超过2MB'], 400);

        $key = $type === 'wechat' ? 'wechat_qrcode' : 'alipay_qrcode';
        $oldFile = getSetting($key);
        if ($oldFile && file_exists(UPLOAD_DIR . basename($oldFile))) {
            @unlink(UPLOAD_DIR . basename($oldFile));
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);

        $url = 'uploads/' . $filename;
        setSetting($key, $url);
        jsonOutput(['code' => 0, 'msg' => '上传成功', 'data' => ['url' => $url]]);
        break;

    case 'admin_add_category':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        if (empty($name)) jsonOutput(['code' => 1, 'msg' => '分类名称不能为空'], 400);
        $maxOrder = $db->query("SELECT COALESCE(MAX(sort_order),-1) + 1 FROM categories")->fetchColumn();
        $stmt = $db->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $maxOrder]);
        jsonOutput(['code' => 0, 'msg' => '分类添加成功', 'data' => ['id' => $db->lastInsertId(), 'name' => $name]]);
        break;

    case 'admin_delete_category':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonOutput(['code' => 1, 'msg' => '无效的分类ID'], 400);
        $db->prepare("UPDATE products SET category_id = 0 WHERE category_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        jsonOutput(['code' => 0, 'msg' => '分类删除成功']);
        break;

    case 'admin_save_categories':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $categories = $input['categories'] ?? [];
        $db->exec("DELETE FROM categories");
        $stmt = $db->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
        foreach ($categories as $i => $cat) {
            $name = trim($cat['name'] ?? '');
            if ($name !== '') $stmt->execute([$name, $i]);
        }
        jsonOutput(['code' => 0, 'msg' => '分类保存成功']);
        break;

    case 'admin_get_categories':
        requireLogin();
        $cats = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
        jsonOutput(['code' => 0, 'data' => $cats]);
        break;

    case 'admin_save_settings':
        requireLogin();
        $input = json_decode(file_get_contents('php://input'), true);
        $contentFields = ['notice_content', 'recharge_content'];
        foreach ($contentFields as $cf) {
            if (isset($input[$cf])) {
                $oldContent = getSetting($cf);
                $newContent = $input[$cf];
                preg_match_all('/src=["\']([^"\']+)["\']/i', $oldContent, $oldImgs);
                preg_match_all('/src=["\']([^"\']+)["\']/i', $newContent, $newImgs);
                $oldImgList = $oldImgs[1] ?? [];
                $newImgList = $newImgs[1] ?? [];
                foreach ($oldImgList as $img) {
                    if (strpos($img, 'uploads/content_') === 0 && !in_array($img, $newImgList)) {
                        $filePath = UPLOAD_DIR . basename($img);
                        if (file_exists($filePath)) @unlink($filePath);
                    }
                }
            }
        }
        $fields = ['site_name', 'announcement', 'faq', 'card_usage', 'refund_policy', 'service_wechat', 'service_qq', 'marquee_1_text', 'marquee_1_color', 'marquee_2_text', 'marquee_2_color', 'notice_content', 'recharge_content', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_ssl'];
        foreach ($fields as $f) {
            if (isset($input[$f])) setSetting($f, $input[$f]);
        }
        jsonOutput(['code' => 0, 'msg' => '设置保存成功']);
        break;

    case 'admin_get_settings':
        requireLogin();
        $data = [];
        $fields = ['site_name', 'site_logo', 'announcement', 'faq', 'card_usage', 'refund_policy', 'service_wechat', 'service_qq', 'service_wechat_qrcode', 'service_qq_qrcode', 'marquee_1_text', 'marquee_1_color', 'marquee_2_text', 'marquee_2_color', 'notice_content', 'recharge_content', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_ssl', 'wechat_qrcode', 'alipay_qrcode'];
        foreach ($fields as $f) $data[$f] = getSetting($f);
        jsonOutput(['code' => 0, 'data' => $data]);
        break;

    case 'admin_upload_logo':
        requireLogin();
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            jsonOutput(['code' => 1, 'msg' => '请选择图片文件'], 400);
        }
        $file = $_FILES['logo'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'], $allowed)) jsonOutput(['code' => 1, 'msg' => '仅支持 JPG/PNG/GIF/WEBP/SVG 格式'], 400);
        if ($file['size'] > 2 * 1024 * 1024) jsonOutput(['code' => 1, 'msg' => '图片不能超过2MB'], 400);
        $oldLogo = getSetting('site_logo');
        if ($oldLogo && file_exists(UPLOAD_DIR . basename($oldLogo))) {
            @unlink(UPLOAD_DIR . basename($oldLogo));
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
        $url = 'uploads/' . $filename;
        setSetting('site_logo', $url);
        jsonOutput(['code' => 0, 'msg' => 'LOGO上传成功', 'data' => ['url' => $url]]);
        break;

    case 'admin_upload_content_image':
        requireLogin();
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonOutput(['code' => 1, 'msg' => '请选择图片文件'], 400);
        }
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) jsonOutput(['code' => 1, 'msg' => '仅支持 JPG/PNG/GIF/WEBP 格式'], 400);
        if ($file['size'] > 5 * 1024 * 1024) jsonOutput(['code' => 1, 'msg' => '图片不能超过5MB'], 400);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'content_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
        $url = 'uploads/' . $filename;
        jsonOutput(['code' => 0, 'msg' => '上传成功', 'data' => ['url' => $url]]);
        break;

    case 'admin_upload_service_wechat':
        requireLogin();
        if (!isset($_FILES['qrcode']) || $_FILES['qrcode']['error'] !== UPLOAD_ERR_OK) {
            jsonOutput(['code' => 1, 'msg' => '请选择图片文件'], 400);
        }
        $file = $_FILES['qrcode'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) jsonOutput(['code' => 1, 'msg' => '仅支持 JPG/PNG/GIF/WEBP 格式'], 400);
        if ($file['size'] > 2 * 1024 * 1024) jsonOutput(['code' => 1, 'msg' => '图片不能超过2MB'], 400);
        $oldFile = getSetting('service_wechat_qrcode');
        if ($oldFile && file_exists(UPLOAD_DIR . basename($oldFile))) {
            @unlink(UPLOAD_DIR . basename($oldFile));
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_wechat_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
        $url = 'uploads/' . $filename;
        setSetting('service_wechat_qrcode', $url);
        jsonOutput(['code' => 0, 'msg' => '微信客服二维码上传成功', 'data' => ['url' => $url]]);
        break;

    case 'admin_upload_service_qq':
        requireLogin();
        if (!isset($_FILES['qrcode']) || $_FILES['qrcode']['error'] !== UPLOAD_ERR_OK) {
            jsonOutput(['code' => 1, 'msg' => '请选择图片文件'], 400);
        }
        $file = $_FILES['qrcode'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) jsonOutput(['code' => 1, 'msg' => '仅支持 JPG/PNG/GIF/WEBP 格式'], 400);
        if ($file['size'] > 2 * 1024 * 1024) jsonOutput(['code' => 1, 'msg' => '图片不能超过2MB'], 400);
        $oldFile = getSetting('service_qq_qrcode');
        if ($oldFile && file_exists(UPLOAD_DIR . basename($oldFile))) {
            @unlink(UPLOAD_DIR . basename($oldFile));
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_qq_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
        $url = 'uploads/' . $filename;
        setSetting('service_qq_qrcode', $url);
        jsonOutput(['code' => 0, 'msg' => 'QQ客服二维码上传成功', 'data' => ['url' => $url]]);
        break;

    default:
        jsonOutput(['code' => 1, 'msg' => '未知操作'], 400);
}
} catch (Exception $e) {
    jsonOutput(['code' => 1, 'msg' => '服务器错误：' . $e->getMessage()], 500);
}
