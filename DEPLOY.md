# 虚拟主机部署指南

## 一、Apache 虚拟主机

### 1. 使用 .htaccess 文件
项目根目录已包含 `.htaccess` 文件，支持以下伪静态规则：

```
/rss.xml          -> rss.php
/api              -> api.php
/api/xxx          -> api.php?xxx
/search           -> search.php
/search/xxx       -> search.php?xxx
/post/123         -> index.php?id=123
/p/123            -> index.php?id=123
/page/2           -> index.php?page=2
```

### 2. 确保 Apache 开启 mod_rewrite
在虚拟主机控制面板中，确保以下模块已启用：
- mod_rewrite
- mod_expires（可选，用于缓存）

### 3. 常见虚拟主机配置

#### cPanel
1. 登录 cPanel
2. 进入 "文件管理器"
3. 确保 `.htaccess` 文件上传到网站根目录
4. 在 "多 PHP 管理器" 中选择 PHP 7.4 或更高版本

#### 宝塔面板
1. 登录宝塔面板
2. 进入网站设置
3. 选择 "伪静态" 标签
4. 选择 "thinkphp" 或 "laravel" 规则，或粘贴 `.htaccess` 内容

#### 阿里云虚拟主机
1. 登录阿里云控制台
2. 进入虚拟主机管理
3. 在 "高级环境设置" 中开启 URL 重写
4. 上传 `.htaccess` 文件到网站根目录

---

## 二、Nginx 虚拟主机

### 1. 使用 nginx.conf 配置
将项目中的 `nginx.conf` 内容添加到您的 Nginx 配置中：

```nginx
# 在 /etc/nginx/sites-available/your-domain 中添加
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/your-project;
    index index.php index.html;
    
    # 包含项目中的伪静态规则
    # ... (复制 nginx.conf 中的内容)
}
```

### 2. 重载 Nginx 配置
```bash
sudo nginx -t          # 测试配置
sudo systemctl reload nginx  # 重载配置
```

---

## 三、通用部署步骤

### 1. 上传文件
将项目文件上传到虚拟主机根目录（通常是 `public_html` 或 `www`）

### 2. 创建数据库
在虚拟主机控制面板中：
1. 创建 MySQL 数据库
2. 创建数据库用户并授权
3. 记录数据库连接信息

### 3. 修改配置文件
编辑 `config.php`，填写您的数据库信息：

```php
$servername = "localhost";
$username = "您的数据库用户名";
$password = "您的数据库密码";
$dbname = "您的数据库名";
```

### 4. 运行安装脚本
访问 `http://your-domain.com/install.php` 完成数据库初始化

### 5. 设置目录权限
确保以下目录有写入权限（755 或 777）：
- `uploads/` - 图片上传目录
- `music/` - 音乐上传目录（如果有）

---

## 四、常见问题

### 1. 500 内部服务器错误
- 检查 `.htaccess` 文件是否正确上传
- 检查 Apache 是否开启 mod_rewrite
- 检查文件权限是否正确

### 2. 404 页面不存在
- 检查伪静态规则是否正确配置
- 检查文件是否上传到正确目录

### 3. 数据库连接失败
- 检查 `config.php` 中的数据库信息
- 确认数据库服务器地址（可能是 `localhost` 或特定 IP）

### 4. 图片上传失败
- 检查 `uploads/` 目录权限
- 检查 PHP 上传大小限制

---

## 五、安全建议

1. **删除安装脚本**：部署完成后删除 `install.php`
2. **修改后台路径**：将 `admin/` 目录重命名为复杂名称
3. **设置强密码**：后台管理使用复杂密码
4. **定期备份**：定期备份数据库和上传的文件
5. **启用 HTTPS**：使用 SSL 证书启用 HTTPS

---

## 六、性能优化

### 1. 启用 Gzip 压缩
在 `.htaccess` 中添加：
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>
```

### 2. 启用浏览器缓存
`.htaccess` 中已配置静态资源缓存

### 3. 使用 CDN（可选）
将静态资源上传到 CDN 加速

---

## 七、支持的 URL 格式

部署完成后，您可以使用以下友好的 URL：

| 功能 | URL 示例 |
|------|---------|
| 首页 | `https://your-domain.com/` |
| RSS | `https://your-domain.com/rss.xml` |
| API | `https://your-domain.com/api` |
| 搜索 | `https://your-domain.com/search?keyword=xxx` |
| 说说详情 | `https://your-domain.com/post/123` |
| 分页 | `https://your-domain.com/page/2` |
| 后台 | `https://your-domain.com/admin/` |

---

如有其他问题，请查看项目文档或联系技术支持。
