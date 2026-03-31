<?php
// 点赞功能数据库更新脚本
include 'config.php';

echo "正在创建点赞表...\n";

// 创建点赞表
$sql = "CREATE TABLE IF NOT EXISTS `likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `anonymous_id` VARCHAR(64) NOT NULL COMMENT '匿名用户ID',
    `author` VARCHAR(100) DEFAULT NULL COMMENT '用户昵称',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '用户邮箱',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_like` (`post_id`, `anonymous_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "点赞表创建成功！\n";
} else {
    echo "创建失败: " . $conn->error . "\n";
}

echo "更新完成！\n";
?>
