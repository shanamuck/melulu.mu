<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: home.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $r = mysqli_query($conn, "SELECT * FROM user_info WHERE username='$username'");
    if (mysqli_num_rows($r) === 1) {
        $user = mysqli_fetch_assoc($r);
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: home.php");
            exit();
        } else { $error = "Invalid username or password."; }
    } else { $error = "Invalid username or password."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="auth-page" data-barba="wrapper">

<div data-barba="container" data-barba-namespace="login">

    <!-- LEFT: Brand panel with grayscale world map background -->
    <div class="auth-brand">
        <div class="auth-brand-inner">
            <img src="assets/img/logo.png" alt="Melulu">
            <h1>melulu</h1>
            <p>Discover music from every corner of the world. Create playlists, explore genres, and connect with sounds across borders.</p>
        </div>
        <div class="auth-brand-footer">© Melulu — Music Without Borders</div>
    </div>

    <!-- RIGHT: Form -->
    <div class="auth-form-wrap">
        <h1>Welcome back</h1>
        <p class="auth-sub">Sign in to continue your music journey</p>

        <form action="login.php" method="post">
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <label class="auth-label">Username</label>
            <input class="auth-input" type="text" name="username" placeholder="Enter your username" required>
            <label class="auth-label">Password</label>
            <input class="auth-input" type="password" name="password" placeholder="Enter your password" required>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:8px;">Login</button>
        </form>
        <p class="auth-link">Don't have an account? <a href="signup.php">Sign up</a></p>
    </div>

</div>

</body>
</html>
