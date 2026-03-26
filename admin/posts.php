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
$perPage = 5; // 每页显示 5 条
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// --- 处理删除操作 ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // 删除后回到当前页，并带上成功提示
    header('Location: posts.php?page=' . $page . '&deleted=1');
    exit;
}

// --- 处理编辑提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $content = trim($_POST['content']);
    $music = trim($_POST['music']);
    
    $images = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../upload/'; 
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['jpg','jpeg','png','gif', 'webp'])) {
                    $filename = uniqid() . '.' . strtolower($ext);
                    $path = 'upload/' . $filename; 
                    $fullPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $fullPath)) {
                        $images[] = $path;
                    }
                }
            }
        }
    } else {
        $old = $conn->prepare("SELECT images FROM posts WHERE id = ?");
        $old->bind_param("i", $id);
        $old->execute();
        $res = $old->get_result();
        if ($row = $res->fetch_assoc()) {
            $images = json_decode($row['images'], true) ?: [];
        }
        $old->close();
    }

    $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
    $stmt = $conn->prepare("UPDATE posts SET content = ?, images = ?, music = ? WHERE id = ?");
    $stmt->bind_param("sssi", $content, $imagesJson, $music, $id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: posts.php?page=' . $page . '&edited=1');
    exit;
}

// --- 获取总数和数据进行分页 ---
$countResult = $conn->query("SELECT COUNT(*) as total FROM posts");
$totalRow = $countResult->fetch_assoc();
$totalPosts = $totalRow['total'];
$totalPages = ceil($totalPosts / $perPage);

// 修正页码超过最大页数的情况
if ($page > $totalPages && $totalPages > 0) {
    header('Location: posts.php?page=' . $totalPages);
    exit;
}

$offset = ($page - 1) * $perPage;
$result = $conn->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");

// --- 生成分页链接逻辑 ---
function generatePagination($currentPage, $totalPages) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // 上一页
    if ($currentPage > 1) {
        $html .= '<a href="?page=' . ($currentPage - 1) . '" class="page-btn prev">&laquo;</a>';
    } else {
        $html .= '<span class="page-btn prev disabled">&laquo;</span>';
    }

    // 页码列表
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    // 如果前面有省略
    if ($startPage > 1) {
        $html .= '<a href="?page=1" class="page-num">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="page-num active">' . $i . '</span>';
        } else {
            $html .= '<a href="?page=' . $i . '" class="page-num">' . $i . '</a>';
        }
    }

    // 如果后面有省略
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="page-ellipsis">...</span>';
        }
        $html .= '<a href="?page=' . $totalPages . '" class="page-num">' . $totalPages . '</a>';
    }

    // 下一页
    if ($currentPage < $totalPages) {
        $html .= '<a href="?page=' . ($currentPage + 1) . '" class="page-btn next">&raquo;</a>';
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
    <title>说说管理</title>
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
        
        .post-item {
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .post-content {
            font-size: 15px;
            color: #333;
            margin-bottom: 10px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .post-images {
            display: flex;
            gap: 8px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .post-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #eee;
            background: #f9f9f9;
        }
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }
        .post-date { color: #999; font-size: 12px; }
        .post-music {
            font-size: 12px;
            color: #07c160;
            background: #f0fdf4;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 8px;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            
        }
        .action-btns { display: flex; gap: 8px; }
        .btn {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            display: inline-block;
        }
        .btn-edit { background: #eef2ff; color: #4a90e2; }
        .btn-delete { background: #fef2f2; color: #e74c3c; }

        /* 分页样式 */
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

        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 500px;
            padding: 20px;
            border-radius: 12px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 14px; }
        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea.form-control { resize: vertical; min-height: 80px; }
        .btn-submit {
            width: 100%;
            background: #07c160;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        .close-modal {
            position: absolute;
            top: 15px; right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
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
        .app-bottom-item { color: #fff; text-decoration: none; opacity: 0.8; }
        .app-bottom-item.active { color: #4a90e2; opacity: 1; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h1>📝 说说管理</h1>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">✅ 说说已删除</div>
    <?php endif; ?>
    <?php if (isset($_GET['edited'])): ?>
        <div class="alert alert-success">✅ 说说已更新</div>
    <?php endif; ?>

    <?php 
    if ($result && $result->num_rows > 0):
        while ($post = $result->fetch_assoc()): 
            $images = json_decode($post['images'], true) ?: [];
            $music = $post['music'];
    ?>
    <div class="post-item">
        <div class="post-content"><?php echo htmlspecialchars($post['content']); ?></div>
        
        <?php if (!empty($images)): ?>
            <div class="post-images">
                <?php foreach ($images as $img): ?>
                    <img src="../<?php echo htmlspecialchars($img); ?>" 
                         onclick="window.open(this.src)"
                         onerror="this.style.display='none';">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="post-meta">
            <div>
                <span class="post-date"><?php echo date('m-d H:i', strtotime($post['created_at'])); ?></span>
                <?php if ($music): ?>
                    <span class="post-music" title="<?php echo htmlspecialchars($music); ?>"> <?php echo htmlspecialchars(mb_substr($music, 0, 10)); ?></span>
                <?php endif; ?>
            </div>
            <div class="action-btns">
                <button class="btn btn-edit" onclick="openEditModal(<?php echo $post['id']; ?>)">编辑</button>
                <a href="posts.php?delete=<?php echo $post['id']; ?>&page=<?php echo $page; ?>" class="btn btn-delete" onclick="return confirm('确定删除？')">删除</a>
            </div>
        </div>
    </div>
    <?php 
        endwhile; 
        
        // 显示分页控件
        echo '<div class="page-info">共 ' . $totalPosts . ' 条说说，第 ' . $page . ' / ' . $totalPages . ' 页</div>';
        echo $paginationHtml;
        
    else:
        echo "<div style='text-align:center;color:#999;padding:40px;'>暂无说说内容</div>";
    endif; 
    ?>
</div>

<!-- 编辑模态框 -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-header">编辑说说</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" id="edit_id">
            <!-- 隐藏字段保留当前页码，虽然表单提交会刷新，但逻辑中已处理跳转 -->
            <input type="hidden" name="current_page" value="<?php echo $page; ?>">
            
            <div class="form-group">
                <label>内容</label>
                <textarea name="content" id="edit_content" class="form-control" required rows="5"></textarea>
            </div>
            <div class="form-group">
                <label>地址</label>
                <input type="text" name="music" id="edit_music" class="form-control" placeholder="北京">
            </div>
            <div class="form-group">
                <label>图片 (重新上传将替换所有旧图)</label>
                <input type="file" name="images[]" multiple accept="image/*" class="form-control">
                <small style="color:#999;font-size:12px;">若不选择图片，将保留原有图片。</small>
            </div>
            <button type="submit" class="btn-submit">保存修改</button>
        </form>
    </div>
</div>

<div class="app-bottom-bar">
    <a href="admin.php" class="app-bottom-item">首页</a>
    <a href="posts.php" class="app-bottom-item active">说说</a>
    <a href="comments.php" class="app-bottom-item">评论</a>
    <a href="/" class="app-bottom-item">前端</a>
    <a href="logout.php" class="app-bottom-item">退出</a>
</div>

<script>
function openEditModal(id) {
    fetch(`get_post.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_content').value = data.content;
            document.getElementById('edit_music').value = data.music || '';
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        })
        .catch(err => alert('获取详情失败'));
}

function closeModal() {
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

</body>
</html>