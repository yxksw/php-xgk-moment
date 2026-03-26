<?php
// 音乐播放器组件
// 从 Meting API 获取歌单数据
$meting_api = 'https://meting2.050815.xyz/api?server=netease&type=playlist&id=13681647281';
$music_data = @file_get_contents($meting_api);
$playlist = $music_data ? json_decode($music_data, true) : [];
?>

<!-- 音乐播放器 -->
<div id="music-player" class="music-player">
    <div class="music-player-inner">
        <!-- 封面和播放按钮 -->
        <div class="music-cover" onclick="musicPlayer.toggle()">
            <img id="music-cover-img" src="https://via.placeholder.com/40" alt="封面">
            <div class="music-play-icon">
                <svg id="music-play-btn" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                <svg id="music-pause-btn" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </div>
        </div>
        
        <!-- 歌曲信息 -->
        <div class="music-info" onclick="musicPlayer.toggleExpand()">
            <div class="music-title" id="music-title">点击播放</div>
            <div class="music-author" id="music-author">YXK Music</div>
        </div>
        
        <!-- 展开的歌词区域 -->
        <div class="music-lyric" id="music-lyric"></div>
        
        <!-- 进度条 -->
        <div class="music-progress" id="music-progress-bar">
            <div class="music-progress-inner" id="music-progress"></div>
        </div>
    </div>
    
    <!-- 隐藏的音乐播放器 -->
    <audio id="music-audio" preload="none"></audio>
</div>

<script>
// 音乐播放器
const musicPlayer = {
    audio: document.getElementById('music-audio'),
    coverImg: document.getElementById('music-cover-img'),
    titleEl: document.getElementById('music-title'),
    authorEl: document.getElementById('music-author'),
    playBtn: document.getElementById('music-play-btn'),
    pauseBtn: document.getElementById('music-pause-btn'),
    lyricEl: document.getElementById('music-lyric'),
    progressEl: document.getElementById('music-progress'),
    progressBar: document.getElementById('music-progress-bar'),
    playerEl: document.getElementById('music-player'),
    
    playlist: <?php echo json_encode($playlist, JSON_UNESCAPED_UNICODE); ?>,
    currentIndex: 0,
    isPlaying: false,
    isExpanded: false,
    
    init() {
        if (!this.playlist || this.playlist.length === 0) {
            this.titleEl.textContent = '暂无音乐';
            return;
        }
        
        // 随机播放
        this.currentIndex = Math.floor(Math.random() * this.playlist.length);
        this.loadSong(this.currentIndex);
        
        // 绑定事件
        this.audio.addEventListener('ended', () => this.next());
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.progressBar.addEventListener('click', (e) => this.seek(e));
        
        // 自动播放（可选）
        // this.play();
    },
    
    loadSong(index) {
        const song = this.playlist[index];
        if (!song) return;
        
        this.audio.src = song.url;
        this.coverImg.src = song.pic || 'https://via.placeholder.com/40';
        this.titleEl.textContent = song.title || '未知歌曲';
        this.authorEl.textContent = song.author || '未知艺术家';
        
        // 加载歌词
        if (song.lrc) {
            this.loadLyric(song.lrc);
        }
    },
    
    loadLyric(lrcUrl) {
        fetch(lrcUrl)
            .then(res => res.text())
            .then(lrc => {
                this.lyrics = this.parseLyric(lrc);
            })
            .catch(() => {
                this.lyrics = [];
            });
    },
    
    parseLyric(lrc) {
        const lines = lrc.split('\n');
        const lyrics = [];
        const timeRegex = /\[(\d{2}):(\d{2})\.(\d{2,3})\]/;
        
        lines.forEach(line => {
            const match = line.match(timeRegex);
            if (match) {
                const min = parseInt(match[1]);
                const sec = parseInt(match[2]);
                const ms = parseInt(match[3]);
                const time = min * 60 + sec + ms / 1000;
                const text = line.replace(timeRegex, '').trim();
                if (text) {
                    lyrics.push({ time, text });
                }
            }
        });
        
        return lyrics;
    },
    
    updateLyric() {
        if (!this.lyrics || this.lyrics.length === 0) return;
        
        const currentTime = this.audio.currentTime;
        let currentLyric = '';
        
        for (let i = 0; i < this.lyrics.length; i++) {
            if (currentTime >= this.lyrics[i].time) {
                currentLyric = this.lyrics[i].text;
            } else {
                break;
            }
        }
        
        if (currentLyric && this.isExpanded) {
            this.lyricEl.textContent = currentLyric;
        }
    },
    
    toggle() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    },
    
    play() {
        this.audio.play();
        this.isPlaying = true;
        this.playBtn.style.display = 'none';
        this.pauseBtn.style.display = 'block';
        this.playerEl.classList.add('playing');
    },
    
    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.playBtn.style.display = 'block';
        this.pauseBtn.style.display = 'none';
        this.playerEl.classList.remove('playing');
    },
    
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.playlist.length;
        this.loadSong(this.currentIndex);
        if (this.isPlaying) {
            this.play();
        }
    },
    
    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.playlist.length) % this.playlist.length;
        this.loadSong(this.currentIndex);
        if (this.isPlaying) {
            this.play();
        }
    },
    
    toggleExpand() {
        this.isExpanded = !this.isExpanded;
        this.playerEl.classList.toggle('expanded', this.isExpanded);
    },
    
    updateProgress() {
        if (!this.audio.duration) return;
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressEl.style.width = progress + '%';
        this.updateLyric();
    },
    
    seek(e) {
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }
};

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    musicPlayer.init();
});
</script>

<style>
/* 音乐播放器样式 */
.music-player {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 9999;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    max-width: 280px;
    overflow: hidden;
}

.music-player-inner {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    gap: 10px;
}

/* 封面 */
.music-cover {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    flex-shrink: 0;
}

.music-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.music-player.playing .music-cover img {
    animation: musicRotate 10s linear infinite;
}

@keyframes musicRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* 播放图标 */
.music-play-icon {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.music-cover:hover .music-play-icon {
    opacity: 1;
}

.music-play-icon svg {
    width: 20px;
    height: 20px;
    color: white;
}

/* 歌曲信息 */
.music-info {
    flex: 1;
    min-width: 0;
    cursor: pointer;
}

.music-title {
    font-size: 13px;
    font-weight: 500;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.music-author {
    font-size: 11px;
    color: #999;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 歌词区域 */
.music-lyric {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s;
    font-size: 12px;
    color: #666;
    text-align: center;
    padding: 0 12px;
}

.music-player.expanded .music-lyric {
    max-height: 60px;
    padding: 8px 12px;
}

/* 进度条 */
.music-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.music-progress-inner {
    height: 100%;
    background: #07c160;
    width: 0%;
    transition: width 0.1s linear;
}

/* 深色模式适配 */
body.dark-mode .music-player {
    background: rgba(45, 45, 45, 0.95);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

body.dark-mode .music-title {
    color: #e0e0e0;
}

body.dark-mode .music-author {
    color: #888;
}

body.dark-mode .music-lyric {
    color: #aaa;
}

body.dark-mode .music-progress {
    background: rgba(255, 255, 255, 0.1);
}

/* 移动端适配 */
@media screen and (max-width: 576px) {
    .music-player {
        bottom: 70px;
        left: 10px;
        max-width: 220px;
    }
    
    .music-player-inner {
        padding: 6px 10px;
    }
    
    .music-cover {
        width: 36px;
        height: 36px;
    }
    
    .music-title {
        font-size: 12px;
    }
    
    .music-author {
        font-size: 10px;
    }
}
</style>
