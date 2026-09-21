<?php // logout.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/includes/access_control.php';


// This removes all information stored in the current login session.
$_SESSION = [];


// This destroys the current login session.
session_destroy();


// This returns the visitor to the login page.
header(
    'Location: '
    . APPLICATION_URL
    . '/index.php'
);

exit;