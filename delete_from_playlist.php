<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$playlist_id = $_POST['playlist_id'];
$song_id = $_POST['song_id'];
mysqli_query(
    $conn,
    "DELETE FROM playlist_song
    WHERE playlist_id='$playlist_id' AND song_id='$song_id'"
);
header("Location: playlist.php?id=$playlist_id");
?>