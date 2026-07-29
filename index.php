<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
redirect(isLoggedIn() ? APP_URL . '/dashboard.php' : APP_URL . '/login.php');
