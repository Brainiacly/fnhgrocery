<?php // operators/op_access.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireSupervisor();

$db = connectDatabase();

$errorMessage = '';
$actingOperatorID = signedInOperatorID();

$operatorID =
    filter_input(
        INPUT_POST,
        'id',
        FILTER_VALIDATE_INT
    );

// The to value is 0 to inactivate the employee and 1 to reactivate them
$makeActive = ($_GET['to'] ?? '0') === '1' ? 1 : 0;

if (!$operatorID) {
    showAccessDeniedPage(
        'Select an employee from the Employee List before opening this page.'
    );
}

$submittedToken =
    $_POST['form_security_token'] ?? '';

if (!tokenIsValid($submittedToken)) {
    showAccessDeniedPage(
        'The form expired. Please return to the Employee List and try again.'
    );
}

$operatorStatement =
    $db->prepare(
        '
        SELECT
            OperatorID,
            StoreID,
            EmployeeNumber,
            Username,
            FullName,
            Email,
            Role,
            StoreNumber,
            StoreName,
            Active
        FROM vw_operatorlist
        WHERE OperatorID = :operatorID
        LIMIT 1
        '
    );

$operatorStatement->execute([
    ':operatorID' => $operatorID
]);

$operatorRecord = $operatorStatement->fetch();

if (
    !$operatorRecord
    ||
    (int) $operatorRecord['StoreID'] !== signedInStoreID()
) {
    showAccessDeniedPage(
        'The selected employee was not found. Return to the Employee List and try again.'
    );
}

$changeIsNeeded = (int) $operatorRecord['Active'] !== $makeActive;
$wordNow = $makeActive === 1 ? 'Reactivate' : 'Inactivate';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['confirm_access'])
) {
    if (!$changeIsNeeded) {
        $errorMessage =
            'The employee already has that access.';
    } else {
        try {
            $accessStatement =
                $db->prepare(
                    '
                    CALL sp_set_operator_active(
                        :actingOperatorID,
                        :operatorID,
                        :active
                    )
                    '
                );

            $accessStatement->execute([
                ':actingOperatorID' => $actingOperatorID,
                ':operatorID' => (int) $operatorID,
                ':active' => $makeActive
            ]);

            $accessStatement->closeCursor();

            header(
                'Location: op_list.php?access='
                . ($makeActive === 1 ? 'reactivated' : 'inactivated')
                . '&show_inactive=1'
            );
            exit;
        } catch (PDOException $exception) {
            $errorMessage =
                databaseMessage(
                    $exception,
                    'The employee access could not be changed.'
                );
        }
    }
}

$pageTitle = $wordNow . ' Employee';
$currentSection = 'operators';
$currentPage = 'access';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel delete-panel">

    <div class="page-intro">

        <h1>
            Confirm <?= escapeOutput($wordNow) ?> Employee
        </h1>

        <p>
            Review the employee information before changing their access.
        </p>

    </div>

    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>

    <?php if (!$changeIsNeeded): ?>

        <div class="message message-warning">
            This employee already has that access.
        </div>

        <div class="form-actions">

            <a
                href="op_list.php"
                class="button button-secondary"
            >
                Return to Employee List
            </a>

        </div>

    <?php else: ?>

        <div class="delete-confirmation-question">
            Are you sure you want to <?= escapeOutput(strtolower($wordNow)) ?> this employee?
        </div>

        <div class="operator-summary">

            <div class="operator-summary-name">
                <?= escapeOutput($operatorRecord['FullName']) ?>
            </div>

            <div class="operator-summary-row">
                <span class="operator-summary-label">
                    Employee Number:
                </span>
                <span>
                    <?= escapeOutput($operatorRecord['EmployeeNumber']) ?>
                </span>
            </div>

            <div class="operator-summary-row">
                <span class="operator-summary-label">
                    Username:
                </span>
                <span>
                    <?= escapeOutput($operatorRecord['Username']) ?>
                </span>
            </div>

            <div class="operator-summary-row">
                <span class="operator-summary-label">
                    Role:
                </span>
                <span>
                    <?= escapeOutput($operatorRecord['Role']) ?>
                </span>
            </div>

            <div class="operator-summary-row">
                <span class="operator-summary-label">
                    Store:
                </span>
                <span>
                    <?= escapeOutput($operatorRecord['StoreNumber']) ?>
                    -
                    <?= escapeOutput($operatorRecord['StoreName']) ?>
                </span>
            </div>

        </div>

        <div class="message message-information">
            <?php if ($makeActive === 0): ?>
                The employee is signed out on their next click and cannot sign in again until they are
                reactivated. Any sale they have open stays open until a Manager takes it over or cancels it.
            <?php else: ?>
                Reactivating restores this account to its existing role and assigned store.
            <?php endif; ?>
        </div>

        <form method="post">

            <input
                type="hidden"
                name="form_security_token"
                value="<?= escapeOutput(formToken()) ?>"
            >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $operatorID ?>"
            >

            <div class="form-actions delete-confirmation-actions">

                <button
                    type="submit"
                    name="confirm_access"
                    value="1"
                    class="button button-primary"
                >
                    Yes, <?= escapeOutput($wordNow) ?>
                </button>

                <a
                    href="op_list.php"
                    class="button button-secondary"
                >
                    No, Cancel
                </a>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php
require __DIR__ . '/../includes/footer.php';
?>