<?php
$servername = "localhost";
$username   = "root";              // MySQL user matching Apache system user
$password   = "";
$dbname     = "melulu_db";

// 1. Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// 2. Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
