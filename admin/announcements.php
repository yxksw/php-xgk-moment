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

$success = '';
$error = '';

// --- 处理删除操作 ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = '公告已删除';
    } else {
        $error = '删除失败：' . $conn->error;
    }
    $stmt->close();
}

// --- 处理添加/编辑 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'markdown';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if (empty($title) || empty($content)) {
        $error = '标题和内容不能为空';
    } else {
        if ($id > 0) {
            // 更新
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, type = ?, is_active = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param("sssiii", $title, $content, $type, $is_active, $sort_order, $id);
        } else {
            // 添加
            $stmt = $conn->prepare("INSERT INTO announcements (title, content, type, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $title, $content, $type, $is_active, $sort_order);
        }
        
        if ($stmt->execute()) {
            $success = $id > 0 ? '公告已更新' : '公告已添加';
        } else {
            $error = '操作失败：' . $conn->error;
        }
        $stmt->close();
    }
}

// --- 获取公告列表 ---
$result = $conn->query("SELECT * FROM announcements ORDER BY sort_order ASC, created_at DESC");

// --- 获取编辑的公告 ---
$editAnnouncement = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editResult = $stmt->get_result();
    $editAnnouncement = $editResult->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
            background-color: #f8f8f8;
            color: #333;
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
            margin-top: 0;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
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

        /* 表单样式 */
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .form-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a90e2;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
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

        /* 公告列表样式 */
        .announcement-list {
            margin-top: 20px;
        }
        .announcement-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .announcement-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        .announcement-type {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e3f2fd;
            color: #1976d2;
        }
        .announcement-type.html {
            background: #fff3e0;
            color: #f57c00;
        }
        .announcement-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 8px;
        }
        .status-active {
            background: #e6f7ed;
            color: #07c160;
        }
        .status-inactive {
            background: #f5f5f5;
            color: #999;
        }
        .announcement-content {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
        }
        .announcement-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        .action-area {
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            border: 1px solid #bbdefb;
            transition: all 0.2s;
        }
        .btn-edit:hover {
            background: #bbdefb;
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
        body.dark-mode h2 {
            color: #e0e0e0;
        }
        body.dark-mode .form-section {
            background: #3d3d3d;
        }
        body.dark-mode .form-group label {
            color: #e0e0e0;
        }
        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea,
        body.dark-mode .form-group select {
            background: #2d2d2d;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .announcement-item {
            background: #3d3d3d;
            border-color: #444;
        }
        body.dark-mode .announcement-title {
            color: #e0e0e0;
        }
        body.dark-mode .announcement-content {
            color: #b0b0b0;
            background: #2d2d2d;
        }
        body.dark-mode .announcement-meta {
            color: #888;
        }
        body.dark-mode .action-area {
            border-color: #444;
        }
        body.dark-mode .btn-secondary {
            background: #4d4d4d;
            color: #e0e0e0;
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
    </style>
</head>
<body>
    <?php include 'includes/admin-navbar.php'; ?>

    <div class="container">
        <h1>📢 公告管理</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 添加/编辑表单 -->
        <div class="form-section">
            <h2><?php echo $editAnnouncement ? '✏️ 编辑公告' : '➕ 添加公告'; ?></h2>
            <form method="POST" action="">
                <?php if ($editAnnouncement): ?>
                    <input type="hidden" name="id" value="<?php echo $editAnnouncement['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">公告标题</label>
                    <input type="text" id="title" name="title" value="<?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['title']) : ''; ?>" placeholder="请输入公告标题" required>
                </div>
                
                <div class="form-group">
                    <label for="content">公告内容</label>
                    <textarea id="content" name="content" placeholder="支持 Markdown 或 HTML 格式" required><?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['content']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">内容类型</label>
                        <select id="type" name="type">
                            <option value="markdown" <?php echo ($editAnnouncement && $editAnnouncement['type'] === 'markdown') ? 'selected' : ''; ?>>Markdown</option>
                            <option value="html" <?php echo ($editAnnouncement && $editAnnouncement['type'] === 'html') ? 'selected' : ''; ?>>HTML</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">排序</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?php echo $editAnnouncement ? $editAnnouncement['sort_order'] : '0'; ?>" placeholder="数字越小越靠前">
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" <?php echo (!$editAnnouncement || $editAnnouncement['is_active']) ? 'checked' : ''; ?>>
                    <label for="is_active">显示公告</label>
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo $editAnnouncement ? '保存修改' : '添加公告'; ?></button>
                <?php if ($editAnnouncement): ?>
                    <a href="announcements.php" class="btn btn-secondary">取消编辑</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- 公告列表 -->
        <h2>📋 公告列表</h2>
        <div class="announcement-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="announcement-item">
                        <div class="announcement-header">
                            <div>
                                <span class="announcement-title"><?php echo htmlspecialchars($row['title']); ?></span>
                                <span class="announcement-type <?php echo $row['type']; ?>"><?php echo strtoupper($row['type']); ?></span>
                                <span class="announcement-status <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $row['is_active'] ? '显示中' : '已隐藏'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="announcement-content"><?php echo nl2br(htmlspecialchars(mb_substr($row['content'], 0, 100, 'UTF-8'))); ?>...</div>
                        <div class="announcement-meta">
                            排序: <?php echo $row['sort_order']; ?> | 
                            创建: <?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?> | 
                            更新: <?php echo date('Y-m-d H:i', strtotime($row['updated_at'])); ?>
                        </div>
                        <div class="action-area">
                            <a href="?edit=<?php echo $row['id']; ?>" class="btn-edit">编辑</a>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('确定要删除这条公告吗？')">删除</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center;color:#999;padding:40px;">暂无公告</div>
            <?php endif; ?>
        </div>
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
                icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
            } else {
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
