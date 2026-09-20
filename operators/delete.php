<?php // operators/delete.php

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$databaseConnection = connectDatabase();

$errorMessage = '';

$operatorID = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$operatorID) {
    http_response_code(400);
    exit('A valid operator ID is required.');
}


// Load the selected operator
$operatorStatement = $databaseConnection->prepare(
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

$operatorRecord = $operatorStatement->fetch();

if (!$operatorRecord) {
    http_response_code(404);
    exit('The selected operator was not found.');
}

$deleteIsAllowed =
    (int)$operatorID !== (int)$_SESSION['operator_id']
    &&
    (int)$operatorRecord['Active'] === 1;


// Process the confirmed delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';

    if (!$deleteIsAllowed) {
        $errorMessage = 'This operator cannot be deleted.';
    } elseif (!formSecurityTokenIsValid($submittedSecurityToken)) {
        $errorMessage =
            'The form expired. Please return to the Operator List and try again.';
    } else {
        try {
            $deleteOperatorStatement = $databaseConnection->prepare(
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

            header('Location: list.php?deleted=1');
            exit;
        } catch (PDOException $exception) {
            $errorMessage = getSafeDatabaseErrorMessage(
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

            <?php elseif ((int)$operatorRecord['Active'] !== 1): ?>

                This operator is already inactive and cannot be deleted again.

            <?php else: ?>

                This operator cannot be deleted.

            <?php endif; ?>

        </div>

        <div class="form-actions">

            <a
                href="list.php"
                class="button button-secondary"
            >
                Return to Operator List
            </a>

        </div>

    <?php else: ?>

        <div class="delete-confirmation-question">
            Are you sure you want to delete this operator?
        </div>

        <div class="operator-delete-information">

            <div class="operator-delete-name">
                <?= escapeOutput($operatorRecord['FullName']) ?>
            </div>

            <div class="operator-delete-row">
                <span class="operator-delete-label">
                    Employee Number:
                </span>

                <span>
                    <?= escapeOutput($operatorRecord['EmployeeNumber']) ?>
                </span>
            </div>

            <div class="operator-delete-row">
                <span class="operator-delete-label">
                    Username:
                </span>

                <span>
                    <?= escapeOutput($operatorRecord['Username']) ?>
                </span>
            </div>

            <div class="operator-delete-row">
                <span class="operator-delete-label">
                    Email:
                </span>

                <span>
                    <?= escapeOutput($operatorRecord['Email']) ?>
                </span>
            </div>

            <div class="operator-delete-row">
                <span class="operator-delete-label">
                    Store:
                </span>

                <span>
                    <?= escapeOutput($operatorRecord['StoreNumber']) ?>
                    -
                    <?= escapeOutput($operatorRecord['StoreName']) ?>
                </span>
            </div>

            <div class="operator-delete-row">
                <span class="operator-delete-label">
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

        <div class="message message-warning">
            This action makes the operator inactive so historical sales information remains connected to the correct employee.
        </div>

        <form method="post">

            <input
                type="hidden"
                name="form_security_token"
                value="<?= escapeOutput(getFormSecurityToken()) ?>"
            >

            <div class="form-actions delete-confirmation-actions">

                <button
                    type="submit"
                    class="button button-danger"
                >
                    Yes, Delete Operator
                </button>

                <a
                    href="list.php"
                    class="button button-secondary"
                >
                    No, Cancel
                </a>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>