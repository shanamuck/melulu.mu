<?php
/**
 * Sidebar Navigation component
 * Expects: $current_page (string) — e.g. 'home', 'explore', 'playlist'
 *          $username (string)
 */
$current_page = $current_page ?? '';
$username     = $username ?? 'User';

// Fetch the real profile picture from the DB
$sidebar_profile_pic = 'assets/img/user-round.svg';
if (isset($_SESSION['user_id'])) {
    $sid = (int)$_SESSION['user_id'];
    $sr = @mysqli_query($conn ?? null, "SELECT profile_pic FROM user_info WHERE id=$sid");
    if ($sr && $row = mysqli_fetch_assoc($sr)) {
        $sidebar_profile_pic = $row['profile_pic'] ?: 'assets/img/user-round.svg';
    }
}
?>
<!-- Sidebar -->
<aside class="app-sidebar">
    <nav class="sidebar-nav">
        <a href="home.php"      class="nav-item <?php if ($current_page === 'home')    echo 'active'; ?>">
            <img src="assets/img/house.svg" alt=""><span>Home</span>
        </a>
        <a href="explore.php"   class="nav-item <?php if ($current_page === 'explore')  echo 'active'; ?>">
            <img src="assets/img/globe.svg" alt=""><span>Explore</span>
        </a>
        <a href="playlist.php"  class="nav-item <?php if ($current_page === 'playlist') echo 'active'; ?>">
            <img src="assets/img/list-music.svg" alt=""><span>Playlists</span>
        </a>
    </nav>

    <div class="sidebar-section-title">Your Library</div>
    <nav class="sidebar-nav">
        <a href="download.php"  class="nav-item <?php if ($current_page === 'download') echo 'active'; ?>">
            <img src="assets/img/arrow-down-to-line.svg" alt=""><span>Downloads</span>
        </a>
        <a href="mood.php" class="nav-item <?php if ($current_page === 'mood') echo 'active'; ?>">
            <img src="assets/img/globe.svg" alt=""><span>Mood</span>
        </a>
    </nav>

    <div class="sidebar-section-title">Create</div>
    <nav class="sidebar-nav">
        <a href="upload.php"  class="nav-item <?php if ($current_page === 'upload') echo 'active'; ?>">
            <img src="assets/img/music-2.svg" alt=""><span>Upload Music</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <a href="profile.php" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:var(--space-sm);flex:1;">
            <img src="<?php echo htmlspecialchars($sidebar_profile_pic); ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
            <div class="sidebar-user-info">
                <div class="sidebar-username"><?php echo htmlspecialchars($username); ?></div>
                <div class="sidebar-user-link">View profile</div>
            </div>
        </a>
        <a href="logout.php" class="sidebar-user-link" style="padding-left:var(--space-sm);">Logout</a>
    </div>
</aside>
