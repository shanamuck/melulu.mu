<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_info WHERE id=$user_id"));
if (!$user) { header("Location: login.php"); exit(); }
$username = $user['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <<link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="app-shell" data-barba="wrapper">

<!-- Top Nav — persistent -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + main (right) -->
<div class="app-shell-middle">

    <!-- Sidebar — persistent, sibling of Barba container -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Barba container — only its inner content swaps -->
    <div data-barba="container" data-barba-namespace="home">
        <main class="app-content">

        <!-- Greeting -->
        <div class="page-header">
            <h1 id="greeting">Good Evening, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Discover music from around the world</p>
        </div>

        <!-- Quick Actions -->
        <div class="section-header"><h2>Quick Actions</h2></div>
        <div class="quick-actions">
            <a href="explore.php" class="action-card">
                <div class="action-card-icon"><img src="assets/img/globe.svg" alt=""></div>
                <span class="action-card-label">Explore Music</span>
            </a>
            <a href="playlist.php" class="action-card">
                <div class="action-card-icon"><img src="assets/img/list-music.svg" alt=""></div>
                <span class="action-card-label">My Playlists</span>
            </a>
            <a href="#" class="action-card">
                <div class="action-card-icon"><img src="assets/img/map.svg" alt=""></div>
                <span class="action-card-label">World Map</span>
            </a>
        </div>

        <!-- Featured Songs -->
        <div class="section-header"><h2>Featured Songs</h2></div>
        <div class="playlist-grid" id="featured-songs">
            <div class="song-skeleton"></div>
            <div class="song-skeleton"></div>
            <div class="song-skeleton"></div>
            <div class="song-skeleton"></div>
        </div>

        </main>
    </div><!-- /barba container -->

</div><!-- /app-shell-middle -->

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/audio-player.js"></script>\n<script src="assets/js/components.js"></script>
<script>
// Time-based greeting
(function() {
    var h = new Date().getHours();
    var text = (h < 12) ? 'Good Morning' : (h < 17) ? 'Good Afternoon' : 'Good Evening';
    var el = document.getElementById('greeting');
    if (el) el.textContent = text + ', <?php echo htmlspecialchars($username); ?>!';
})();

// Load featured songs
(function() {
    var container = document.getElementById('featured-songs');
    if (!container) return;
    fetch('api/fetch_songs.php?limit=8')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.songs || data.songs.length === 0) {
                container.innerHTML = '<div style="color:var(--text-muted);padding:1rem 0;">No songs yet. <a href="explore.php" style="color:var(--accent-soft);">Explore music</a></div>';
                return;
            }
            container.innerHTML = data.songs.map(function(s) {
                var thumb = s.thumbnail || 'assets/img/music-2.svg';
                return '<div class="playlist-card" ' +
                    'data-id="' + s.id + '" ' +
                    'data-src="' + (s.file_path || '') + '" ' +
                    'data-title="' + escapeHtml(s.title) + '" ' +
                    'data-artist="' + escapeHtml(s.artist) + '" ' +
                    'data-thumb="' + escapeHtml(thumb) + '">' +
                    '<div class="playlist-card-cover">' +
                    '<img src="' + escapeHtml(thumb) + '" alt="" style="width:100%;height:100%;object-fit:cover;">' +
                    '</div>' +
                    '<div class="playlist-card-body">' +
                    '<div class="playlist-card-name">' + escapeHtml(s.title) + '</div>' +
                    '<div class="playlist-card-meta">' + escapeHtml(s.artist) + '</div>' +
                    '</div></div>';
            }).join('');

            container.querySelectorAll('.playlist-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    var src    = card.getAttribute('data-src');
                    var title  = card.getAttribute('data-title');
                    var artist = card.getAttribute('data-artist');
                    var id     = parseInt(card.getAttribute('data-id'), 10) || 0;
                    if (src) playSongSrc(src, title, artist, null, id);
                });
            });
        })
        .catch(function() {
            container.innerHTML = '<div style="color:var(--status-red);padding:1rem 0;">Failed to load songs.</div>';
        });
})();
</script>
</body>
</html>
