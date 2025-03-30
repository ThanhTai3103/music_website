class AudioPlayer {
    constructor() {
        this.audio = new Audio();
        this.isPlaying = false;
        this.currentSong = null;
        
        // Khởi tạo các elements
        this.playButton = document.querySelector('#play-button');
        this.progressBar = document.querySelector('#progress-bar');
        this.currentTime = document.querySelector('#current-time');
        this.duration = document.querySelector('#duration');
        this.volumeBar = document.querySelector('#volume-bar');
        this.songTitle = document.querySelector('#song-title');
        this.songArtist = document.querySelector('#song-artist');
        this.songImage = document.querySelector('#song-image');
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Play/Pause button
        this.playButton.addEventListener('click', () => this.togglePlay());
        
        // Progress bar
        this.progressBar.addEventListener('input', (e) => {
            const time = (this.audio.duration * e.target.value) / 100;
            this.audio.currentTime = time;
        });
        
        // Volume control
        this.volumeBar.addEventListener('input', (e) => {
            this.audio.volume = e.target.value / 100;
        });
        
        // Audio events
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.onSongEnd());
        this.audio.addEventListener('loadedmetadata', () => this.updateDuration());
    }

    async loadAndPlay(songData) {
        try {
            this.currentSong = songData;
            this.audio.src = songData.url;
            
            // Cập nhật UI player (nếu có)
            this.updatePlayerUI();
            
            // Phát nhạc
            await this.audio.play();
        } catch (error) {
            console.error('Error playing audio:', error);
            throw error;
        }
    }

    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    play() {
        this.audio.play();
        this.isPlaying = true;
        this.playButton.innerHTML = '<i class="fas fa-pause"></i>';
    }

    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.playButton.innerHTML = '<i class="fas fa-play"></i>';
    }

    updateProgress() {
        const percent = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressBar.value = percent;
        
        // Update current time
        const minutes = Math.floor(this.audio.currentTime / 60);
        const seconds = Math.floor(this.audio.currentTime % 60);
        this.currentTime.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    updateDuration() {
        const minutes = Math.floor(this.audio.duration / 60);
        const seconds = Math.floor(this.audio.duration % 60);
        this.duration.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    onSongEnd() {
        this.pause();
        // Có thể thêm logic để phát bài tiếp theo
    }

    updatePlayerUI() {
        // Cập nhật giao diện player với thông tin bài hát
        if (this.currentSong) {
            // Cập nhật tên bài hát, nghệ sĩ, ảnh...
            const playerTitle = document.querySelector('.player-title');
            const playerArtist = document.querySelector('.player-artist');
            const playerImage = document.querySelector('.player-image');
            
            if (playerTitle) playerTitle.textContent = this.currentSong.title;
            if (playerArtist) playerArtist.textContent = this.currentSong.artist;
            if (playerImage) playerImage.src = this.currentSong.image;
        }
    }
} 