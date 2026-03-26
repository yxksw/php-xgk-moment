<?php
// Live2D 看板娘组件
// 参考: https://blog.dogxi.me/diy-website-live2d/
?>

<!-- Live2D 看板娘 -->
<script>
    const cdnPath = 'https://cdn.jsdelivr.net/gh/dogxii/live2d-widget-v3@main/';
    
    const config = {
        // 资源路径
        path: {
            homePath: '/',
            modelPath: cdnPath + 'Resources/',
            cssPath: cdnPath + 'waifu.css',
            tipsJsonPath: cdnPath + 'waifu-tips.json',
            tipsJsPath: cdnPath + 'waifu-tips.js',
            live2dCorePath: cdnPath + 'Core/live2dcubismcore.js',
            live2dSdkPath: cdnPath + 'live2d-sdk.js',
        },
        // 工具栏
        tools: [
            'hitokoto',      // 一言
            'asteroids',     // 小行星游戏
            'express',       // 切换表情
            'switch-model',  // 切换模型
            'switch-texture',// 切换服装
            'photo',         // 截图
            'info',          // 关于
            'quit',          // 关闭
        ],
        // 模型拖拽
        drag: {
            enable: true,
            direction: ['x', 'y'],
        },
        // 模型切换(order: 顺序切换，random: 随机切换)
        switchType: 'random',
    };
    
    // 只在桌面端显示
    if (screen.width >= 768) {
        Promise.all([
            loadExternalResource(config.path.cssPath, 'css'),
            loadExternalResource(config.path.live2dCorePath, 'js'),
            loadExternalResource(config.path.live2dSdkPath, 'js'),
            loadExternalResource(config.path.tipsJsPath, 'js'),
        ]).then(() => {
            initWidget({
                waifuPath: config.path.tipsJsonPath,
                cdnPath: config.path.modelPath,
                tools: config.tools,
                dragEnable: config.drag.enable,
                dragDirection: config.drag.direction,
                switchType: config.switchType,
            });
        });
    }
    
    // 异步加载资源
    function loadExternalResource(url, type) {
        return new Promise((resolve, reject) => {
            let tag;
            if (type === 'css') {
                tag = document.createElement('link');
                tag.rel = 'stylesheet';
                tag.href = url;
            } else if (type === 'js') {
                tag = document.createElement('script');
                tag.src = url;
            }
            if (tag) {
                tag.onload = () => resolve(url);
                tag.onerror = () => reject(url);
                document.head.appendChild(tag);
            }
        });
    }
</script>

<style>
/* Live2D 看板娘样式适配 */

/* 调整看板娘位置，固定在右下角 */
#waifu {
    position: fixed;
    bottom: 0 !important;
    right: 0 !important;
    left: auto !important;
    z-index: 9998;
}

/* 工具栏样式适配 */
#waifu-tool {
    position: absolute;
    top: 20px;
    right: -30px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

#waifu-tool span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

#waifu-tool span:hover {
    background: #07c160;
    color: white;
    transform: scale(1.1);
}

/* 深色模式适配 */
body.dark-mode #waifu-tool span {
    background: rgba(45, 45, 45, 0.9);
    color: #e0e0e0;
}

body.dark-mode #waifu-tool span:hover {
    background: #07c160;
    color: white;
}

/* 对话框样式适配 - 显示在看板娘左上方 */
#waifu-tips {
    position: absolute;
    top: 20%;
    right: 80%;
    left: auto;
    transform: translateY(-50%);
    margin-right: 5px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
    font-size: 13px;
    color: #333;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    backdrop-filter: blur(10px);
    max-width: 200px;
    white-space: normal;
    word-wrap: break-word;
}

#waifu-tips.show {
    opacity: 1;
}

#waifu-tips::after {
    content: '';
    position: absolute;
    top: 50%;
    right: -6px;
    left: auto;
    bottom: auto;
    transform: translateY(-50%);
    border-width: 6px 0 6px 6px;
    border-style: solid;
    border-color: transparent transparent transparent rgba(255, 255, 255, 0.95);
}

/* 深色模式对话框 */
body.dark-mode #waifu-tips {
    background: rgba(45, 45, 45, 0.95);
    color: #e0e0e0;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
}

body.dark-mode #waifu-tips::after {
    border-color: transparent transparent transparent rgba(45, 45, 45, 0.95);
}

/* 移动端隐藏 */
@media screen and (max-width: 768px) {
    #waifu {
        display: none !important;
    }
}

/* 画布样式 */
#live2d-canvas {
    position: relative;
    cursor: grab;
}

#live2d-canvas:active {
    cursor: grabbing;
}
</style>
