<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: home.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $password   = mysqli_real_escape_string($conn, $_POST['password']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $check = mysqli_query($conn, "SELECT * FROM user_info WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username already taken.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = mysqli_query($conn, "INSERT INTO user_info (username, password, email, phone, first_name, last_name)
            VALUES ('$username', '$hash', '$email', '$phone', '$first_name', '$last_name')");
        if ($ins) { header("Location: home.php"); exit(); }
        else { $error = "Error creating account."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
    </head>
<body class="auth-page" data-barba="wrapper">

<div data-barba="container" data-barba-namespace="signup">

    <!-- LEFT: Brand panel with grayscale world map background -->
    <div class="auth-brand">
        <div class="auth-brand-inner">
            <img src="assets/img/logo.png" alt="Melulu">
            <h1>melulu</h1>
            <p>Join the community. Discover music from every corner of the world, build playlists, and connect with sounds across borders.</p>
        </div>
        <div class="auth-brand-footer">© Melulu — Music Without Borders</div>
    </div>

    <!-- RIGHT: Form -->
    <div class="auth-form-wrap">
        <h1>Create account</h1>
        <p class="auth-sub">Start your music journey today</p>

        <form action="signup.php" method="post">
            <?php if ($error): ?>
                <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="auth-row">
                <div>
                    <label class="auth-label">First name</label>
                    <input class="auth-input" type="text" name="first_name" placeholder="First" required>
                </div>
                <div>
                    <label class="auth-label">Last name</label>
                    <input class="auth-input" type="text" name="last_name" placeholder="Last" required>
                </div>
            </div>
            <label class="auth-label">Username</label>
            <input class="auth-input" type="text" name="username" placeholder="Choose a username" required>
            <label class="auth-label">Email</label>
            <input class="auth-input" type="email" name="email" placeholder="you@example.com" required>
            <label class="auth-label">Phone</label>
            <input class="auth-input" type="text" name="phone" placeholder="+1 234 567 8900" required>
            <label class="auth-label">Password</label>
            <input class="auth-input" type="password" name="password" placeholder="Create a password" required>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:8px;">Create Account</button>
        </form>
        <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
    </div>

</div>

</body>
</html>
