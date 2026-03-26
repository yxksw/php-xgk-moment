    <!-- 底部信息 -->
    <div class="footer" style="padding: 10px 15px; color: #999; font-size: 14px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; margin-top: 20px;">
        <span>Copyright © 2026 Powered by 异飨客</span>
        <div>
            <span class="visitors">❤️ <a href="https://xgk.pw" class="no-underline" style="color:#07c160;text-decoration:none;"> XGK </a></span>
        </div>
    </div>
</div> <!-- End .card -->

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


</body>
</html>