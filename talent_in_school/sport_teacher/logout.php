<?php

session_start();
session_unset();
session_destroy();

header("Location: sport_teacher_login.php");
exit();

?>