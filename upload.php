<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM user_info WHERE id=$user_id"));
$username = $user['username'] ?? 'User';
$current_page = 'upload';

$success = '';
$error = '';

// ── Handle upload ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $title  = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $artist = mysqli_real_escape_string($conn, trim($_POST['artist'] ?? ''));
    $album  = mysqli_real_escape_string($conn, trim($_POST['album'] ?? ''));
    $genre  = mysqli_real_escape_string($conn, trim($_POST['genre'] ?? ''));
    $duration = '00:00:00';

    if ($title === '' || $artist === '') {
        $error = 'Title and artist are required.';
    } elseif (!isset($_FILES['song_file']) || $_FILES['song_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid audio file.';
    } else {
        $allowed = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/flac', 'audio/x-m4a', 'audio/m4a', 'audio/aac'];
        $type = $_FILES['song_file']['type'];
        $ext = strtolower(pathinfo($_FILES['song_file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma'];
        if (!in_array($type, $allowed) && !in_array($ext, $allowed_ext)) {
            $error = 'Only audio files (MP3, WAV, OGG, FLAC, M4A, AAC) are allowed.';
        } else {
            $safe_title = preg_replace('/[^\w\-\. ]+/u', '_', $title);
            $filename = $safe_title . '_' . time() . '.' . $ext;
            $dest = 'music/' . $filename;

            // Handle thumbnail upload
            $thumbnail = '';
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $img_allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['thumbnail']['type'], $img_allowed)) {
                    $img_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                    $thumb_name = $safe_title . '_thumb_' . time() . '.' . $img_ext;
                    $thumb_dest = 'assets/img/song-thumbnails/' . $thumb_name;
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_dest)) {
                        $thumbnail = $thumb_dest;
                    }
                }
            }

            if (move_uploaded_file($_FILES['song_file']['tmp_name'], $dest)) {
                $file_path = $dest;
                $sql = "INSERT INTO song (title, artist, album, genre, mood, file_path)
                        VALUES ('$title', '$artist', '$album', '$genre', '$file_path')";
                if (mysqli_query($conn, $sql)) {
                    $new_id = mysqli_insert_id($conn);
                    if ($thumbnail) {
                        mysqli_query($conn, "UPDATE song SET thumbnail='" . mysqli_real_escape_string($conn, $thumbnail) . "' WHERE id=$new_id");
                    }
                    $success = 'Song uploaded successfully. <a href="explore.php" style="color:var(--accent-soft);">View all songs</a>';
                } else {
                    $error = 'Database insert failed: ' . mysqli_error($conn);
                    @unlink($dest);
                    if ($thumbnail && file_exists($thumbnail)) @unlink($thumbnail);
                }
            } else {
                $error = 'Failed to save file. Check directory permissions.';
            }
        }
    }
}

$genres_result = mysqli_query($conn, "SELECT DISTINCT genre FROM song WHERE genre IS NOT NULL AND genre <> '' ORDER BY genre ASC");
$genres = [];
while ($g = mysqli_fetch_assoc($genres_result)) { $genres[] = $g['genre']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Music - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css"> 
</head>
<body class="app-shell">

<!-- Top Nav -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + main (right) -->
<div class="app-shell-middle">

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Content -->
    <div data-barba="container" data-barba-namespace="upload">
        <main class="app-content">

        <div class="page-header">
            <h1>Upload Music</h1>
            <p>Add new songs to the library</p>
        </div>

        <?php if ($success): ?>
            <div class="auth-success" style="margin-bottom:var(--space-md);"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="auth-error" style="margin-bottom:var(--space-md);"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="upload-card">
            <div class="upload-dropzone" id="upload-dropzone">
                <img src="assets/img/music-2.svg" alt="" class="upload-dropzone-icon">
                <div class="upload-dropzone-text">Drop your audio file here or click to browse</div>
                <div class="upload-dropzone-hint">Supports MP3, WAV, OGG, FLAC, M4A, AAC</div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="upload-form" id="upload-form">
                <input type="hidden" name="upload" value="1">

                <div class="upload-file-input-wrap">
                    <input type="file" name="song_file" id="song-file" accept="audio/*" required>
                </div>

                <div class="profile-row">
                    <div class="profile-field">
                        <label class="auth-label">Song title *</label>
                        <input class="auth-input" type="text" name="title" placeholder="Enter song title" required>
                    </div>
                    <div class="profile-field">
                        <label class="auth-label">Artist *</label>
                        <input class="auth-input" type="text" name="artist" placeholder="Artist name" required>
                    </div>
                </div>

                <div class="profile-row">
                    <div class="profile-field">
                        <label class="auth-label">Album</label>
                        <input class="auth-input" type="text" name="album" placeholder="Album name (optional)">
                    </div>
                    <div class="profile-field">
                        <label class="auth-label">Genre</label>
                        <select name="genre" class="auth-input">
                            <option value="">Select genre (optional)</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Mood selection -->
                <label class="auth-label">Mood <span class="auth-optional">(optional)</span></label>
                <select name="mood" class="auth-input" style="margin-bottom:var(--space-sm);">
                    <option value="">No mood</option>
                    <option value="happy">😊 Happy</option>
                    <option value="chill">😌 Chill</option>
                    <option value="sad">😔 Sad</option>
                    <option value="focus">🧠 Focus</option>
                    <option value="party">🎉 Party</option>
                </select>

                                <!-- Thumbnail upload -->
                <label class="auth-label" style="margin-top:var(--space-sm);">Thumbnail image <span class="auth-optional">(optional)</span></label>
                <div class="upload-thumb-wrap">
                    <label class="upload-thumb-btn">
                        <img src="assets/img/image.svg" alt="" class="upload-thumb-icon">
                        <span id="upload-thumb-label">Choose image</span>
                        <input type="file" name="thumbnail" accept="image/*" id="upload-thumb-input"
                               onchange="document.getElementById('upload-thumb-label').textContent = this.files[0] ? this.files[0].name : 'Choose image'">
                    </label>
                </div>

                <div class="upload-form-footer">
                    <span class="upload-file-name" id="upload-file-name">No file selected</span>
                    <button type="submit" class="btn btn-primary">Upload Song</button>
                </div>
            </form>
        </div>

        </main>
    </div>

</div>

<!-- Player -->
<?php include 'components/player.php'; ?>

<script src="assets/js/components.js"></script>
<script>
(function () {
    var dropzone = document.getElementById('upload-dropzone');
    var fileInput = document.getElementById('song-file');
    var fileName = document.getElementById('upload-file-name');

    dropzone.addEventListener('click', function () { fileInput.click(); });
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('is-dragover');
    });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFileName();
        }
    });
    fileInput.addEventListener('change', updateFileName);

    function updateFileName() {
        if (fileInput.files.length > 0) {
            fileName.textContent = fileInput.files[0].name;
            dropzone.classList.add('has-file');
            dropzone.querySelector('.upload-dropzone-text').textContent = fileInput.files[0].name;
        }
    }
})();
</script>
</body>
</html>
