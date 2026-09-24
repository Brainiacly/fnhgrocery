<?php // includes/nav.php

/**
 * Brian Phillips
 * CSC 680
 */

if (!isset($currentSection)) {
    $currentSection = '';
}

if (!isset($currentPage)) {
    $currentPage = '';
}


$navigationLoginErrorMessage =
    isset($loginErrorMessage)
        ? $loginErrorMessage
        : '';


$onOperatorPage =
    operatorIsAdministrator()
    &&
    $currentSection === 'operators';


$onOperatorList =
    $onOperatorPage
    &&
    $currentPage === 'list';


$onOperatorActionPage =
    $onOperatorPage
    &&
    in_array(
        $currentPage,
        [
            'create',
            'update',
            'delete',
            'reactivate'
        ],
        true
    );


$onAccountPage =
    $currentSection === 'account';


$onSalesPage =
    $currentSection === 'sales';


$onInventoryPage =
    $currentSection === 'inventory';
?>


<aside class="<?= operatorIsLoggedIn() ? 'site-navigation site-navigation-logged-in' : 'site-navigation site-navigation-public' ?>">

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


            <div class="message message-warning navigation-demo-notice">

                <strong>
                    Demo Admin Credentials
                </strong>

                <div>
                    Username: admin
                </div>

                <div>
                    Password: Admin#123
                </div>


                <strong>
                    <br>
                    Demo Operator Credentials
                </strong>

                <div>
                    Username: TestBob
                </div>

                <div>
                    Password: #Testing123
                </div>

                <div>
                    <br>
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

                <!-- No navigation until access is assigned -->


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
                        id="operatorUpdateButton"
                        form="operatorSelectionForm"
                        formaction="<?= APPLICATION_URL ?>/operators/update.php"
                        formmethod="post"
                        class="button operator-nav-button operator-nav-update"
                        disabled
                    >
                        Modify Operator
                    </button>


                    <button
                        type="submit"
                        id="operatorStatusButton"
                        form="operatorSelectionForm"
                        formaction="<?= APPLICATION_URL ?>/operators/delete.php"
                        formmethod="post"
                        class="button operator-nav-button operator-nav-delete"
                        aria-describedby="currentAccountDeleteNote"
                        disabled
                    >
                        Delete Operator
                    </button>


                    <button
                        type="reset"
                        id="operatorClearButton"
                        form="operatorSelectionForm"
                        class="button operator-nav-button operator-nav-clear"
                        disabled
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


                <a
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


            <?php elseif ($onOperatorActionPage): ?>

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
                    href="<?= APPLICATION_URL ?>/operators/operator_list.php"
                    class="navigation-link"
                >
                    Cancel
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


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
                    href="<?= APPLICATION_URL ?>/operators/operator_list.php"
                    class="navigation-link"
                >
                    Manage Operators
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


            <?php elseif (operatorIsAdministrator()): ?>

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


                <a
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/operators/operator_list.php"
                    class="navigation-link"
                >
                    Manage Operators
                </a>


            <?php elseif ($onSalesPage): ?>

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


                <?php if ($currentPage !== 'new'): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/sales/new.php"
                        class="navigation-link"
                    >
                        New Sale
                    </a>

                <?php endif; ?>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/account.php"
                    class="navigation-link"
                >
                    Manage My Account
                </a>


            <?php elseif ($onInventoryPage): ?>

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
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <?php if ($currentPage !== 'list'): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                        class="navigation-link"
                    >
                        Store Stock Levels
                    </a>

                <?php endif; ?>


                <a
                    href="<?= APPLICATION_URL ?>/account.php"
                    class="navigation-link"
                >
                    Manage My Account
                </a>


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


                <a
                    href="<?= APPLICATION_URL ?>/sales/new.php"
                    class="navigation-link"
                >
                    New Sale
                </a>


                <a
                    href="<?= APPLICATION_URL ?>/inventory/store_stock_levels.php"
                    class="navigation-link"
                >
                    Store Stock Levels
                </a>


                <?php if (!$onAccountPage): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/account.php"
                        class="navigation-link"
                    >
                        Manage My Account
                    </a>

                <?php endif; ?>


                <?php if ($onAccountPage): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/index.php"
                        class="navigation-link"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

</aside>