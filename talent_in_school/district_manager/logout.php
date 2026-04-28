<?php
// File: /district_manager/logout.php
session_start();
session_unset();
session_destroy();
header("Location: district_manager_login.php");
exit;
?>