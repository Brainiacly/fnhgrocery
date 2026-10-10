<?php // index.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/includes/access_control.php';

$loginErrorMessage = '';

// Sent here when the sign-in time limit has passed
if (($_GET['expired'] ?? '') === '1') {
    $loginErrorMessage =
        'Your ' . SIGN_IN_HOURS . '-hour sign-in has ended. Please sign in again.';
}


// Process the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $enteredUsername =
        trim($_POST['username'] ?? '');

    $enteredPassword =
        $_POST['password'] ?? '';

    $submittedToken =
        $_POST['form_security_token'] ?? '';


    if (!tokenIsValid($submittedToken)) {

        $loginErrorMessage =
            'The login form expired. Please try again.';

    } elseif ($enteredUsername === '' || $enteredPassword === '') {

        $loginErrorMessage =
            'Enter both your username and password.';

    } else {

        try {

            $db =
                connectDatabase();


            $loginStatement =
                $db->prepare(
                    '
                    SELECT
                        OperatorID,
                        StoreID,
                        StoreNumber,
                        StoreName,
                        EmployeeNumber,
                        Username,
                        PasswordHash,
                        FirstName,
                        MiddleInitial,
                        LastName,
                        Role
                    FROM vw_operatorlogin
                    WHERE Username = :enteredUsername
                    LIMIT 1
                    '
                );


            $loginStatement->execute([
                ':enteredUsername' => $enteredUsername
            ]);


            $operatorRecord =
                $loginStatement->fetch();


            if (
                $operatorRecord
                &&
                password_verify(
                    $enteredPassword,
                    $operatorRecord['PasswordHash']
                )
            ) {

                session_regenerate_id(true);


                $_SESSION['signed_in_at'] = time();

                $_SESSION['operator_id'] =
                    $operatorRecord['OperatorID'];

                $_SESSION['store_id'] =
                    $operatorRecord['StoreID'];

                $_SESSION['store_number'] =
                    $operatorRecord['StoreNumber'];

                $_SESSION['store_name'] =
                    $operatorRecord['StoreName'];

                $_SESSION['employee_number'] =
                    $operatorRecord['EmployeeNumber'];

                $_SESSION['username'] =
                    $operatorRecord['Username'];

                $_SESSION['first_name'] =
                    $operatorRecord['FirstName'];

                $_SESSION['middle_initial'] =
                    $operatorRecord['MiddleInitial'];

                $_SESSION['last_name'] =
                    $operatorRecord['LastName'];

                $_SESSION['role'] =
                    $operatorRecord['Role'];


                header(
                    'Location: '
                    .
                    APPLICATION_URL
                    .
                    '/index.php'
                );

                exit;
            }


            $loginErrorMessage =
                'The username or password is incorrect.';

        } catch (PDOException $exception) {

            error_log(
                $exception->getMessage()
            );

            $loginErrorMessage =
                'The login could not be completed.';
        }
    }
}


$pageTitle =
    'Home';

$currentSection =
    'home';

$currentPage =
    'home';


require __DIR__ . '/includes/header.php';
?>


<?php if (!isLoggedIn()): ?>

    <div class="home-public">

        <div class="home-public-image home-public-image-left" aria-hidden="true">

            <img src="<?= APPLICATION_URL ?>/assets/images/image3.png" alt="">

        </div>


        <section class="home-public-main">

            <div class="home-public-heading">

                <h1>
                    FnH Groceries Employee System
                </h1>

                <p>
                    Authorized employees may sign in using their assigned credentials.
                </p>

            </div>


            <div class="home-public-sections">

                <section class="home-public-section">

                    <h2>
                        Protect Private Information
                    </h2>

                    <p>
                        Employees are responsible for protecting the privacy of customer, employee, and company
                        information they access while using this system.
                    </p>

                </section>


                <section class="home-public-section">

                    <h2>
                        Use Passwords Safely
                    </h2>

                    <p>
                        Be careful when entering your password, never share it with another person,
                        and change your password regularly to help keep your account secure.
                    </p>

                </section>


                <section class="home-public-section">

                    <h2>
                        Secure Your Workstation
                    </h2>

                    <p>
                        Never leave a signed-in workstation unattended. Log out before stepping away
                        and report anything unusual to an administrator.
                    </p>

                </section>

            </div>

        </section>


        <div class="home-public-image home-public-image-right" aria-hidden="true">

            <img src="<?= APPLICATION_URL ?>/assets/images/image6.png" alt="">

        </div>

    </div>


<?php elseif (!hasAccess()): ?>

    <section class="content-panel home-pending">

        <div class="home-pending-message">
            Contact Administrator for Access
        </div>


        <div class="home-pending-user">

            Logged in as:

            <strong>
                <?= escapeOutput($_SESSION['username'] ?? '') ?>
            </strong>

        </div>

    </section>


<?php else: ?>

    <section class="content-panel">

        <div class="page-intro">

            <h1>
                Welcome,
                <?= escapeOutput(
                    signedInName()
                ) ?>
            </h1>

        </div>


        <div class="home-account">

            <div class="home-account-row">

                <div class="home-account-item">

                    <span class="home-account-label">
                        Username:
                    </span>

                    <span class="home-account-value">
                        <?= escapeOutput(
                            $_SESSION['username'] ?? ''
                        ) ?>
                    </span>

                </div>


                <div class="home-account-item">

                    <span class="home-account-label">
                        Store:
                    </span>

                    <span class="home-account-value">

                        <?= escapeOutput(
                            $_SESSION['store_number'] ?? ''
                        ) ?>

                        -

                        <?= escapeOutput(
                            signedInStoreName()
                        ) ?>

                    </span>

                </div>

            </div>


            <div class="home-account-row">

                <div class="home-account-item">

                    <span class="home-account-label">
                        Employee Number:
                    </span>

                    <span class="home-account-value">
                        <?= escapeOutput(
                            $_SESSION['employee_number'] ?? ''
                        ) ?>
                    </span>

                </div>


                <div class="home-account-item">

                    <span class="home-account-label">
                        Privilege:
                    </span>

                    <span class="home-account-value">

                        <?php if (
                            signedInRole()
                            ===
                            'Pending'
                        ): ?>

                            No Access

                        <?php else: ?>

                            <?= escapeOutput(
                                signedInRole()
                            ) ?>

                        <?php endif; ?>

                    </span>

                </div>

            </div>

        </div>


        <p class="home-signed-in-note">
            You are signed in to the FnH Groceries system.
        </p>


        <section class="home-menu">

            <h2>
                Main Menu
            </h2>


            <div class="home-menu-grid">

                <?php if (canUseRegister()): ?>

                    <a href="<?= escapeOutput($saleNavigationHref) ?>" class="home-menu-card">

                        <strong>
                            <?= escapeOutput($saleNavigationLabel) ?>
                        </strong>

                        <span>
                            <?= escapeOutput($saleLinkText) ?>
                        </span>

                    </a>

                <?php endif; ?>


                <?php if (canUseExpress()): ?>

                    <a href="<?= APPLICATION_URL ?>/express/ex_home.php" class="home-menu-card">

                        <strong>
                            Express Orders
                        </strong>

                        <span>
                            Take, pick, prepare, and check out Express orders.
                        </span>

                    </a>

                <?php endif; ?>


                <a href="<?= APPLICATION_URL ?>/inventory/inv_stock.php" class="home-menu-card">

                    <strong>
                        Store Stock Levels
                    </strong>

                    <span>
                        View current product quantities and total store inventory.
                    </span>

                </a>


                <?php if (isAdministrator()): ?>

                    <a href="<?= APPLICATION_URL ?>/inventory/inv_manage.php" class="home-menu-card">

                        <strong>
                            Manage Inventory
                        </strong>

                        <span>
                            Add or edit products and adjust current store inventory.
                        </span>

                    </a>

                <?php endif; ?>


                <a href="<?= APPLICATION_URL ?>/transactions/tr_list.php" class="home-menu-card">

                    <strong>
                        Transaction Viewer
                    </strong>

                    <span>
                        Review permitted Regular and Express transactions and open receipt details.
                    </span>

                </a>


                <a href="<?= APPLICATION_URL ?>/training/tc_home.php" class="home-menu-card">

                    <strong>
                        Training Center
                    </strong>

                    <span>
                        Review use-case instructions and Assignment 4 training movies.
                    </span>

                </a>


                <?php if (canViewOperators()): ?>

                    <a href="<?= APPLICATION_URL ?>/operators/op_list.php" class="home-menu-card">

                        <strong>
                            Employees
                        </strong>

                        <span>
                            <?= isAdministrator()
                                ? 'Create, edit, and manage employee accounts.'
                                : 'Review employee activity and manage permitted access.' ?>
                        </span>

                    </a>

                <?php else: ?>

                    <a href="<?= APPLICATION_URL ?>/account.php" class="home-menu-card">

                        <strong>
                            My Account
                        </strong>

                        <span>
                            Review and update your employee account information.
                        </span>

                    </a>

                <?php endif; ?>

            </div>

        </section>


        <section class="home-security">

            <h2>
                Security Tips
            </h2>

            <ul class="home-security-list">

                <li>
                    Do not leave this workstation unattended.
                </li>

                <li>
                    Log out before stepping away.
                </li>

                <li>
                    Contact an administrator if you notice anything wrong.
                </li>

            </ul>

        </section>

    </section>

<?php endif; ?>


<?php require __DIR__ . '/includes/footer.php'; ?>