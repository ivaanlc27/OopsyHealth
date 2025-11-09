<?php
session_start();
session_unset();
session_destroy();
header('Location: /mail/login.php');
exit;
