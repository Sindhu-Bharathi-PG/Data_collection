<?php
// api/logout.php - Admin Logout
session_start();
session_destroy();
header('Location: /api/login.php?msg=logged_out');
exit;
?>
