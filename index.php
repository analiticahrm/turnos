<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
if (empty($_SERVER['HTTP_USER_AGENT'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}
header('Location: Vista/index.php');
exit;
