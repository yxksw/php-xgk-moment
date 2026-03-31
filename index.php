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

$result = $conn->query("SELECT * FROM posts ORDER BY is_pinned DESC, created_at DESC LIMIT $limit OFFSET $offset");

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
        <?php 
            $is_pinned = intval($post['is_pinned'] ?? 0);
            $is_marked = intval($post['is_marked'] ?? 0);
        ?>
        <div class="post-item <?php echo $is_pinned > 0 ? 'post-pinned post-pinned-' . $is_pinned : ''; ?> <?php echo $is_marked > 0 ? 'post-marked post-marked-' . $is_marked : ''; ?>" id="post-<?php echo $post['id']; ?>" data-post-id="<?php echo $post['id']; ?>">
            <div class="post-header">
                <img src="<?php echo htmlspecialchars($friend_avatar ?: 'https://via.placeholder.com/32'); ?>">
                <div class="post-author-wrapper">
                    <div class="post-author">
                        <?php echo htmlspecialchars($friend_name); ?>
                    </div>
                    <div class="post-tags">
                        <?php if ($is_pinned > 0): ?>
                            <span class="tag tag-pinned" title="置顶">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L12 22M12 2L6 8M12 2L18 8"/>
                                </svg>
                                置顶
                            </span>
                        <?php endif; ?>
                        <?php if ($is_marked > 0): ?>
                            <span class="tag tag-marked">广告</span>
                        <?php endif; ?>
                    </div>
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
            
            <a href="https://www.bing.com/search?q=<?php echo urlencode($post['music']); ?>" target="_blank" class="dz" style="text-decoration:none;cursor:pointer;" title="在 Bing 搜索此位置">
                <?php echo nl2br(htmlspecialchars($post['music'])); ?> 
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-left:4px;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                </svg>
            </a>
                
            <div class="plan">
                <span><?php echo date('Y年m月d日', strtotime($post['created_at'])); ?></span>
                <div class="post-actions">
                    <?php if ($is_logged_in): ?>
                        <a href="includes/edit-post.php?id=<?php echo $post['id']; ?>" class="action-btn edit-btn" title="编辑" style="background:transparent !important;border:none !important;box-shadow:none !important;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <a href="javascript:void(0);" class="action-btn delete-btn" title="删除" onclick="deletePost(<?php echo $post['id']; ?>)" style="background:transparent !important;border:none !important;box-shadow:none !important;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <!-- 点赞按钮 -->
                    <a href="javascript:void(0);" class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>" title="点赞" style="color:#ff6b6b;margin-right:10px;float:right;background:transparent !important;border:none !important;box-shadow:none !important;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="like-icon">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <span class="like-count" style="font-size:12px;margin-left:2px;">0</span>
                    </a>
                    <a href="javascript:void(0);" class="comment-menu-btn" data-post-id="<?php echo $post['id']; ?>" style="color:#07c160;border-radius: 5px; font-size:16px; text-decoration:none; float:right;" onclick="showCommentForm(<?php echo $post['id']; ?>)">
                        <svg width="40" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="5" cy="12" r="2" fill="currentColor"/>
  <circle cx="12" cy="12" r="2" fill="currentColor"/>
  <circle cx="19" cy="12" r="2" fill="currentColor"/>
</svg>
                    </a>
                </div>
            </div>
            <!-- 点赞和评论区域 -->
            <div class="post-comment-container" id="post-comment-container-<?php echo $post['id']; ?>" data-post-id="<?php echo $post['id']; ?>" style="display:none;">
                <!-- 点赞列表 -->
                <div class="pcc-like-list" data-post-id="<?php echo $post['id']; ?>" style="display:none;">
                    <div class="pcc-like-summary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" height="16" width="16" stroke-width="1.5" stroke="currentColor" class="like-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                        <span class="like-users-text"></span>
                    </div>
                </div>
                <!-- 评论列表 -->
                <div class="pcc-comment-list" id="pcc-comment-list-<?php echo $post['id']; ?>">
                    <!-- 评论将通过 JS 加载 -->
                </div>
                <!-- 评论表单（默认隐藏，点击评论按钮显示） -->
                <div class="comment-form-wrapper" id="comment-form-wrapper-<?php echo $post['id']; ?>" style="display:none;">
                    <form class="comment-form" id="comment-form-<?php echo $post['id']; ?>" onsubmit="return submitComment(event, <?php echo $post['id']; ?>)">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="form-row">
                            <input type="text" name="name" placeholder="昵称" maxlength="50" required>
                            <input type="email" name="email" placeholder="邮箱（可选）">
                        </div>
                        <textarea name="content" placeholder="写下你的想法..." rows="2" maxlength="500" required></textarea>
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="hideCommentForm(<?php echo $post['id']; ?>)">取消</button>
                            <button type="submit" class="btn-submit">发送</button>
                        </div>
                    </form>
                </div>
            </div>
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
// // 音乐播放器
// include 'includes/music-player.php';

// Live2D 看板娘
include 'includes/live2d-widget.php';

// 修改点：路径加上 includes/
include 'includes/footer.php';
?>

<!-- 点赞功能 JavaScript -->
<script>
// 生成或获取匿名用户ID
function getAnonymousId() {
    let anonymousId = localStorage.getItem('anonymous_id');
    if (!anonymousId) {
        anonymousId = 'anon_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('anonymous_id', anonymousId);
    }
    return anonymousId;
}

// 获取用户信息
function getUserInfo() {
    let userInfo = localStorage.getItem('comment_user_info');
    if (userInfo) {
        return JSON.parse(userInfo);
    }
    return { name: '', email: '' };
}

// 加载点赞数据
function loadLikes(postId) {
    const anonymousId = getAnonymousId();
    fetch(`api/like.php?do=getLikes&post_id=${postId}&anonymous_id=${anonymousId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLikeUI(postId, data.likes, data.isLiked, data.likeUsers);
            }
        })
        .catch(error => console.error('加载点赞数据失败:', error));
}

// 更新点赞UI
function updateLikeUI(postId, likes, isLiked, likeUsers) {
    const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
    const likeList = document.querySelector(`.pcc-like-list[data-post-id="${postId}"]`);
    const container = document.getElementById('post-comment-container-' + postId);
    const commentList = document.getElementById('pcc-comment-list-' + postId);
    const likeCount = likeBtn.querySelector('.like-count');
    const likeIcon = likeBtn.querySelector('.like-icon');

    // 更新点赞数
    likeCount.textContent = likes;

    // 更新点赞状态样式
    if (isLiked) {
        likeIcon.setAttribute('fill', '#ff6b6b');
        likeBtn.style.color = '#ff6b6b';
    } else {
        likeIcon.setAttribute('fill', 'none');
        likeBtn.style.color = '#999';
    }

    // 更新点赞用户列表（在评论容器内）
    if (likeList) {
        const likeUsersText = likeList.querySelector('.like-users-text');
        if (likeUsers && likeUsers.length > 0) {
            const userNames = likeUsers.map(u => u.author).join('、');
            likeUsersText.textContent = userNames + (likes > likeUsers.length ? ` 等${likes}人` : '');
            likeList.style.display = 'flex';
        } else {
            likeList.style.display = 'none';
        }
    }
    
    // 更新容器显示状态
    updateCommentContainer(postId);
}

// 切换点赞
function toggleLike(postId) {
    const anonymousId = getAnonymousId();
    const userInfo = getUserInfo();

    fetch(`api/like.php?do=like&post_id=${postId}&anonymous_id=${anonymousId}&author=${encodeURIComponent(userInfo.name)}&email=${encodeURIComponent(userInfo.email)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLikeUI(postId, data.likes, data.isLiked, data.likeUsers);

                // 显示提示
                const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
                const tooltip = document.createElement('span');
                tooltip.textContent = data.action === 'liked' ? '已点赞' : '已取消';
                tooltip.style.cssText = 'position:absolute;background:#333;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;z-index:1000;white-space:nowrap;';
                tooltip.className = 'like-tooltip';
                likeBtn.style.position = 'relative';
                likeBtn.appendChild(tooltip);

                setTimeout(() => {
                    tooltip.remove();
                }, 1500);
            }
        })
        .catch(error => console.error('点赞操作失败:', error));
}

// 更新评论容器显示状态（统一入口）
function updateCommentContainer(postId) {
    const container = document.getElementById('post-comment-container-' + postId);
    const commentList = document.getElementById('pcc-comment-list-' + postId);
    const likeList = document.querySelector('.pcc-like-list[data-post-id="' + postId + '"]');
    
    if (!container) return;
    
    const hasComments = commentList && commentList.children.length > 0;
    const hasLikes = likeList && likeList.style.display !== 'none';
    
    if (hasComments || hasLikes) {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

// 初始化点赞功能
document.addEventListener('DOMContentLoaded', function() {
    // 为所有点赞按钮绑定事件
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            toggleLike(postId);
        });
    });

    // 加载所有说说的点赞数据
    document.querySelectorAll('.like-btn').forEach(btn => {
        const postId = btn.getAttribute('data-post-id');
        loadLikes(postId);
    });
});
</script>