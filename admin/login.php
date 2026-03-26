<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // 检查管理员凭证
    if ($username === 'yxk' && $password === 'qweasdzxc123') {
        $_SESSION['admin'] = true;
        header('Location: ../index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>


<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>管理员登录</title>
    <style>
        body { font-family: sans-serif; background: #07c160; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; transition: background-color 0.3s ease; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; transition: background-color 0.3s ease, color 0.3s ease; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; transition: color 0.3s ease; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; transition: color 0.3s ease; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        button { background: #07c160; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; width: 100%; margin-top: 10px; transition: background-color 0.3s ease; }
        .error { color: red; text-align: center; margin-top: 10px; }

        /* 明暗模式切换按钮 */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
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

        /* 深色模式样式 */
        body.dark-mode {
            background: #1a1a1a;
        }
        body.dark-mode .login-container {
            background: #2d2d2d;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        body.dark-mode h1 {
            color: #e0e0e0;
        }
        body.dark-mode label {
            color: #b0b0b0;
        }
        body.dark-mode input {
            background: #3d3d3d;
            color: #e0e0e0;
            border-color: #555;
        }
        body.dark-mode input::placeholder {
            color: #888;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔒 管理员登录</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名" value="admin" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" value="" required>
            </div>
            <button type="submit">登录</button>
        </form>
    </div>

    <!-- 明暗模式切换按钮 -->
    <button class="theme-toggle" id="themeToggle" title="切换明暗模式">
        <svg id="lightIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0-8 0m-5 0h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>
        <svg id="darkIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="display:none;"><g fill="none" stroke="#ffffff" stroke-width="2"><path d="M20.958 15.325c.204-.486-.379-.9-.868-.684a7.7 7.7 0 0 1-3.101.648c-4.185 0-7.577-3.324-7.577-7.425a7.3 7.3 0 0 1 1.134-3.91c.284-.448-.057-1.068-.577-.936C5.96 4.041 3 7.613 3 11.862C3 16.909 7.175 21 12.326 21c3.9 0 7.24-2.345 8.632-5.675Z"/><path d="M15.611 3.103c-.53-.354-1.162.278-.809.808l.63.945a2.33 2.33 0 0 1 0 2.588l-.63.945c-.353.53.28 1.162.81.808l.944-.63a2.33 2.33 0 0 1 2.588 0l.945.63c.53.354 1.162-.278.808-.808l-.63-.945a2.33 2.33 0 0 1 0-2.588l.63-.945c.354-.53-.278-1.162-.809-.808l-.944.63a2.33 2.33 0 0 1-2.588 0z"/></g></svg>
    </button>

    <script>
        // 明暗模式切换功能
        (function() {
            const themeToggle = document.getElementById('themeToggle');
            const lightIcon = document.getElementById('lightIcon');
            const darkIcon = document.getElementById('darkIcon');
            const body = document.body;
            
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            function applyTheme(theme) {
                if (theme === 'dark') {
                    body.classList.add('dark-mode');
                    lightIcon.style.display = 'none';
                    darkIcon.style.display = 'block';
                } else {
                    body.classList.remove('dark-mode');
                    lightIcon.style.display = 'block';
                    darkIcon.style.display = 'none';
                }
            }
            
            applyTheme(currentTheme);
            
            themeToggle.addEventListener('click', function() {
                const isDark = body.classList.contains('dark-mode');
                const newTheme = isDark ? 'light' : 'dark';
                
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        })();
    </script>
</body>
</html>