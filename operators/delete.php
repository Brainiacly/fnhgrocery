<?php // operators/delete.php

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
    http_response_code(404);
    exit('The selected operator was not found.');
}

$openSaleStatement =
    $databaseConnection->prepare(
        '
        SELECT
            sr.ReceiptID,
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

$deleteIsAllowed =
    (int)$operatorID
    !==
    (int)$_SESSION['operator_id']
    &&
    (int)$operatorRecord['Active']
    ===
    1;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['confirm_delete'])
) {
    if (!$deleteIsAllowed) {
        $errorMessage =
            'This operator cannot be deleted.';
    } elseif (
        $openSaleRecord
        &&
        ($_POST['confirm_open_sale_cancel'] ?? '') !== '1'
    ) {
        $errorMessage =
            'Confirm that the open sale may be cancelled before deleting this operator.';
    } else {
        try {
            $deleteOperatorStatement =
                $databaseConnection->prepare(
                    '
                    CALL sp_delete_operator(
                        ?,
                        ?
                    )
                    '
                );

            $deleteOperatorStatement->execute([
                (int)$operatorID,
                (int)$_SESSION['operator_id']
            ]);

            $deleteOperatorStatement->closeCursor();

            header(
                'Location: operator_list.php?deleted=1'
            );
            exit;
        } catch (PDOException $exception) {
            $errorMessage =
                getSafeDatabaseErrorMessage(
                    $exception,
                    'The operator could not be deleted.'
                );
        }
    }
}

$pageTitle = 'Delete Operator';
$currentSection = 'operators';
$currentPage = 'delete';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel delete-panel">
    <div class="page-intro">
        <h1>
            Confirm Delete Operator
        </h1>

        <p>
            Review the operator information carefully before continuing.
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!$deleteIsAllowed): ?>
        <div class="message message-warning">
            <?php if (
                (int)$operatorID
                ===
                (int)$_SESSION['operator_id']
            ): ?>
                You cannot delete the account that you are currently using.
            <?php elseif (
                (int)$operatorRecord['Active']
                !==
                1
            ): ?>
                This operator is already inactive and cannot be deleted again.
            <?php else: ?>
                This operator cannot be deleted.
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a
                href="operator_list.php"
                class="button button-secondary"
            >
                Return to Operator List
            </a>
        </div>
    <?php else: ?>
        <div class="delete-confirmation-question">
            Are you sure you want to delete this operator?
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

        <?php if ($openSaleRecord): ?>
            <div class="message message-warning">
                <strong>Open Sale Warning:</strong>
                This operator currently has an open sale on Register
                <?= escapeOutput($openSaleRecord['RegisterNumber']) ?>.
                Deleting this operator will cancel that sale, restore its merchandise to inventory,
                release the register, and record the administrator action in the transaction journal.
            </div>
        <?php else: ?>
            <div class="message message-warning">
                This action makes the operator inactive so historical sales information remains connected to the correct employee.
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

            <?php if ($openSaleRecord): ?>
                <div class="form-field">
                    <label>
                        <input
                            type="checkbox"
                            name="confirm_open_sale_cancel"
                            value="1"
                            required
                        >
                        I understand that the open sale will be cancelled.
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-actions delete-confirmation-actions">
                <button
                    type="submit"
                    name="confirm_delete"
                    value="1"
                    class="button button-danger"
                >
                    <?php if ($openSaleRecord): ?>
                        Yes, Cancel Sale and Delete Operator
                    <?php else: ?>
                        Yes, Delete Operator
                    <?php endif; ?>
                </button>

                <a
                    href="operator_list.php"
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