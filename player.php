<?php
/**
 * Music Player component
 * Persistent bottom bar — must be outside the Barba container
 */
?>
<!-- Music Player -->
<div class="app-player" id="music-player">
    <!-- Left: Song Info -->
    <div class="player-song-info">
        <div class="player-thumb">
            <img src="assets/img/user.svg" alt="Album" id="player-thumb-img">
        </div>
        <div class="player-song-text">
            <div class="player-song-title" id="player-title">Select a song</div>
            <div class="player-artist" id="player-artist">Browse your playlists</div>
        </div>
        <button class="player-heart-btn" id="heart-btn" onclick="toggleLike()" title="Like">
            <img src="assets/img/heart.svg" alt="Like" id="heart-icon" class="disabled">
        </button>
    </div>

    <!-- Center: Controls + Progress -->
    <div class="player-center">
        <div class="player-controls">
            <button class="player-btn" id="shuffle-btn" title="Shuffle" onclick="toggleShuffle()">
                <img src="assets/img/shuffle.svg" alt="Shuffle">
            </button>
            <button class="player-btn" id="prev-btn" title="Previous" onclick="prevTrack()">
                <img src="assets/img/skip-back.svg" alt="Previous">
            </button>
            <button class="player-btn player-btn-play" id="play-pause-btn" title="Play" onclick="togglePlay()">
                <img src="assets/img/circle-play.svg" alt="Play" id="play-icon">
            </button>
            <button class="player-btn" id="next-btn" title="Next" onclick="nextTrack()">
                <img src="assets/img/skip-forward.svg" alt="Next">
            </button>
            <button class="player-btn" id="repeat-btn" title="Repeat" onclick="toggleRepeat()">
                <img src="assets/img/rotate-ccw.svg" alt="Repeat">
            </button>
        </div>
        <div class="player-progress">
            <span class="progress-time" id="current-time">0:00</span>
            <div class="progress-track">
                <div class="progress-track-fill" id="seek-fill"></div>
                <input type="range" id="seek-bar" min="0" max="100" value="0" oninput="seekTo(this.value)">
            </div>
            <span class="progress-time" id="total-time">0:00</span>
        </div>
    </div>

    <!-- Right: Volume + Queue -->
    <div class="player-right">
        <div class="volume-wrap">
            <img src="assets/img/volume.svg" alt="Volume">
            <div class="volume-track">
                <div class="volume-track-fill" id="volume-fill"></div>
                <input type="range" id="volume-bar" min="0" max="100" value="70" oninput="setVolume(this.value)">
            </div>
        </div>
        <button class="player-btn" title="Queue">
            <img src="assets/img/square-play.svg" alt="Queue">
        </button>
    </div>
</div>

<!-- Hidden Audio Element -->
<audio id="audio-player" preload="auto" style="display:none"></audio>
