<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = 'playlist';

$playlists_result = mysqli_query($conn, "SELECT * FROM playlist WHERE user_id=$user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Playlists - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="app-shell" data-barba="wrapper">

<!-- Top Nav -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + Barba container (right) -->
<div class="app-shell-middle">

    <!-- Sidebar — persistent, not in Barba container -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Barba container — wraps main content, swaps on navigation -->
    <div data-barba="container" data-barba-namespace="playlist">
        <main class="app-content">
            <div class="page-header">
                <h1>My Playlists</h1>
                <p>Organize your favorite songs into collections</p>
            </div>
            <a href="create_playlist.php" class="btn btn-primary" style="margin-bottom:var(--space-lg);display:inline-flex;">
                + Create New Playlist
            </a>
            <?php if (mysqli_num_rows($playlists_result) === 0): ?>
                <div style="color:var(--text-muted);padding:var(--space-xl) 0;text-align:center;">
                    You haven't created any playlists yet. Create your first one!
                </div>
            <?php else: ?>
                <div class="playlist-grid">
                    <?php while ($pl = mysqli_fetch_assoc($playlists_result)):
                        $cnt = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM playlist_song WHERE playlist_id=" . (int)$pl['id']))[0];
                    ?>
                        <a href="view_playlist.php?id=<?php echo (int)$pl['id']; ?>" class="playlist-card">
                            <div class="playlist-card-cover">
                                <img src="assets/img/music-2.svg" alt="" style="width:32px;height:32px;opacity:0.6;">
                            </div>
                            <div class="playlist-card-body">
                                <div class="playlist-card-name"><?php echo htmlspecialchars($pl['name']); ?></div>
                                <div class="playlist-card-meta"><?php echo $cnt; ?> song<?php echo $cnt !== 1 ? 's' : ''; ?></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

</div><!-- /app-shell-middle -->

<!-- Player — persistent, not in Barba container -->
<?php include 'components/player.php'; ?>

<script src="assets/js/components.js"></script>
</body>
</html>
