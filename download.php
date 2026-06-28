<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_info WHERE id=$user_id"));
if (!$user) { header("Location: login.php"); exit(); }
$username = $user['username'];

// ── Filter parameters ────────────────────────────────────
$q     = isset($_GET['q'])     ? trim($_GET['q'])     : '';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';

// ── Distinct genres for the filter ────────────────────────
$genres_result = mysqli_query($conn, "SELECT DISTINCT genre FROM song WHERE genre IS NOT NULL AND genre <> '' ORDER BY genre ASC");
$genres = [];
while ($g = mysqli_fetch_assoc($genres_result)) { $genres[] = $g['genre']; }

// ── Build song query (uses the same pattern as explore.php) ─
$where = "1=1";
$bind = [];
$bind_types = '';
if ($q !== '') {
    $where .= " AND (title LIKE ? OR artist LIKE ? OR album LIKE ?)";
    $like = '%' . $q . '%';
    array_push($bind, $like, $like, $like);
    $bind_types .= 'sss';
}
if ($genre !== '') {
    $where .= " AND genre = ?";
    $bind[] = $genre;
    $bind_types .= 's';
}
$sql = "SELECT id, title, artist, album, genre, country, duration, file_path
        FROM song WHERE $where ORDER BY artist ASC, title ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($bind)) {
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind);
}
mysqli_stmt_execute($stmt);
$songs_result = mysqli_stmt_get_result($stmt);
$total_songs = (int)mysqli_num_rows($songs_result);

// ── Load user's playlists ─────────────────────────────────
$playlists = mysqli_query($conn, "
    SELECT p.id, p.name, p.description,
           (SELECT COUNT(*) FROM playlist_song ps WHERE ps.playlist_id = p.id) AS song_count
    FROM playlist p
    WHERE p.user_id = $user_id
    ORDER BY p.name ASC
");
$total_playlists = (int)mysqli_num_rows($playlists);

// Pre-compute query string builder for filter links
function dl_link(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - Melulu</title>
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
    <div data-barba="container" data-barba-namespace="download">
        <main class="app-content">

        <!-- ─── Page header with ZIP download button ──────────────── -->
        <div class="dl-toolbar">
            <div class="page-header" style="margin-bottom:0;">
                <h1>Downloads</h1>
                <p>Save songs and playlists to your device for offline listening</p>
            </div>
            <a href="#" class="dl-zip-btn" id="dl-zip-btn" data-no-barba
               title="Select songs to download as a ZIP" aria-disabled="true">
                <img src="assets/img/arrow-down-to-line.svg" alt="" class="dl-zip-icon">
                <span class="dl-zip-label">Download ZIP</span>
                <span class="dl-zip-count" id="dl-zip-count">0</span>
            </a>
        </div>

        <!-- ─── Playlists (compact list) ──────────────────────────── -->
        <?php if ($total_playlists > 0): ?>
            <div class="section-header" style="margin-top:var(--space-lg);">
                <h2>Your Playlists</h2>
                <span class="section-header-count"><?php echo $total_playlists; ?></span>
            </div>
            <div class="song-list">
                <?php $pl_num = 1; while ($p = mysqli_fetch_assoc($playlists)): ?>
                    <div class="song-row dl-playlist-row"
                         data-id="<?php echo (int)$p['id']; ?>">
                        <span class="song-row-num"><?php echo $pl_num++; ?></span>
                        <div class="song-row-thumb dl-thumb-playlist">
                            <img src="assets/img/list-music.svg" alt="">
                        </div>
                        <div class="song-row-info">
                            <div class="song-row-title"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="song-row-artist">
                                <?php if (!empty($p['description'])): ?>
                                    <?php echo htmlspecialchars(mb_strimwidth($p['description'], 0, 80, '…')); ?>
                                <?php else: ?>
                                    <span style="opacity:0.5;">No description</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="dl-genre-tag"><?php echo (int)$p['song_count']; ?> song<?php echo (int)$p['song_count'] === 1 ? '' : 's'; ?></span>
                        <span class="dl-duration">·</span>
                        <div class="song-row-actions">
                            <a href="view_playlist.php?id=<?php echo (int)$p['id']; ?>"
                               class="dl-dl-btn" data-no-barba
                               title="Open playlist">
                                <img src="assets/img/eye.svg" alt="Open">
                            </a>
                            <a href="download_playlist.php?id=<?php echo (int)$p['id']; ?>"
                               class="dl-dl-btn" data-no-barba
                               title="Download as .m3u">
                                <img src="assets/img/arrow-down-to-line.svg" alt="Download">
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- ─── All Songs section ────────────────────────────────── -->
        <div class="section-header" style="margin-top:var(--space-xl);">
            <h2>All Songs</h2>
            <span class="section-header-count"><?php echo number_format($total_songs); ?></span>
            <?php if ($q || $genre): ?>
                <span class="dl-section-filtered">filtered</span>
            <?php endif; ?>
        </div>

        <!-- ─── Filter bar (search + genre pills) ─────────────────── -->
        <form method="GET" class="dl-filter-bar" id="dl-filter">
            <div class="dl-search-field">
                <img src="assets/img/search.svg" alt="" class="dl-search-icon">
                <input type="text" name="q" placeholder="Search title, artist, or album…"
                       value="<?php echo htmlspecialchars($q); ?>" autocomplete="off"
                       id="dl-search-input">
                <?php if ($q !== ''): ?>
                    <a href="<?php echo dl_link(['q' => '']); ?>" class="dl-search-clear" data-no-barba title="Clear search">
                        <img src="assets/img/x.svg" alt="">
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($q || $genre): ?>
                <a href="download.php" class="dl-filter-clear" data-no-barba>
                    <img src="assets/img/x.svg" alt="" style="width:11px;height:11px;opacity:0.7;">
                    Clear filters
                </a>
            <?php endif; ?>
        </form>

        <!-- Genre pills -->
        <div class="dl-genre-pills">
            <a href="<?php echo dl_link(['genre' => '']); ?>"
               class="dl-genre-pill <?php echo $genre === '' ? 'is-active' : ''; ?>"
               data-no-barba>All</a>
            <?php foreach ($genres as $g): ?>
                <a href="<?php echo dl_link(['genre' => $g]); ?>"
                   class="dl-genre-pill <?php echo $g === $genre ? 'is-active' : ''; ?>"
                   data-no-barba><?php echo htmlspecialchars($g); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- ─── Songs list (uses .song-list / .song-row like explore) ─ -->
        <?php if ($total_songs === 0): ?>
            <div class="dl-empty">
                <img src="assets/img/search.svg" alt="" class="dl-empty-icon">
                <div class="dl-empty-title">No songs match</div>
                <div class="dl-empty-sub">
                    <?php echo ($q || $genre) ? 'Try a different filter or clear the active ones.' : 'No songs in the library yet.'; ?>
                </div>
                <?php if ($q || $genre): ?>
                    <a href="download.php" class="dl-empty-cta" data-no-barba>Clear filters</a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <div class="song-list" id="dl-songs-list">
                <?php $num = 1; while ($s = mysqli_fetch_assoc($songs_result)): ?>
                    <div class="song-row dl-song-row"
                         data-id="<?php echo (int)$s['id']; ?>"
                         data-src="<?php echo htmlspecialchars($s['file_path'] ?? ''); ?>"
                         data-title="<?php echo htmlspecialchars($s['title']); ?>"
                         data-artist="<?php echo htmlspecialchars($s['artist']); ?>">
                        <!-- Checkbox for ZIP selection -->
                        <label class="dl-row-check" title="Select for ZIP">
                            <input type="checkbox" class="dl-song-check" value="<?php echo (int)$s['id']; ?>">
                            <span class="dl-check-box"></span>
                        </label>
                        <span class="song-row-num"><?php echo $num++; ?></span>
                        <!-- Themed music icon (purple gradient bg, white icon) -->
                        <div class="song-row-thumb dl-thumb-song">
                            <img src="<?php echo htmlspecialchars($s['thumbnail'] ?? '' ?: 'assets/img/music-2.svg'); ?>" alt="" loading="lazy">
                        </div>
                        <div class="song-row-info">
                            <div class="song-row-title"><?php echo htmlspecialchars($s['title']); ?></div>
                            <div class="song-row-artist">
                                <?php echo htmlspecialchars($s['artist']); ?>
                                <?php if (!empty($s['album'])): ?>
                                    · <em style="font-style:normal;opacity:0.7;"><?php echo htmlspecialchars($s['album']); ?></em>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($s['genre'])): ?>
                            <span class="dl-genre-tag"><?php echo htmlspecialchars($s['genre']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($s['duration'])): ?>
                            <span class="dl-duration"><?php echo htmlspecialchars(substr($s['duration'], 0, 5)); ?></span>
                        <?php endif; ?>
                        <div class="song-row-actions">
                            <button class="row-like-btn" data-id="<?php echo (int)$s['id']; ?>" title="Like"
                                    onclick="event.stopPropagation(); toggleRowLike(this, <?php echo (int)$s['id']; ?>);">
                                <img src="assets/img/heart.svg" alt="Like">
                            </button>
                            <a href="edit_song.php?id=<?php echo (int)$s['id']; ?>" class="row-add-btn" data-no-barba title="Edit song">
                                <img src="assets/img/pencil.svg" alt="Edit">
                            </a>
                            <a href="add_to_playlist.php?id=<?php echo (int)$s['id']; ?>" class="row-add-btn" data-no-barba title="Add to playlist">
                                <img src="assets/img/circle-plus.svg" alt="Add">
                            </a>
                            <a href="download_song.php?id=<?php echo (int)$s['id']; ?>" class="dl-dl-btn" data-no-barba title="Download this song">
                                <img src="assets/img/arrow-down-to-line.svg" alt="Download">
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>

        </main>
    </div>

</div>

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/audio-player.js"></script>\n<script src="assets/js/components.js"></script>
<script>
document.querySelectorAll('.dl-song-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
        // Ignore clicks on action buttons, links, and the checkbox itself
        if (e.target.closest('A') || e.target.closest('BUTTON') || e.target.closest('LABEL')) return;
        var src    = row.getAttribute('data-src');
        var title  = row.getAttribute('data-title');
        var artist = row.getAttribute('data-artist');
        var id     = parseInt(row.getAttribute('data-id'), 10) || 0;
        if (src) playSongSrc(src, title, artist, null, id);
    });
});
prefillRowLikes();

// ZIP download wiring
(function () {
    var checks = document.querySelectorAll('.dl-song-check');
    var zipBtn = document.getElementById('dl-zip-btn');
    var zipCnt = document.getElementById('dl-zip-count');
    if (!checks.length || !zipBtn) return;

    function update() {
        var ids = [];
        checks.forEach(function (c) { if (c.checked) ids.push(c.value); });
        var n = ids.length;
        zipCnt.textContent = n;
        if (n > 0) {
            zipBtn.classList.add('is-active');
            zipBtn.removeAttribute('aria-disabled');
            zipBtn.setAttribute('href', 'download_zip.php?ids=' + ids.join(','));
        } else {
            zipBtn.classList.remove('is-active');
            zipBtn.setAttribute('aria-disabled', 'true');
            zipBtn.removeAttribute('href');
        }
        // Mark selected rows
        checks.forEach(function (c) {
            var row = c.closest('.dl-song-row');
            if (row) row.classList.toggle('is-selected', c.checked);
        });
    }
    checks.forEach(function (c) { c.addEventListener('change', update); });
    update();
})();
</script>
</body>
</html>
