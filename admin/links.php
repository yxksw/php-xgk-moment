<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

include '../config.php';
include '../includes/functions.php';

$message = '';
$error = '';

// 处理添加友链
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (empty($name) || empty($url)) {
            $error = '名称和链接不能为空';
        } else {
            $stmt = $conn->prepare("INSERT INTO links (name, url, avatar, description, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $name, $url, $avatar, $description, $sort_order);
            if ($stmt->execute()) {
                $message = '友链添加成功';
            } else {
                $error = '添加失败：' . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (empty($name) || empty($url)) {
            $error = '名称和链接不能为空';
        } else {
            $stmt = $conn->prepare("UPDATE links SET name = ?, url = ?, avatar = ?, description = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $name, $url, $avatar, $description, $sort_order, $id);
            if ($stmt->execute()) {
                $message = '友链更新成功';
            } else {
                $error = '更新失败：' . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM links WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = '友链删除成功';
        } else {
            $error = '删除失败：' . $conn->error;
        }
    }
}

// 获取所有友链
$links = $conn->query("SELECT * FROM links ORDER BY sort_order ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>友链管理</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding-bottom: 60px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #07c160;
        }
        
        /* 消息提示 */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* 添加按钮 */
        .add-btn {
            display: inline-block;
            background: #07c160;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin-bottom: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .add-btn:hover {
            background: #06ad56;
        }
        
        /* 友链列表 */
        .links-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .link-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        .link-item:last-child {
            border-bottom: none;
        }
        .link-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .link-info {
            flex: 1;
            min-width: 0;
        }
        .link-name {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        .link-url {
            font-size: 13px;
            color: #666;
            word-break: break-all;
        }
        .link-desc {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        .link-actions {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-edit {
            background: #4a90e2;
            color: white;
        }
        .btn-edit:hover {
            background: #357abd;
        }
        .btn-delete {
            background: #ff6b6b;
            color: white;
        }
        .btn-delete:hover {
            background: #ff5252;
        }
        
        /* 弹窗 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            font-size: 18px;
            color: #333;
        }
        .close-modal {
            font-size: 24px;
            color: #999;
            cursor: pointer;
            background: none;
            border: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .btn-cancel {
            padding: 10px 20px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-save {
            padding: 10px 20px;
            background: #07c160;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* 底部导航 */
        .app-bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #333;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
        }
        .app-bottom-item {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
        }
        .app-bottom-item.active {
            color: #4a90e2;
        }
        
        /* 深色模式 */
        body.dark-mode {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-mode h1 {
            color: #e0e0e0;
            border-color: #444;
        }
        body.dark-mode .links-list {
            background: #2d2d2d;
        }
        body.dark-mode .link-item {
            border-color: #444;
        }
        body.dark-mode .link-name {
            color: #e0e0e0;
        }
        body.dark-mode .link-url {
            color: #888;
        }
        body.dark-mode .link-desc {
            color: #666;
        }
        body.dark-mode .modal-content {
            background: #2d2d2d;
        }
        body.dark-mode .modal-header h2 {
            color: #e0e0e0;
        }
        body.dark-mode .form-group label {
            color: #b0b0b0;
        }
        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea {
            background: #3d3d3d;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode .app-bottom-bar {
            background: #1a1a1a;
            border-top: 1px solid #444;
        }
    </style>
</head>
<body>

<?php include 'includes/admin-navbar.php'; ?>

    <div class="container">
        <h1>友链管理</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <button class="add-btn" onclick="openAddModal()">+ 添加友链</button>
        
        <div class="links-list">
            <?php if ($links && $links->num_rows > 0): ?>
                <?php while ($link = $links->fetch_assoc()): ?>
                    <div class="link-item">
                        <img src="<?php echo htmlspecialchars($link['avatar'] ?: 'https://via.placeholder.com/50'); ?>" alt="" class="link-avatar">
                        <div class="link-info">
                            <div class="link-name"><?php echo htmlspecialchars($link['name']); ?></div>
                            <div class="link-url"><?php echo htmlspecialchars($link['url']); ?></div>
                            <?php if ($link['description']): ?>
                                <div class="link-desc"><?php echo htmlspecialchars($link['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-actions">
                            <button class="btn-edit" onclick="openEditModal(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars($link['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($link['url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($link['avatar'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($link['description'], ENT_QUOTES); ?>', <?php echo $link['sort_order']; ?>)">编辑</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('确定要删除这个友链吗？');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <button type="submit" class="btn-delete">删除</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="link-item" style="justify-content: center; color: #999;">
                    暂无友链，点击上方按钮添加
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 添加/编辑弹窗 -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">添加友链</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="linkId" value="">
                
                <div class="form-group">
                    <label>网站名称 *</label>
                    <input type="text" name="name" id="linkName" required placeholder="例如：小归客的博客">
                </div>
                
                <div class="form-group">
                    <label>网站链接 *</label>
                    <input type="url" name="url" id="linkUrl" required placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <label>头像地址</label>
                    <input type="url" name="avatar" id="linkAvatar" placeholder="https://example.com/avatar.jpg">
                </div>
                
                <div class="form-group">
                    <label>描述</label>
                    <textarea name="description" id="linkDesc" placeholder="简短的网站描述..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" name="sort_order" id="linkSort" value="0" placeholder="数字越小越靠前">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn-save">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('formAction').value = 'add';
            document.getElementById('linkId').value = '';
            document.getElementById('modalTitle').textContent = '添加友链';
            document.getElementById('linkName').value = '';
            document.getElementById('linkUrl').value = '';
            document.getElementById('linkAvatar').value = '';
            document.getElementById('linkDesc').value = '';
            document.getElementById('linkSort').value = '0';
            document.getElementById('linkModal').classList.add('show');
        }
        
        function openEditModal(id, name, url, avatar, description, sort) {
            document.getElementById('formAction').value = 'edit';
            document.getElementById('linkId').value = id;
            document.getElementById('modalTitle').textContent = '编辑友链';
            document.getElementById('linkName').value = name;
            document.getElementById('linkUrl').value = url;
            document.getElementById('linkAvatar').value = avatar;
            document.getElementById('linkDesc').value = description;
            document.getElementById('linkSort').value = sort;
            document.getElementById('linkModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('linkModal').classList.remove('show');
        }
        
        // 点击弹窗外部关闭
        document.getElementById('linkModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // 明暗模式切换
        (function() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</body>
</html>
