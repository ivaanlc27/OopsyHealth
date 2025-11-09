<?php

session_start();
if (!empty($_SESSION['mail_user'])) {
    header('Location: /mail/inbox.php');
    exit;
}
header('Location: /mail/login.php');
exit;
