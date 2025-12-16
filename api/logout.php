<?php
// api/logout.php - Admin Logout
// Clear the auth cookie
setcookie('hospital_admin_auth', '', time() - 3600, '/');
header('Location: /api/login.php?msg=logged_out');
exit;
?>
