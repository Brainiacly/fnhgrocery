<?php // operators/list.php

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$databaseConnection = connectDatabase();

$roleFilter = $_GET['role'] ?? 'all';
$showInactive = ($_GET['show_inactive'] ?? '') === '1';

if (
    !in_array(
        $roleFilter,
        [
            'all',
            'Administrator',
            'Operator'
        ],
        true
    )
) {
    $roleFilter = 'all';
}


// Load the operators
$operatorListStatement = $databaseConnection->prepare(
    '
    SELECT
        OperatorID,
        EmployeeNumber,
        Username,
        FirstName,
        LastName,
        FullName,
        Role,
        Active,
        CreatedAt
    FROM vw_operatorlist
    WHERE
        (:showInactive = 1 OR Active = 1)
        AND
        (:showAllRoles = 1 OR Role = :roleFilter)
    ORDER BY
        Active DESC,
        LastName,
        FirstName
    '
);

$operatorListStatement->execute([
    ':showInactive' => $showInactive ? 1 : 0,
    ':showAllRoles' => $roleFilter === 'all' ? 1 : 0,
    ':roleFilter' => $roleFilter
]);

$operatorRecords = $operatorListStatement->fetchAll();


// Show confirmation after operator changes
$successMessage = '';

if (isset($_GET['created'])) {
    $successMessage = 'The operator was created successfully.';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'The operator was updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $successMessage = 'The operator was deleted successfully and is now inactive.';
}

$pageTitle = 'Operator List';
$currentSection = 'operators';
$currentPage = 'list';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel operator-list-panel">

    <div class="page-intro operator-list-intro">

        <div class="operator-list-heading">
            <h1>
                Operator List
            </h1>

            <p>
                Select an operator to update or delete.
            </p>
        </div>

        <form
            method="get"
            class="operator-filter-form"
        >

            <div class="operator-filter-field">
                <label for="roleFilter">
                    Show
                </label>

                <select
                    id="roleFilter"
                    name="role"
                    onchange="this.form.submit()"
                >
                    <option
                        value="all"
                        <?= $roleFilter === 'all' ? 'selected' : '' ?>
                    >
                        All
                    </option>

                    <option
                        value="Administrator"
                        <?= $roleFilter === 'Administrator' ? 'selected' : '' ?>
                    >
                        Admins
                    </option>

                    <option
                        value="Operator"
                        <?= $roleFilter === 'Operator' ? 'selected' : '' ?>
                    >
                        Operators
                    </option>
                </select>
            </div>

            <label class="operator-inactive-filter">

                <input
                    type="checkbox"
                    name="show_inactive"
                    value="1"
                    class="operator-inactive-checkbox"
                    onchange="this.form.submit()"
                    <?= $showInactive ? 'checked' : '' ?>
                >

                <span>
                    Inactive
                </span>

            </label>

        </form>

    </div>

    <?php if ($successMessage !== ''): ?>

        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>

    <form
        id="operatorSelectionForm"
        method="get"
        class="operator-selection-form"
    >

        <?php if (count($operatorRecords) === 0): ?>

            <div class="operator-table-empty">
                No operators were found.
            </div>

        <?php else: ?>

            <div
                class="operator-table-area"
                tabindex="0"
                aria-label="Operator list. Scroll horizontally if needed."
            >

                <table class="operator-table">

                    <colgroup>
                        <col class="operator-column-select">
                        <col class="operator-column-employee">
                        <col class="operator-column-username">
                        <col class="operator-column-name">
                        <col class="operator-column-created">
                        <col class="operator-column-status">
                    </colgroup>

                    <thead>
                        <tr>

                            <th class="operator-heading-select">
                                Select
                            </th>

                            <th class="operator-heading-employee">
                                <span>
                                    Employee
                                </span>

                                <small>
                                    Number
                                </small>
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                <span>
                                    Name
                                </span>

                                <small class="operator-heading-detail">
                                    Last, First, MI
                                </small>
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($operatorRecords as $operatorRecord): ?>

                            <?php
                            $isCurrentOperator =
                                (int)$operatorRecord['OperatorID']
                                ===
                                (int)$_SESSION['operator_id'];

                            $isActiveOperator =
                                (int)$operatorRecord['Active'] === 1;

                            $radioClass = 'operator-select-radio';

                            if ($isCurrentOperator || !$isActiveOperator) {
                                $radioClass .=
                                    ' operator-select-radio-protected';
                            }

                            if ($isCurrentOperator) {
                                $radioClass .=
                                    ' operator-select-radio-current';
                            }

                            if (!$isActiveOperator) {
                                $radioClass .=
                                    ' operator-select-radio-inactive';
                            }

                            $createdAtDisplay = $operatorRecord['CreatedAt']
                                ? date(
                                    'M j, Y',
                                    strtotime($operatorRecord['CreatedAt'])
                                )
                                : '';
                            ?>

                            <tr>

                                <td class="operator-select-cell">

                                    <label
                                        class="operator-radio-label"
                                        for="operator_<?= (int)$operatorRecord['OperatorID'] ?>"
                                    >

                                        <input
                                            type="radio"
                                            id="operator_<?= (int)$operatorRecord['OperatorID'] ?>"
                                            name="id"
                                            value="<?= (int)$operatorRecord['OperatorID'] ?>"
                                            class="<?= $radioClass ?>"
                                            required
                                        >

                                        <span class="screen-reader-text">
                                            Select
                                            <?= escapeOutput($operatorRecord['Username']) ?>
                                        </span>

                                    </label>

                                </td>

                                <td class="operator-employee-cell">
                                    <?= escapeOutput($operatorRecord['EmployeeNumber']) ?>
                                </td>

                                <td>
                                    <?= escapeOutput($operatorRecord['Username']) ?>
                                </td>

                                <td>
                                    <?= escapeOutput($operatorRecord['FullName']) ?>
                                </td>

                                <td class="operator-created-cell">
                                    <?= escapeOutput($createdAtDisplay) ?>
                                </td>

                                <td>

                                    <?php if ($isActiveOperator): ?>

                                        <span class="status-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                    <?php if ($isCurrentOperator): ?>

                                        <span class="status-context">
                                            Current account
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <p class="operator-list-count">
                <?= count($operatorRecords) ?>
                operator<?= count($operatorRecords) === 1 ? '' : 's' ?> shown
            </p>

        <?php endif; ?>

    </form>

    <div class="operator-list-instructions">

        <h2>
            Using This List
        </h2>

        <ul>
            <li>
                Select an operator to use Update, Delete, or Clear Selection in the navigation area.
            </li>

            <li>
                Delete makes an operator inactive so previous records remain connected to the correct employee.
            </li>

            <li>
                Check Inactive to include inactive operators in the list.
            </li>

            <li>
                The account currently signed in cannot be deleted.
            </li>

            <li>
                The last active Administrator cannot be deleted or changed to another access level.
            </li>
        </ul>

    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>