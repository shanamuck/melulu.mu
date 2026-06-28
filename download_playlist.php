<?php
/**
 * Download a playlist as an .m3u file.
 * GET /download_playlist.php?id=<playlist_id>
 *
 * The .m3u references each song by its basename. Most players will
 * only resolve relative paths if the .m3u sits next to the audio files,
 * so we emit just the song title + artist on a separate line as a
 * comment and use file basenames as the actual entries.
 */
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Unauthorized'); }

$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Missing or invalid id'); }

// Confirm the playlist belongs to the current user
$stmt = mysqli_prepare($conn, "SELECT id, name, description FROM playlist WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
mysqli_stmt_execute($stmt);
$playlist = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$playlist) { http_response_code(404); exit('Playlist not found'); }

// Fetch the songs in the playlist
$stmt = mysqli_prepare($conn, "
    SELECT song.title, song.artist, song.album, song.duration, song.file_path
    FROM song
    INNER JOIN playlist_song ON song.id = playlist_song.song_id
    WHERE playlist_song.playlist_id = ?
    ORDER BY song.title ASC
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Build the M3U contents
$lines = [];
$lines[] = '#EXTM3U';
$lines[] = '#PLAYLIST:' . $playlist['name'];
if (!empty($playlist['description'])) {
    $lines[] = '#DESCRIPTION:' . $playlist['description'];
}
$lines[] = '#GENERATED:M3U exported from Melulu';
$lines[] = '#TRACKS:' . (int)mysqli_num_rows($result);

while ($s = mysqli_fetch_assoc($result)) {
    $title  = $s['title']  ?? '';
    $artist = $s['artist'] ?? '';
    $dur    = $s['duration'] ?? '';
    $file   = $s['file_path'] ?? '';

    // Convert TIME (HH:MM:SS) to M3U seconds (EXTINF uses seconds)
    $seconds = 0;
    if ($dur && preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $dur, $m)) {
        $seconds = ((int)$m[1] * 3600) + ((int)$m[2] * 60) + (int)$m[3];
    }

    // #EXTINF:duration,Artist - Title
    $label = trim($artist . ' - ' . $title, ' -');
    $lines[] = '#EXTINF:' . $seconds . ',' . $label;

    // Path entry — use basename of file_path so the m3u is portable
    $entry = $file !== '' ? basename($file) : $label;
    $lines[] = $entry;
}
mysqli_stmt_close($stmt);

$body = implode("\r\n", $lines) . "\r\n";

// Safe filename
$filename = preg_replace('/[^\w\-\. ]+/u', '_', $playlist['name']);
if ($filename === '') { $filename = 'playlist_' . $id; }
$filename .= '.m3u';

while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($body));
header('Cache-Control: must-revalidate, no-store, no-cache, private');
header('Pragma: public');

echo $body;
exit;
