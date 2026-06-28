<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_info WHERE id=$user_id"));
if (!$user) { header("Location: login.php"); exit(); }
$username = $user['username'];
$current_page = 'profile';

$success = '';
$error = '';

// ── Handle form submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name'] ?? ''));
        $last_name  = mysqli_real_escape_string($conn, trim($_POST['last_name'] ?? ''));
        $email      = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $phone      = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));

        // Check email uniqueness (exclude current user)
        $check = mysqli_query($conn, "SELECT id FROM user_info WHERE email='$email' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Email already in use by another account.';
        } else {
            // Change password if provided
            $pw_sql = '';
            $new_pass = trim($_POST['new_password'] ?? '');
            if ($new_pass !== '') {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pw_sql = ", password='" . mysqli_real_escape_string($conn, $hashed) . "'";
            }
            $sql = "UPDATE user_info SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' $pw_sql WHERE id=$user_id";
            if (mysqli_query($conn, $sql)) {
                $success = 'Profile updated successfully.';
                // Refresh user data
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_info WHERE id=$user_id"));
            } else {
                $error = 'Error updating profile.';
            }
        }
    }

    if ($action === 'upload_pic') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $type = $_FILES['profile_pic']['type'];
            if (!in_array($type, $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WebP images are allowed.';
            } else {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $dest = 'assets/img/profiles/' . $filename;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                    // Delete old pic if not default
                    $old = $user['profile_pic'] ?? '';
                    mysqli_query($conn, "UPDATE user_info SET profile_pic='" . mysqli_real_escape_string($conn, $dest) . "' WHERE id=$user_id");
                    if ($old && $old !== 'assets/img/user.svg' && file_exists($old)) {
                        @unlink($old);
                    }
                    $success = 'Profile picture updated.';
                    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_info WHERE id=$user_id"));
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        } else {
            $error = 'No file selected or upload error.';
        }
    }
}

$profile_pic = $user['profile_pic'] ?: 'assets/img/user.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Melulu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/style.css">
    <link rel="stylesheet" type="text/css" href="assets/mobile.css">
</head>
<body class="app-shell">

<!-- Top Nav — persistent -->
<?php include 'components/topnav.php'; ?>

<!-- Middle row: sidebar (left) + main (right) -->
<div class="app-shell-middle">

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Content -->
    <div data-barba="container" data-barba-namespace="profile">
        <main class="app-content">

        <div class="page-header">
            <h1>Profile</h1>
            <p>Manage your account details</p>
        </div>

        <?php if ($success): ?>
            <div class="auth-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Profile Picture -->
        <div class="profile-section">
            <div class="profile-pic-section">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic" id="profile-pic-img">
                <div class="profile-pic-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <form method="POST" enctype="multipart/form-data" class="profile-pic-form">
                    <input type="hidden" name="action" value="upload_pic">
                    <label class="btn btn-primary profile-upload-btn">
                        Change photo
                        <input type="file" name="profile_pic" accept="image/*" style="display:none" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
        </div>

        <!-- Info Form -->
        <div class="profile-section">
            <h2 class="profile-section-title">Account Information</h2>
            <form method="POST" class="profile-form">
                <input type="hidden" name="action" value="update_info">

                <div class="profile-row">
                    <div class="profile-field">
                        <label class="auth-label">First name</label>
                        <input class="auth-input" type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="profile-field">
                        <label class="auth-label">Last name</label>
                        <input class="auth-input" type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <label class="auth-label">Username</label>
                <input class="auth-input" type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity:0.6;cursor:not-allowed;">

                <label class="auth-label">Email</label>
                <input class="auth-input" type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label class="auth-label">Phone</label>
                <input class="auth-input" type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

                <hr class="profile-hr">

                <label class="auth-label">New password <span class="auth-optional">(leave blank to keep current)</span></label>
                <input class="auth-input" type="password" name="new_password" placeholder="••••••••">

                <button type="submit" class="btn btn-primary" style="margin-top:var(--space-md);">Save Changes</button>
            </form>
        </div>

        </main>
    </div>

</div>

<!-- Player — persistent -->
<?php include 'components/player.php'; ?>

<script src="assets/js/components.js"></script>
</body>
</html>
