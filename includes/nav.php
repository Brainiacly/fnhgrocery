<?php // includes/nav.php

$currentSection = $currentSection ?? '';
$currentPage = $currentPage ?? '';

$loggedInUsername = (string)($_SESSION['username'] ?? '');
$navigationLoginErrorMessage = $loginErrorMessage ?? '';

$onAdministratorHome =
    operatorIsAdministrator()
    &&
    $currentPage === 'home';

$onOperatorPage =
    operatorIsAdministrator()
    &&
    $currentSection === 'operators';

$onOperatorList =
    $onOperatorPage
    &&
    $currentPage === 'list';

$navigationClass = operatorIsLoggedIn()
    ? 'site-navigation site-navigation-logged-in'
    : 'site-navigation site-navigation-public';

?>

<aside class="<?= $navigationClass ?>">

    <?php if (!operatorIsLoggedIn()): ?>

        <nav
            class="navigation-menu navigation-login"
            aria-label="Login"
        >

            <div class="navigation-login-heading">
                Operator Login
            </div>

            <div class="navigation-login-description">
                Enter your assigned FnH Groceries credentials.
            </div>

            <?php if ($navigationLoginErrorMessage !== ''): ?>

                <div class="navigation-login-error">
                    <?= escapeOutput($navigationLoginErrorMessage) ?>
                </div>

            <?php endif; ?>

            <form
                id="login"
                method="post"
                action="<?= APPLICATION_URL ?>/index.php#login"
                class="navigation-login-form"
            >

                <input
                    type="hidden"
                    name="form_security_token"
                    value="<?= escapeOutput(getFormSecurityToken()) ?>"
                >

                <div class="navigation-login-field">
                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="50"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="navigation-login-field">
                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button
                    type="submit"
                    name="login"
                    value="1"
                    class="button button-primary navigation-login-button"
                >
                    Login
                </button>

            </form>

            <div class="navigation-demo-note">
                Demo Admin and Operator credentials are listed below
            </div>

            <div class="message message-warning">

                <strong>
                    Demo Admin Credentials
                </strong>

                <div>
                    Username: admin
                </div>

                <div>
                    Password: Admin#123
                </div>

            </div>

            <div class="message message-warning">

                <strong>
                    Demo Operator Credentials
                </strong>

                <div>
                    Username: TestBob
                </div>

                <div>
                    Password: #Testing123
                </div>

                <br>

                <div>
                    Username: TestAlice
                </div>

                <div>
                    Password: Testing123$
                </div>

            </div>

        </nav>

    <?php else: ?>

        <nav
            class="navigation-menu"
            aria-label="Main navigation"
        >

            <?php if (!operatorHasAssignedAccess()): ?>

                <div class="navigation-operator">
                    <span class="navigation-operator-label">
                        Logged in as:
                    </span>

                    <strong>
                        <?= escapeOutput($loggedInUsername) ?>
                    </strong>
                </div>

            <?php elseif ($onOperatorList): ?>

                <a
                    href="<?= APPLICATION_URL ?>/index.php"
                    class="navigation-link navigation-home-link"
                >
                    <span
                        class="navigation-home-icon"
                        aria-hidden="true"
                    >
                        &#8962;
                    </span>

                    <span>
                        Home
                    </span>
                </a>

                <a
                    href="<?= APPLICATION_URL ?>/account.php"
                    class="navigation-link"
                >
                    Manage My Account
                </a>

                <div class="operator-nav-actions">

                    <a
                        href="<?= APPLICATION_URL ?>/operators/create.php"
                        class="button operator-nav-button operator-nav-create"
                    >
                        Create Operator
                    </a>

                    <button
                        type="submit"
                        form="operatorSelectionForm"
                        formaction="<?= APPLICATION_URL ?>/operators/update.php"
                        formmethod="get"
                        class="button operator-nav-button operator-nav-update"
                    >
                        Update Operator
                    </button>

                    <button
                        type="submit"
                        form="operatorSelectionForm"
                        formaction="<?= APPLICATION_URL ?>/operators/delete.php"
                        formmethod="get"
                        class="button operator-nav-button operator-nav-delete"
                        aria-describedby="currentAccountDeleteNote"
                    >
                        Delete Operator
                    </button>

                    <button
                        type="reset"
                        form="operatorSelectionForm"
                        class="button operator-nav-button operator-nav-clear"
                    >
                        Clear Selection
                    </button>

                    <div
                        id="currentAccountDeleteNote"
                        class="operator-delete-current-note"
                    >
                        Current account cannot be deleted.
                    </div>

                </div>

                <div class="navigation-operator">
                    <span class="navigation-operator-label">
                        Logged in as:
                    </span>

                    <strong>
                        <?= escapeOutput($loggedInUsername) ?>
                    </strong>
                </div>

            <?php elseif ($onOperatorPage): ?>

                <a
                    href="<?= APPLICATION_URL ?>/index.php"
                    class="navigation-link navigation-home-link"
                >
                    <span
                        class="navigation-home-icon"
                        aria-hidden="true"
                    >
                        &#8962;
                    </span>

                    <span>
                        Home
                    </span>
                </a>

                <a
                    href="<?= APPLICATION_URL ?>/operators/list.php"
                    class="navigation-link"
                >
                    Manage Operators
                </a>

                <a
                    href="<?= APPLICATION_URL ?>/account.php"
                    class="navigation-link"
                >
                    Manage My Account
                </a>

                <div class="navigation-operator">
                    <span class="navigation-operator-label">
                        Logged in as:
                    </span>

                    <strong>
                        <?= escapeOutput($loggedInUsername) ?>
                    </strong>
                </div>

                <?php if ($currentPage === 'delete'): ?>

                    <div
                        class="navigation-feature-image"
                        aria-hidden="true"
                    >
                        <img
                            src="<?= APPLICATION_URL ?>/assets/images/image3.png"
                            alt=""
                        >
                    </div>

                <?php endif; ?>

            <?php else: ?>

                <?php if ($currentPage !== 'home'): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/index.php"
                        class="navigation-link navigation-home-link"
                    >
                        <span
                            class="navigation-home-icon"
                            aria-hidden="true"
                        >
                            &#8962;
                        </span>

                        <span>
                            Home
                        </span>
                    </a>

                <?php endif; ?>

                <?php if ($onAdministratorHome): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/operators/list.php"
                        class="navigation-link"
                    >
                        Manage Operators
                    </a>

                <?php endif; ?>

                <?php if ($currentSection !== 'account'): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/account.php"
                        class="navigation-link"
                    >
                        Manage My Account
                    </a>

                <?php endif; ?>

                <div class="navigation-operator">
                    <span class="navigation-operator-label">
                        Logged in as:
                    </span>

                    <strong>
                        <?= escapeOutput($loggedInUsername) ?>
                    </strong>
                </div>

                <?php if ($currentPage === 'home'): ?>

                    <div
                        class="navigation-feature-image"
                        aria-hidden="true"
                    >
                        <img
                            src="<?= APPLICATION_URL ?>/assets/images/image3.png"
                            alt=""
                        >
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

</aside>