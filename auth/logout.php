<?php
require_once __DIR__ . '/../includes/functions.php';
log_activity('logout');
session_unset();
session_destroy();
header('Location: /auth/login.php');
exit;
