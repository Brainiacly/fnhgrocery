<?php // operators/update.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$databaseConnection = connectDatabase();
$errorMessage = '';

$operatorID =
    filter_input(
        INPUT_POST,
        'id',
        FILTER_VALIDATE_INT
    );

if (!$operatorID) {
    http_response_code(400);
    exit('A valid operator ID is required.');
}

$submittedSecurityToken =
    $_POST['form_security_token'] ?? '';

if (
    !formSecurityTokenIsValid(
        $submittedSecurityToken
    )
) {
    http_response_code(403);
    exit('The form expired. Please return to the Operator List and try again.');
}

$operatorStatement =
    $databaseConnection->prepare(
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
    http_response_code(404);
    exit('The selected operator was not found.');
}

$openSaleStatement =
    $databaseConnection->prepare(
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
        LIMIT 1
        '
    );

$openSaleStatement->execute([
    ':operatorID' => $operatorID
]);

$openSaleRecord =
    $openSaleStatement->fetch();


$storeListStatement =
    $databaseConnection->query(
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

    $openSaleWouldBeCancelled =
        $openSaleRecord
        &&
        (
            (int)$selectedStoreID
            !==
            (int)$operatorRecord['StoreID']
            ||
            $selectedRole === 'Pending'
        );

    $openSaleCancellationConfirmed =
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
        !middleInitialIsValid(
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

    } elseif (strlen($phone) > 20) {

        $errorMessage =
            'Phone number cannot contain more than 20 characters.';

    } elseif (
        $newPassword !== ''
        &&
        !passwordMeetsRequirements(
            $newPassword
        )
    ) {

        $errorMessage =
            passwordRequirementText();

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
                'Administrator'
            ],
            true
        )
    ) {

        $errorMessage =
            'Select a valid access level.';

    } elseif (
        $openSaleWouldBeCancelled
        &&
        !$openSaleCancellationConfirmed
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


            $updateOperatorStatement =
                $databaseConnection->prepare(
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


            $updateOperatorStatement->execute([
                (int)$operatorID,
                (int)$selectedStoreID,
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
                (int)$_SESSION['operator_id']
            ]);


            $updateOperatorStatement->closeCursor();


            if (
                (int)$operatorID
                ===
                (int)$_SESSION['operator_id']
            ) {

                refreshCurrentOperatorSession();


                if (!operatorIsAdministrator()) {

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
                'Location: operator_list.php?updated=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                getSafeDatabaseErrorMessage(
                    $exception,
                    'The operator could not be modified.'
                );
        }
    }
}


$pageTitle =
    'Modify Operator';

$currentSection =
    'operators';

$currentPage =
    'update';


require __DIR__ . '/../includes/header.php';
?>


<section class="content-panel form-panel">

    <div class="page-intro">

        <h1>
            Modify Operator
        </h1>

        <p>
            Modify the selected FnH Groceries operator and access level.
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

        <input
            type="hidden"
            name="id"
            value="<?= (int)$operatorID ?>"
        >


        <div class="form-grid">

            <div class="form-field">

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

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= escapeOutput($username) ?>"
                    maxlength="50"
                    required
                >

            </div>


            <div class="form-field">

                <label for="role">
                    Access *
                </label>

                <select
                    id="role"
                    name="role"
                    required
                >

                    <option
                        value="Pending"
                        <?= $selectedRole === 'Pending' ? 'selected' : '' ?>
                    >
                        No Access
                    </option>

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
                >

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
                    Leave blank to keep the current password. <?= escapeOutput(passwordRequirementText()) ?>
                </div>

            </div>


            <div class="form-field">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                >

            </div>

        </div>

        <?php if ($openSaleRecord): ?>
            <div
                id="openSaleChangeWarning"
                class="message message-warning"
                hidden
            >
                This operator currently has an open sale on Register
                <?= escapeOutput($openSaleRecord['RegisterNumber']) ?>.
                Changing the assigned store or changing access to No Access will cancel that sale,
                restore its merchandise to inventory, release the register, and record the
                administrator action in the transaction journal.

                <div class="form-field">
                    <label>
                        <input
                            type="checkbox"
                            id="confirm_open_sale_cancel"
                            name="confirm_open_sale_cancel"
                            value="1"
                            <?= $openSaleCancellationConfirmed ? 'checked' : '' ?>
                        >
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
                    '<?= (int)$operatorRecord['StoreID'] ?>';

                function updateOpenSaleWarning()
                {
                    const mustCancel =
                        storeSelect.value !== originalStoreID
                        ||
                        roleSelect.value === 'Pending';

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

            <button
                type="submit"
                name="save_update"
                value="1"
                class="button button-primary"
            >
                Save Changes
            </button>

            <a
                href="operator_list.php"
                class="button button-secondary"
            >
                Cancel
            </a>

        </div>

    </form>

</section>


<?php
require __DIR__ . '/../includes/footer.php';
?>