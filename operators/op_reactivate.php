<?php // operators/op_reactivate.php

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
        SELECT
            OperatorID,
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

$operatorRecord =
    $operatorStatement->fetch();

if (!$operatorRecord) {
    showAccessDeniedPage(
        'The selected employee was not found. Return to the Employee List and try again.'
    );
}

$reactivateIsAllowed =
    (int)$operatorRecord['Active']
    ===
    0;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['confirm_reactivate'])
) {
    if (!$reactivateIsAllowed) {
        $errorMessage =
            'This employee is already active.';
    } else {
        try {
            $reactivateStatement =
                $db->prepare(
                    '
                    CALL sp_reactivate_operator(
                        ?
                    )
                    '
                );

            $reactivateStatement->execute([
                (int)$operatorID
            ]);

            $reactivateStatement->closeCursor();

            header(
                'Location: op_list.php?reactivated=1&show_inactive=1'
            );
            exit;
        } catch (PDOException $exception) {
            $errorMessage =
                databaseMessage(
                    $exception,
                    'The employee could not be reactivated.'
                );
        }
    }
}

$pageTitle = 'Reactivate Employee';
$currentSection = 'operators';
$currentPage = 'reactivate';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel delete-panel">
    <div class="page-intro">
        <h1>
            Confirm Reactivate Employee
        </h1>

        <p>
            Review the employee information before restoring access.
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!$reactivateIsAllowed): ?>
        <div class="message message-warning">
            This employee is already active.
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
            Are you sure you want to reactivate this employee?
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
                    Email:
                </span>

                <span>
                    <?= escapeOutput($operatorRecord['Email']) ?>
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

            <div class="operator-summary-row">
                <span class="operator-summary-label">
                    Role:
                </span>

                <span>
                    <?php if ($operatorRecord['Role'] === 'Pending'): ?>
                        No Access
                    <?php else: ?>
                        <?= escapeOutput($operatorRecord['Role']) ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="message message-information">
            Reactivating restores this account to its existing role and assigned store.
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
                value="<?= (int)$operatorID ?>"
            >

            <div class="form-actions delete-confirmation-actions">
                <button
                    type="submit"
                    name="confirm_reactivate"
                    value="1"
                    class="button button-primary"
                >
                    Yes, Reactivate Employee
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