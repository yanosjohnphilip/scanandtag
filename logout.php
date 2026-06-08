<?php
session_start();
session_unset();
session_destroy();
session_write_close(); // Close the session write to prevent new session start
setcookie(session_name(), '', 0, '/'); // Clear the session cookie
header("Location: login.php");
exit();
?>
