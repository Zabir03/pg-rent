<?php
require_once __DIR__ . '/includes/functions.php';
header('Location: ' . (current_user() ? '/dashboard/index.php' : '/auth/login.php'));
exit;
