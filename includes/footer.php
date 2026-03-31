    <!-- 底部信息 -->
    <div class="footer" style="padding: 10px 15px; color: #999; font-size: 14px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; margin-top: 20px;">
        <span>Copyright © 2026 Powered by 异飨客</span>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/rss.xml" title="RSS 订阅" style="color:#ff6600;text-decoration:none;display:flex;align-items:center;gap:3px;" target="_blank">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.503 20.752c0 1.794-1.456 3.248-3.251 3.248-1.796 0-3.252-1.454-3.252-3.248 0-1.794 1.456-3.248 3.252-3.248 1.795.001 3.251 1.454 3.251 3.248zm-6.503-12.572v4.811c6.05.062 10.96 4.966 11.022 11.009h4.817c-.062-8.71-7.118-15.758-15.839-15.82zm0-3.368c10.58.046 19.152 8.594 19.183 19.188h4.817c-.03-13.231-10.755-23.954-24-24v4.812z"/></svg>
                RSS
            </a>
            <span class="visitors">❤️ <a href="https://xgk.pw" class="no-underline" style="color:#07c160;text-decoration:none;"> XGK </a></span>
        </div>
    </div>
</div> <!-- End .card -->

<!-- 回到顶部按钮 -->
<button id="backToTop" class="back-to-top" onclick="scrollToTop()" title="回到顶部">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 19V5M5 12l7-7 7 7"/>
    </svg>
</button>

<style>
/* 回到顶部按钮样式 */
.back-to-top {
    position: fixed;
    bottom: 30px;
    left: 30px;
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e0e0e0;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 9999;
}

.back-to-top:hover {
    background: #07c160;
    border-color: #07c160;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(7, 193, 96, 0.3);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
}

/* 深色模式下的回到顶部按钮 */
body.dark-mode .back-to-top {
    background: rgba(60, 60, 60, 0.95);
    border-color: #555;
    color: #e0e0e0;
    z-index: 9999;
}

body.dark-mode .back-to-top:hover {
    background: #07c160;
    border-color: #07c160;
    color: #fff;
}

/* 移动端适配 */
@media (max-width: 576px) {
    .back-to-top {
        bottom: 20px;
        right: 20px;
        width: 40px;
        height: 40px;
    }
}
</style>

<script>
// 回到顶部功能
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// 监听滚动事件，控制按钮显示/隐藏
document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (!backToTopBtn) {
        console.log('Back to top button not found');
        return;
    }
    
    // 滚动时检查位置
    function checkScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        if (scrollTop > 200) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    }
    
    // 节流函数，优化性能
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }
    
    // 使用节流优化滚动监听
    window.addEventListener('scroll', throttle(checkScroll, 100), { passive: true });
    
    // 初始检查（延迟一点确保页面完全加载）
    setTimeout(checkScroll, 100);
});
</script>

<!-- 图片预览脚本 (仅管理员可见部分需要，放在这里全局可用) -->
<script>
function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    const countDiv = document.getElementById('imageCount');
    if(!preview) return;
    preview.innerHTML = '';
    if (!input.files || input.files.length === 0) return;
    const max = 9;
    const files = Array.from(input.files).slice(0, max);
    files.forEach(file => {
        if (!file.type.match('image.*')) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '60px';
            img.style.height = '60px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
    if(countDiv) countDiv.textContent = `已选择 ${files.length} 张图片（最多 ${max} 张）`;
}
</script>

<!-- 评论系统脚本 -->
<script>
const loadedComments = new Set();

// 页面加载时自动加载所有评论
function initComments() {
    document.querySelectorAll('.post-comment-container').forEach(container => {
        const postId = container.getAttribute('data-post-id');
        if (postId) {
            loadCommentsData(postId);
        }
    });
}

// 加载评论数据（只加载评论列表，不显示表单）
function loadCommentsData(postId) {
    const commentList = document.getElementById('pcc-comment-list-' + postId);
    const container = document.getElementById('post-comment-container-' + postId);
    const likeList = document.querySelector('.pcc-like-list[data-post-id="' + postId + '"]');
    if (!commentList || !container) return;
    
    fetch('admin/get_comments.php?post_id=' + postId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            let hasComments = false;
            
            if (data.comments && data.comments.length > 0) {
                hasComments = true;
                data.comments.forEach(comment => {
                    const isAuthor = comment.is_author || false;
                    html += `
                    <div class="pcc-comment-item" data-comment-id="${comment.id}">
                        <a href="javascript:void(0);" data-name="${comment.name}">${escapeHtml(comment.name)}</a>
                        ${isAuthor ? '<span class="author-badge">作者</span>' : ''}
                        <span>:</span>
                        <span class="pcc-comment-content" onclick="showReplyForm(${postId}, '${escapeHtml(comment.name)}')">${comment.content}</span>
                    </div>`;
                });
                commentList.innerHTML = html;
            } else {
                commentList.innerHTML = '';
            }
            
            // 使用统一的函数更新容器显示状态
            if (typeof updateCommentContainer === 'function') {
                updateCommentContainer(postId);
            } else {
                // 备用逻辑：直接检查并更新
                const hasLikes = likeList && likeList.style.display !== 'none';
                if (hasComments || hasLikes) {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
            }
        })
        .catch(() => {
            // 加载失败时隐藏容器
            container.style.display = 'none';
        });
}

// 显示评论表单
function showCommentForm(postId) {
    const formWrapper = document.getElementById('comment-form-wrapper-' + postId);
    if (!formWrapper) return;
    
    // 确保容器显示
    const container = document.getElementById('post-comment-container-' + postId);
    if (container) {
        container.style.display = 'block';
    }
    
    // 显示表单
    formWrapper.style.display = 'block';
    
    // 恢复用户信息
    const userInfo = getUserInfo();
    const nameInput = formWrapper.querySelector('input[name="name"]');
    const emailInput = formWrapper.querySelector('input[name="email"]');
    if (userInfo.name && nameInput) nameInput.value = userInfo.name;
    if (userInfo.email && emailInput) emailInput.value = userInfo.email;
    
    // 聚焦到内容输入框
    const textarea = formWrapper.querySelector('textarea[name="content"]');
    if (textarea) {
        textarea.focus();
    }
}

// 隐藏评论表单
function hideCommentForm(postId) {
    const formWrapper = document.getElementById('comment-form-wrapper-' + postId);
    if (formWrapper) {
        formWrapper.style.display = 'none';
    }
    
    // 使用统一的函数更新容器显示状态
    if (typeof updateCommentContainer === 'function') {
        updateCommentContainer(postId);
    }
}

// 显示回复表单（点击评论内容时）
function showReplyForm(postId, authorName) {
    showCommentForm(postId);
    const formWrapper = document.getElementById('comment-form-wrapper-' + postId);
    if (formWrapper) {
        const textarea = formWrapper.querySelector('textarea[name="content"]');
        if (textarea) {
            textarea.value = '回复 @' + authorName + ': ';
            textarea.focus();
        }
    }
}

// HTML转义
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 页面加载完成后初始化评论
document.addEventListener('DOMContentLoaded', initComments);

function submitComment(event, postId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('comment-error-' + postId);
    const button = form.querySelector('button[type="submit"]');
    errorDiv.style.display = 'none';
    button.disabled = true;
    button.textContent = '发送中...';
    fetch('admin/submit_comment.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) { window.location.reload(); } 
        else { errorDiv.textContent = data.message || '提交失败'; errorDiv.style.display = 'block'; }
        button.disabled = false;
        button.textContent = '发送';
    })
    .catch(() => { errorDiv.textContent = '网络错误'; errorDiv.style.display = 'block'; button.disabled = false; button.textContent = '发送'; });
    return false;
}

function closeCommentForm(postId) {
    const formWrapper = document.querySelector('#comment-container-' + postId + ' .comment-form-wrapper');
    if (formWrapper) formWrapper.style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.comment-container')) return;
    if (e.target.closest('.comment-name') || e.target.closest('form') || e.target.closest('button') || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
    const container = e.target.closest('.comment-container');
    const postId = container.id.replace('comment-container-', '');
    closeCommentForm(postId);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-name')) {
        const name = e.target.getAttribute('data-name');
        const postContainer = e.target.closest('.post-item');
        if (!postContainer) return;
        const postId = postContainer.id.replace('post-', '');
        const form = document.getElementById('comment-form-' + postId);
        const textarea = form ? form.querySelector('textarea[name="content"]') : null;
        if (!form || !textarea) {
            loadComments(postId);
            setTimeout(() => {
                const newTextarea = document.querySelector(`#comment-form-${postId} textarea[name="content"]`);
                if (newTextarea) { newTextarea.focus(); newTextarea.value = `@${name} `; }
            }, 300);
        } else {
            form.style.display = 'block';
            textarea.focus();
            textarea.value = textarea.value.trim() ? textarea.value + `\n@${name} ` : `@${name} `;
        }
    }
});

// Delete post function
function deletePost(postId) {
    if (!confirm('确定要删除这条说说吗？此操作不可恢复。')) {
        return;
    }
    
    fetch('admin/delete_post.php?id=' + postId, {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the post element from DOM
            const postElement = document.getElementById('post-' + postId);
            if (postElement) {
                postElement.style.opacity = '0';
                postElement.style.transform = 'translateX(-100%)';
                postElement.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    postElement.remove();
                    // Check if no posts left
                    const posts = document.querySelectorAll('.post-item');
                    if (posts.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            alert(data.message || '删除失败');
        }
    })
    .catch(() => {
        alert('网络错误，请稍后重试');
    });
}
</script>

<!-- 导航栏与弹窗控制脚本 (含滚动条修复) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('topNavbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.add('active');
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        if (scrollbarWidth > 0) {
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = scrollbarWidth + 'px';
        } else {
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    };

   
        // --- 3. 绑定图标点击事件 ---
    
    const btnLogin = document.getElementById('btnLogin');
    const btnGroups = document.getElementById('btnGroups');
    const btnSettings = document.getElementById('btnSettings');
    const btnPost = document.getElementById('btnPost'); // 新增：获取发表按钮

    if (btnLogin) {
        btnLogin.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('loginModal');
        });
    }

    if (btnGroups) {
        btnGroups.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('groupModal');
        });
    }

    if (btnSettings) {
        btnSettings.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('settingsModal');
        });
    }

    // 新增：绑定发表说说按钮
    if (btnPost) {
        btnPost.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('postModal');
        });
    }
    
    
    
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal.id);
        });
    });
});
</script>

<script>
// 打开灯箱
function openLightbox(src) {
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    
    lightboxImg.src = src;
    lightbox.classList.add('active');
    
    // 禁止背景页面滚动
    document.body.style.overflow = 'hidden';
}

// 关闭灯箱
function closeLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    lightbox.classList.remove('active');
    
    // 恢复背景页面滚动
    document.body.style.overflow = '';
    
    // 可选：延迟清空 src 以防闪烁
    setTimeout(() => {
        document.getElementById('lightboxImg').src = '';
    }, 300);
}

// 监听 ESC 键关闭
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeLightbox();
    }
});

</script>


<script>
    // 明暗模式切换功能
    (function() {
        const themeToggle = document.getElementById('themeToggle');
        const navLightIcon = document.getElementById('navLightIcon');
        const navDarkIcon = document.getElementById('navDarkIcon');
        const body = document.body;
        
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        function applyTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('dark-mode');
                if (navLightIcon) navLightIcon.style.display = 'none';
                if (navDarkIcon) navDarkIcon.style.display = 'block';
            } else {
                body.classList.remove('dark-mode');
                if (navLightIcon) navLightIcon.style.display = 'block';
                if (navDarkIcon) navDarkIcon.style.display = 'none';
            }
        }
        
        applyTheme(currentTheme);
        
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const isDark = body.classList.contains('dark-mode');
                const newTheme = isDark ? 'light' : 'dark';
                
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }
    })();
</script>

<!-- 评论系统样式 -->
<style>
/* 点赞和评论容器 - 完全参考 icefox */
.post-comment-container {
    background-color: #f0f0f0;
    margin-top: 10px;
    padding: 10px;
    margin-left: 40px; /* 与文章内容对齐，不到头像位置 */
}

body.dark-mode .post-comment-container {
    background-color: #181818;
}

/* 点赞列表 */
.pcc-like-list {
    font-size: 14px;
    color: #576b95;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pcc-like-summary {
    display: flex;
    align-items: center;
    gap: 5px;
}

.pcc-like-list .like-icon {
    flex-shrink: 0;
    color: #ff6b6b;
    width: 16px;
    height: 16px;
}

.pcc-like-list .like-users-text {
    color: #576b95;
}

body.dark-mode .pcc-like-list .like-users-text {
    color: #7d8fb3;
}

/* 评论列表 */
.pcc-comment-list {
    font-size: 14px;
    line-height: 1.6;
    margin-top: 8px;
}

.pcc-comment-item {
    margin-bottom: 4px;
    word-wrap: break-word;
}

.pcc-comment-item:last-child {
    margin-bottom: 0;
}

/* 评论作者链接 - icefox 样式 */
.pcc-comment-list > .pcc-comment-item a {
    font-size: 14px;
    color: #576b95;
    text-decoration: none;
}

body.dark-mode .pcc-comment-list > .pcc-comment-item a {
    color: #7d8fb3;
}

.pcc-comment-list > .pcc-comment-item span {
    font-size: 14px;
    color: #333;
}

body.dark-mode .pcc-comment-list > .pcc-comment-item span {
    color: #e0e0e0;
}

/* 作者标识 */
.pcc-comment-item .author-badge {
    font-size: 11px;
    padding: 2px 6px;
    background: #ff6b6b;
    color: white;
    border-radius: 3px;
    margin: 0 4px;
    font-weight: 500;
}

body.dark-mode .pcc-comment-item .author-badge {
    background: #ff6b6b;
}

/* 评论内容 */
.pcc-comment-content {
    color: #333;
    cursor: pointer;
}

body.dark-mode .pcc-comment-content {
    color: #e0e0e0;
}

.pcc-comment-content:hover {
    color: #07c160;
}

/* 评论表单 */
.comment-form-wrapper {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #ddd;
}

body.dark-mode .comment-form-wrapper {
    border-top-color: #444;
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.comment-form .form-row {
    display: flex;
    gap: 10px;
}

.comment-form input[type="text"],
.comment-form input[type="email"],
.comment-form textarea {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: #fff;
    color: #333;
    box-sizing: border-box;
}

body.dark-mode .comment-form input[type="text"],
body.dark-mode .comment-form input[type="email"],
body.dark-mode .comment-form textarea {
    background: #3d3d3d;
    border-color: #555;
    color: #e0e0e0;
}

.comment-form input[type="text"],
.comment-form input[type="email"] {
    flex: 1;
}

.comment-form textarea {
    width: 100%;
    resize: vertical;
    min-height: 60px;
}

.comment-form input:focus,
.comment-form textarea:focus {
    outline: none;
    border-color: #07c160;
}

/* 表单按钮 */
.comment-form .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.comment-form .btn-cancel,
.comment-form .btn-submit {
    padding: 6px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    border: none;
}

.comment-form .btn-cancel {
    background: #e0e0e0;
    color: #666;
}

body.dark-mode .comment-form .btn-cancel {
    background: #444;
    color: #bbb;
}

.comment-form .btn-submit {
    background: #07c160;
    color: white;
}

.comment-form .btn-submit:hover {
    background: #06b359;
}

/* 移动端适配 */
@media (max-width: 576px) {
    .comment-form .form-row {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

</body>
</html>