<?php
// logout.php - Admin Logout
session_start();
session_destroy();
header('Location: login.php?msg=logged_out');
exit;
?>
