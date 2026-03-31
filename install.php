<?php
session_start();
if (!file_exists('config.php')) {
    die("❌ 请先创建 config.php 配置文件！");
}
include 'config.php';

$installed = false;
$result = $conn->query("SHOW TABLES LIKE 'settings'");
if ($result && $result->num_rows > 0) {
    $installed = true;
}

// 数据库升级功能
function runDatabaseUpgrades($conn) {
    $messages = [];
    
    // 1. 检查并添加 is_pinned 字段
    $checkResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_pinned'");
    if ($checkResult->num_rows == 0) {
        $sql = "ALTER TABLE posts ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 COMMENT '置顶级别：0=不置顶, 1=置顶栏位1, 2=置顶栏位2, 3=置顶栏位3' AFTER music";
        if ($conn->query($sql)) {
            $messages[] = "✅ 已添加字段 'is_pinned'";
        } else {
            $messages[] = "❌ 添加字段 'is_pinned' 失败: " . $conn->error;
        }
    }
    
    // 1.1 检查并添加全文搜索索引
    $checkIndex = $conn->query("SHOW INDEX FROM posts WHERE Key_name = 'idx_content'");
    if ($checkIndex->num_rows == 0) {
        $sql = "ALTER TABLE posts ADD FULLTEXT INDEX `idx_content` (`content`)";
        if ($conn->query($sql)) {
            $messages[] = "✅ 已添加全文搜索索引 'idx_content'";
        } else {
            $messages[] = "❌ 添加全文搜索索引失败: " . $conn->error;
        }
    }
    
    // 2. 检查并添加 is_marked 字段（或从 is_ad 重命名）
    $checkResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_marked'");
    if ($checkResult->num_rows == 0) {
        // 检查是否存在旧的 is_ad 字段
        $checkAdResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_ad'");
        if ($checkAdResult->num_rows > 0) {
            // 重命名 is_ad 为 is_marked
            $sql = "ALTER TABLE posts CHANGE COLUMN is_ad is_marked TINYINT(1) DEFAULT 0 COMMENT '标记级别：0=普通, 1=标记栏位1, 2=标记栏位2, 3=标记栏位3'";
            if ($conn->query($sql)) {
                $messages[] = "✅ 已将 'is_ad' 重命名为 'is_marked'";
            } else {
                $messages[] = "❌ 重命名字段失败: " . $conn->error;
            }
        } else {
            // 创建 is_marked 字段
            $sql = "ALTER TABLE posts ADD COLUMN is_marked TINYINT(1) DEFAULT 0 COMMENT '标记级别：0=普通, 1=标记栏位1, 2=标记栏位2, 3=标记栏位3' AFTER is_pinned";
            if ($conn->query($sql)) {
                $messages[] = "✅ 已添加字段 'is_marked'";
            } else {
                $messages[] = "❌ 添加字段 'is_marked' 失败: " . $conn->error;
            }
        }
    }
    
    // 3. 创建点赞表
    $conn->query("CREATE TABLE IF NOT EXISTS `likes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT NOT NULL,
        `anonymous_id` VARCHAR(64) NOT NULL COMMENT '匿名用户ID',
        `author` VARCHAR(100) DEFAULT NULL COMMENT '用户昵称',
        `email` VARCHAR(100) DEFAULT NULL COMMENT '用户邮箱',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_like` (`post_id`, `anonymous_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = "✅ 点赞表检查完成";
    
    // 4. 创建友链表
    $conn->query("CREATE TABLE IF NOT EXISTS `links` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL COMMENT '网站名称',
        `url` VARCHAR(500) NOT NULL COMMENT '网站链接',
        `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像地址',
        `description` VARCHAR(255) DEFAULT NULL COMMENT '网站描述',
        `sort_order` INT DEFAULT 0 COMMENT '排序，数字越小越靠前',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友链表'");
    $messages[] = "✅ 友链表检查完成";
    
    // 5. 检查并添加 parent_id 字段到评论表
    $checkResult = $conn->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
    if ($checkResult->num_rows == 0) {
        $sql = "ALTER TABLE comments ADD COLUMN parent_id INT DEFAULT 0 COMMENT '回复的评论ID，0表示顶级评论' AFTER post_id";
        if ($conn->query($sql)) {
            $messages[] = "✅ 已添加字段 'parent_id' 到评论表";
        } else {
            $messages[] = "❌ 添加字段 'parent_id' 失败: " . $conn->error;
        }
    }
    
    // 6. 添加邮件通知相关设置
    $mailSettings = [
        ['smtp_enabled', '0'],
        ['smtp_host', ''],
        ['smtp_port', '587'],
        ['smtp_username', ''],
        ['smtp_password', ''],
        ['smtp_from_email', ''],
        ['smtp_from_name', ''],
        ['admin_email', ''],
        ['notify_admin_on_comment', '1'],
        ['notify_user_on_reply', '1']
    ];
    foreach ($mailSettings as $setting) {
        $checkStmt = $conn->prepare("SELECT id FROM settings WHERE name = ?");
        $checkStmt->bind_param("s", $setting[0]);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows == 0) {
            $insertStmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $setting[0], $setting[1]);
            $insertStmt->execute();
        }
    }
    $messages[] = "✅ 邮件设置检查完成";
    
    // 7. 创建公告表
    $conn->query("CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL COMMENT '公告标题',
        `content` TEXT NOT NULL COMMENT '公告内容（支持Markdown和HTML）',
        `type` ENUM('markdown', 'html') DEFAULT 'markdown' COMMENT '内容类型',
        `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否显示：0=隐藏, 1=显示',
        `sort_order` INT DEFAULT 0 COMMENT '排序，数字越小越靠前',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表'");
    $messages[] = "✅ 公告表检查完成";
    
    return $messages;
}

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS `posts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `images` TEXT,
            `music` VARCHAR(255),
            `is_pinned` TINYINT(1) DEFAULT 0 COMMENT '置顶级别：0=不置顶, 1=置顶栏位1, 2=置顶栏位2, 3=置顶栏位3',
            `is_marked` TINYINT(1) DEFAULT 0 COMMENT '标记级别：0=普通, 1=标记栏位1, 2=标记栏位2, 3=标记栏位3',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FULLTEXT INDEX `idx_content` (`content`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS `comments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `post_id` INT NOT NULL,
            `parent_id` INT DEFAULT 0 COMMENT '回复的评论ID，0表示顶级评论',
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 运行数据库升级
        $upgradeMessages = runDatabaseUpgrades($conn);
        
        $defaults = [
            ['site_title', '我的朋友圈'],
            ['friend_name', '小归客'],
            ['friend_avatar', 'https://img12.360buyimg.com/ddimg/jfs/t1/187753/34/18828/41739/60c8012bE0e3f64e6/12a8c8f4b3f4b3f4.jpg'],
            ['friend_background', ''],
            ['friend_signature', '记录生活中的小确幸'],
            ['music_url', ''],
            // SMTP邮件配置
            ['smtp_enabled', '0'],
            ['smtp_host', ''],
            ['smtp_port', '587'],
            ['smtp_username', ''],
            ['smtp_password', ''],
            ['smtp_from_email', ''],
            ['smtp_from_name', ''],
            ['admin_email', ''],
            ['notify_admin_on_comment', '1'],
            ['notify_user_on_reply', '1']
        ];

        foreach ($defaults as $setting) {
            $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->bind_param("sss", $setting[0], $setting[1], $setting[1]);
            $stmt->execute();
        }

        // 创建管理员凭证文件（安全起见不存数据库）
        file_put_contents('admin_password.txt', 'admin'); // 明文存储（仅用于演示，生产建议加密）

        $_SESSION['success'] = "✅ 安装成功！管理员账号：admin / 密码：admin";
        $_SESSION['upgrade_msgs'] = $upgradeMessages;
        header('Location: login.php');
        exit;

    } catch (Exception $e) {
        $error = "❌ 安装失败：" . htmlspecialchars($e->getMessage());
    }
}

// 处理升级请求（已安装系统使用）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade'])) {
    try {
        $upgradeMessages = runDatabaseUpgrades($conn);
        $upgradeSuccess = true;
    } catch (Exception $e) {
        $upgradeError = "❌ 升级失败：" . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>朋友圈 - 系统安装/升级</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .btn { width: 100%; padding: 12px; background: #07c160; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-bottom: 10px; }
        .btn-secondary { background: #4a90e2; }
        .btn:hover { opacity: 0.9; }
        .msg { text-align: center; margin-top: 15px; }
        .error { color: red; }
        .success { color: green; }
        .upgrade-log { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-top: 15px; text-align: left; font-size: 14px; }
        .upgrade-log p { margin: 5px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #1976d2; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🚀 朋友圈系统</h2>
        
        <?php if ($installed): ?>
            <!-- 已安装状态 - 显示升级选项 -->
            <div class="info">
                ✅ 系统已安装，您可以运行数据库升级来更新表结构。
            </div>
            
            <?php if (isset($upgradeSuccess)): ?>
                <div class="msg success">✅ 数据库升级完成！</div>
                <?php if (!empty($upgradeMessages)): ?>
                    <div class="upgrade-log">
                        <strong>升级日志：</strong>
                        <?php foreach ($upgradeMessages as $msg): ?>
                            <p><?php echo htmlspecialchars($msg); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif (isset($upgradeError)): ?>
                <div class="msg error"><?php echo $upgradeError; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="upgrade" class="btn btn-secondary">运行数据库升级</button>
            </form>
            
            <p style="text-align:center; margin-top:15px;">
                <a href="login.php" style="color:#07c160;">前往登录</a> | 
                <a href="index.php" style="color:#4a90e2;">查看首页</a>
            </p>
            
        <?php else: ?>
            <!-- 未安装状态 - 显示安装选项 -->
            <?php if (isset($error)): ?>
                <div class="msg error"><?php echo $error; ?></div>
            <?php elseif (isset($_SESSION['success'])): ?>
                <div class="msg success"><?php echo $_SESSION['success']; ?></div>
            <?php else: ?>
                <p style="text-align:center; margin-bottom:20px;">自动创建数据表并初始化默认设置</p>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="install" class="btn">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
