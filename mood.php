<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = 'mood';

// Read the mood from the URL (?mood=happy) and persist it in the session
if (isset($_GET['mood']) && in_array($_GET['mood'], ['happy', 'chill', 'sad', 'focus', 'party'], true)) {
    $_SESSION['mood'] = $_GET['mood'];
}
$mood = $_SESSION['mood'] ?? '';

$dog_map = [
    'happy' => 'happy_dog.png',
    'sad'   => 'sad_dog.png',
    'chill' => 'chill_dog.png',
    'focus' => 'focus_dog.png',
    'party' => 'party_dog.png',
];
$dog = $dog_map[$mood] ?? 'default_dog.png';

// Fetch songs that match the selected mood (per-song mood, not session)
$songs_result = null;
if ($mood !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id, title, artist, album, genre, country
                                    FROM song WHERE mood = ? ORDER BY artist ASC, title ASC LIMIT 50");
    mysqli_stmt_bind_param($stmt, 's', $mood);
    mysqli_stmt_execute($stmt);
    $songs_result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood - Melulu</title>
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
    <div data-barba="container" data-barba-namespace="mood">
        <main class="app-content">

        <div class="page-header">
            <h1>Your Mood</h1>
            <p>Set your vibe and we'll tailor your experience</p>
        </div>

        <!-- Mood Chips -->
        <div style="margin-bottom:var(--space-lg);">
            <?php
            $moods = [
                'happy' => '😊 Happy',
                'chill' => '😌 Chill',
                'sad'   => '😔 Sad',
                'focus' => '🧠 Focus',
                'party' => '🎉 Party',
            ];
            foreach ($moods as $key => $label):
            ?>
                <a href="mood.php?mood=<?php echo $key; ?>"
                   class="chip <?php if ($mood === $key) echo 'active'; ?>"
                   data-no-barba>
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($mood): ?>
        <!-- Mood Display -->
        <div class="card" style="display:flex;align-items:center;gap:var(--space-lg);max-width:500px;">
            <div style="flex-shrink:0;">
                <img src="dogs/<?php echo $dog; ?>" alt="<?php echo ucfirst($mood); ?>"
                     style="width:100px;height:100px;border-radius:var(--radius-md);object-fit:cover;">
            </div>
            <div>
                <h2 style="font-size:var(--text-heading);font-weight:var(--weight-semibold);color:var(--accent-bright);margin-bottom:6px;">
                    Today's Mood: <?php echo ucfirst($mood); ?>
                </h2>
                <p style="color:var(--text-muted);font-size:var(--text-body);">We've got the perfect vibes for you.</p>
            </div>
        </div>

        <?php if ($songs_result && mysqli_num_rows($songs_result) > 0): ?>
            <!-- Songs that match the selected mood -->
            <div class="section-header" style="margin-top:var(--space-lg);">
                <h2>Songs for this mood</h2>
                <span class="section-header-count"><?php echo (int)mysqli_num_rows($songs_result); ?></span>
            </div>
            <div class="song-list">
                <?php while ($s = mysqli_fetch_assoc($songs_result)): ?>
                    <div class="song-row"
                         data-id="<?php echo (int)$s['id']; ?>"
                         data-src=""
                         data-title="<?php echo htmlspecialchars($s['title']); ?>"
                         data-artist="<?php echo htmlspecialchars($s['artist']); ?>">
                        <span class="song-row-num">·</span>
                        <div class="song-row-thumb">
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
                        <div class="song-row-actions">
                            <a href="add_to_playlist.php?id=<?php echo (int)$s['id']; ?>" class="row-add-btn" data-no-barba title="Add to playlist">
                                <img src="assets/img/circle-plus.svg" alt="Add">
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
    </div><!-- /barba container -->

</div><!-- /app-shell-middle -->

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/audio-player.js"></script>\n<script src="assets/js/components.js"></script>
</body>
</html>
