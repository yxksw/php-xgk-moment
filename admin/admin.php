<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
// 【重要】确认此文件位置：如果在 admin/ 目录下，请使用 'config.php'
// 如果此文件仍在根目录或其他位置，请相应调整路径。
// 根据你之前的描述，文件已移至 admin/，所以这里应该是 'config.php'
// 但你提供的代码片段里写的是 '../config.php'，如果报错请改为 'config.php'
if (file_exists('config.php')) {
    include 'config.php';
} elseif (file_exists('../config.php')) {
    include '../config.php';
} else {
    die("配置文件 config.php 未找到");
}

// 获取统计
$postsCount = $conn->query("SELECT COUNT(*) FROM posts")->fetch_row()[0];
$commentsCount = $conn->query("SELECT COUNT(*) FROM comments")->fetch_row()[0];

// 获取最近 5 条说说
$recentPosts = $conn->query("SELECT id, content, created_at FROM posts ORDER BY created_at DESC LIMIT 5");

// 获取最近 5 条评论 (关联查询获取说说内容)
$recentComments = $conn->query("SELECT c.name, c.content, c.created_at, p.id as post_id, p.content as post_content 
                                FROM comments c 
                                JOIN posts p ON c.post_id = p.id 
                                ORDER BY c.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站概要</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f8f8;
            color: #333;
            margin: 0;
            padding: 0px;
            font-size: 16px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 10px;
            border-radius: 8px;
        }

        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* 概要部分样式 */
        .site-overview {
            margin-bottom: 30px;
        }
        .site-overview p {
            margin: 5px 0;
            font-size: 15px;
            color: #555;
        }
        .highlight-digit {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .quick-links {
            margin-top: 20px;
        }
        .quick-links a {
            color: #4a90e2;
            text-decoration: none;
            margin-right: 20px;
            font-size: 15px;
            font-weight: 500;
        }
        .quick-links a:hover {
            text-decoration: underline;
        }

        /* 分区标题 */
        h2 {
            font-size: 20px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 30px;
            font-weight: 600;
        }

        /* 最近文章和回复列表 */
        .post-list, .comment-list {
            list-style: none;
            padding: 0;
            margin-top: 15px;
        }
        .post-list li, .comment-list li {
            padding: 12px 0; /* 稍微增加高度以容纳新内容 */
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start; /* 改为顶部对齐，防止长文本影响布局 */
        }
        .date-col {
            color: #888;
            font-size: 14px;
            width: 40px;
            margin-right: 15px;
            text-align: left;
            flex-shrink: 0; /* 防止日期被压缩 */
            padding-top: 2px;
        }
        .title-col {
            flex: 1;
            overflow: hidden;
        }
        .comment-user {
            color: #4a90e2;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            display: block;
            margin-bottom: 4px;
        }
        .comment-user:hover {
            text-decoration: underline;
        }
        .comment-excerpt {
            color: #666;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }
        /* 新增：说说来源样式 */
        .comment-source {
            font-size: 12px;
            color: #999;
            background: #eee;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .comment-source span {
            color: #666;
            font-weight: bold;
            margin-right: 4px;
        }

        /* 官方日志部分 */
        .official-log {
            text-align: center;
            margin-top: 40px;
            color: #999;
            font-size: 14px;
        }

        /* 底部菜单 (模拟App底部) */
        .app-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #333;
            color: #fff;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            font-size: 14px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        .app-bottom-item {
            color: #fff;
            text-decoration: none;
        }
        .app-bottom-item.active {
            color: #4a90e2;
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
        body.dark-mode h1,
        body.dark-mode h2 {
            color: #e0e0e0;
            border-color: #444;
        }
        body.dark-mode .site-overview p {
            color: #b0b0b0;
        }
        body.dark-mode .highlight-digit {
            color: #4a90e2;
        }
        body.dark-mode .quick-links a {
            color: #6ab3ff;
        }
        body.dark-mode .date-col {
            color: #888;
        }
        body.dark-mode .comment-user {
            color: #6ab3ff;
        }
        body.dark-mode .comment-excerpt {
            color: #b0b0b0;
        }
        body.dark-mode .comment-source {
            background: #3d3d3d;
            color: #888;
        }
        body.dark-mode .comment-source span {
            color: #aaa;
        }
        body.dark-mode .post-list li,
        body.dark-mode .comment-list li {
            border-color: #444;
        }
        body.dark-mode .official-log {
            color: #888;
        }
        body.dark-mode .app-bottom-bar {
            background-color: #1a1a1a;
            border-top: 1px solid #444;
        }
        body.dark-mode .app-bottom-item {
            color: #b0b0b0;
        }
        body.dark-mode .app-bottom-item.active {
            color: #6ab3ff;
        }
        body.dark-mode .btn-home {
            background-color: #6ab3ff;
            color: #1a1a1a;
        }
        body.dark-mode .btn-home:hover {
            background-color: #4a90e2;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>网站概要</h1>

        <div class="site-overview">
            <p>
                目前有 <span class="highlight-digit"><?php echo $postsCount; ?></span> 篇说说, 并有 <span class="highlight-digit"><?php echo $commentsCount; ?></span> 条评论
            </p>
           
            <p>点击下面的链接快速开始:</p>
            <div class="quick-links">
                <a href="/includes/edit-page.php">撰写新文章</a>
                <a href="posts.php">说说管理</a>
                <a href="comments.php">评论管理</a>
                <a href="settings.php">系统设置</a>
            </div>
        </div>

        <h2>最近发布的说说</h2>
        <ul class="post-list">
            <?php if ($recentPosts && $recentPosts->num_rows > 0): ?>
                <?php while($row = $recentPosts->fetch_assoc()): 
                    $date = date('n.j', strtotime($row['created_at']));
                    $content = mb_substr(strip_tags($row['content']), 0, 30) . '...';
                ?>
                <li>
                    <span class="date-col"><?php echo $date; ?></span>
                    <span class="title-col"><a href="posts.php" style="color:#4a90e2;text-decoration:none;font-size:16px;"><?php echo htmlspecialchars($content); ?></a></span>
                </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li><span class="date-col">-</span><span class="title-col">暂无说说</span></li>
            <?php endif; ?>
        </ul>

        <h2>最近得到的回复</h2>
        <ul class="comment-list">
            <?php if ($recentComments && $recentComments->num_rows > 0): ?>
                <?php while($row = $recentComments->fetch_assoc()): 
                    $date = date('n.j', strtotime($row['created_at']));
                    // 处理说说内容摘要，防止过长
                    $postTitle = mb_substr(strip_tags($row['post_content']), 0, 20) . '...';
                ?>
                <li>
                    <span class="date-col"><?php echo $date; ?></span>
                    <span class="title-col">
                        <!-- 评论者名字 -->
                        <a href="#" class="comment-user"><?php echo htmlspecialchars($row['name']); ?>:</a>
                        
                        <!-- 评论内容 -->
                        <span class="comment-excerpt"><?php echo htmlspecialchars($row['content']); ?></span>
                        
                        <!-- 【新增】显示回复于哪条说说 -->
                        <div class="comment-source">
                            <span>📄 回复于:</span> <?php echo htmlspecialchars($postTitle); ?>
                        </div>
                    </span>
                </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li><span class="date-col">-</span><span class="title-col">暂无回复</span></li>
            <?php endif; ?>
        </ul>

        <h2>官方最新日志</h2>
        <div class="official-log">
            <p>由 <strong>小归客</strong> 强力驱动, 版本 1.2.1</p>
            <div class="footer-links" style="margin-top:10px;">
                <a href="#" style="color:#4a90e2;text-decoration:none;margin:0 5px;">帮助文档</a> •
                <a href="#" style="color:#4a90e2;text-decoration:none;margin:0 5px;">支持论坛</a>
            </div>
        </div>
    </div>

    <!-- 模拟App底部菜单栏 -->
    <div class="app-bottom-bar">
        <a href="admin.php" class="app-bottom-item active">小归客</a>
        <a href="posts.php" class="app-bottom-item">说说</a>
        <a href="comments.php" class="app-bottom-item">评论</a>
        <a href="settings.php" class="app-bottom-item">设置</a>
        <a href="/" class="app-bottom-item">前端</a>
        <a href="logout.php" class="app-bottom-item">登出</a>
    </div>

    <!-- 明暗模式切换按钮 -->
    <button class="theme-toggle" id="themeToggle" title="切换明暗模式">
        <!-- 浅色模式图标 (太阳) -->
        <svg id="lightIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0-8 0m-5 0h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>
        <!-- 深色模式图标 (月亮) -->
        <svg id="darkIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="display:none;"><g fill="none" stroke="#ffffff" stroke-width="2"><path d="M20.958 15.325c.204-.486-.379-.9-.868-.684a7.7 7.7 0 0 1-3.101.648c-4.185 0-7.577-3.324-7.577-7.425a7.3 7.3 0 0 1 1.134-3.91c.284-.448-.057-1.068-.577-.936C5.96 4.041 3 7.613 3 11.862C3 16.909 7.175 21 12.326 21c3.9 0 7.24-2.345 8.632-5.675Z"/><path d="M15.611 3.103c-.53-.354-1.162.278-.809.808l.63.945a2.33 2.33 0 0 1 0 2.588l-.63.945c-.353.53.28 1.162.81.808l.944-.63a2.33 2.33 0 0 1 2.588 0l.945.63c.53.354 1.162-.278.808-.808l-.63-.945a2.33 2.33 0 0 1 0-2.588l.63-.945c.354-.53-.278-1.162-.809-.808l-.944.63a2.33 2.33 0 0 1-2.588 0z"/></g></svg>
    </button>

    <script>
        // 明暗模式切换功能
        (function() {
            const themeToggle = document.getElementById('themeToggle');
            const lightIcon = document.getElementById('lightIcon');
            const darkIcon = document.getElementById('darkIcon');
            const body = document.body;
            
            // 从 localStorage 读取主题设置
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            // 应用主题
            function applyTheme(theme) {
                if (theme === 'dark') {
                    body.classList.add('dark-mode');
                    lightIcon.style.display = 'none';
                    darkIcon.style.display = 'block';
                } else {
                    body.classList.remove('dark-mode');
                    lightIcon.style.display = 'block';
                    darkIcon.style.display = 'none';
                }
            }
            
            // 初始化主题
            applyTheme(currentTheme);
            
            // 切换主题
            themeToggle.addEventListener('click', function() {
                const isDark = body.classList.contains('dark-mode');
                const newTheme = isDark ? 'light' : 'dark';
                
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        })();
    </script>

</body>
</html>