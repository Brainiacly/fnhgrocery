<?php // operators/create.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$databaseConnection = connectDatabase();

$errorMessage = '';

$selectedStoreID = '';
$username = '';
$firstName = '';
$middleInitial = '';
$lastName = '';
$email = '';
$phone = '';
$selectedRole = 'Operator';
$hireDate = '';


// Load active stores
$storeListStatement = $databaseConnection->query(
    '
    SELECT
        StoreID,
        StoreNumber,
        StoreName
    FROM vw_storelist
    WHERE Active = 1
    ORDER BY StoreNumber
    '
);

$storeRecords = $storeListStatement->fetchAll();


// Process the Create Operator form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedStoreID = $_POST['store_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $enteredPassword = $_POST['password'] ?? '';
    $confirmedPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $middleInitial = trim($_POST['middle_initial'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedRole = $_POST['role'] ?? 'Operator';
    $hireDate = $_POST['hire_date'] ?? '';
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {
        $errorMessage = 'The form expired. Please try again.';
    } elseif (
        $selectedStoreID === ''
        ||
        $username === ''
        ||
        $enteredPassword === ''
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
    } elseif (!passwordMeetsRequirements($enteredPassword)) {
        $errorMessage =
            'The password must contain at least 8 characters, 1 uppercase letter, 1 lowercase letter, and 1 symbol.';
    } elseif ($enteredPassword !== $confirmedPassword) {
        $errorMessage = 'The password and confirmation do not match.';
    } elseif (
        !in_array(
            $selectedRole,
            [
                'Administrator',
                'Operator'
            ],
            true
        )
    ) {
        $errorMessage = 'Select a valid operator role.';
    } else {
        try {
            if ($middleInitial !== '') {
                $middleInitial = strtoupper($middleInitial);
            }

            $passwordHash = password_hash(
                $enteredPassword,
                PASSWORD_DEFAULT
            );

            $createOperatorStatement = $databaseConnection->prepare(
                '
                CALL sp_create_operator(
                    ?,
                    ?,
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

            $createOperatorStatement->execute([
                (int)$selectedStoreID,
                $username,
                $passwordHash,
                $firstName,
                $middleInitial,
                $lastName,
                $email,
                $phone,
                $selectedRole,
                $hireDate === '' ? null : $hireDate
            ]);

            $createOperatorStatement->closeCursor();

            header('Location: list.php?created=1');
            exit;
        } catch (PDOException $exception) {
            $errorMessage = getSafeDatabaseErrorMessage(
                $exception,
                'The operator could not be created.'
            );
        }
    }
}

$pageTitle = 'Create Operator';
$currentSection = 'operators';
$currentPage = 'create';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel form-panel">

    <div class="page-intro">
        <h1>
            Create Operator
        </h1>

        <p>
            Enter the information for the new FnH Groceries operator.
        </p>
    </div>

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

            <div class="form-field form-field-full-width">
                <label for="store_id">
                    Assigned Store *
                </label>

                <select
                    id="store_id"
                    name="store_id"
                    required
                >
                    <option value="">
                        Select Store
                    </option>

                    <?php foreach ($storeRecords as $storeRecord): ?>

                        <option
                            value="<?= (int)$storeRecord['StoreID'] ?>"
                            <?= (string)$selectedStoreID === (string)$storeRecord['StoreID'] ? 'selected' : '' ?>
                        >
                            <?= escapeOutput($storeRecord['StoreNumber']) ?>
                            -
                            <?= escapeOutput($storeRecord['StoreName']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
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
                    title="Enter one letter or leave this field blank."
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
                <label for="role">
                    Role *
                </label>

                <select
                    id="role"
                    name="role"
                    required
                >
                    <option
                        value="Operator"
                        <?= $selectedRole === 'Operator' ? 'selected' : '' ?>
                    >
                        Operator
                    </option>

                    <option
                        value="Administrator"
                        <?= $selectedRole === 'Administrator' ? 'selected' : '' ?>
                    >
                        Administrator
                    </option>
                </select>
            </div>

            <div class="form-field">
                <label for="hire_date">
                    Hire Date
                </label>

                <input
                    type="date"
                    id="hire_date"
                    name="hire_date"
                    value="<?= escapeOutput($hireDate) ?>"
                >
            </div>

            <div class="form-field">
                <label for="password">
                    Password *
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >

                <div class="field-help">
                    Use at least 8 characters with an uppercase letter, lowercase letter, and symbol.
                </div>
            </div>

            <div class="form-field">
                <label for="confirm_password">
                    Confirm Password *
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="button button-primary"
            >
                Create Operator
            </button>

            <a
                href="list.php"
                class="button button-secondary"
            >
                Cancel
            </a>

        </div>

    </form>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>