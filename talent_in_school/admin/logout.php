<?php

session_start();
session_unset();
session_destroy();

header("Location: super_admin.php");
exit();

?>