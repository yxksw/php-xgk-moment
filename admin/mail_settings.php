<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

include '../config.php';
include '../includes/functions.php';

$success = '';
$error = '';

// 获取当前设置
$settings = [
    'smtp_enabled' => getSetting($conn, 'smtp_enabled'),
    'smtp_host' => getSetting($conn, 'smtp_host'),
    'smtp_port' => getSetting($conn, 'smtp_port') ?: '587',
    'smtp_username' => getSetting($conn, 'smtp_username'),
    'smtp_password' => getSetting($conn, 'smtp_password'),
    'smtp_from_email' => getSetting($conn, 'smtp_from_email'),
    'smtp_from_name' => getSetting($conn, 'smtp_from_name'),
    'admin_email' => getSetting($conn, 'admin_email'),
    'notify_admin_on_comment' => getSetting($conn, 'notify_admin_on_comment'),
    'notify_user_on_reply' => getSetting($conn, 'notify_user_on_reply'),
];

// 保存设置
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $newSettings = [
            'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
            'smtp_username' => trim($_POST['smtp_username'] ?? ''),
            'smtp_password' => trim($_POST['smtp_password'] ?? ''),
            'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
            'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'notify_admin_on_comment' => isset($_POST['notify_admin_on_comment']) ? '1' : '0',
            'notify_user_on_reply' => isset($_POST['notify_user_on_reply']) ? '1' : '0',
        ];
        
        foreach ($newSettings as $name => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->bind_param("sss", $name, $value, $value);
            $stmt->execute();
        }
        
        $settings = $newSettings;
        $success = '设置已保存';
    }
    
    // 测试邮件发送
    if (isset($_POST['test_mail'])) {
        include '../includes/mail_functions.php';
        
        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的测试邮箱地址';
        } else {
            $mailSender = new MailSender($conn);
            $siteName = getSetting($conn, 'site_title');
            $result = $mailSender->send(
                $testEmail,
                '测试用户',
                "{$siteName} - 邮件测试",
                "<h2>邮件测试成功！</h2><p>如果您收到这封邮件，说明您的SMTP配置正确。</p><p>发送时间：" . date('Y-m-d H:i:s') . "</p>"
            );
            
            if ($result['success']) {
                $success = '测试邮件已发送，请查收';
            } else {
                $error = '测试邮件发送失败：' . $result['message'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件设置 - 朋友圈后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 20px 80px 20px;
        }
        .header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        .nav {
            margin-top: 15px;
        }
        .nav a {
            color: #666;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }
        .nav a:hover {
            color: #07c160;
        }
        .card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #07c160;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 12px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            cursor: pointer;
            font-weight: normal;
        }
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #07c160;
            color: #fff;
        }
        .btn-primary:hover {
            background: #06ad56;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e6f7ed;
            color: #07c160;
            border: 1px solid #c6ebd5;
        }
        .alert-error {
            background: #fff2f0;
            color: #ff4d4f;
            border: 1px solid #ffccc7;
        }
        .section-divider {
            margin: 30px 0;
            border: none;
            border-top: 1px solid #eee;
        }
        .test-mail-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .test-mail-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
        }

        /* 明暗模式切换按钮 */
        .theme-toggle {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #4a90e2;
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .theme-toggle:hover {
            transform: scale(1.1);
            background: #357abd;
        }
        .theme-toggle svg {
            width: 24px;
            height: 24px;
        }

        /* 深色模式样式 */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-mode .container {
            background-color: #2d2d2d;
        }
        body.dark-mode .header {
            background: #3d3d3d;
        }
        body.dark-mode .header h1 {
            color: #e0e0e0;
        }
        body.dark-mode .nav a {
            color: #b0b0b0;
        }
        body.dark-mode .nav a:hover {
            color: #6ab3ff;
        }
        body.dark-mode .card {
            background: #3d3d3d;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        body.dark-mode .card h2 {
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .form-group label {
            color: #e0e0e0;
        }
        body.dark-mode .form-group input {
            background: #2d2d2d;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .form-group small {
            color: #888;
        }
        body.dark-mode .checkbox-group label {
            color: #e0e0e0;
        }
        body.dark-mode .test-mail-section {
            background: #2d2d2d;
        }
        body.dark-mode .btn-secondary {
            background: #4d4d4d;
            color: #e0e0e0;
        }
        body.dark-mode .btn-secondary:hover {
            background: #5d5d5d;
        }
        body.dark-mode .alert-success {
            background: #1e3a2f;
            color: #4ade80;
            border-color: #2f5a3f;
        }
        body.dark-mode .alert-error {
            background: #3d1f1f;
            color: #ff6b6b;
            border-color: #5d2f2f;
        }

        /* 底部导航 */
        .app-bottom-bar {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background-color: #333;
            color: #fff;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            font-size: 14px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 999;
        }
        .app-bottom-item {
            color: #fff;
            text-decoration: none;
            opacity: 0.8;
        }
        .app-bottom-item.active {
            color: #4a90e2;
            opacity: 1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container">
        <div class="header">
            <h1>📧 邮件设置</h1>
            <div class="nav">
                <a href="admin.php">← 返回后台首页</a>
                <a href="settings.php">系统设置</a>
                <a href="comments.php">评论管理</a>
            </div>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="card">
                <h2>📬 通知开关</h2>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" <?php echo $settings['smtp_enabled'] === '1' ? 'checked' : ''; ?>>
                    <label for="smtp_enabled">启用邮件通知功能</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="notify_admin_on_comment" name="notify_admin_on_comment" <?php echo $settings['notify_admin_on_comment'] === '1' ? 'checked' : ''; ?>>
                    <label for="notify_admin_on_comment">有新评论时通知博主</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="notify_user_on_reply" name="notify_user_on_reply" <?php echo $settings['notify_user_on_reply'] === '1' ? 'checked' : ''; ?>>
                    <label for="notify_user_on_reply">评论被回复时通知用户</label>
                </div>
            </div>
            
            <div class="card">
                <h2>⚙️ SMTP服务器配置</h2>
                
                <div class="form-group">
                    <label for="smtp_host">SMTP服务器地址</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" placeholder="例如：smtp.qq.com 或 smtp.gmail.com">
                    <small>常用的SMTP服务器：QQ邮箱(smtp.qq.com)、163邮箱(smtp.163.com)、Gmail(smtp.gmail.com)</small>
                </div>
                
                <div class="form-group">
                    <label for="smtp_port">SMTP端口</label>
                    <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" placeholder="587">
                    <small>常用端口：25、587（推荐）、465（SSL）</small>
                </div>
                
                <div class="form-group">
                    <label for="smtp_username">SMTP用户名</label>
                    <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" placeholder="通常为您的邮箱地址">
                </div>
                
                <div class="form-group">
                    <label for="smtp_password">SMTP密码/授权码</label>
                    <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>" placeholder="邮箱授权码或密码">
                    <small>QQ邮箱和163邮箱需要使用授权码而不是登录密码</small>
                </div>
                
                <div class="form-group">
                    <label for="smtp_from_email">发件人邮箱</label>
                    <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email']); ?>" placeholder="noreply@example.com">
                </div>
                
                <div class="form-group">
                    <label for="smtp_from_name">发件人名称</label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name']); ?>" placeholder="<?php echo htmlspecialchars(getSetting($conn, 'site_title') ?? '我的朋友圈'); ?>">
                    <small>显示在收件人邮箱中的发件人名称</small>
                </div>
            </div>
            
            <div class="card">
                <h2>👤 博主设置</h2>
                
                <div class="form-group">
                    <label for="admin_email">博主邮箱（接收通知）</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" placeholder="admin@example.com">
                    <small>有新评论时，通知将发送到这个邮箱</small>
                </div>
                
                <button type="submit" name="save_settings" class="btn btn-primary">💾 保存设置</button>
            </div>
            
            <div class="card">
                <h2>🧪 邮件测试</h2>
                <p style="margin-bottom: 15px; color: #666;">保存设置后，可以发送测试邮件验证配置是否正确。</p>
                <p style="margin-bottom: 15px; color: #999; font-size: 12px;">提示：如果SMTP服务器响应较慢，测试可能需要几秒钟，请耐心等待。</p>
                
                <div class="test-mail-section">
                    <h3>发送测试邮件</h3>
                    <div class="form-group">
                        <label for="test_email">测试邮箱地址</label>
                        <input type="email" id="test_email" name="test_email" placeholder="请输入接收测试邮件的邮箱">
                    </div>
                    <button type="submit" name="test_mail" class="btn btn-secondary" id="testMailBtn" onclick="return confirm('确认发送测试邮件？')">📤 发送测试邮件</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 明暗模式切换按钮 -->
    <button class="theme-toggle" onclick="toggleTheme()" title="切换明暗模式">
        <svg id="theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
    </button>

    <script>
        // 主题切换功能
        function toggleTheme() {
            const body = document.body;
            const isDark = body.classList.toggle('dark-mode');
            localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
            updateThemeIcon(isDark);
        }

        function updateThemeIcon(isDark) {
            const icon = document.getElementById('theme-icon');
            if (isDark) {
                // 月亮图标
                icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            } else {
                // 太阳图标
                icon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';
            }
        }

        // 初始化主题
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                updateThemeIcon(true);
            }
        });
    </script>
</body>
</html>
