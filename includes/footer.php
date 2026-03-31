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
    right: 30px;
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e0e0e0;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    z-index: 999;
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
    background: rgba(60, 60, 60, 0.9);
    border-color: #555;
    color: #e0e0e0;
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
(function() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (!backToTopBtn) return;
    
    // 滚动时检查位置
    function checkScroll() {
        if (window.pageYOffset > 300) {
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
    window.addEventListener('scroll', throttle(checkScroll, 100));
    
    // 初始检查
    checkScroll();
})();
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

function loadComments(postId) {
    const container = document.getElementById('comment-container-' + postId);
    if (!container) return;
    if (loadedComments.has(postId)) {
        const form = container.querySelector('form');
        if (form) { form.style.display = 'block'; form.querySelector('input[name="name"]').focus(); }
        return;
    }
    container.innerHTML = '<div style="font-size:13px;color:#999;">加载中...</div>';
    fetch('admin/get_comments.php?post_id=' + postId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.comments && data.comments.length > 0) {
                data.comments.forEach(comment => {
                    html += `<div style="display:flex;align-items:flex-start;margin:6px 0;">
                        <img src="${comment.avatar}" style="width:20px;height:20px;border-radius:50%;margin-right:8px;flex-shrink:0;" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22 viewBox=%220 0 24 24%22 fill=%22%23ccc%22><circle cx=%2212%22 cy=%2212%22 r=%2210%22/></svg>'">
                        <div style="font-size:13px;line-height:1.4;color:#333;">
                            <span class="comment-name" data-name="${comment.name}" style="cursor:pointer;color:#07c160;font-weight:bold;">${comment.name}</span>: ${comment.content}
                        </div>
                    </div>`;
                });
            }
            html += `<form id="comment-form-${postId}" onsubmit="return submitComment(event, ${postId})" style="display:block;margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
                <input type="hidden" name="post_id" value="${postId}">
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <input type="text" name="name" placeholder="名字" maxlength="50" required style="width: 110px;flex:1;padding:6px 8px;font-size:13px;border:1px solid #ddd;border-radius:3px;">
                    <input type="email" name="email" placeholder="QQ邮箱（可选）" style="flex:1;padding:6px 8px;font-size:13px;border:1px solid #ddd;border-radius:3px;">
                </div>
                <textarea name="content" placeholder="写下你的想法..." rows="2" maxlength="500" required style="width:95%;padding:6px 8px;font-size:13px;border:1px solid #ddd;border-radius:3px;margin-bottom:8px;"></textarea>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" onclick="closeCommentForm(${postId})" style="background:none;border:1px solid #ddd;color:#666;padding:5px 12px;border-radius:3px;font-size:13px;cursor:pointer;">取消评论</button>
                    <button type="submit" style="background:#07c160;color:white;border:none;padding:5px 12px;border-radius:3px;font-size:13px;cursor:pointer;">发送</button>
                </div>
            </form>
            <div id="comment-error-${postId}" style="color:#e74c3c;font-size:12px;margin-top:8px;display:none;"></div>`;
            container.innerHTML = html;
            loadedComments.add(postId);
            const input = document.querySelector(`#comment-form-${postId} input[name="name"]`);
            if(input) input.focus();
        })
        .catch(() => { container.innerHTML = '<div style="color:#e74c3c;font-size:13px;">加载失败</div>'; });
}

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
    const form = document.getElementById('comment-form-' + postId);
    if (form) form.style.display = 'none';
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

</body>
</html>