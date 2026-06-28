<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: home.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melulu — Music Without Borders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="landing-page" data-barba="wrapper">
<div data-barba="container" data-barba-namespace="index">
    <div class="landing">
        <div class="landing-inner">
            <div class="landing-logo">
                <img src="assets/img/logo.png" alt="Melulu">
            </div>
            <h1>melulu</h1>
            <p>Discover music from every corner of the world.<br>Create playlists, explore genres, and connect with sounds across borders.</p>
            <div class="landing-actions">
                <a href="signup.php" class="landing-btn primary">Get Started</a>
                <a href="login.php" class="landing-btn secondary">Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
