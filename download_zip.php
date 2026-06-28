<?php
/**
 * Download multiple songs as a single ZIP archive.
 * GET /download_zip.php?ids=1,2,3
 *
 * Songs that have no audio file on disk are skipped (with a warning header).
 * The response is streamed in chunks to handle large archives safely.
 */
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Unauthorized'); }

$idsParam = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$ids = array_values(array_filter(array_map('intval', explode(',', $idsParam)), function ($v) {
    return $v > 0;
}));
if (count($ids) === 0) {
    http_response_code(400);
    exit('No song ids provided.');
}
// Cap to a reasonable batch size
$ids = array_slice($ids, 0, 100);

include 'db.php';

// Build a placeholder for IN clause
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Fetch the songs
$stmt = mysqli_prepare($conn, "SELECT id, title, artist, file_path FROM song WHERE id IN ($placeholders) ORDER BY title ASC");
mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...$ids);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$songs = [];
while ($row = $result->fetch_assoc()) {
    $songs[] = $row;
}
mysqli_stmt_close($stmt);

if (count($songs) === 0) {
    http_response_code(404);
    exit('No matching songs found.');
}

// Helper: find the actual on-disk path for a stored file_path
function resolve_song_path($filePath) {
    $candidates = [
        __DIR__ . '/' . $filePath,
        __DIR__ . '/assets/' . $filePath,
        __DIR__ . '/songs/' . $filePath,
        __DIR__ . '/music/' . $filePath,
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) return $c;
    }
    return null;
}

// Find which songs have files
$found  = []; // [song_id => ['disk' => $path, 'zip' => $zipName]]
$missing = []; // [song_id => title]
foreach ($songs as $s) {
    if (empty($s['file_path'])) { $missing[] = $s['title']; continue; }
    $diskPath = resolve_song_path($s['file_path']);
    if (!$diskPath) { $missing[] = $s['title']; continue; }
    // Build a clean name inside the zip: "Artist - Title.mp3"
    $label = trim(($s['artist'] ?? '') . ' - ' . ($s['title'] ?? ''), ' -');
    if ($label === '') $label = 'song_' . $s['id'];
    $ext = pathinfo($diskPath, PATHINFO_EXTENSION) ?: 'mp3';
    $zipName = preg_replace('/[^\w\-\. ]+/u', '_', $label) . '.' . $ext;
    $found[$s['id']] = ['disk' => $diskPath, 'zip' => $zipName, 'title' => $s['title']];
}

if (count($found) === 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "None of the selected songs have an audio file on disk yet.\n\n";
    echo "Missing:\n - " . implode("\n - ", array_map('htmlspecialchars', $missing));
    exit;
}

// If X-Force-Error is set (for testing), bail before sending headers
while (ob_get_level() > 0) { ob_end_clean(); }

// Set headers BEFORE creating the zip — but we have to be careful:
// ZipArchive writes to a temp file, so we can set headers first.
$zipName = 'melulu-songs-' . date('Ymd-His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: public');

// Create a temp file for the zip (more robust than streaming directly)
$tmp = tempnam(sys_get_temp_dir(), 'melulu_zip_');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Failed to create zip archive');
}

// Track zip entry names to avoid collisions
$usedNames = [];
foreach ($found as $songId => $info) {
    $name = $info['zip'];
    if (isset($usedNames[$name])) {
        // Disambiguate: "Title (1).mp3", "Title (2).mp3", ...
        $i = 1;
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        do {
            $name = $base . " ($i)." . $ext;
            $i++;
        } while (isset($usedNames[$name]));
    }
    $usedNames[$name] = true;
    $zip->addFile($info['disk'], $name);
}

// If any songs were skipped, include a README inside the zip
if (!empty($missing)) {
    $readme  = "Melulu — Multi-song download\r\n";
    $readme .= "Generated: " . date('c') . "\r\n\r\n";
    $readme .= count($found) . " of " . count($songs) . " songs were included.\r\n\r\n";
    if (!empty($missing)) {
        $readme .= "Skipped (no audio file on disk):\r\n";
        foreach ($missing as $t) {
            $readme .= "  - " . $t . "\r\n";
        }
    }
    $zip->addFromString('README.txt', $readme);
}

$zip->close();

// Stream the zip
$size = filesize($tmp);
header('Content-Length: ' . $size);

$fp = fopen($tmp, 'rb');
if ($fp === false) {
    @unlink($tmp);
    http_response_code(500);
    exit('Failed to open zip');
}
while (!feof($fp)) {
    $chunk = fread($fp, 8192);
    if ($chunk === false) break;
    echo $chunk;
    flush();
}
fclose($fp);
@unlink($tmp);
exit;
