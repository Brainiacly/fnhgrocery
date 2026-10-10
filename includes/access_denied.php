<?php // includes/access_denied.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

?>

<section class="content-panel access-denied-panel">

    <div
        class="access-denied-symbol"
        aria-hidden="true"
    >
        !
    </div>

    <div class="access-denied-copy">

        <h1>
            Access Denied
        </h1>

        <p>
            <?= escapeOutput($accessDeniedMessage ?? 'You do not have permission to open this page.') ?>
        </p>

        <p class="access-denied-help">
            Your account is still signed in. Choose an available option from the navigation menu.
        </p>

    </div>

</section>