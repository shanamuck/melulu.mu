<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = mysqli_query($conn, "SELECT * FROM playlist WHERE id=$playlist_id AND user_id=$user_id");
if (mysqli_num_rows($result) == 0) { header("Location: playlist.php"); exit(); }
$playlist = mysqli_fetch_assoc($result);

$songs_result = mysqli_query($conn, "SELECT song.* FROM song
    INNER JOIN playlist_song ON song.id = playlist_song.song_id
    WHERE playlist_song.playlist_id = $playlist_id
    ORDER BY song.title ASC");

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = 'playlist';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($playlist['name']); ?> - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    </head>
<body class="app-shell" data-barba="wrapper">

<!-- Top Nav — persistent -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + main (right) -->
<div class="app-shell-middle">

    <!-- Sidebar — persistent, sibling of Barba container -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Barba container — only its inner content swaps -->
    <div data-barba="container" data-barba-namespace="view-playlist">
        <main class="app-content">

        <a href="playlist.php" class="page-link">← Back to Playlists</a>

        <div class="page-header" style="margin-top:var(--space-sm);">
            <h1><?php echo htmlspecialchars($playlist['name']); ?></h1>
            <?php if (!empty($playlist['description'])): ?>
                <p><?php echo htmlspecialchars($playlist['description']); ?></p>
            <?php else: ?>
                <p><?php echo mysqli_num_rows($songs_result); ?> songs</p>
            <?php endif; ?>
        </div>

        <a href="add_to_playlist.php?id=<?php echo $playlist_id; ?>" class="btn btn-primary" style="margin-bottom:var(--space-lg);display:inline-flex;">
            + Add Songs
        </a>

        <?php if (mysqli_num_rows($songs_result) === 0): ?>
            <div style="color:var(--text-muted);padding:var(--space-xl) 0;text-align:center;">
                This playlist is empty. Add some songs!
            </div>
        <?php else: ?>
            <?php $num = 1; while ($song = mysqli_fetch_assoc($songs_result)): ?>
                <div class="song-row" data-id="<?php echo (int)$song['id']; ?>"
                     data-src="<?php echo htmlspecialchars($song['file_path'] ?? ''); ?>"
                     data-title="<?php echo htmlspecialchars($song['title']); ?>"
                     data-artist="<?php echo htmlspecialchars($song['artist']); ?>">
                    <span class="song-row-num"><?php echo $num++; ?></span>
                    <div class="song-row-thumb"><img src="<?php echo htmlspecialchars($song['thumbnail'] ?? '' ?: 'assets/img/music-2.svg'); ?>" alt="" loading="lazy"></div>
                    <div class="song-row-info">
                        <div class="song-row-title"><?php echo htmlspecialchars($song['title']); ?></div>
                        <div class="song-row-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                    </div>
                    <?php if (!empty($song['file_path'])): ?>
                    <div class="ma-player compact"
                        data-src="<?php echo htmlspecialchars($song['file_path']); ?>"
                        data-title="<?php echo htmlspecialchars($song['title']); ?>"
                        data-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-song-id="<?php echo (int)$song['id']; ?>"></div>
                    <?php endif; ?>
                    <div class="song-row-actions">
                        <a href="edit_song.php?id=<?php echo (int)$song['id']; ?>" class="row-like-btn" data-no-barba title="Edit song" style="opacity:0.5;">
                            <img src="assets/img/pencil.svg" alt="Edit">
                        </a>
                        <button class="row-like-btn" data-id="<?php echo (int)$song['id']; ?>" title="Like" onclick="event.stopPropagation(); toggleRowLike(this, <?php echo (int)$song['id']; ?>);">
                            <img src="assets/img/heart.svg" alt="Like">
                        </button>
                        <form method="POST" action="delete_from_playlist.php" style="display:inline;">
                            <input type="hidden" name="playlist_id" value="<?php echo $playlist_id; ?>">
                            <input type="hidden" name="song_id" value="<?php echo (int)$song['id']; ?>">
                            <button type="submit" class="row-remove-btn" title="Remove from playlist"
                                    onclick="return confirm('Remove this song from the playlist?');">
                                <img src="assets/img/x.svg" alt="Remove">
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

    </main>
    </div><!-- /barba container -->

</div><!-- /app-shell-middle -->

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/audio-player.js"></script>
<script src="assets/js/components.js"></script>
<script>
document.querySelectorAll('.song-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
        if (e.target.closest('FORM') || e.target.closest('BUTTON') || e.target.closest('.ma-player')) return;
        var src    = row.getAttribute('data-src');
        var title  = row.getAttribute('data-title');
        var artist = row.getAttribute('data-artist');
        var id     = parseInt(row.getAttribute('data-id'), 10) || 0;
        if (src) playSongSrc(src, title, artist, null, id);
    });
});
prefillRowLikes();

// When a row's custom player fires 'melulu-play', also update the
// persistent bottom player so the user can control playback from either.
window.addEventListener('melulu-play', function (e) {
    if (typeof playSongSrc === 'function' && e.detail) {
        playSongSrc(e.detail.src, e.detail.title, e.detail.artist, null, e.detail.songId);
    }
});
</script>
</body>
</html>
