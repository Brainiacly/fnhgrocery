<?php // index.php

require_once __DIR__ . '/includes/access_control.php';

$loginErrorMessage = '';


// Process the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $enteredUsername = trim($_POST['username'] ?? '');
    $enteredPassword = $_POST['password'] ?? '';
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {
        $loginErrorMessage = 'The login form expired. Please try again.';
    } elseif ($enteredUsername === '' || $enteredPassword === '') {
        $loginErrorMessage = 'Enter both your username and password.';
    } else {
        try {
            $databaseConnection = connectDatabase();

            $loginStatement = $databaseConnection->prepare(
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

            $operatorRecord = $loginStatement->fetch();

            if (
                $operatorRecord
                &&
                password_verify(
                    $enteredPassword,
                    $operatorRecord['PasswordHash']
                )
            ) {
                session_regenerate_id(true);

                $_SESSION['operator_id'] = $operatorRecord['OperatorID'];
                $_SESSION['store_id'] = $operatorRecord['StoreID'];
                $_SESSION['store_number'] = $operatorRecord['StoreNumber'];
                $_SESSION['store_name'] = $operatorRecord['StoreName'];
                $_SESSION['employee_number'] = $operatorRecord['EmployeeNumber'];
                $_SESSION['username'] = $operatorRecord['Username'];
                $_SESSION['first_name'] = $operatorRecord['FirstName'];
                $_SESSION['middle_initial'] = $operatorRecord['MiddleInitial'];
                $_SESSION['last_name'] = $operatorRecord['LastName'];
                $_SESSION['role'] = $operatorRecord['Role'];

                header('Location: ' . APPLICATION_URL . '/index.php');
                exit;
            }

            $loginErrorMessage = 'The username or password is incorrect.';
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $loginErrorMessage = 'The login could not be completed.';
        }
    }
}

$pageTitle = 'Home';
$currentSection = 'home';
$currentPage = 'home';

require __DIR__ . '/includes/header.php';
?>

<?php if (!operatorIsLoggedIn()): ?>

    <div class="home-public">

        <div
            class="home-public-image home-public-image-left"
            aria-hidden="true"
        >
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image3.png"
                alt=""
            >
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
                        Employees are responsible for protecting the privacy of customer, employee, and company information they access while using this system.
                    </p>
                </section>

                <section class="home-public-section">
                    <h2>
                        Use Passwords Safely
                    </h2>

                    <p>
                        Be careful when entering your password, never share it with another person, and change your password regularly to help keep your account secure.
                    </p>
                </section>

                <section class="home-public-section">
                    <h2>
                        Secure Your Workstation
                    </h2>

                    <p>
                        Never leave a signed-in workstation unattended. Log out before stepping away and report anything unusual to an administrator.
                    </p>
                </section>

            </div>

        </section>

        <div
            class="home-public-image home-public-image-right"
            aria-hidden="true"
        >
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image6.png"
                alt=""
            >
        </div>

    </div>

<?php elseif (!operatorHasAssignedAccess()): ?>

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
                <?= escapeOutput(getLoggedInOperatorDisplayName()) ?>
            </h1>
        </div>

        <div class="home-account">

            <div class="home-account-row">

                <div class="home-account-item">
                    <span class="home-account-label">
                        Username:
                    </span>

                    <span class="home-account-value">
                        <?= escapeOutput($_SESSION['username'] ?? '') ?>
                    </span>
                </div>

                <div class="home-account-item">
                    <span class="home-account-label">
                        Store:
                    </span>

                    <span class="home-account-value">
                        <?= escapeOutput($_SESSION['store_number'] ?? '') ?>
                        -
                        <?= escapeOutput($_SESSION['store_name'] ?? '') ?>
                    </span>
                </div>

            </div>

            <div class="home-account-row">

                <div class="home-account-item">
                    <span class="home-account-label">
                        Employee Number:
                    </span>

                    <span class="home-account-value">
                        <?= escapeOutput($_SESSION['employee_number'] ?? '') ?>
                    </span>
                </div>

                <div class="home-account-item">
                    <span class="home-account-label">
                        Privilege:
                    </span>

                    <span class="home-account-value">

                        <?php if (($_SESSION['role'] ?? '') === 'Pending'): ?>

                            No Access

                        <?php else: ?>

                            <?= escapeOutput($_SESSION['role'] ?? '') ?>

                        <?php endif; ?>

                    </span>
                </div>

            </div>

        </div>

        <p class="home-signed-in-note">
            You are signed in to the FnH Groceries system.
        </p>

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