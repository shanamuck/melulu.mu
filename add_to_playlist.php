<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = '';
$error = '';
$just_created_id = 0; // track a freshly-created playlist to pre-select it in the dropdown

/* ─────────────────────────────────────────────────────────
   Handle POST actions
   - action=add            : add a song to a chosen playlist
   - action=create_playlist: create a new playlist (from the dialog)
   ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'create_playlist') {
        $name = trim($_POST['playlist_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $carry_song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;
        if ($name === '') {
            $error = 'Please enter a playlist name.';
        } else {
            $n = mysqli_real_escape_string($conn, $name);
            $d = mysqli_real_escape_string($conn, $desc);
            $ins = mysqli_query($conn, "INSERT INTO playlist(user_id, name, description) VALUES($user_id, '$n', '$d')");
            if ($ins) {
                $new_playlist_id = (int)mysqli_insert_id($conn);
                // If a song was carried over (user came from 'add to playlist'),
                // add the song to the new playlist in one step and redirect to the playlist view.
                if ($carry_song_id > 0) {
                    $check = mysqli_prepare($conn, "SELECT 1 FROM song WHERE id = ?");
                    mysqli_stmt_bind_param($check, 'i', $carry_song_id);
                    mysqli_stmt_execute($check);
                    $exists = mysqli_stmt_get_result($check);
                    if (mysqli_num_rows($exists) > 0) {
                        $dup = mysqli_prepare($conn, "SELECT 1 FROM playlist_song WHERE playlist_id = ? AND song_id = ?");
                        mysqli_stmt_bind_param($dup, 'ii', $new_playlist_id, $carry_song_id);
                        mysqli_stmt_execute($dup);
                        if (mysqli_num_rows(mysqli_stmt_get_result($dup)) === 0) {
                            $add = mysqli_prepare($conn, "INSERT INTO playlist_song (playlist_id, song_id) VALUES (?, ?)");
                            mysqli_stmt_bind_param($add, 'ii', $new_playlist_id, $carry_song_id);
                            mysqli_stmt_execute($add);
                            mysqli_stmt_close($add);
                        }
                        mysqli_stmt_close($dup);
                    }
                    mysqli_stmt_close($check);
                    // One-shot flow: playlist created + song added → go to playlist view
                    header("Location: view_playlist.php?id=$new_playlist_id");
                    exit();
                }
                // No song to add (user came from create_playlist.php via the sidebar)
                $just_created_id = $new_playlist_id;
            } else {
                $error = 'Error creating playlist.';
            }
        }
    } else {
        // action=add — original behaviour
        $song_id     = isset($_POST['song_id'])     ? (int)$_POST['song_id']     : 0;
        $playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
        if ($song_id > 0 && $playlist_id > 0) {
            // Make sure the playlist belongs to the current user
            $own = mysqli_query($conn, "SELECT 1 FROM playlist WHERE id=$playlist_id AND user_id=$user_id");
            if (mysqli_num_rows($own) === 0) {
                $error = 'Invalid song or playlist.';
            } else {
                $check = mysqli_query($conn, "SELECT 1 FROM playlist_song WHERE playlist_id=$playlist_id AND song_id=$song_id");
                if (mysqli_num_rows($check) > 0) {
                    $error = 'Song is already in this playlist.';
                } else {
                    mysqli_query($conn, "INSERT INTO playlist_song(playlist_id, song_id) VALUES($playlist_id, $song_id)");
                    header("Location: view_playlist.php?id=$playlist_id");
                    exit();
                }
            }
        } else {
            $error = 'Invalid song or playlist.';
        }
    }
}

/* ─────────────────────────────────────────────────────────
   Load data needed by the form
   ───────────────────────────────────────────────────────── */
$song_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$song = null;
if ($song_id > 0) {
    $sr = mysqli_query($conn, "SELECT * FROM song WHERE id=$song_id");
    $song = mysqli_fetch_assoc($sr);
}
$playlists_result = mysqli_query($conn, "SELECT * FROM playlist WHERE user_id=$user_id ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Playlist - Melulu</title>
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
    <div data-barba="container" data-barba-namespace="add-to-playlist">
        <main class="app-content">

        <a href="explore.php" class="page-link">← Back to Explore</a>

        <div class="page-header" style="margin-top:var(--space-sm);">
            <h1>Add to Playlist</h1>
            <?php if ($song): ?>
                <p>Adding <strong><?php echo htmlspecialchars($song['title']); ?></strong> by <?php echo htmlspecialchars($song['artist']); ?></p>
            <?php else: ?>
                <p>Select a playlist to add this song to</p>
            <?php endif; ?>
        </div>

        <?php if ($song): ?>
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!--
              ┌──────────────────────────────────────────┐
              │  add_to_playlist COMPONENT (form)       │
              │  - "select playlist" + "create new"     │
              │  - dialog contains the create form      │
              └──────────────────────────────────────────┘
            -->
            <form method="POST" class="page-form add-to-playlist-form" action="add_to_playlist.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="song_id" value="<?php echo (int)$song_id; ?>">

                <label class="auth-label">Choose a playlist</label>
                <div class="playlist-picker">
                    <select name="playlist_id" required>
                        <option value="">Select a playlist...</option>
                        <?php while ($pl = mysqli_fetch_assoc($playlists_result)): ?>
                            <option value="<?php echo (int)$pl['id']; ?>"
                                <?php if ((int)$pl['id'] === $just_created_id) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($pl['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" class="btn btn-secondary" id="open-create-dialog"
                            title="Create a new playlist">
                        <img src="assets/img/plus.svg" alt="" style="width:14px;height:14px;">
                        New
                    </button>
                </div>

                <div style="margin-top:var(--space-md);">
                    <button type="submit" class="btn btn-primary">Add to Playlist</button>
                </div>
            </form>

            <!--
              ┌──────────────────────────────────────────┐
              │  create_playlist DIALOG (component)      │
              └──────────────────────────────────────────┘
            -->
            <dialog id="create-playlist-dialog">
                <form method="POST" action="add_to_playlist.php" class="page-form">
                    <input type="hidden" name="action" value="create_playlist">
                    <!-- carry the song id through so we return to the same add screen -->
                    <input type="hidden" name="song_id" value="<?php echo (int)$song_id; ?>">

                    <h2>Create new playlist</h2>
                    <p>Give it a name — you can edit details later</p>

                    <label class="auth-label">Playlist name</label>
                    <input type="text" name="playlist_name" placeholder="My new playlist" required autofocus>

                    <label class="auth-label">Description <span class="optional">(optional)</span></label>
                    <input type="text" name="description" placeholder="A short description">

                    <?php if ($error && $just_created_id === 0): ?>
                        <!-- only show dialog-scoped errors (i.e. create errors) -->
                        <div class="auth-error" style="margin-top:var(--space-sm);">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="dialog-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-create-dialog">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </dialog>

            <?php if ($just_created_id > 0): ?>
                <script>
                // auto-open the dialog? No — close it and show success inline.
                // The dropdown has already been rendered with the new playlist pre-selected.
                </script>
                <div class="auth-success" style="margin-top:var(--space-md);">
                    ✓ Playlist created — it's pre-selected above. Click <strong>Add to Playlist</strong> to finish.
                </div>
            <?php elseif ($error && isset($_POST['action']) && $_POST['action'] === 'create_playlist'): ?>
                <script>
                // Re-open the dialog on validation error so the user can fix it
                document.addEventListener('DOMContentLoaded', function () {
                    var d = document.getElementById('create-playlist-dialog');
                    if (d && typeof d.showModal === 'function') d.showModal();
                });
                </script>
            <?php endif; ?>

        <?php else: ?>
            <div style="color:var(--text-muted);">
                No song selected. <a href="explore.php" style="color:var(--accent-soft);">Browse songs</a>
            </div>
        <?php endif; ?>

    </main>
    </div><!-- /barba container -->

</div><!-- /app-shell-middle -->

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/components.js"></script>
<script>
(function () {
    // Open/close handlers for the create-playlist dialog
    document.addEventListener('DOMContentLoaded', function () {
        var dlg     = document.getElementById('create-playlist-dialog');
        var openBtn = document.getElementById('open-create-dialog');
        var cancel  = document.getElementById('cancel-create-dialog');
        if (!dlg || !openBtn) return;

        openBtn.addEventListener('click', function () {
            if (typeof dlg.showModal === 'function') dlg.showModal();
            else dlg.setAttribute('open', '');
        });
        if (cancel) {
            cancel.addEventListener('click', function () {
                if (typeof dlg.close === 'function') dlg.close();
                else dlg.removeAttribute('open');
            });
        }
        // Click on the backdrop closes the dialog (native <dialog> only)
        dlg.addEventListener('click', function (e) {
            var rect = dlg.getBoundingClientRect();
            var inside = e.clientX >= rect.left && e.clientX <= rect.right
                      && e.clientY >= rect.top  && e.clientY <= rect.bottom;
            if (!inside && typeof dlg.close === 'function') dlg.close();
        });
    });
})();
</script>
</body>
</html>
