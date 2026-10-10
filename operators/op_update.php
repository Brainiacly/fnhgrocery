<?php // operators/op_update.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$db = connectDatabase();
$errorMessage = '';

$operatorID =
    filter_input(
        INPUT_POST,
        'id',
        FILTER_VALIDATE_INT
    );

if (!$operatorID) {
    showAccessDeniedPage(
        'Select an employee from the Employee List before opening this page.'
    );
}

$submittedToken =
    $_POST['form_security_token'] ?? '';

if (
    !tokenIsValid(
        $submittedToken
    )
) {
    showAccessDeniedPage(
        'The form expired. Please return to the Employee List and try again.'
    );
}

$operatorStatement =
    $db->prepare(
        '
        SELECT *
        FROM vw_operatorlist
        WHERE OperatorID = :operatorID
        LIMIT 1
        '
    );

$operatorStatement->execute([
    ':operatorID' => $operatorID
]);

$operatorRecord =
    $operatorStatement->fetch();

if (!$operatorRecord) {
    showAccessDeniedPage(
        'The selected employee was not found. Return to the Employee List and try again.'
    );
}

// An Administrator can update details, but may not remove the last active
// Administrator role. sp_update_operator separately validates the same rule.
$isFinalActiveAdministrator = false;
if (
    $operatorRecord['Role'] === 'Administrator'
    && (int) $operatorRecord['Active'] === 1
) {
    $activeAdministratorCount = (int) $db->query(
        "SELECT COUNT(*) FROM operator WHERE Role = 'Administrator' AND Active = 1"
    )->fetchColumn();
    $isFinalActiveAdministrator = $activeAdministratorCount <= 1;
}

$openSaleStatement =
    $db->prepare(
        '
        SELECT
            sr.ReceiptID,
            sr.StoreID,
            sr.RegisterID,
            sr.TransactionNumber,
            r.RegisterNumber
        FROM salesreceipt sr
        JOIN register r
            ON r.RegisterID = sr.RegisterID
           AND r.StoreID = sr.StoreID
        WHERE sr.OperatorID = :operatorID
          AND sr.Status = \'Open\'
          AND sr.SaleType = \'Regular\'
        LIMIT 1
        '
    );

$openSaleStatement->execute([
    ':operatorID' => $operatorID
]);

$openSaleRecord =
    $openSaleStatement->fetch();


$storeListStatement =
    $db->query(
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

$storeRecords =
    $storeListStatement->fetchAll();


$selectedStoreID =
    $operatorRecord['StoreID'];

$employeeNumber =
    $operatorRecord['EmployeeNumber'];

$username =
    $operatorRecord['Username'];

$firstName =
    $operatorRecord['FirstName'];

$middleInitial =
    $operatorRecord['MiddleInitial'] ?? '';

$lastName =
    $operatorRecord['LastName'];

$email =
    $operatorRecord['Email'];

$phone =
    $operatorRecord['Phone'] ?? '';

$selectedRole =
    $operatorRecord['Role'];

$hireDate =
    $operatorRecord['HireDate'] ?? '';

$cancelConfirmed =
    false;


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_update'])
) {

    $selectedStoreID =
        $_POST['store_id'] ?? '';

    $username =
        trim(
            $_POST['username'] ?? ''
        );

    $firstName =
        trim(
            $_POST['first_name'] ?? ''
        );

    $middleInitial =
        trim(
            $_POST['middle_initial'] ?? ''
        );

    $lastName =
        trim(
            $_POST['last_name'] ?? ''
        );

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $phone =
        trim(
            $_POST['phone'] ?? ''
        );

    $selectedRole =
        $_POST['role'] ?? 'Pending';

    $hireDate =
        $_POST['hire_date'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmedPassword =
        $_POST['confirm_password'] ?? '';

    $saleWouldCancel =
        $openSaleRecord
        &&
        (
            (int) $selectedStoreID
            !==
            (int) $operatorRecord['StoreID']
            ||
            $selectedRole === 'Pending'
            ||
            $selectedRole !== $operatorRecord['Role']
        );

    $cancelConfirmed =
        ($_POST['confirm_open_sale_cancel'] ?? '')
        ===
        '1';


    if (
        $selectedStoreID === ''
        ||
        $username === ''
        ||
        $firstName === ''
        ||
        $lastName === ''
        ||
        $email === ''
    ) {

        $errorMessage =
            'Complete all required fields.';

    } elseif (
        !initialIsValid(
            $middleInitial
        )
    ) {

        $errorMessage =
            'Middle initial must be one letter or left blank.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errorMessage =
            'Enter a valid email address.';

    } elseif (!phoneIsValid($phone)) {

        $errorMessage =
            'Phone number must contain exactly 10 digits or be left blank.';

    } elseif (
        $newPassword !== ''
        &&
        !passwordIsValid(
            $newPassword
        )
    ) {

        $errorMessage =
            passwordRules();

    } elseif (
        $newPassword !== ''
        &&
        $newPassword !== $confirmedPassword
    ) {

        $errorMessage =
            'The new password and confirmation do not match.';

    } elseif (
        !in_array(
            $selectedRole,
            [
                'Pending',
                'Operator',
                'Administrator',
                'Manager',
                'Personal Shopper'
            ],
            true
        )
    ) {

        $errorMessage =
            'Select a valid access level.';

    } elseif (
        $isFinalActiveAdministrator
        && $selectedRole !== 'Administrator'
    ) {

        $errorMessage =
            'This is the final active Administrator. Assign another active Administrator before changing this role.';

    } elseif (
        $saleWouldCancel
        &&
        !$cancelConfirmed
    ) {

        $errorMessage =
            'Confirm that the open sale may be cancelled before saving these changes.';

    } else {

        try {

            if ($middleInitial !== '') {

                $middleInitial =
                    strtoupper(
                        $middleInitial
                    );
            }


            $newPasswordHash = null;


            if ($newPassword !== '') {

                $newPasswordHash =
                    password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );
            }


            $updateStatement =
                $db->prepare(
                    '
                    CALL sp_update_operator(
                        ?,
                        ?,
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


            $updateStatement->execute([
                (int) $operatorID,
                (int) $selectedStoreID,
                $username,
                $newPasswordHash,
                $firstName,
                $middleInitial,
                $lastName,
                $email,
                $phone,
                $selectedRole,
                $hireDate === ''
                ? null
                : $hireDate,
                signedInOperatorID()
            ]);


            $updateStatement->closeCursor();


            if (
                (int) $operatorID
                ===
                signedInOperatorID()
            ) {

                refreshSession();


                if (!isAdministrator()) {

                    header(
                        'Location: '
                        .
                        APPLICATION_URL
                        .
                        '/index.php'
                    );

                    exit;
                }
            }


            header(
                'Location: op_list.php?updated=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                databaseMessage(
                    $exception,
                    'The employee could not be modified.'
                );
        }
    }
}


$pageTitle =
    'Modify Employee';

$currentSection =
    'operators';

$currentPage =
    'update';


require __DIR__ . '/../includes/header.php';
?>


<section class="content-panel form-panel">

    <div class="page-intro">

        <h1>
            Modify Employee
        </h1>

        <p>
            Modify the selected FnH Groceries employee and access level.
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


    <form method="post">

        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

        <input type="hidden" name="id" value="<?= (int) $operatorID ?>">


        <div class="form-grid">

            <div class="form-field">

                <label for="store_id">
                    Assigned Store *
                </label>

                <select id="store_id" name="store_id" required>

                    <option value="">
                        Select Store
                    </option>


                    <?php foreach ($storeRecords as $storeRecord): ?>

                        <option value="<?= (int) $storeRecord['StoreID'] ?>" <?= (string) $selectedStoreID === (string) $storeRecord['StoreID'] ? 'selected' : '' ?>>
                            <?= escapeOutput($storeRecord['StoreNumber']) ?>
                            -
                            <?= escapeOutput($storeRecord['StoreName']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-field">

                <label>
                    Employee Number
                </label>

                <div class="read-only-value">
                    <?= escapeOutput($employeeNumber) ?>
                </div>

            </div>


            <div class="form-field">

                <label for="username">
                    Username *
                </label>

                <input type="text" id="username" name="username" value="<?= escapeOutput($username) ?>" maxlength="50"
                    required>

            </div>


            <div class="form-field">

                <label for="role">
                    Access *
                </label>

                <select id="role" name="role" required>

                    <option value="Pending" <?= $selectedRole === 'Pending' ? 'selected' : '' ?>>
                        No Access
                    </option>

                    <option value="Operator" <?= $selectedRole === 'Operator' ? 'selected' : '' ?>>
                        Operator
                    </option>

                    <option value="Manager" <?= $selectedRole === 'Manager' ? 'selected' : '' ?>>
                        Manager
                    </option>

                    <option value="Administrator" <?= $selectedRole === 'Administrator' ? 'selected' : '' ?>>
                        Administrator
                    </option>

                    <option value="Personal Shopper" <?= $selectedRole === 'Personal Shopper' ? 'selected' : '' ?>>
                        Personal Shopper
                    </option>

                </select>

                <?php if ($isFinalActiveAdministrator): ?>
                    <div class="field-help">
                        This is the final active Administrator. Another active Administrator
                        must exist before this account can change roles.
                    </div>
                <?php endif; ?>

            </div>


            <div class="form-field">

                <label for="first_name">
                    First Name *
                </label>

                <input type="text" id="first_name" name="first_name" value="<?= escapeOutput($firstName) ?>"
                    maxlength="60" required>

            </div>


            <div class="form-field">

                <label for="middle_initial">
                    Middle Initial (Optional)
                </label>

                <input type="text" id="middle_initial" name="middle_initial" value="<?= escapeOutput($middleInitial) ?>"
                    maxlength="1" pattern="[A-Za-z]">

            </div>


            <div class="form-field">

                <label for="last_name">
                    Last Name *
                </label>

                <input type="text" id="last_name" name="last_name" value="<?= escapeOutput($lastName) ?>" maxlength="60"
                    required>

            </div>


            <div class="form-field">

                <label for="email">
                    Email *
                </label>

                <input type="email" id="email" name="email" value="<?= escapeOutput($email) ?>" maxlength="120"
                    required>

            </div>


            <div class="form-field">

                <label for="phone">
                    Phone
                </label>

                <input type="tel" id="phone" name="phone" value="<?= escapeOutput($phone) ?>" minlength="10"
                    maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                    title="Enter exactly 10 digits with no spaces or punctuation.">

            </div>


            <div class="form-field">

                <label for="hire_date">
                    Hire Date
                </label>

                <input type="date" id="hire_date" name="hire_date" value="<?= escapeOutput($hireDate) ?>">

            </div>


            <div class="form-field">

                <label for="new_password">
                    New Password
                </label>

                <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password">

                <div class="field-help">
                    Leave blank to keep the current password. <?= escapeOutput(passwordRules()) ?>
                </div>

            </div>


            <div class="form-field">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input type="password" id="confirm_password" name="confirm_password" minlength="8"
                    autocomplete="new-password">

            </div>

        </div>

        <?php if ($openSaleRecord): ?>
            <div id="openSaleChangeWarning" class="message message-warning" hidden>
                This employee currently has an open sale on Register
                <?= escapeOutput($openSaleRecord['RegisterNumber']) ?>.
                Changing the assigned store or access role will cancel that regular sale,
                restore its merchandise to inventory, release the register, and record the
                administrator action in the transaction journal.

                <div class="form-field">
                    <label>
                        <input type="checkbox" id="confirm_open_sale_cancel" name="confirm_open_sale_cancel" value="1"
                            <?= $cancelConfirmed ? 'checked' : '' ?>>
                        I understand that this open sale will be cancelled.
                    </label>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const storeSelect =
                        document.getElementById('store_id');
                    const roleSelect =
                        document.getElementById('role');
                    const warning =
                        document.getElementById('openSaleChangeWarning');
                    const confirmation =
                        document.getElementById('confirm_open_sale_cancel');
                    const originalStoreID =
                        '<?= (int) $operatorRecord['StoreID'] ?>';
                    const originalRole =
                        '<?= escapeOutput($operatorRecord['Role']) ?>';

                    function updateOpenSaleWarning() {
                        const mustCancel =
                            storeSelect.value !== originalStoreID
                            ||
                            roleSelect.value !== originalRole;

                        warning.hidden = !mustCancel;

                        if (!mustCancel) {
                            confirmation.checked = false;
                        }
                    }

                    storeSelect.addEventListener(
                        'change',
                        updateOpenSaleWarning
                    );

                    roleSelect.addEventListener(
                        'change',
                        updateOpenSaleWarning
                    );

                    updateOpenSaleWarning();
                });
            </script>
        <?php endif; ?>


        <div class="form-actions">

            <button type="submit" name="save_update" value="1" class="button button-primary">
                Save Changes
            </button>

            <a href="op_list.php" class="button button-secondary">
                Cancel
            </a>

        </div>

    </form>

</section>


<?php
require __DIR__ . '/../includes/footer.php';
?>