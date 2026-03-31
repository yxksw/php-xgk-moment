# 📝 YXK Moment - 轻量级朋友圈博客系统

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> 一个简洁、轻量的类朋友圈博客系统，支持说说发布、图片上传、评论互动等功能。

## ✨ 功能特性

- 🏠 **朋友圈风格** - 仿微信朋友圈的界面设计，简洁美观
- 📝 **说说发布** - 支持文字、图片（最多9张）、位置信息
- 💬 **评论系统** - 支持游客评论，带 QQ 头像自动获取
- 🌓 **明暗模式** - 支持浅色/深色主题切换，自动保存偏好
- 📱 **响应式设计** - 完美适配手机、平板、电脑
- 🔍 **RSS 订阅** - 支持 RSS 2.0 订阅，地址 `/rss.xml`
- 📢 **公告系统** - 支持 Markdown/HTML 格式的公告弹窗展示
- ⚙️ **后台管理** - 完善的管理后台，支持说说/评论/公告管理
- 🖼️ **图片灯箱** - 点击说说图片可放大预览
- 🔐 **安全登录** - 管理员登录验证，支持退出
- 🔗 **友链管理** - 支持添加和管理友情链接
- 📧 **邮件通知** - 支持评论邮件通知功能

## 📁 目录结构

```
├── /admin/                 # 后台管理目录
│   ├── admin.php          # 后台首页（概览）
│   ├── login.php          # 管理员登录
│   ├── logout.php         # 退出登录
│   ├── posts.php          # 说说管理（增删改查）
│   ├── comments.php       # 评论管理
│   ├── announcements.php  # 公告管理
│   ├── links.php          # 友情链接管理
│   ├── mail_settings.php  # 邮件设置
│   ├── settings.php       # 网站设置
│   ├── get_comments.php   # 获取评论 API
│   ├── get_post.php       # 获取说说详情 API
│   └── submit_comment.php # 提交评论 API
│
├── /includes/             # 公共文件目录
│   ├── header.php         # 页面头部（含导航、弹窗）
│   ├── footer.php         # 页面底部（含脚本）
│   ├── functions.php      # 公共函数库
│   └── edit-page.php      # 发表说说页面
│
├── /upload/               # 图片上传目录（需创建，755权限）
├── /img/                  # 静态图片资源
│
├── index.php              # 前端首页（朋友圈展示）
├── search.php             # 搜索功能
├── config.php             # 数据库配置文件
├── install.php            # 一键安装程序
├── rss.xml                # RSS 订阅源
├── .htaccess              # URL重写及缓存配置
└── README.md              # 项目说明文档
```

## 🚀 安装部署

### 环境要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx 服务器
- 启用 mod_rewrite（Apache）

### 安装步骤

1. **克隆仓库到网站目录**
   ```bash
   git clone https://github.com/yxksw/php-xgk-moment.git
   cd php-xgk-moment
   ```

2. **配置数据库**
   
   编辑 `config.php` 文件：
   ```php
   $host = 'localhost';
   $user = '你的数据库用户名';
   $password = '你的数据库密码';
   $database = '你的数据库名';
   ```

3. **创建上传目录**
   ```bash
   mkdir upload
   chmod 755 upload
   ```

4. **运行安装程序**
   
   访问 `http://你的网站/install.php`，按提示完成数据库初始化。

5. **修改管理员密码**
   
   编辑 `admin/login.php` 文件，修改默认账号密码：
   ```php
   if ($username === 'admin' && $password === 'admin') {
       // 修改这里的 'admin' 为你想要的账号密码
   }
   ```

6. **完成安装**
   
   访问 `http://你的网站/` 查看前台，
   访问 `http://你的网站/admin/login.php` 进入后台。

## 🔧 使用说明

### 前台功能

- **浏览说说** - 首页展示所有说说，支持分页
- **发表评论** - 点击说说下方的评论图标，填写昵称和邮箱（可选）即可评论
- **查看图片** - 点击说说中的图片可放大预览
- **切换主题** - 点击右下角的主题切换按钮，在浅色/深色模式间切换
- **RSS订阅** - 点击底部 RSS 图标或访问 `/rss.xml`
- **查看公告** - 点击顶部导航栏的公告图标查看最新公告
- **搜索说说** - 使用快捷键 `Ctrl+K` 或点击搜索图标快速搜索说说

### 后台功能

- **网站概要** - 查看说说数量、评论数量、最近动态
- **说说管理** - 发布、编辑、删除说说
- **评论管理** - 查看、删除评论
- **公告管理** - 发布、编辑、删除公告，支持 Markdown 和 HTML 格式
- **友链管理** - 添加、编辑、删除友情链接
- **邮件设置** - 配置 SMTP 服务器，开启评论邮件通知
- **网站设置** - 修改网站标题、昵称、头像、背景图、个性签名

### 发表说说

1. 登录后台，点击底部导航的「前端」返回首页
2. 点击顶部导航栏的编辑图标
3. 填写内容、选择图片（最多9张）、填写位置
4. 点击「立即发表」

## ⚙️ 配置说明

### 数据库表结构

安装程序会自动创建以下数据表：

- `posts` - 说说数据表
- `comments` - 评论数据表
- `settings` - 网站设置表
- `announcements` - 公告数据表
- `links` - 友情链接表

### 网站设置项

| 设置项 | 说明 |
|--------|------|
| site_title | 网站标题，显示在浏览器标签页 |
| friend_name | 博主昵称，显示在朋友圈 |
| friend_avatar | 博主头像 URL |
| friend_background | 朋友圈背景图 URL |
| friend_signature | 个性签名，显示在昵称下方 |

## 🛡️ 安全建议

1. **修改默认密码** - 安装完成后立即修改 `admin/login.php` 中的默认账号密码
2. **删除安装文件** - 安装完成后建议删除或重命名 `install.php`
3. **设置目录权限** - 上传目录设置为 755，防止非法文件执行
4. **定期备份** - 定期备份数据库和上传的图片文件

## 📝 更新日志

### v1.3.0 (2026-03-31)
- ✨ 新增公告系统，支持 Markdown/HTML 格式
- ✨ 新增友情链接管理功能
- ✨ 新增评论邮件通知功能
- ✨ 新增说说搜索功能（支持快捷键 Ctrl+K）
- ✨ 新增后台顶部导航栏
- 🎨 优化深色模式样式适配
- 🔧 修复已知问题

### v1.2.1 (2026-03-26)
- ✨ 新增明暗模式切换功能
- ✨ 新增 RSS 订阅支持
- ✨ 新增回到首页按钮
- 🎨 优化深色模式样式适配
- 🔧 修复评论框深色模式显示问题

### v1.2.0 (2026-03-25)
- ✨ 新增图片灯箱预览功能
- ✨ 新增评论 @ 功能
- 🎨 优化 UI 界面
- 🔧 修复已知问题

### v1.1.0 (2026-03-20)
- ✨ 新增说说位置信息
- ✨ 新增分页功能
- 🎨 优化移动端体验

### v1.0.0 (2026-03-15)
- 🎉 项目初始版本发布

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

1. Fork 本仓库
2. 创建你的特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交你的修改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开一个 Pull Request

## 📄 开源协议

本项目采用 [MIT](LICENSE) 协议开源。

## 👨‍💻 作者信息

- **作者**: 小归客
- **博客**: https://xgk.pw
- **演示**: https://hanbi.fun
- **GitHub**: https://github.com/yxksw/php-xgk-moment

---

> 💖 如果这个项目对你有帮助，欢迎给个 Star！
