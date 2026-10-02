<?php // logout.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/includes/access_control.php';


// Clear and end the session
$_SESSION = [];
session_destroy();


// Back to login
header(
    'Location: '
    . APPLICATION_URL
    . '/index.php'
);

exit;