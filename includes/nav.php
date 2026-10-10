<?php // includes/nav.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!isset($currentSection)) {
    $currentSection = '';
}

if (!isset($currentPage)) {
    $currentPage = '';
}


$navLoginError =
    isset($loginErrorMessage)
    ? $loginErrorMessage
    : '';


$onOperatorPage =
    canViewOperators()
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


$onSalesPage =
    $currentSection === 'sales';


// Mark the link for the area the employee is in
$activeLinkClass =
    ' navigation-link-active';

$homeLinkClass =
    'navigation-link navigation-home-link'
    . ($currentPage === 'home' ? $activeLinkClass : '');

$salesLinkClass =
    'navigation-link'
    . ($currentSection === 'sales' ? $activeLinkClass : '');

$expressLinkClass =
    'navigation-link'
    . ($currentSection === 'express' ? $activeLinkClass : '');

$transactionsLinkClass =
    'navigation-link'
    . ($currentSection === 'transactions' ? $activeLinkClass : '');

$stockLinkClass =
    'navigation-link'
    . (
        $currentSection === 'inventory' && $currentPage === 'list'
        ? $activeLinkClass
        : ''
    );

$manageLinkClass =
    'navigation-link'
    . (
        $currentSection === 'inventory'
        && in_array($currentPage, ['manage', 'adjust', 'product', 'coupons'], true)
        ? $activeLinkClass
        : ''
    );
$registersLinkClass =
    'navigation-link'
    . ($currentSection === 'registers' ? $activeLinkClass : '');
// An Administrator deletes an employee, a Manager inactivates one
$statusPage = isAdministrator() ? 'op_delete.php' : 'op_access.php?to=0';
$trainingLinkClass =
    'navigation-link'
    . ($currentSection === 'training' ? $activeLinkClass : '');

$accountLinkClass =
    'navigation-link'
    . ($currentSection === 'account' ? $activeLinkClass : '');

$operatorsLinkClass =
    'navigation-link'
    . ($currentSection === 'operators' ? $activeLinkClass : '');


// Set the sales navigation action for the current operator
$saleNavigationHref =
    APPLICATION_URL . '/sales/sale_new.php';

$saleNavigationLabel =
    'New Sale';

$saleLinkText =
    'Ring up groceries and begin a new customer transaction.';


if (
    isLoggedIn()
    &&
    canUseRegister()
) {

    try {

        $saleLinkDb =
            connectDatabase();

        $saleLinkStatement =
            $saleLinkDb->prepare(
                '
                SELECT
                    sr.ReceiptID,
                    COUNT(srl.ReceiptLineID)
                        AS ItemLineCount
                FROM salesreceipt sr
                LEFT JOIN salesreceiptline srl
                    ON srl.ReceiptID =
                        sr.ReceiptID
                WHERE sr.StoreID =
                    :storeID
                  AND sr.OperatorID =
                    :operatorID
                  AND sr.Status =
                    \'Open\'
                  AND sr.SaleType =
                    \'Regular\'
                GROUP BY
                    sr.ReceiptID,
                    sr.TransactionDateTime
                ORDER BY
                    sr.TransactionDateTime DESC,
                    sr.ReceiptID DESC
                LIMIT 1
                '
            );

        $saleLinkStatement->execute([
            ':storeID' =>
                signedInStoreID(),

            ':operatorID' =>
                signedInOperatorID()
        ]);

        $saleNavigationRecord =
            $saleLinkStatement->fetch();


        if ($saleNavigationRecord) {

            $saleNavigationHref =
                APPLICATION_URL
                . '/sales/sale_new.php?receipt='
                . (int) $saleNavigationRecord['ReceiptID'];


            if (
                (int) $saleNavigationRecord['ItemLineCount']
                >
                0
            ) {

                $saleNavigationLabel =
                    'Continue Sale';

                $saleLinkText =
                    'Return to your open transaction and continue ringing up groceries.';

            } else {

                $saleNavigationLabel =
                    'Return to Checkout';

                $saleLinkText =
                    'Return to your open register session.';
            }
        }

    } catch (PDOException $exception) {

        error_log(
            $exception->getMessage()
        );
    }
}


$showSaleNavigationLink =
    canUseRegister()
    &&
    !(
        $onSalesPage
        &&
        $currentPage === 'new'
    );
?>


<aside class="<?=
    isLoggedIn()
    ? 'site-navigation site-navigation-logged-in'
    : 'site-navigation site-navigation-public'
    ?>">

    <?php if (!isLoggedIn()): ?>

        <nav class="navigation-menu navigation-login" aria-label="Login">

            <div class="navigation-login-heading">
                Operator Login
            </div>


            <div class="navigation-login-description">
                Enter your assigned FnH Groceries credentials.
            </div>


            <form id="login" method="post" action="<?= APPLICATION_URL ?>/index.php#login" class="navigation-login-form">

                <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">


                <div class="navigation-login-field">

                    <label for="username">
                        Username
                    </label>

                    <input type="text" id="username" name="username" maxlength="50" required autocomplete="username">

                </div>


                <div class="navigation-login-field">

                    <label for="password">
                        Password
                    </label>

                    <input type="password" id="password" name="password" required autocomplete="current-password">

                </div>


                <button type="submit" name="login" value="1" class="button button-primary navigation-login-button">
                    Login
                </button>

            </form>


            <?php if ($navLoginError !== ''): ?>

                <div class="navigation-login-error" role="alert">
                    <?= escapeOutput($navLoginError) ?>
                </div>

            <?php endif; ?>

        </nav>


    <?php else: ?>

        <nav class="navigation-menu" aria-label="Main navigation">

            <?php if (!hasAccess()): ?>

                <div class="navigation-group">
                    <a href="<?= APPLICATION_URL ?>/index.php"
                        class="navigation-link navigation-home-link navigation-link-active">
                        <span class="navigation-home-icon" aria-hidden="true">&#8962;</span>
                        <span>Home</span>
                    </a>
                </div>

            <?php else: ?>

                <div class="navigation-group">
                    <a href="<?= APPLICATION_URL ?>/index.php" class="<?= $homeLinkClass ?>">
                        <span class="navigation-home-icon" aria-hidden="true">&#8962;</span>
                        <span>Home</span>
                    </a>
                </div>

                <?php if ($onOperatorList && canViewOperators()): ?>
                    <div class="navigation-group">
                        <div class="navigation-group-title">Employee Actions</div>
                        <div class="operator-nav-actions">
                            <?php if (isAdministrator()): ?>
                                <a href="<?= APPLICATION_URL ?>/operators/op_create.php"
                                    class="button operator-nav-button operator-nav-create">
                                    Create Employee
                                </a>
                                <button type="submit" id="operatorUpdateButton" form="operatorSelectionForm"
                                    formaction="<?= APPLICATION_URL ?>/operators/op_update.php" formmethod="post"
                                    class="button operator-nav-button operator-nav-update" disabled>
                                    Modify Employee
                                </button>
                            <?php endif; ?>
                            <button type="submit" id="operatorStatusButton" form="operatorSelectionForm"
                                formaction="<?= APPLICATION_URL ?>/operators/<?= $statusPage ?>" formmethod="post"
                                class="button operator-nav-button operator-nav-delete" aria-describedby="currentAccountDeleteNote"
                                disabled>
                                <?= isAdministrator() ? 'Delete Employee' : 'Inactivate Employee' ?>
                            </button>
                            <a href="#" id="operatorTransactionsLink" aria-disabled="true"
                                class="button operator-nav-button operator-nav-update">
                                View Transactions
                            </a>
                            <button type="reset" id="operatorClearButton" form="operatorSelectionForm"
                                class="button operator-nav-button operator-nav-clear" disabled>
                                Clear Selection
                            </button>
                            <a href="#operatorListHelp" class="button operator-nav-button operator-nav-help">
                                Help
                            </a>
                            <div id="currentAccountDeleteNote" class="operator-delete-current-note">
                                <?= isAdministrator()
                                    ? 'Current account cannot be deleted.'
                                    : 'You cannot change your own access.' ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($onOperatorActionPage): ?>

                    <div class="navigation-group">
                        <div class="navigation-group-title">Employee Actions</div>
                        <a href="<?= APPLICATION_URL ?>/operators/op_list.php" class="navigation-link">
                            Back to Operators
                        </a>
                    </div>

                <?php endif; ?>

                <?php if (canUseRegister() || canUseExpress() || canViewTransactions()): ?>

                    <div class="navigation-group">
                        <div class="navigation-group-title">Sales</div>

                        <?php if ($showSaleNavigationLink): ?>
                            <a href="<?= escapeOutput($saleNavigationHref) ?>" class="<?= $salesLinkClass ?>">
                                <?= escapeOutput($saleNavigationLabel) ?>
                            </a>
                        <?php endif; ?>

                        <?php if (canUseExpress()): ?>
                            <a href="<?= APPLICATION_URL ?>/express/ex_home.php" class="<?= $expressLinkClass ?>">
                                Express Orders
                            </a>
                        <?php endif; ?>

                        <?php if (canViewTransactions()): ?>
                            <a href="<?= APPLICATION_URL ?>/transactions/tr_list.php" class="<?= $transactionsLinkClass ?>">
                                Transactions
                            </a>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

                <div class="navigation-group">
                    <div class="navigation-group-title">Inventory</div>

                    <a href="<?= APPLICATION_URL ?>/inventory/inv_stock.php" class="<?= $stockLinkClass ?>">
                        Stock Levels
                    </a>

                    <?php if (canManageInventory()): ?>
                        <a href="<?= APPLICATION_URL ?>/inventory/inv_manage.php" class="<?= $manageLinkClass ?>">
                            Manage Inventory
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (canViewOperators()): ?>
                    <div class="navigation-group">
                        <a href="<?= APPLICATION_URL ?>/operators/op_list.php" class="<?= $operatorsLinkClass ?>">
                            Employees
                        </a>
                    </div>
                <?php endif; ?>

                <div class="navigation-group">
                    <a href="<?= APPLICATION_URL ?>/training/tc_home.php" class="<?= $trainingLinkClass ?>">
                        Training Center
                    </a>
                </div>

                <div class="navigation-group">
                    <a href="<?= APPLICATION_URL ?>/account.php" class="<?= $accountLinkClass ?>">
                        My Account
                    </a>
                </div>

                <?php if (isAdministrator()): ?>
                    <div class="navigation-group navigation-group-end">
                        <a href="<?= APPLICATION_URL ?>/registers/reg_setup.php" class="<?= $registersLinkClass ?>">
                            Register Configuration
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

</aside>