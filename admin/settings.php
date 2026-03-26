<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 智能加载配置文件
if (file_exists('config.php')) {
    include 'config.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
} else {
    die("配置文件 config.php 未找到");
}

if (!isset($conn) || $conn === null) {
    die("数据库连接失败。");
}

// 允许修改的设置项白名单 (防止恶意添加字段)
$allowedSettings = [
    'site_title',
    'friend_name',
    'friend_avatar',
    'friend_background',
    'friend_signature'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $name => $value) {
        // 只在白名单内的字段才更新
        if (in_array($name, $allowedSettings)) {
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $stmt->bind_param("ss", $value, $name);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: settings.php?updated=1');
    exit;
}

$result = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #fff;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 16px;
            line-height: 1.6;
            padding-bottom: 80px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            min-height: 100vh;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            margin-top: 0;border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-success { background: #d4edda; color: #155724; }

        /* 设置卡片 */
        .settings-card {
            background: #fff;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 12px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            width: 100%;
            background: #07c160;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #06ad56;
        }

        /* 图片预览小工具 */
        .img-preview-tip {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .img-preview-tip img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #eee;
            display: none; /* 默认隐藏，JS 控制显示 */
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

<div class="container">
    <h1>⚙️ 网站设置</h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ 设置已保存成功</div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="POST">
            <div class="form-group">
                <label for="site_title">网站名称</label>
                <input type="text" id="site_title" name="site_title" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                <small>显示在浏览器标题栏和首页顶部的名称。</small>
            </div>

            <div class="form-group">
                <label for="friend_name">朋友昵称</label>
                <input type="text" id="friend_name" name="friend_name" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['friend_name'] ?? ''); ?>" required>
                <small>朋友圈主页显示的昵称。</small>
            </div>

            <div class="form-group">
                <label for="friend_avatar">头像图片链接</label>
                <input type="url" id="friend_avatar" name="friend_avatar" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['friend_avatar'] ?? ''); ?>" 
                       placeholder="https://example.com/avatar.jpg"
                       oninput="previewImage(this, 'avatarPreview')">
                <div class="img-preview-tip">
                    <span>预览:</span>
                    <img id="avatarPreview" src="" alt="预览">
                </div>
                <small>建议使用正方形图片，支持 http/https 链接。</small>
            </div>

            <div class="form-group">
                <label for="friend_background">背景图片链接</label>
                <input type="url" id="friend_background" name="friend_background" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['friend_background'] ?? ''); ?>" 
                       placeholder="https://example.com/background.jpg"
                       oninput="previewImage(this, 'bgPreview')">
                <div class="img-preview-tip">
                    <span>预览:</span>
                    <img id="bgPreview" src="" alt="预览" style="border-radius: 4px; width: 60px; height: 40px; object-fit: cover;">
                </div>
                <small>留空则无背景。建议使用宽屏图片。</small>
            </div>

            <div class="form-group">
                <label for="friend_signature">个性签名</label>
                <textarea id="friend_signature" name="friend_signature" class="form-control" 
                          placeholder="填写一句个性签名..."><?php echo htmlspecialchars($settings['friend_signature'] ?? ''); ?></textarea>
                <small>显示在昵称下方的简短介绍。</small>
            </div>
            
            <button type="submit" class="btn-submit">💾 保存设置</button>
        </form>
    </div>
</div>

<!-- 底部导航栏 -->
<div class="app-bottom-bar">
    <a href="admin.php" class="app-bottom-item">首页</a>
    <a href="posts.php" class="app-bottom-item">说说</a>
    <a href="comments.php" class="app-bottom-item">评论</a>
    <a href="/" class="app-bottom-item">前端</a>
    <a href="settings.php" class="app-bottom-item active">设置</a>
</div>

<script>
// 简单的图片链接预览功能
function previewImage(input, imgId) {
    const img = document.getElementById(imgId);
    const url = input.value.trim();
    
    if (url) {
        img.src = url;
        img.style.display = 'block';
        img.onerror = function() {
            this.style.display = 'none';
        };
    } else {
        img.style.display = 'none';
    }
}

// 页面加载时尝试预览已有图片
window.addEventListener('DOMContentLoaded', () => {
    const avatarInput = document.getElementById('friend_avatar');
    const bgInput = document.getElementById('friend_background');
    if(avatarInput.value) previewImage(avatarInput, 'avatarPreview');
    if(bgInput.value) previewImage(bgInput, 'bgPreview');
});
</script>

</body>
</html>