<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = 'explore';

$q       = isset($_GET['q'])      ? trim($_GET['q'])      : (isset($_GET['search']) ? trim($_GET['search']) : '');
$country = isset($_GET['country']) && $_GET['country'] !== 'map' ? trim($_GET['country']) : '';

// Matching-playlists result is computed below after the song query.
// Initialise to null so the early `if ($playlists_result && ...)` block
// can reference it without a PHP "Undefined variable" warning.
$playlists_result = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="app-shell" data-barba="wrapper">

<!-- Top Nav — persistent -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + main (right) -->
<div class="app-shell-middle">

    <!-- Sidebar — persistent, sibling of Barba container -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Barba container — only its inner content swaps -->
    <div data-barba="container" data-barba-namespace="explore">
        <main class="app-content">

        <!-- Hero -->
        <div class="explore-hero">
            <h1>Explore The World Through Music</h1>
            <p>Discover songs from every corner of the globe</p>
            <form method="GET" class="explore-search">
                <div class="explore-search-input-wrap">
                    <img src="assets/img/search.svg" alt="" class="explore-search-icon">
                    <input type="text" name="q" placeholder="Search songs, artists, albums, genres..." value="<?php echo htmlspecialchars($q); ?>">
                </div>
                <select name="country">
                    <option value="map">All Countries</option>
                    <?php
                    $countries = [
                        'usa' => 'United States', 'GBR' => 'United Kingdom', 'CAN' => 'Canada',
                        'AUS' => 'Australia', 'IND' => 'India', 'BRA' => 'Brazil',
                        'nor' => 'Norway', 'jpn' => 'Japan', 'kor' => 'Korea',
                        'ita' => 'Italy', 'egy' => 'Egypt'
                    ];
                    foreach ($countries as $code => $label):
                    ?>
                        <option value="<?php echo $code; ?>" <?php if ($country === $code) echo 'selected'; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>

        <?php
        // Pre-fetch matching playlists if there's a search query.
        // Done here (before the display block) so the check below can
        // safely reference $playlists_result.
        if ($q !== '') {
            $pesc = mysqli_real_escape_string($conn, $q);
            $playlists_result = mysqli_query($conn, "SELECT * FROM playlist WHERE name LIKE '%$pesc%' OR description LIKE '%$pesc%' ORDER BY name ASC LIMIT 12");
        }
        ?>

        <?php if ($playlists_result && mysqli_num_rows($playlists_result) > 0): ?>
            <!-- Matching playlists -->
            <div class="section-header" style="margin-top:var(--space-md);">
                <h2>Matching Playlists</h2>
                <span style="font-size:var(--text-caption);color:var(--text-muted);">
                    <?php echo (int)mysqli_num_rows($playlists_result); ?> match<?php echo mysqli_num_rows($playlists_result) === 1 ? '' : 'es'; ?>
                </span>
            </div>
            <div class="playlist-grid" style="margin-bottom:var(--space-xl);">
                <?php while ($pl = mysqli_fetch_assoc($playlists_result)): ?>
                    <a href="view_playlist.php?id=<?php echo (int)$pl['id']; ?>" class="playlist-card" data-no-barba>
                        <div class="playlist-card-cover">
                            <img src="assets/img/list-music.svg" alt="" style="width:36px;height:36px;opacity:0.7;filter:invert(1);">
                        </div>
                        <div class="playlist-card-body">
                            <div class="playlist-card-name"><?php echo htmlspecialchars($pl['name']); ?></div>
                            <div class="playlist-card-meta">
                                <?php echo htmlspecialchars(mb_strimwidth($pl['description'] ?? '', 0, 50, '…')); ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- Results -->
        <div class="section-header"><h2><?php echo $q || $country ? 'Songs' : 'All Songs'; ?></h2></div>

        <?php
        $where = "1=1";
        $params = [];
        if ($q !== '') {
            $esc = mysqli_real_escape_string($conn, $q);
            // Match across song.title / artist / album / genre
            $where .= " AND (title LIKE '%$esc%' OR artist LIKE '%$esc%' OR album LIKE '%$esc%' OR genre LIKE '%$esc%')";
        }
        if ($country !== '') {
            $esc = mysqli_real_escape_string($conn, $country);
            $where .= " AND country LIKE '%$esc%'";
        }
        $result = mysqli_query($conn, "SELECT * FROM song WHERE $where ORDER BY title ASC LIMIT 100");
        ?>

        <?php if (mysqli_num_rows($result) === 0): ?>
            <div style="color:var(--text-muted);padding:var(--space-xl) 0;text-align:center;">No songs found. Try a different search.</div>
        <?php else: ?>
            <div class="song-list">
                <?php $num = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="song-row"
                         data-id="<?php echo (int)$row['id']; ?>"
                         data-src="<?php echo htmlspecialchars($row['file_path'] ?? ''); ?>"
                         data-title="<?php echo htmlspecialchars($row['title']); ?>"
                         data-artist="<?php echo htmlspecialchars($row['artist']); ?>">
                        <span class="song-row-num"><?php echo $num++; ?></span>
                        <div class="song-row-thumb"><img src="<?php echo htmlspecialchars($row['thumbnail'] ?? '' ?: 'assets/img/music-2.svg'); ?>" alt="" loading="lazy"></div>
                        <div class="song-row-info">
                            <div class="song-row-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="song-row-artist"><?php echo htmlspecialchars($row['artist']); ?></div>
                        </div>
                        <?php if (!empty($row['file_path'])): ?>
                        <div class="ma-player compact"
                            data-src="<?php echo htmlspecialchars($row['file_path']); ?>"
                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                            data-artist="<?php echo htmlspecialchars($row['artist']); ?>"
                            data-song-id="<?php echo (int)$row['id']; ?>"></div>
                        <?php endif; ?>
                        <div class="song-row-actions">
                            <button class="row-like-btn" data-id="<?php echo (int)$row['id']; ?>" title="Like" onclick="event.stopPropagation(); toggleRowLike(this, <?php echo (int)$row['id']; ?>);">
                                <img src="assets/img/heart.svg" alt="Like">
                            </button>
                            <a href="edit_song.php?id=<?php echo (int)$row['id']; ?>" class="row-add-btn" data-no-barba title="Edit song">
                                <img src="assets/img/pencil.svg" alt="Edit">
                            </a>
                            <a href="add_to_playlist.php?id=<?php echo (int)$row['id']; ?>" class="row-add-btn" data-no-barba title="Add to playlist">
                                <img src="assets/img/circle-plus.svg" alt="Add">
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
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
        if (e.target.closest('A') || e.target.closest('BUTTON') || e.target.closest('.ma-player')) return;
        var src    = row.getAttribute('data-src');
        var title  = row.getAttribute('data-title');
        var artist = row.getAttribute('data-artist');
        var id     = parseInt(row.getAttribute('data-id'), 10) || 0;
        if (src) playSongSrc(src, title, artist, null, id);
    });
});
prefillRowLikes();

// Sync the topnav search input with the current query
(function () {
    var input = document.getElementById('topnav-search-input');
    if (input) input.value = <?php echo json_encode($q); ?>;
})();

// When a row's custom player fires 'melulu-play' (on window), also update the
// persistent bottom player so the user can control playback from either.
window.addEventListener('melulu-play', function (e) {
    if (typeof playSongSrc === 'function' && e.detail) {
        playSongSrc(e.detail.src, e.detail.title, e.detail.artist, null, e.detail.songId);
    }
});
window.addEventListener('melulu-like', function (e) {
    if (e.detail && e.detail.btn && e.detail.songId) {
        toggleRowLike(e.detail.btn, e.detail.songId);
    }
});
</script>
</body>
</html>
