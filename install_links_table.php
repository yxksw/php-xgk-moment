<?php
// 友链表安装脚本
// 运行此文件创建 links 数据表

include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '网站名称',
    url VARCHAR(500) NOT NULL COMMENT '网站链接',
    avatar VARCHAR(500) DEFAULT NULL COMMENT '头像地址',
    description VARCHAR(255) DEFAULT NULL COMMENT '网站描述',
    sort_order INT DEFAULT 0 COMMENT '排序，数字越小越靠前',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友链表'";

if ($conn->query($sql)) {
    echo "✅ 友链表创建成功！<br>";
    echo "表名: links<br>";
    echo "字段: id, name, url, avatar, description, sort_order, created_at, updated_at<br><br>";
    echo "<a href='admin/links.php'>进入友链管理</a>";
} else {
    echo "❌ 创建失败: " . $conn->error;
}
?>
