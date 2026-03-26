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

// --- 配置分页 ---
$perPage = 10; // 每页显示 10 条
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// --- 处理删除操作 ---
if (isset($_GET['delete_comment'])) {
    $id = (int)$_GET['delete_comment'];
    // 使用预处理防止注入
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // 删除后回到当前页
    header('Location: comments.php?page=' . $page . '&deleted=1');
    exit;
}

// --- 获取总数和数据进行分页 ---
// 统计总数
$countResult = $conn->query("SELECT COUNT(*) as total FROM comments");
$totalRow = $countResult->fetch_assoc();
$totalComments = $totalRow['total'];
$totalPages = ceil($totalComments / $perPage);

// 修正页码超过最大页数的情况
if ($page > $totalPages && $totalPages > 0) {
    header('Location: comments.php?page=' . $totalPages);
    exit;
}

$offset = ($page - 1) * $perPage;

// 获取数据 (关联查询获取说说内容)
$result = $conn->query("
    SELECT c.*, p.content AS post_content, p.id AS post_id
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    ORDER BY c.created_at DESC 
    LIMIT $perPage OFFSET $offset
");

// --- 生成分页链接逻辑 (复用逻辑) ---
function generatePagination($currentPage, $totalPages, $scriptName = 'comments.php') {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // 上一页
    if ($currentPage > 1) {
        $html .= '<a href="' . $scriptName . '?page=' . ($currentPage - 1) . '" class="page-btn prev">&laquo;</a>';
    } else {
        $html .= '<span class="page-btn prev disabled">&laquo;</span>';
    }

    // 页码列表
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        $html .= '<a href="' . $scriptName . '?page=1" class="page-num">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="page-num active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $scriptName . '?page=' . $i . '" class="page-num">' . $i . '</a>';
        }
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
        $html .= '<a href="' . $scriptName . '?page=' . $totalPages . '" class="page-num">' . $totalPages . '</a>';
    }

    // 下一页
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $scriptName . '?page=' . ($currentPage + 1) . '" class="page-btn next">&raquo;</a>';
    } else {
        $html .= '<span class="page-btn next disabled">&raquo;</span>';
    }

    $html .= '</div>';
    return $html;
}

$paginationHtml = generatePagination($page, $totalPages);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评论管理</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f8f8;
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
        
        /* 评论卡片样式 */
        .comment-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .comment-author {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }
        .comment-email {
            font-weight: normal;
            color: #999;
            font-size: 12px;
            margin-left: 5px;
        }
        .comment-date {
            color: #999;
            font-size: 12px;
            white-space: nowrap;
        }
        .comment-content {
            color: #333;
            font-size: 14px;
            margin: 10px 0;
            white-space: pre-wrap;
            word-break: break-all;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
        }
        .comment-source {
            font-size: 12px;
            color: #666;
            background: #eef2ff;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .comment-source a {
            color: #4a90e2;
            text-decoration: none;
        }
        .comment-source a:hover {
            text-decoration: underline;
        }
        .action-area {
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
        }
        .btn-delete {
            background: #fef2f2;
            color: #e74c3c;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            border: 1px solid #fee2e2;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: #fee2e2;
        }

        /* 分页样式 (与 posts.php 一致) */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 30px 0 20px;
            flex-wrap: wrap;
        }
        .page-num, .page-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: #fff;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid #e0e0e0;
            transition: all 0.2s;
        }
        .page-num:hover, .page-btn:hover:not(.disabled) {
            background: #f0f0f0;
            border-color: #ccc;
        }
        .page-num.active {
            background: #4a90e2;
            color: #fff;
            border-color: #4a90e2;
            font-weight: bold;
        }
        .page-ellipsis {
            color: #999;
            padding: 0 5px;
        }
        .page-btn.disabled {
            color: #ccc;
            cursor: not-allowed;
            background: #f9f9f9;
        }
        .page-info {
            text-align: center;
            color: #999;
            font-size: 13px;
            margin-bottom: 10px;
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
        body.dark-mode h1 {
            color: #e0e0e0;
            border-color: #444;
        }
        body.dark-mode .comment-item {
            background: #3d3d3d;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        body.dark-mode .comment-author {
            color: #e0e0e0;
        }
        body.dark-mode .comment-email {
            color: #888;
        }
        body.dark-mode .comment-date {
            color: #888;
        }
        body.dark-mode .comment-content {
            color: #e0e0e0;
            background: #2d2d2d;
        }
        body.dark-mode .comment-source {
            background: #2d2d2d;
            color: #888;
        }
        body.dark-mode .comment-source a {
            color: #6ab3ff;
        }
        body.dark-mode .action-area {
            border-color: #444;
        }
        body.dark-mode .page-num, body.dark-mode .page-btn {
            background: #3d3d3d;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .page-num:hover, body.dark-mode .page-btn:hover:not(.disabled) {
            background: #4d4d4d;
        }
        body.dark-mode .page-num.active {
            background: #4a90e2;
            color: #fff;
        }
        body.dark-mode .page-btn.disabled {
            background: #2d2d2d;
            color: #666;
        }
        body.dark-mode .page-info {
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
        body.dark-mode .alert-success {
            background: #1e3a2f;
            color: #4ade80;
        }
        body.dark-mode .btn-delete {
            background: #3d1f1f;
            color: #ff6b6b;
            border-color: #5d2f2f;
        }
        body.dark-mode .btn-delete:hover {
            background: #4d2f2f;
        }
    </style>
</head>
<body>

<?php include 'includes/admin-navbar.php'; ?>

<div class="container">
    <h1>💬 评论管理</h1>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">✅ 评论已删除</div>
    <?php endif; ?>

    <?php 
    if ($result && $result->num_rows > 0):
        while ($comment = $result->fetch_assoc()): 
    ?>
    <div class="comment-item">
        <div class="comment-header">
            <div>
                <span class="comment-author"><?php echo htmlspecialchars($comment['name']); ?></span>
                <span class="comment-email">&lt;<?php echo htmlspecialchars($comment['email']); ?>&gt;</span>
            </div>
            <span class="comment-date"><?php echo date('m-d H:i', strtotime($comment['created_at'])); ?></span>
        </div>

        <!-- 来源说说 -->
        <div class="comment-source">
            📄 回复于：<a href="posts.php"><?php echo mb_substr(htmlspecialchars($comment['post_content']), 0, 25, 'UTF-8'); ?>...</a>
        </div>

        <!-- 评论内容 -->
        <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>

        <div class="action-area">
            <a href="comments.php?delete_comment=<?php echo $comment['id']; ?>&page=<?php echo $page; ?>" 
               class="btn-delete" 
               onclick="return confirm('确定要删除这条评论吗？')">
               删除评论
            </a>
        </div>
    </div>
    <?php 
        endwhile; 
        
        // 显示分页信息
        echo '<div class="page-info">共 ' . $totalComments . ' 条评论，第 ' . $page . ' / ' . $totalPages . ' 页</div>';
        echo $paginationHtml;
        
    else:
        echo "<div style='text-align:center;color:#999;padding:40px;'>暂无评论内容</div>";
    endif; 
    ?>
</div>



</body>
</html>