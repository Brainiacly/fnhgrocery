<?php // includes/footer.php

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

    </main>

</div>


<footer class="site-footer">

    <span>
        Developed by
        <?= escapeOutput(DEVELOPER_NAME) ?>
    </span>

    <span>
        |
    </span>

    <a
        href="mailto:<?= escapeOutput(DEVELOPER_EMAIL) ?>?subject=FnH%20Groceries%20Feedback"
    >
        Send Feedback
    </a>

</footer>

<script>
    // Every message box gets a button to close it
    document.querySelectorAll('.message').forEach(function (box) {

        const closeButton = document.createElement('button');

        closeButton.type = 'button';
        closeButton.className = 'button button-secondary message-close';
        closeButton.title = 'Close this message';
        closeButton.setAttribute('aria-label', 'Close this message');
        closeButton.innerHTML = '&times;';

        closeButton.addEventListener('click', function () {
            box.remove();
        });

        box.classList.add('message-closable');
        box.appendChild(closeButton);
    });
</script>

</body>

</html>