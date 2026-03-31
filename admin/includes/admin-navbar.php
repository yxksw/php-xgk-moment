<?php
// 后台顶部导航栏组件
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    ['id' => 'admin', 'name' => '首页', 'icon' => '🏠', 'url' => 'admin.php'],
    ['id' => 'posts', 'name' => '说说', 'icon' => '📝', 'url' => 'posts.php'],
    ['id' => 'comments', 'name' => '评论', 'icon' => '💬', 'url' => 'comments.php'],
    ['id' => 'links', 'name' => '友链', 'icon' => '🔗', 'url' => 'links.php'],
    ['id' => 'announcements', 'name' => '公告', 'icon' => '📢', 'url' => 'announcements.php'],
    ['id' => 'mail_settings', 'name' => '邮件', 'icon' => '📧', 'url' => 'mail_settings.php'],
    ['id' => 'settings', 'name' => '设置', 'icon' => '⚙️', 'url' => 'settings.php'],
];
?>

<!-- 顶部导航栏 -->
<nav class="admin-navbar">
    <div class="navbar-container">
        <!-- Logo/品牌 -->
        <a href="admin.php" class="navbar-brand">
            <span class="brand-icon">🎯</span>
            <span class="brand-text">管理后台</span>
        </a>
        
        <!-- 桌面端导航菜单 -->
        <div class="navbar-menu">
            <?php foreach ($nav_items as $item): ?>
                <a href="<?php echo $item['url']; ?>" 
                   class="nav-link <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <span class="nav-text"><?php echo $item['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- 右侧操作区 -->
        <div class="navbar-actions">
            <!-- 明暗模式切换 -->
            <button class="theme-toggle-btn" id="navbarThemeToggle" title="切换主题">
                <span class="theme-icon-light">☀️</span>
                <span class="theme-icon-dark">🌙</span>
            </button>
            
            <!-- 返回前台 -->
            <a href="/" class="nav-action" title="返回前台">
                <span>🏠</span>
            </a>
            
            <!-- 退出登录 -->
            <a href="logout.php" class="nav-action logout" title="退出登录" onclick="return confirm('确定要退出登录吗？')">
                <span>🚪</span>
            </a>
            
            <!-- 移动端菜单按钮 -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="打开菜单">
                <span class="menu-line"></span>
                <span class="menu-line"></span>
                <span class="menu-line"></span>
            </button>
        </div>
    </div>
    
    <!-- 移动端侧边栏菜单 -->
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <span class="sidebar-title">菜单</span>
            <button class="sidebar-close" id="sidebarClose" aria-label="关闭菜单">✕</button>
        </div>
        <div class="sidebar-menu">
            <?php foreach ($nav_items as $item): ?>
                <a href="<?php echo $item['url']; ?>" 
                   class="sidebar-link <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
                    <span class="sidebar-text"><?php echo $item['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer">
            <a href="/" class="sidebar-link">
                <span class="sidebar-icon">🏠</span>
                <span class="sidebar-text">返回前台</span>
            </a>
            <a href="logout.php" class="sidebar-link logout" onclick="return confirm('确定要退出登录吗？')">
                <span class="sidebar-icon">🚪</span>
                <span class="sidebar-text">退出登录</span>
            </a>
        </div>
    </div>
    
    <!-- 遮罩层 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</nav>

<style>
/* 顶部导航栏样式 */
.admin-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    height: 60px;
}

.navbar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Logo/品牌 */
.navbar-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 600;
    font-size: 18px;
}

.brand-icon {
    font-size: 24px;
}

/* 导航菜单 */
.navbar-menu {
    display: flex;
    align-items: center;
    gap: 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    font-size: 14px;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background: #f5f5f5;
    color: #333;
}

.nav-link.active {
    background: #07c160;
    color: #fff;
}

.nav-icon {
    font-size: 16px;
}

/* 右侧操作区 */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.theme-toggle-btn,
.nav-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.theme-toggle-btn:hover,
.nav-action:hover {
    background: #f5f5f5;
}

.nav-action.logout:hover {
    background: #ffebee;
}

/* 主题图标切换 */
.theme-icon-dark {
    display: none;
}

body.dark-mode .theme-icon-light {
    display: none;
}

body.dark-mode .theme-icon-dark {
    display: inline;
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 36px;
    height: 36px;
    gap: 5px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 8px;
}

.menu-line {
    display: block;
    width: 20px;
    height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s ease;
}

/* 移动端侧边栏 */
.mobile-sidebar {
    position: fixed;
    top: 0;
    right: -280px;
    width: 280px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 1001;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.mobile-sidebar.open {
    right: 0;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
}

.sidebar-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.sidebar-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    padding: 4px;
}

.sidebar-menu {
    flex: 1;
    padding: 12px;
    overflow-y: auto;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    font-size: 15px;
    margin-bottom: 4px;
    transition: all 0.2s ease;
}

.sidebar-link:hover {
    background: #f5f5f5;
    color: #333;
}

.sidebar-link.active {
    background: #07c160;
    color: #fff;
}

.sidebar-icon {
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.sidebar-footer {
    padding: 12px;
    border-top: 1px solid #eee;
}

.sidebar-link.logout {
    color: #f44336;
}

.sidebar-link.logout:hover {
    background: #ffebee;
}

/* 遮罩层 */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sidebar-overlay.show {
    opacity: 1;
    visibility: visible;
}

/* 深色模式适配 */
body.dark-mode .admin-navbar {
    background: #1a1a1a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

body.dark-mode .navbar-brand {
    color: #e0e0e0;
}

body.dark-mode .nav-link {
    color: #b0b0b0;
}

body.dark-mode .nav-link:hover {
    background: #2d2d2d;
    color: #e0e0e0;
}

body.dark-mode .nav-link.active {
    background: #07c160;
    color: #fff;
}

body.dark-mode .theme-toggle-btn:hover,
body.dark-mode .nav-action:hover {
    background: #2d2d2d;
}

body.dark-mode .mobile-sidebar {
    background: #1a1a1a;
}

body.dark-mode .sidebar-header {
    border-color: #333;
}

body.dark-mode .sidebar-title {
    color: #e0e0e0;
}

body.dark-mode .sidebar-close {
    color: #888;
}

body.dark-mode .sidebar-link {
    color: #b0b0b0;
}

body.dark-mode .sidebar-link:hover {
    background: #2d2d2d;
    color: #e0e0e0;
}

body.dark-mode .sidebar-link.active {
    background: #07c160;
    color: #fff;
}

body.dark-mode .sidebar-footer {
    border-color: #333;
}

body.dark-mode .menu-line {
    background: #e0e0e0;
}

/* 响应式布局 */
@media screen and (max-width: 768px) {
    .navbar-menu {
        display: none;
    }
    
    .mobile-menu-btn {
        display: flex;
    }
    
    .navbar-actions .nav-action:not(.logout) {
        display: none;
    }
    
    .brand-text {
        display: none;
    }
}

@media screen and (max-width: 480px) {
    .navbar-container {
        padding: 0 12px;
    }
    
    .navbar-brand {
        font-size: 16px;
    }
}

/* 页面内容顶部留白 */
body {
    padding-top: 60px;
}
</style>

<script>
// 移动端菜单控制
(function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function openSidebar() {
        mobileSidebar.classList.add('open');
        sidebarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        mobileSidebar.classList.remove('open');
        sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openSidebar);
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // 主题切换
    const themeToggle = document.getElementById('navbarThemeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = document.body.classList.contains('dark-mode');
            const newTheme = isDark ? 'light' : 'dark';
            
            if (newTheme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
            
            localStorage.setItem('theme', newTheme);
        });
    }
    
    // 初始化主题
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
})();
</script>
