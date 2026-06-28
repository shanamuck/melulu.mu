<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['playlist_name']);
    $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
    if ($name === '') {
        $error = 'Please enter a playlist name.';
    } else {
        $n = mysqli_real_escape_string($conn, $name);
        $d = mysqli_real_escape_string($conn, $desc);
        $ins = mysqli_query($conn, "INSERT INTO playlist(user_id, name, description) VALUES($user_id, '$n', '$d')");
        if ($ins) { header("Location: playlist.php"); exit(); }
        else { $error = 'Error creating playlist.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Playlist - Melulu</title>
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
    <div data-barba="container" data-barba-namespace="create-playlist">
        <main class="app-content">

        <a href="playlist.php" class="page-link">← Back to Playlists</a>

        <div class="page-header" style="margin-top:var(--space-sm);">
            <h1>Create New Playlist</h1>
            <p>Give your playlist a name and start adding songs</p>
        </div>

        <form method="POST" class="page-form">
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <input type="text" name="playlist_name" placeholder="Playlist Name" required>
            <br>
            <input type="text" name="description" placeholder="Description (optional)">
            <br><br>
            <button type="submit" name="create" class="btn btn-primary">Create Playlist</button>
        </form>

    </main>
    </div><!-- /barba container -->

</div><!-- /app-shell-middle -->

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/components.js"></script>
</body>
</html>
