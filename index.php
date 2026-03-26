<?php
session_start();
// 修改点：路径加上 includes/
include 'config.php'; 
include 'includes/functions.php'; 

// 处理发表说说
handlePostSubmission($conn);

// 获取设置
$site_title = getSetting($conn, 'site_title');
$friend_name = getSetting($conn, 'friend_name');
$friend_avatar = getSetting($conn, 'friend_avatar');
$friend_background = getSetting($conn, 'friend_background');
$friend_signature = getSetting($conn, 'friend_signature');

// 分页逻辑
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

$totalResult = $conn->query("SELECT COUNT(*) as total FROM posts");
$totalPosts = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPosts / $limit);
$offset = ($page - 1) * $limit;

$result = $conn->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

// 修改点：路径加上 includes/
include 'includes/header.php';
?>

 
    <!-- 说说列表 -->
    <?php if ($result->num_rows > 0): ?>
        <?php while ($post = $result->fetch_assoc()): 
            $images = json_decode($post['images'], true) ?: [];
            $count = count($images);
            $gridClass = $count == 1 ? 'grid-1' : ($count == 2 ? 'grid-2' : 'grid-' . min($count, 9));
        ?>
        <div class="post-item" id="post-<?php echo $post['id']; ?>">
            <div class="post-header">
                <img src="<?php echo htmlspecialchars($friend_avatar ?: 'https://via.placeholder.com/32'); ?>">
                <div>
                    <div class="post-author"><?php echo htmlspecialchars($friend_name); ?></div>
                    <div class="post-time"></div>
                </div>
            </div>
            <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

            <?php if ($count > 0): ?>
            
            <div class="post-images-grid <?php echo $gridClass; ?>">
    <?php foreach (array_slice($images, 0, 9) as $img): ?>
        <!-- 添加 onclick 事件调用 openLightbox -->
        <img src="<?php echo htmlspecialchars($img); ?>" 
             loading="lazy" 
             alt="图片"
             style="cursor: zoom-in;" 
             onclick="openLightbox(this.src)">
    <?php endforeach; ?>
</div>

<!-- 灯箱容器 (放在 post-images-grid 外面，body 结束前或者这里都可以) -->
<div id="imageLightbox" class="lightbox-overlay" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="lightboxImg" src="" alt="放大图片">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>
            
            
            
            <?php endif; ?>
            
            <span class="dz"><?php echo nl2br(htmlspecialchars($post['music'])); ?> 
            </span>
                
            <div class="plan">
                <span><?php echo date('Y年m月d日', strtotime($post['created_at'])); ?></span>
                <div>
                    <a href="javascript:void(0);" style="color:#07c160;border-radius: 5px; font-size:16px; text-decoration:none; float:right;" onclick="loadComments(<?php echo $post['id']; ?>)">
                        
                        
                        <svg width="40" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="5" cy="12" r="2" fill="currentColor"/>
  <circle cx="12" cy="12" r="2" fill="currentColor"/>
  <circle cx="19" cy="12" r="2" fill="currentColor"/>
</svg>
                    </a>
                </div>
            </div>
            <div class="comment-container" id="comment-container-<?php echo $post['id']; ?>" style="margin-left: 40px; margin-top:12px;"></div>
        </div> 
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;color:#999;margin:30px 0;">暂无说说</div>
    <?php endif; ?>

    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">&laquo; 上一页</a>
        <?php else: ?>
            <span class="disabled">&laquo; 上一页</span>
        <?php endif; ?>
        <span class="current">第 <?php echo $page; ?> 页 / 共 <?php echo $totalPages; ?> 页</span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>">下一页 &raquo;</a>
        <?php else: ?>
            <span class="disabled">下一页 &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<?php
// 音乐播放器
include 'includes/music-player.php';

// Live2D 看板娘
include 'includes/live2d-widget.php';

// 修改点：路径加上 includes/
include 'includes/footer.php';
?>