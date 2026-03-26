/admin/
     ├── admin.php            ← 后台管理
     ├── comments.php             评论管理
     ├── get_comments.php         评论头像api
     ├── get_post.php             内容管理api
     ├── login.php            ← 登录页
     ├── logout.php               退出
     ├── posts.php                说说管理
     ├── settings.php             网站设置
     ├── submit_comment.php       评论api
/includes/
     ├── edit-page.php            说说发表
     ├── footer.php               底部
     ├── functions.php            
     ├── header.php               头部
/img/          这个是我自己用的图片文件
/upload/              ← 图片上传目录（需手动创建，755权限）
├── install.php          ← 一键安装（建库+建表+初始化）
├── config.php           ← 数据库配置
├── index.php            ← 前端主页（朋友圈）
├── admin_password.txt       明文密码
└── README.md                项目说明文档


安装步骤：
1.在config.php中配置数据库信息
2.你的网站/install.php
3.你的网站admin/login.php

默认账号：admin  默认密码：admin
密码没有进入数据库，在login.php中修改($username === 'admin' && $password === 'admin') 

时间<?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?> 2026年3月25日22:12:41

程序作者：小归客
演示地址：https://hanbi.fun
作者博客：https://xgk.pw
开源地址：
大家尽量保留这件文件，谢谢大家、