<?php
require_once __DIR__ . '/../src/config.php';
startAppSession();
session_unset();
session_destroy();
clearAuthSessionCookie();
clearPending2FACookie();
header('Location: ../public/index.php');
exit;
