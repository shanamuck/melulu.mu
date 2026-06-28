<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = '';

$song_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($song_id <= 0) { header("Location: explore.php"); exit(); }

$song = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM song WHERE id=$song_id"));
if (!$song) { header("Location: explore.php"); exit(); }

$success = '';
$error = '';
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'explore.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $artist  = mysqli_real_escape_string($conn, trim($_POST['artist'] ?? ''));
    $album   = mysqli_real_escape_string($conn, trim($_POST['album'] ?? ''));
    $genre   = mysqli_real_escape_string($conn, trim($_POST['genre'] ?? ''));
    $mood    = trim($_POST['mood'] ?? '');
    $country = strtoupper(trim($_POST['country'] ?? ''));
    $country = substr($country, 0, 3);
    $valid_moods = ['happy', 'chill', 'sad', 'focus', 'party'];
    if ($mood !== '' && !in_array($mood, $valid_moods)) $mood = '';

    if ($title === '' || $artist === '') {
        $error = 'Title and artist are required.';
    } else {
        $sql = "UPDATE song SET title='$title', artist='$artist', album='$album', genre='$genre', mood='$mood', country='$country' WHERE id=$song_id";
        if (mysqli_query($conn, $sql)) {
            $success = 'Song updated.';
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $img_allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['thumbnail']['type'], $img_allowed)) {
                    $img_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                    $thumb_name = 'song_' . $song_id . '_thumb_' . time() . '.' . $img_ext;
                    $thumb_dest = 'assets/img/song-thumbnails/' . $thumb_name;
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_dest)) {
                        if ($song['thumbnail'] && file_exists($song['thumbnail'])) @unlink($song['thumbnail']);
                        mysqli_query($conn, "UPDATE song SET thumbnail='" . mysqli_real_escape_string($conn, $thumb_dest) . "' WHERE id=$song_id");
                        $success .= ' Artwork updated.';
                    } else { $error = 'Thumbnail upload failed.'; }
                } else { $error = 'Invalid image type.'; }
            }
            $song = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM song WHERE id=$song_id"));
        } else { $error = 'Database error: ' . mysqli_error($conn); }
    }
}

$genres_result = mysqli_query($conn, "SELECT DISTINCT genre FROM song WHERE genre IS NOT NULL AND genre <> '' ORDER BY genre ASC");
$genres = [];
while ($g = mysqli_fetch_assoc($genres_result)) { $genres[] = $g['genre']; }
$moods = ['happy' => '😊 Happy', 'chill' => '😌 Chill', 'sad' => '😔 Sad', 'focus' => '🧠 Focus', 'party' => '🎉 Party'];
$thumb_url = $song['thumbnail'] ?: 'assets/img/music-2.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Song - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
</head>
<body class="app-shell">

<?php include 'components/topnav.php'; ?>

<div class="app-shell-middle">
    <?php include 'components/sidebar.php'; ?>

    <div data-barba="container" data-barba-namespace="edit-song">
        <main class="app-content">

        <!-- Back link -->
        <a href="<?php echo htmlspecialchars($referrer); ?>" class="edit-back">← Back to songs</a>

        <!-- Hero: song identity -->
        <div class="edit-hero">
            <div class="edit-hero-artwork">
                <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="" id="edit-thumb-preview">
                <label class="edit-hero-cam" title="Change artwork">
                    <img src="assets/img/camera.svg" alt="">
                    <input type="file" name="thumbnail" accept="image/*"
                           onchange="document.getElementById('edit-thumb-preview').src = URL.createObjectURL(this.files[0])">
                </label>
            </div>
            <div class="edit-hero-info">
                <div class="edit-hero-title"><?php echo htmlspecialchars($song['title']); ?></div>
                <div class="edit-hero-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                <div class="edit-hero-meta">
                    <?php if ($song['album']): ?><span>💿 <?php echo htmlspecialchars($song['album']); ?></span><?php endif; ?>
                    <?php if ($song['genre']): ?><span class="edit-hero-tag"><?php echo htmlspecialchars($song['genre']); ?></span><?php endif; ?>
                    <?php if ($song['mood']): ?><span>🎵 <?php echo ucfirst($song['mood']); ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="auth-success" style="margin-bottom:var(--space-md);"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="auth-error" style="margin-bottom:var(--space-md);"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Edit form -->
        <form method="POST" enctype="multipart/form-data" class="edit-form-body">
            <div class="edit-section">
                <div class="edit-section-title">
                    <span class="edit-section-icon">📋</span>
                    Basic Information
                </div>

                <div class="edit-grid-2">
                    <div class="edit-field">
                        <label class="edit-label">Song title *</label>
                        <input class="edit-input" type="text" name="title"
                               value="<?php echo htmlspecialchars($song['title']); ?>" required>
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Artist *</label>
                        <input class="edit-input" type="text" name="artist"
                               value="<?php echo htmlspecialchars($song['artist']); ?>" required>
                    </div>
                </div>

                <div class="edit-field">
                    <label class="edit-label">Album</label>
                    <input class="edit-input" type="text" name="album"
                           value="<?php echo htmlspecialchars($song['album'] ?? ''); ?>" placeholder="Album name">
                </div>
            </div>

            <div class="edit-section">
                <div class="edit-section-title">
                    <span class="edit-section-icon">🏷️</span>
                    Classification
                </div>

                <div class="edit-grid-3">
                    <div class="edit-field">
                        <label class="edit-label">Genre</label>
                        <select name="genre" class="edit-input">
                            <option value="">None</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?php echo htmlspecialchars($g); ?>"
                                    <?php if ($song['genre'] === $g) echo 'selected'; ?>><?php echo htmlspecialchars($g); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Mood</label>
                        <select name="mood" class="edit-input">
                            <option value="">None</option>
                            <?php foreach ($moods as $key => $label): ?>
                                <option value="<?php echo $key; ?>"
                                    <?php if ($song['mood'] === $key) echo 'selected'; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Country</label>
                        <input class="edit-input" type="text" name="country" maxlength="3"
                               value="<?php echo htmlspecialchars($song['country'] ?? ''); ?>" placeholder="e.g. USA">
                    </div>
                </div>
            </div>

            <div class="edit-actions">
                <a href="<?php echo htmlspecialchars($referrer); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">Save Changes</button>
            </div>
        </form>

        </main>
    </div>
</div>

<?php include 'components/player.php'; ?>
<script src="assets/js/components.js"></script>
</body>
</html>
