<?php
/**
 * Top Navigation Bar component
 * Expects: $username (string)
 */
$username = $username ?? 'User';

// Fetch the real profile picture from the DB
$tnav_pic = 'assets/img/user-round.svg';
if (isset($_SESSION['user_id'])) {
    $sid = (int)$_SESSION['user_id'];
    $sr = @mysqli_query($conn ?? null, "SELECT profile_pic FROM user_info WHERE id=$sid");
    if ($sr && $row = mysqli_fetch_assoc($sr)) {
        $tnav_pic = $row['profile_pic'] ?: 'assets/img/user-round.svg';
    }
}
?>
<!-- Top Navigation -->
<nav class="app-topnav">
    <a href="home.php" class="nav-logo">
        <img src="assets/img/logo.png" alt="Melulu">
        <span>melulu</span>
    </a>

    <div class="nav-search">
        <form class="search-bar" action="explore.php" method="GET" id="topnav-search-form" data-no-barba>
            <img src="assets/img/search.svg" alt="" aria-hidden="true">
            <input type="text" name="q" placeholder="Search songs, artists, playlists..."
                   id="topnav-search-input" autocomplete="off">
            <?php
            // If we're currently on explore.php, preserve the country filter
            $topnav_country = isset($_GET['country']) ? $_GET['country'] : '';
            if ($topnav_country !== ''):
            ?>
                <input type="hidden" name="country" value="<?php echo htmlspecialchars($topnav_country); ?>">
            <?php endif; ?>
        </form>
    </div>

    <div class="nav-actions">
        <!-- Notifications Bell -->
        <div class="nav-icon-btn" id="notif-btn" onclick="toggleNotifications()" style="position:relative">
            <img src="assets/img/bell-dot.svg" alt="Notifications">
            <span class="nav-badge" id="notif-badge" style="display:none">0</span>
        </div>
        <div class="notif-dropdown" id="notif-dropdown">
            <div class="notif-header">
                <span>Notifications</span>
                <button onclick="markAllRead()">Mark all read</button>
            </div>
            <div class="notif-list" id="notif-list">
                <div class="notif-empty">Loading...</div>
            </div>
        </div>

        <!-- User Avatar — links to profile, shows real pic -->
        <a href="profile.php" class="nav-icon-btn" title="<?php echo htmlspecialchars($username); ?>">
            <img src="<?php echo htmlspecialchars($tnav_pic); ?>" alt="Profile"
                 style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
        </a>
    </div>
</nav>
