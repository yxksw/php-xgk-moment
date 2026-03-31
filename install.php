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
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS `comments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `post_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 创建点赞表
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
        
        
        $defaults = [
            ['site_title', '我的朋友圈'],
            ['friend_name', '小归客'],
            ['friend_avatar', 'https://img12.360buyimg.com/ddimg/jfs/t1/187753/34/18828/41739/60c8012bE0e3f64e6/12a8c8f4b3f4b3f4.jpg'],
            ['friend_background', ''],
            ['friend_signature', '记录生活中的小确幸'],
            ['music_url', '']
        ];

        foreach ($defaults as $setting) {
            $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->bind_param("sss", $setting[0], $setting[1], $setting[1]);
            $stmt->execute();
        }

        // 创建管理员凭证文件（安全起见不存数据库）
        file_put_contents('admin_password.txt', 'admin'); // 明文存储（仅用于演示，生产建议加密）

        $_SESSION['success'] = "✅ 安装成功！管理员账号：admin / 密码：admin";
        header('Location: login.php');
        exit;

    } catch (Exception $e) {
        $error = "❌ 安装失败：" . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>朋友圈 - 一键安装</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 40px; }
        .card { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .btn { width: 100%; padding: 12px; background: #07c160; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .msg { text-align: center; margin-top: 15px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🚀 朋友圈系统安装</h2>
        <?php if ($installed): ?>
            <div class="msg success">✅ 系统已安装，<a href="login.php" style="color:#07c160;">点击登录</a></div>
        <?php elseif (isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php elseif (isset($_SESSION['success'])): ?>
            <div class="msg success"><?php echo $_SESSION['success']; ?></div>
        <?php else: ?>
            <p style="text-align:center; margin-bottom:20px;">自动创建数据表并初始化默认设置</p>
            <form method="POST">
                <button type="submit" name="install" class="btn">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
   
    