<?php // account.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/includes/access_control.php';

requireOperatorLogin();

$databaseConnection = connectDatabase();

$errorMessage = '';
$successMessage = '';


// Load the current operator
$accountStatement = $databaseConnection->prepare(
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
        Email,
        Phone,
        Role
    FROM vw_operatorlogin
    WHERE OperatorID = :operatorID
    LIMIT 1
    '
);

$accountStatement->execute([
    ':operatorID' => $_SESSION['operator_id']
]);

$accountRecord = $accountStatement->fetch();

if (!$accountRecord) {
    header('Location: ' . APPLICATION_URL . '/logout.php');
    exit;
}

$username = $accountRecord['Username'];
$firstName = $accountRecord['FirstName'];
$middleInitial = (string)$accountRecord['MiddleInitial'];
$lastName = $accountRecord['LastName'];
$email = $accountRecord['Email'];
$phone = (string)$accountRecord['Phone'];


// Show confirmation after an update
if (isset($_GET['updated'])) {
    $successMessage = 'Your account was updated successfully.';
}


// Process account changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $middleInitial = trim($_POST['middle_initial'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';

    $isChangingPassword =
        $currentPassword !== ''
        ||
        $newPassword !== ''
        ||
        $confirmNewPassword !== '';

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {
        $errorMessage = 'The form expired. Please try again.';
    } elseif (
        $username === ''
        ||
        $firstName === ''
        ||
        $lastName === ''
        ||
        $email === ''
    ) {
        $errorMessage = 'Complete all required fields.';
    } elseif (strlen($username) > 50) {
        $errorMessage = 'Username cannot contain more than 50 characters.';
    } elseif (!middleInitialIsValid($middleInitial)) {
        $errorMessage = 'Middle initial must be one letter or left blank.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Enter a valid email address.';
    } elseif (strlen($phone) > 20) {
        $errorMessage = 'Phone number cannot contain more than 20 characters.';
    } elseif (
        $isChangingPassword
        &&
        (
            $currentPassword === ''
            ||
            $newPassword === ''
            ||
            $confirmNewPassword === ''
        )
    ) {
        $errorMessage =
            'Enter your current password, a new password, and the confirmation to change your password.';
    } elseif (
        $isChangingPassword
        &&
        !password_verify(
            $currentPassword,
            $accountRecord['PasswordHash']
        )
    ) {
        $errorMessage = 'Your current password is incorrect.';
    } elseif (
        $isChangingPassword
        &&
        !passwordMeetsRequirements($newPassword)
    ) {
        $errorMessage =
            passwordRequirementText();
    } elseif (
        $isChangingPassword
        &&
        $newPassword !== $confirmNewPassword
    ) {
        $errorMessage =
            'The new password and confirmation do not match.';
    } else {
        try {
            if ($middleInitial !== '') {
                $middleInitial = strtoupper($middleInitial);
            }

            $newPasswordHash = $isChangingPassword
                ? password_hash($newPassword, PASSWORD_DEFAULT)
                : null;

            $updateAccountStatement = $databaseConnection->prepare(
                '
                CALL sp_update_own_account(
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
                '
            );

            $updateAccountStatement->execute([
                (int)$_SESSION['operator_id'],
                $username,
                $firstName,
                $middleInitial,
                $lastName,
                $email,
                $phone,
                $newPasswordHash
            ]);

            $updateAccountStatement->closeCursor();

            // Refresh the session after account changes
            refreshCurrentOperatorSession();

            header(
                'Location: '
                . APPLICATION_URL
                . '/account.php?updated=1'
            );

            exit;
        } catch (PDOException $exception) {
            $errorMessage = getSafeDatabaseErrorMessage(
                $exception,
                'Your account could not be updated.'
            );
        }
    }
}


$pageTitle = 'My Account';
$currentSection = 'account';
$currentPage = 'account';

require __DIR__ . '/includes/header.php';
?>

<section class="content-panel form-panel">

    <div class="page-intro">
        <h1>
            My Account
        </h1>

        <p>
            Update your account, contact details, or password.
        </p>
    </div>

    <?php if ($successMessage !== ''): ?>

        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>

    <form method="post">

        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <div class="form-grid">

            <div class="form-field">
                <label>
                    Employee Number
                </label>

                <div class="read-only-value">
                    <?= escapeOutput($accountRecord['EmployeeNumber']) ?>
                </div>
            </div>

            <div class="form-field">
                <label for="username">
                    Username *
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= escapeOutput($username) ?>"
                    maxlength="50"
                    required
                    autocomplete="username"
                >

                <div class="field-help">
                    Username must be unique.
                </div>
            </div>

            <div class="form-field">
                <label>
                    Assigned Store
                </label>

                <div class="read-only-value">
                    <?= escapeOutput($accountRecord['StoreNumber']) ?>
                    -
                    <?= escapeOutput($accountRecord['StoreName']) ?>
                </div>

                <div class="field-help">
                    Store assignments are managed by an administrator.
                </div>
            </div>

            <div class="form-field">
                <label>
                    Privilege
                </label>

                <div class="read-only-value">

                    <?php if ($accountRecord['Role'] === 'Pending'): ?>

                        No Access

                    <?php else: ?>

                        <?= escapeOutput($accountRecord['Role']) ?>

                    <?php endif; ?>

                </div>
            </div>

            <div class="form-field">
                <label for="first_name">
                    First Name *
                </label>

                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="<?= escapeOutput($firstName) ?>"
                    maxlength="60"
                    required
                >
            </div>

            <div class="form-field">
                <label for="middle_initial">
                    Middle Initial (Optional)
                </label>

                <input
                    type="text"
                    id="middle_initial"
                    name="middle_initial"
                    value="<?= escapeOutput($middleInitial) ?>"
                    maxlength="1"
                    pattern="[A-Za-z]"
                >
            </div>

            <div class="form-field">
                <label for="last_name">
                    Last Name *
                </label>

                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="<?= escapeOutput($lastName) ?>"
                    maxlength="60"
                    required
                >
            </div>

            <div class="form-field">
                <label for="email">
                    Email *
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= escapeOutput($email) ?>"
                    maxlength="120"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-field">
                <label for="phone">
                    Phone
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?= escapeOutput($phone) ?>"
                    maxlength="20"
                    autocomplete="tel"
                >
            </div>

            <div class="form-field">
                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                >

                <div class="field-help">
                    Required only when changing your password.
                </div>
            </div>

            <div class="form-field">
                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="8"
                    autocomplete="new-password"
                >

                <div class="field-help">
                    Leave blank to keep your current password. <?= escapeOutput(passwordRequirementText()) ?>
                </div>
            </div>

            <div class="form-field">
                <label for="confirm_new_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_new_password"
                    name="confirm_new_password"
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="button button-primary"
            >
                Save Changes
            </button>

            <a
                href="<?= APPLICATION_URL ?>/index.php"
                class="button button-secondary"
            >
                Cancel
            </a>

        </div>

    </form>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>