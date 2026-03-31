<?php
// 【关键 1】必须在任何 HTML 输出前开启 Session 和处理请求
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 引入配置 (根据你的实际路径调整，假设 header.php 在根目录或包含 config 的地方)
// 如果 header.php 是被 index.php include 的，确保 index.php 已经引入了 config.php
// 这里为了保险，尝试引入一次，如果已引入则忽略
if (!isset($conn) && file_exists('config.php')) {
    include 'config.php';
}

// 【关键 2】处理 AJAX 登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json'); // 返回 JSON
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // TODO: 在这里替换成真实的数据库验证逻辑
    // 示例：硬编码 admin / admin
    $valid_user = ($username === 'admin' && $password === 'admin');
    
    if ($valid_user) {
        $_SESSION['admin'] = true;
        // 模拟用户信息，实际应从数据库读取
        $_SESSION['user_info'] = [
            'nickname' => '异飨客',
            'email' => 'yxksw@foxmail.com',
            'website' => 'https://moment.050815.xyz',
            'avatar' => 'https://cn.cravatar.com/avatar/56cd72b5460ecaa08ddffea9562f5629?size=512' 
        ];
        echo json_encode(['success' => true, 'message' => '登录成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
    }
    exit; // 重要：JSON 响应后必须退出，不再输出下面的 HTML
}

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$is_logged_in = isset($_SESSION['admin']);
$user_info = $_SESSION['user_info'] ?? [];
?>




<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($site_title); ?></title>
    
    <!-- RSS 订阅链接 -->
    <link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars($site_title); ?> - RSS 订阅" href="/rss.xml" />
    
    <link rel="stylesheet" href="https://font.sec.miui.com/font/css?family=MiSans:400,700:MiSans" />
    <style>
        body {
            background-color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding: 0px;
            margin: 0px;
            overflow-y: scroll; /* 关键修复：强制保留滚动条位置防止跳动 */
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* 深色模式基础样式 */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-mode .card {
            background-color: #1a1a1a;
        }
        body.dark-mode .post-item {
            background: #2d2d2d;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
        }
        body.dark-mode .post-author {
            color: #e0e0e0;
        }
        body.dark-mode .post-content {
            color: #e0e0e0;
        }
        body.dark-mode .plan {
            color: #888;
            border-color: #444;
        }
        body.dark-mode .dz {
            color: #6ab3ff;
        }
        body.dark-mode .signature {
            background-color: #2d2d2d;
            color: #b0b0b0;
        }
        body.dark-mode .top-navbar.scrolled {
            background-color: #2d2d2d;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        body.dark-mode .modal-content {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        body.dark-mode .modal-header {
            border-color: #444;
        }
        body.dark-mode .modal-header h3 {
            color: #e0e0e0;
        }
        body.dark-mode .close-btn {
            color: #888;
        }
        body.dark-mode .close-btn:hover {
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
        body.dark-mode .list-item {
            border-color: #444;
        }
        body.dark-mode .list-item:hover {
            background-color: #3d3d3d;
        }
        body.dark-mode .item-title {
            color: #e0e0e0;
        }
        body.dark-mode .item-sub {
            color: #888;
        }
        body.dark-mode .setting-item {
            color: #e0e0e0;
        }
        body.dark-mode .info-row {
            background: #3d3d3d;
        }
        body.dark-mode .info-label {
            color: #888;
        }
        body.dark-mode .info-value {
            color: #e0e0e0;
        }
        body.dark-mode .footer {
            background-color: #2d2d2d;
            border-color: #444;
            color: #888;
        }
        body.dark-mode .pagination a,
        body.dark-mode .pagination span {
            background: #2d2d2d;
            border-color: #444;
            color: #6ab3ff;
        }
        body.dark-mode .pagination .disabled {
            color: #666;
            border-color: #333;
        }
        body.dark-mode .pagination .current {
            background: #07c160;
            color: white;
        }
        body.dark-mode .footer a[href*="rss"] {
            color: #ff944d !important;
        }
        /* 深色模式下导航栏图标颜色 */
        body.dark-mode .top-navbar.scrolled .nav-icon {
            color: #e0e0e0;
        }

        /* 评论框深色模式适配 */
        body.dark-mode .comment-container input[type="text"],
        body.dark-mode .comment-container input[type="email"],
        body.dark-mode .comment-container textarea {
            background: #3d3d3d !important;
            color: #e0e0e0 !important;
            border-color: #555 !important;
        }
        body.dark-mode .comment-container input::placeholder,
        body.dark-mode .comment-container textarea::placeholder {
            color: #888 !important;
        }
        body.dark-mode .comment-container button[type="button"] {
            background: #3d3d3d !important;
            color: #b0b0b0 !important;
            border-color: #555 !important;
        }
        body.dark-mode .comment-container form {
            border-color: #444 !important;
        }
        /* 评论内容文字颜色 */
        body.dark-mode .comment-container div[style*="color:#333"] {
            color: #e0e0e0 !important;
        }
        body.dark-mode .comment-container > div {
            color: #e0e0e0 !important;
        }
        
        * {font-family: MiSans}
        
        /* 自定义鼠标样式 */
        body {
            cursor: url('/default.cur'), auto;
        }
        
        /* 链接和按钮的鼠标样式 */
        a, button, .nav-icon, .music-cover, .theme-toggle, #waifu-tool span {
            cursor: url('/default.cur'), pointer;
        }
        
        /* 输入框的鼠标样式 */
        input, textarea, .form-control {
            cursor: url('/default.cur'), text;
        }
        
        .card { max-width: 576px; width: 100%; margin: 0 auto; padding: 0px; }
        .cover-section { position: relative; width: 100%; height: 320px; overflow: hidden; }
        .cover-section img { width: 100%; height: 100%; object-fit: cover; }
        .author-info { position: absolute; bottom: 10px; right: 20px; display: flex; align-items: flex-end; gap: 10px; }
        .author-name { color: #fff; font-size: 18px; font-weight: bold; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
        .author-avatar { width: 50px; height: 50px; border-radius: 2px; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.3); overflow: hidden; }
        .author-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .signature { margin-right: 10px; text-align: right; color: #999; font-size: 16px; padding: 10px 0; background-color: #fff; }
        .post-form { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: <?php echo $is_logged_in ? 'block' : 'none'; ?>; }
        .post-item { background: #fff; padding: 15px; margin-bottom: 0px; border-radius: 0px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .post-header { display: flex; align-items: center; margin-bottom: 10px; }
        .post-header img { width: 32px; height: 32px; border-radius: 2px; margin-right: 10px; }
        .post-author { font-weight: bold; font-size: 18px; margin-bottom: 14px;}
        .post-time { color: #999; font-size: 12px; }
        .post-content { line-height: 1.6; margin: 10px 0; margin-left: 40px; }
        .post-images-grid { display: grid; gap: 5px; margin: 10px 0; margin-left: 40px; }
        .grid-1 { grid-template-columns: 1fr;  justify-items: start; /* 关键修改：从 center 改为 start，让内容靠左对齐 */}
        .grid-1 img { max-width: 50%; height: auto; border-radius: 6px;}
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3, .grid-4, .grid-5, .grid-6, .grid-7, .grid-8, .grid-9 { grid-template-columns: repeat(3, 1fr); }
        .post-images-grid img {width: 100%;           /* 宽度自适应父容器 */
    height: auto;
    aspect-ratio: 4 / 4;   /* 强制保持 4:4 比例，浏览器会自动计算高度 */
    object-fit: cover;     /* 关键：裁剪图片以填满区域，不变形 */
    border-radius: 4px;
    display: block; }
        .plan { padding: 1px; color: #999; font-size: 12px; display: flex; margin-left: 40px; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0, 0, 0, 0.1); }
        .dz{ margin: 10px 0;margin-left: 40px;font-size: 12px;color:#576b95;}
        
        /* 置顶和广告标签样式 - 参考图样式 */
        .post-author-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .post-author {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 0;
        }
        
        .post-tags {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.2;
        }
        
        /* 置顶标签 - 绿色背景 */
        .tag-pinned {
            background-color: #07c160;
            color: #fff;
        }
        
        /* 标记标签 - 橙色背景 */
        .tag-marked {
            background-color: #ff9500;
            color: #fff;
        }
        
        /* 深色模式下的标签样式 */
        body.dark-mode .tag-pinned {
            background-color: #07c160;
            color: #fff;
        }
        
        body.dark-mode .tag-marked {
            background-color: #cc7a00;
            color: #fff;
        }
        
        /* 搜索弹窗样式 */
        .search-modal-content {
            max-width: 500px;
            width: 90%;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            border-color: #07c160;
        }
        
        .search-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #07c160;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }
        
        .search-btn:hover {
            background: #06b359;
        }
        
        .search-results {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .search-placeholder {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-size: 14px;
        }
        
        .search-result-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-result-item:hover {
            background: #f9f9f9;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-content {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .search-result-date {
            font-size: 12px;
            color: #999;
        }
        
        .search-no-results {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-size: 14px;
        }
        
        .search-loading {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-size: 14px;
        }
        
        /* 深色模式下的搜索样式 */
        body.dark-mode .search-input {
            background: #3d3d3d;
            border-color: #555;
            color: #e0e0e0;
        }
        
        body.dark-mode .search-input::placeholder {
            color: #888;
        }
        
        body.dark-mode .search-result-item {
            border-bottom-color: #444;
        }
        
        body.dark-mode .search-result-item:hover {
            background: #3d3d3d;
        }
        
        body.dark-mode .search-result-content {
            color: #e0e0e0;
        }
        
        body.dark-mode .search-placeholder,
        body.dark-mode .search-no-results,
        body.dark-mode .search-loading {
            color: #888;
        }
        
        /* 说说操作按钮样式 */
        .post-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .edit-btn {
            color: #07c160;
            background: rgba(7, 193, 96, 0.1);
        }
        
        .edit-btn:hover {
            background: #07c160;
            color: white;
        }
        
        .delete-btn {
            color: #ff4d4f;
            background: rgba(255, 77, 79, 0.1);
        }
        
        .delete-btn:hover {
            background: #ff4d4f;
            color: white;
        }
        
        /* 深色模式下的操作按钮 */
        body.dark-mode .edit-btn {
            color: #07c160;
            background: rgba(7, 193, 96, 0.2);
        }
        
        body.dark-mode .edit-btn:hover {
            background: #07c160;
            color: white;
        }
        
        body.dark-mode .delete-btn {
            color: #ff4d4f;
            background: rgba(255, 77, 79, 0.2);
        }
        
        body.dark-mode .delete-btn:hover {
            background: #ff4d4f;
            color: white;
        }
        
        .alert { padding: 8px; border-radius: 4px; margin: 10px 0; font-size: 13px; text-align: center; }
        .alert-success { background: #e6f4ea; color: #07c160; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; padding: 10px 0; align-items: center; }
        .pagination a, .pagination span { padding: 6px 12px; text-decoration: none; border: 1px solid #ddd; border-radius: 4px; color: #07c160; font-size: 14px; }
        .pagination .disabled { color: #ccc; pointer-events: none; border-color: #eee; }
        .pagination .current { background: #07c160; color: white; border-color: #07c160; }
        
        /* 导航栏 */
        .top-navbar { position: fixed; top: 0; left: 50%; transform: translateX(-50%); max-width: 576px; width: 100%; height: 50px; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; box-sizing: border-box; z-index: 1000; background-color: transparent; transition: background-color 0.3s ease, box-shadow 0.3s ease; border-radius: 0 0 8px 8px; }
        .top-navbar.scrolled { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .nav-left, .nav-right { display: flex; gap: 20px; align-items: center; }
        .nav-icon { width: 24px; height: 24px; color: #fff; cursor: pointer; transition: color 0.3s, transform 0.2s; filter: drop-shadow(0 0 2px rgba(0,0,0,0.6)); }
        .top-navbar.scrolled .nav-icon { color: #333; filter: none; }
        .nav-icon:hover { transform: scale(1.1); }
        .nav-icon a { text-decoration: none; display: block; } /* 确保链接内的图标也生效 */
        
        /* 音乐播放器样式 */
        .music-player {
            width: 120px;
            height: 40px;
            display: flex;
            align-items: center;
        }
        
        .sh-main-top-mu {
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 5px;
            margin-right: 5px;
            cursor: pointer;
        }
        
        .sh-main-top-g-container {
            width: 60px;
            height: 2px;
            background: rgb(215 215 215 / 75%);
            margin-left: 2px;
            margin-right: 2px;
            border-radius: 4px;
        }
        
        .sh-main-top-mu-bgmq {
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 5px;
            margin-right: 5px;
            cursor: pointer;
        }
        
        /* 音乐动画样式 */
        .shaft-load2 {
            width: 100%;
            height: 2px;
            overflow: hidden;
        }
        
        .shaft-load2 > div {
            float: left;
            height: 100%;
            width: 5px;
            margin-right: 1px;
            display: inline-block;
            background: #959595;
            -webkit-animation: loading2 1.5s infinite ease-in-out;
            -moz-animation: loading2 1.5s infinite ease-in-out;
            -o-animation: loading2 1.5s infinite ease-in-out;
            animation: loading2 1.5s infinite ease-in-out;
            -webkit-transform: scaleY(0.05) translateX(-5px);
            -moz-transform: scaleY(0.05) translateX(-5px);
            -ms-transform: scaleY(0.05) translateX(-5px);
            -o-transform: scaleY(0.05) translateX(-5px);
            transform: scaleY(0.05) translateX(-5px);
        }
        
        @-webkit-keyframes loading2 {
            10% {
                background: #ffffff;
            }
            15% {
                -webkit-transform: scaleY(1.2) translateX(10px);
                -moz-transform: scaleY(1.2) translateX(10px);
                -ms-transform: scaleY(1.2) translateX(10px);
                -o-transform: scaleY(1.2) translateX(10px);
                transform: scaleY(1.2) translateX(10px);
                background: #ffffff;
            }
            90%, 100% {
                -webkit-transform: scaleY(0.05) translateX(-5px);
                -moz-transform: scaleY(0.05) translateX(-5px);
                -ms-transform: scaleY(0.05) translateX(-5px);
                -o-transform: scaleY(0.05) translateX(-5px);
                transform: scaleY(0.05) translateX(-5px);
            }
        }
        
        @keyframes loading2 {
            10% {
                background: #ffffff;
            }
            15% {
                -webkit-transform: scaleY(1.2) translateX(10px);
                -moz-transform: scaleY(1.2) translateX(10px);
                -ms-transform: scaleY(1.2) translateX(10px);
                -o-transform: scaleY(1.2) translateX(10px);
                transform: scaleY(1.2) translateX(10px);
                background: #ffffff;
            }
            90%, 100% {
                -webkit-transform: scaleY(0.05) translateX(-5px);
                -moz-transform: scaleY(0.05) translateX(-5px);
                -ms-transform: scaleY(0.05) translateX(-5px);
                -o-transform: scaleY(0.05) translateX(-5px);
                transform: scaleY(0.05) translateX(-5px);
            }
        }
        
        /* 深色模式下的音乐播放器样式 */
        body.dark-mode .sh-main-top-g-container {
            background: rgb(100 100 100 / 75%);
        }
        
        body.dark-mode .shaft-load2 > div {
            background: #666;
        }
        
        /* 深色模式下的动画颜色 */
        body.dark-mode .shaft-load2 > div {
            background: #666;
        }
        
        /* 动画规则不能嵌套在选择器内部，所以我们通过修改元素样式来适配深色模式 */
        
        /* 图标字体 */
        @font-face {
          font-family: "iconfont"; /* Project id 3781624 */
          src: url('//at.alicdn.com/t/c/font_3781624_acf7eqdy5ke.woff2?t=1703660110630') format('woff2'),
               url('//at.alicdn.com/t/c/font_3781624_acf7eqdy5ke.woff?t=1703660110630') format('woff'),
               url('//at.alicdn.com/t/c/font_3781624_acf7eqdy5ke.ttf?t=1703660110630') format('truetype');
        }
        
        .iconfont {
          font-family: "iconfont" !important;
          font-size: 16px;
          font-style: normal;
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
        }
        
        .icon-jixu:before {
          content: "\e68b";
        }
        
        .icon-iconstop:before {
          content: "\e69d";
        }
        
        .icon-yinle_2:before {
          content: "\e705";
        }
        
        .ri-z-sx {
            font-size: 20px;
            color: var(--iconbs);
        }
        
        :root {
            --iconbs: rgb(255 255 255);
        }
        
        .top-navbar.scrolled .ri-z-sx {
            color: #333;
        }
        
        body.dark-mode .top-navbar.scrolled .ri-z-sx {
            color: #e0e0e0;
        }

        /* 弹窗核心样式 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 2000; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: white; width: 90%; max-width: 360px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; transform: translateY(-20px); transition: transform 0.3s ease; position: relative; }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; }
        .modal-header h3 { margin: 0; font-size: 18px; color: #333; }
        .close-btn { font-size: 24px; color: #999; cursor: pointer; line-height: 1; }
        .close-btn:hover { color: #333; }
        .modal-body { padding: 20px; }
        
        /* 表单与列表样式 */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; color: #666; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .login-btn { width: 100%; padding: 12px; background-color: #07c160; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .login-btn:hover { background-color: #06b359; }
        .login-btn:disabled { background-color: #ccc; cursor: not-allowed; }
        .error-msg { color: #ff4d4f; font-size: 13px; text-align: center; margin-top: 10px; display: none; }

        .list-group { display: flex; flex-direction: column; gap: 10px; }
        .list-item { display: flex; align-items: center; padding: 10px; border: 1px solid #f0f0f0; border-radius: 8px; cursor: pointer; }
        .list-item:hover { background-color: #f9f9f9; }
        .item-icon { font-size: 24px; margin-right: 12px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: #eef2ff; border-radius: 50%; }
        .item-icon img { width: 40px; height: 40px;border-radius: 8px;}
        
        .item-info { flex: 1; }
        .item-title { font-weight: 600; color: #333; font-size: 15px; }
        .item-sub { font-size: 12px; color: #999; margin-top: 2px; }
        .action-btn { padding: 10px; background: #f0f0f0; border: none; border-radius: 6px; color: #333; font-weight: 500; cursor: pointer; width: 100%; margin-top: 15px; }
        
        .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; font-size: 15px; color: #333; cursor: pointer; }
        .switch { position: relative; display: inline-block; width: 40px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #07c160; }
        input:checked + .slider:before { transform: translateX(18px); }

        /* 用户信息面板特有样式 */
        .user-info-container { text-align: center; }
        .user-avatar-lg { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #07c160; padding: 2px; margin-bottom: 15px; object-fit: cover; }
        .info-row { display: flex; align-items: center; background: #f9f9f9; padding: 12px; margin-bottom: 10px; border-radius: 8px; text-align: left; }
        .info-icon-svg { width: 20px; color: #07c160; margin-right: 10px; }
        .info-label { width: 70px; color: #666; font-size: 14px; }
        .info-value { flex: 1; color: #333; font-size: 14px; word-break: break-all; }
        .role-badge { color: #ff4d4f; font-weight: bold; }
        .action-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-admin { flex: 1; background: #07c160; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; font-size: 14px; }
        .btn-logout { flex: 1; background: white; color: #ff4d4f; border: 1px solid #ff4d4f; padding: 10px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; font-size: 14px; }
        .btn-logout:hover { background: #fff0f0; }
        
        
        
        /* --- 灯箱样式 --- */
.lightbox-overlay {
    display: none; /* 默认隐藏 */
    position: fixed;
    z-index: 3000; /* 确保在最上层 */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9); /* 黑色半透明背景 */
    justify-content: center;
    align-items: center;
    flex-direction: column;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.lightbox-overlay.active {
    display: flex;
    opacity: 1;
}

.lightbox-content {
    max-width: 90%;
    max-height: 80vh; /* 最大高度为视口的 80% */
    border-radius: 4px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    transform: scale(0.8);
    transition: transform 0.3s ease;
}

.lightbox-overlay.active .lightbox-content {
    transform: scale(1);
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    user-select: none;
}

.lightbox-close:hover {
    color: #bbb;
}

.lightbox-caption {
    margin-top: 15px;
    color: #ccc;
    font-size: 14px;
    text-align: center;
    max-width: 80%;
}
       
    </style>
    
</head>
<body>


<div class="card">
    <!-- 顶部导航栏 -->
    <div class="top-navbar" id="topNavbar">
        <div class="nav-left">
            <!-- 登录/用户图标：根据状态切换行为 -->
            <svg class="nav-icon" 
                 onclick="<?php echo $is_logged_in ? "openModal('userInfoModal')" : "openModal('loginModal')"; ?>" 
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="<?php echo $is_logged_in ? '用户中心' : '登录'; ?>">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M6 20v-2a6 6 0 0 1 12 0v2"></path>
            </svg>
            
            <svg class="nav-icon" id="btnGroups" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="cursor: pointer;" title="群组" onclick="openModal('groupModal')">
                <circle cx="9" cy="7" r="3"></circle>
                <circle cx="15" cy="7" r="3"></circle>
                <path d="M3 20v-2a6 6 0 0 1 6-6h6a6 6 0 0 1 6 6v2"></path>
            </svg>
            
            <!-- 明暗模式切换按钮 -->
            <svg class="nav-icon" id="themeToggle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="cursor: pointer;" title="切换明暗模式">
                <!-- 太阳图标（浅色模式） -->
                <g id="navLightIcon">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                </g>
                <!-- 月亮图标（深色模式）默认隐藏 -->
                <g id="navDarkIcon" style="display:none;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </g>
            </svg>
            
            <!-- 音乐播放器 -->
            <div class="nav-icon music-player" style="position: relative; display: flex; align-items: center;">
                <div class="sh-main-top-mu" lang="0" onclick="syaudbf()"><i class="iconfont icon-jixu ri-z-sx" id="sh-main-top-mu" lang="0" data-bfzt="bb"></i></div>
                <div id="sh-main-top-g-m" class="sh-main-top-g-container" lang="音乐">
                    <div id="sh-main-top-mucisjd" lang="0" style="display:none">
                        <!--音乐动画-->
                        <div class="shaft-load2">
                            <div class="shaft1"></div>
                            <div class="shaft2"></div>
                            <div class="shaft3"></div>
                            <div class="shaft4"></div>
                            <div class="shaft5"></div>
                            <div class="shaft6"></div>
                            <div class="shaft7"></div>
                            <div class="shaft8"></div>
                            <div class="shaft9"></div>
                        </div>
                    </div>
                </div>
                <div class="sh-main-top-mu-bgmq" onclick="sjsyyy()"><i class="iconfont icon-yinle_2 ri-z-sx" id="sh-main-top-mu-bgmq"></i></div>
                <audio id="sh-main-top-musicplay-b" src="https://cdn.261770.xyz/music/%E4%B8%80%E5%8F%A5%E8%AF%9D%E5%BD%A2%E5%AE%B9%E4%B8%8D%E4%BA%86%E7%BB%88%E6%9E%81%E7%AC%94%E8%AE%B0%20-%20%E5%BA%94%E6%9C%89%E6%A3%A0%E3%80%81%E5%8F%B6%E8%90%BD%E6%A2%A6%E4%B8%AD%E3%80%81%E7%BB%AF%E8%A8%80%E3%80%81%E5%B0%8F%E5%B1%B1xl%E3%80%81%E9%9C%84%E9%95%81%E3%80%81%E9%83%AD%E6%9B%A6%E9%98%B3%E3%80%81%E5%A0%87%E5%A2%A8%E5%AE%89%E6%AD%8C%E3%80%81%E5%A4%A9%E7%BD%97.mp3" type="audio/mp3" controls="controls" style="display: none;">
                   </audio>
            </div>
        </div>
        <div class="nav-right">
            <!-- 搜索按钮 -->
            <svg class="nav-icon" id="btnSearch" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="cursor: pointer;" title="搜索" onclick="openModal('searchModal')">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
            
            <?php if ($is_logged_in): ?>
                <a href="includes/edit-page.php" title="发表说说" style="text-decoration: none;">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </a>
            <?php endif; ?>
            
            <svg class="nav-icon" id="btnSettings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="cursor: pointer;" title="设置" onclick="openModal('settingsModal')">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </div>
    </div>

    <!-- 1. 登录弹窗 (未登录时显示) -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>用户登录</h3>
                <span class="close-btn" onclick="closeModal('loginModal')">&times;</span>
            </div>
            <div class="modal-body">
                
                
                <!-- 1. 修改 form 标签：添加 action 指向 login.php，移除 id="loginForm" (或保留但改变逻辑) -->
<form id="loginForm" action="admin/login.php" method="POST">
    <div class="form-group"><label>用户名</label><input type="text" name="username" placeholder="请输入用户名" required value="admin"></div>
    <div class="form-group"><label>密码</label><input type="password" name="password" placeholder="请输入密码" required value=""></div>
    
    <!-- 2. 修改按钮类型：改为 submit，移除 onclick 或 JS 监听 -->
    <button type="submit" class="login-btn" id="loginBtn">立即登录</button>
    
    <div id="loginError" class="error-msg"></div>
</form>

<!-- 3. 修改下方的 JavaScript：注释掉之前的 AJAX 代码，改用简单提交或无操作 -->
<script>
    // 既然表单有 action="login.php" 和 method="POST"，浏览器会自动处理跳转
    // 不需要额外的 fetch 代码
    
    // 如果你希望点击登录后关闭弹窗（其实没必要，因为页面会刷新跳转），
    // 但实际上，一旦点击提交，页面就会跳转到 login.php 处理，然后跳去 admin.php。
    // 所以原来的 AJAX 代码必须删除或注释，否则会拦截提交。
    
    /* 
    // --- 删除或注释掉之前那段复杂的 fetch 代码 ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // e.preventDefault(); // <--- 绝对不能有这行，否则不会跳转
            // ... 其他 AJAX 代码都删掉
        });
    }
    */
</script>
                
            </div>
        </div>
    </div>

    <!-- 2. 用户信息弹窗 (登录后显示) -->
    <div id="userInfoModal" class="modal-overlay">
        <div class="modal-content user-info-container">
            <div class="modal-header">
                <h3>用户信息</h3>
                <span class="close-btn" onclick="closeModal('userInfoModal')">&times;</span>
            </div>
            <div class="modal-body">
                <img src="<?php echo htmlspecialchars($friend_avatar ?: 'https://via.placeholder.com/80'); ?>" alt="Avatar" class="user-avatar-lg">
                
                <div class="info-row">
                    <svg class="info-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span class="info-label">昵称:</span>
                    <span class="info-value"><?php echo htmlspecialchars($friend_name ?? '博主'); ?></span>
                </div>
                
                <div class="info-row">
                    <svg class="info-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    <span class="info-label">邮箱:</span>
                    <span class="info-value">yxksw@foxmail.com</span>
                </div>

                <div class="info-row">
                    <svg class="info-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <span class="info-label">网址:</span>
                    <span class="info-value" style="color:#07c160;"><?php 
// 组合协议 (http/https) 和 域名 + 路径
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
echo htmlspecialchars($current_url); 
?></span>
                </div>

                <div class="info-row">
                    <svg class="info-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span class="info-label">权限组:</span>
                    <span class="info-value role-badge">管理员</span>
                </div>

                <div class="action-buttons">
                    <a href="admin/admin.php" class="btn-admin">⚙️ 管理后台</a>
                    <a href="?action=logout" class="btn-logout" onclick="return confirm('确定要退出登录吗？')">🚪 退出登录</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 友链弹窗 -->
    <div id="groupModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>我的朋友</h3>
                <span class="close-btn" onclick="closeModal('groupModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php
                    // 获取友链数据
                    $friend_links = [];
                    if (isset($conn) && $conn) {
                        try {
                            // 检查表是否存在
                            $table_check = $conn->query("SHOW TABLES LIKE 'links'");
                            if ($table_check && $table_check->num_rows > 0) {
                                $links_result = $conn->query("SELECT * FROM links ORDER BY sort_order ASC, id DESC LIMIT 20");
                                if ($links_result && $links_result->num_rows > 0) {
                                    while ($link = $links_result->fetch_assoc()) {
                                        $friend_links[] = $link;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // 忽略数据库错误
                        }
                    }
                    ?>
                    <?php if (!empty($friend_links)): ?>
                        <?php foreach ($friend_links as $link): ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="list-item" title="<?php echo htmlspecialchars($link['description'] ?? ''); ?>">
                                <div class="item-icon">
                                    <img src="<?php echo htmlspecialchars($link['avatar'] ?: 'https://q1.qlogo.cn/g?b=qq&nk=0&s=100'); ?>" class="logo" alt="<?php echo htmlspecialchars($link['name']); ?>">
                                </div>
                                <div class="item-info">
                                    <div class="item-title"><?php echo htmlspecialchars($link['name']); ?></div>
                                    <div class="item-sub"><?php echo htmlspecialchars($link['url']); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-item" style="justify-content: center; color: #999;">
                            暂无友链
                        </div>
                    <?php endif; ?>
                </div>
                <button class="action-btn" onclick="closeModal('groupModal')">关闭</button>
            </div>
        </div>
    </div>

    <!-- 设置弹窗 -->
    <div id="settingsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>系统设置</h3>
                <span class="close-btn" onclick="closeModal('settingsModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="setting-item"><span> 消息通知更新中</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
                <div class="setting-item"><span>🌙 深色模式更新中</span><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                <?php if ($is_logged_in): ?>
                    <div class="setting-item" style="border-top:1px solid #eee; margin-top:10px; padding-top:10px;" onclick="window.location.href='?action=logout'">
                        <span style="color: #d32f2f; cursor:pointer;">退出登录</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 搜索弹窗 -->
    <div id="searchModal" class="modal-overlay">
        <div class="modal-content search-modal-content">
            <div class="modal-header">
                <h3>搜索文章</h3>
                <span class="close-btn" onclick="closeModal('searchModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-form">
                    <input type="text" id="searchInput" class="search-input" placeholder="输入关键词搜索文章..." onkeypress="if(event.key==='Enter') performSearch()">
                    <button class="search-btn" onclick="performSearch()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
                <div id="searchResults" class="search-results">
                    <div class="search-placeholder">输入关键词开始搜索</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 背景图及悬浮信息 -->
    <div class="cover-section">
        <img src="<?php echo htmlspecialchars($friend_background ?? 'https://via.placeholder.com/800x320'); ?>" alt="背景图">
        <div class="author-info">
            <div class="author-name"><?php echo htmlspecialchars($friend_name ?? '博主'); ?></div>
            <div class="author-avatar">
                <img src="<?php echo htmlspecialchars($friend_avatar ?: 'https://via.placeholder.com/80'); ?>" alt="头像">
            </div>
        </div>
    </div>

    <div class="signature"><?php echo htmlspecialchars($friend_signature ?? '这个人很懒，什么都没写'); ?></div>

    <!-- 音乐播放器脚本 -->
    <script>
    function syaudbf() {
        var mainTopGM = document.getElementById("sh-main-top-g-m");
        
        if (mainTopGM.style.background === "rgba(255, 255, 255, 0)") {
            mainTopGM.style.background = "";
        } else {
            mainTopGM.style.background = "rgba(255, 255, 255, 0)";
        }
    }
    
    document.getElementById('sh-main-top-mu').addEventListener('click', function() {
        var iconElement = document.getElementById('sh-main-top-mu');
        var element = document.getElementById('sh-main-top-mucisjd');
        var audioElement = document.getElementById('sh-main-top-musicplay-b');
        
        if (iconElement.getAttribute('lang') === '0') {
            iconElement.setAttribute('lang', '1');
            iconElement.setAttribute('class', 'iconfont icon-iconstop ri-z-sx');
            iconElement.setAttribute('data-bfzt', 'bbz');
        } else {
            iconElement.setAttribute('lang', '0');
            iconElement.setAttribute('class', 'iconfont icon-jixu ri-z-sx');
            iconElement.setAttribute('data-bfzt', 'bb');
        }
        
        if (element.style.display === 'none') {
            element.style.display = 'block';
        } else {
            element.style.display = 'none';
        }
        
        if (audioElement.paused) {
            audioElement.play();
        } else {
            audioElement.pause();
        }
    });
    
    function sjsyyy() {
        var audioElement = document.getElementById('sh-main-top-musicplay-b');
        audioElement.pause();
        audioElement.currentTime = 0;
        document.getElementById('sh-main-top-mu').setAttribute('lang', '0');
        document.getElementById('sh-main-top-mu').setAttribute('class', 'iconfont icon-jixu ri-z-sx');
        document.getElementById('sh-main-top-mu').setAttribute('data-bfzt', 'bb');
        document.getElementById('sh-main-top-mucisjd').style.display = 'none';
    }
    
    // 搜索功能
    function performSearch() {
        const keyword = document.getElementById('searchInput').value.trim();
        const resultsContainer = document.getElementById('searchResults');
        
        if (!keyword) {
            resultsContainer.innerHTML = '<div class="search-placeholder">输入关键词开始搜索</div>';
            return;
        }
        
        resultsContainer.innerHTML = '<div class="search-loading">搜索中...</div>';
        
        // 发送搜索请求
        fetch('search.php?keyword=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                if (data.code === 200 && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(post => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='?id=${post.id}'">
                                <div class="search-result-content">${escapeHtml(post.content)}</div>
                                <div class="search-result-date">${post.formatted_date}</div>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = '<div class="search-no-results">未找到相关内容</div>';
                }
            })
            .catch(error => {
                console.error('搜索错误:', error);
                resultsContainer.innerHTML = '<div class="search-no-results">搜索出错，请稍后重试</div>';
            });
    }
    
    // HTML 转义函数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>



