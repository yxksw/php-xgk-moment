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
    if ($username === 'admin' && $password === 'admin') {
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
        body { font-family: sans-serif; background: #07c160; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #07c160; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; width: 100%; margin-top: 10px; }
        .error { color: red; text-align: center; margin-top: 10px; }
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
</body>
</html>