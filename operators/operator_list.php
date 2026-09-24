<?php // operators/operator_list.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$databaseConnection = connectDatabase();

$roleFilter = $_GET['role'] ?? 'all';

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

$showInactive =
    ($_GET['show_inactive'] ?? '')
    ===
    '1';

$showAllRoles =
    $roleFilter === 'all';

$operatorListStatement =
    $databaseConnection->prepare(
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
            EmployeeNumber ASC,
            Username ASC
        '
    );

$operatorListStatement->execute([
    ':showInactive' => $showInactive ? 1 : 0,
    ':showAllRoles' => $showAllRoles ? 1 : 0,
    ':roleFilter' => $roleFilter
]);

$operatorRecords =
    $operatorListStatement->fetchAll();

$successMessage = '';

if (isset($_GET['created'])) {
    $successMessage =
        'The operator was created successfully.';
}

if (isset($_GET['updated'])) {
    $successMessage =
        'The operator was updated successfully.';
}

if (isset($_GET['deleted'])) {
    $successMessage =
        'The operator was deleted successfully and is now inactive.';
}

if (isset($_GET['reactivated'])) {
    $successMessage =
        'The operator was reactivated successfully.';
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
                Select an operator to update, delete, or reactivate.
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

            <div class="operator-filter-field">
                <label
                    for="showInactive"
                    class="operator-inactive-label"
                >
                    <input
                        type="checkbox"
                        id="showInactive"
                        name="show_inactive"
                        value="1"
                        <?= $showInactive ? 'checked' : '' ?>
                        onchange="this.form.submit()"
                    >
                    <span>
                        Inactive
                    </span>
                </label>
            </div>
        </form>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>
    <?php endif; ?>

    <form
        id="operatorSelectionForm"
        method="post"
        class="operator-selection-form"
    >
        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <?php if (count($operatorRecords) === 0): ?>
            <div class="operator-table-empty">
                No operators were found.
            </div>
        <?php else: ?>
            <div
                class="table-container"
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

                                <span class="operator-heading-second-line">
                                    Number
                                </span>
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                <span>
                                    Name
                                </span>

                                <span class="operator-heading-second-line">
                                    Last, First, MI
                                </span>
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
                                (int)$operatorRecord['Active']
                                ===
                                1;

                            $radioClass =
                                'operator-select-radio';

                            if ($isCurrentOperator) {
                                $radioClass .=
                                    ' operator-select-radio-protected operator-select-radio-current';
                            }

                            if (!$isActiveOperator) {
                                $radioClass .=
                                    ' operator-select-radio-inactive';
                            }

                            $createdAtDisplay =
                                $operatorRecord['CreatedAt']
                                    ? date(
                                        'M j, Y',
                                        strtotime(
                                            $operatorRecord['CreatedAt']
                                        )
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
                                            data-active="<?= $isActiveOperator ? '1' : '0' ?>"
                                            data-current="<?= $isCurrentOperator ? '1' : '0' ?>"
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

                                <td class="operator-username-cell">
                                    <?= escapeOutput($operatorRecord['Username']) ?>
                                </td>

                                <td class="operator-name-cell">
                                    <?= escapeOutput($operatorRecord['FullName']) ?>
                                </td>

                                <td class="operator-created-cell">
                                    <?= escapeOutput($createdAtDisplay) ?>
                                </td>

                                <td class="operator-status-cell">
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
                operator<?= count($operatorRecords) === 1 ? '' : 's' ?> shown.
            </p>
        <?php endif; ?>
    </form>

    <div class="operator-list-help">
        <h2>
            Using This List
        </h2>

        <ul>
            <li>
                Select an active operator to update or delete the account.
            </li>

            <li>
                Check Inactive to include inactive operators in the list.
            </li>

            <li>
                Selecting an inactive operator changes Delete Operator to Reactivate Operator.
            </li>

            <li>
                Delete makes an operator inactive so historical sales records remain connected.
            </li>

            <li>
                The account you are currently signed in with cannot be deleted.
            </li>

            <li>
                The final active Administrator cannot be deleted or demoted.
            </li>
        </ul>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectionForm =
        document.getElementById('operatorSelectionForm');
    const updateButton =
        document.getElementById('operatorUpdateButton');
    const statusButton =
        document.getElementById('operatorStatusButton');
    const clearButton =
        document.getElementById('operatorClearButton');

    if (
        !selectionForm
        ||
        !updateButton
        ||
        !statusButton
        ||
        !clearButton
    ) {
        return;
    }

    const deleteAddress =
        '<?= APPLICATION_URL ?>/operators/delete.php';
    const reactivateAddress =
        '<?= APPLICATION_URL ?>/operators/reactivate.php';

    function updateOperatorButtons()
    {
        const selectedOperator =
            selectionForm.querySelector(
                'input[name="id"]:checked'
            );

        if (!selectedOperator) {
            updateButton.disabled = true;
            statusButton.disabled = true;
            clearButton.disabled = true;
            statusButton.textContent =
                'Delete Operator';
            statusButton.setAttribute(
                'formaction',
                deleteAddress
            );
            statusButton.classList.remove(
                'operator-nav-update'
            );
            statusButton.classList.add(
                'operator-nav-delete'
            );
            return;
        }

        updateButton.disabled = false;
        clearButton.disabled = false;

        const operatorIsActive =
            selectedOperator.dataset.active === '1';
        const operatorIsCurrent =
            selectedOperator.dataset.current === '1';

        if (!operatorIsActive) {
            statusButton.disabled = false;
            statusButton.textContent =
                'Reactivate Operator';
            statusButton.setAttribute(
                'formaction',
                reactivateAddress
            );
            statusButton.removeAttribute(
                'aria-describedby'
            );
            statusButton.classList.remove(
                'operator-nav-delete'
            );
            statusButton.classList.add(
                'operator-nav-update'
            );
            return;
        }

        statusButton.textContent =
            'Delete Operator';
        statusButton.setAttribute(
            'formaction',
            deleteAddress
        );
        statusButton.setAttribute(
            'aria-describedby',
            'currentAccountDeleteNote'
        );
        statusButton.classList.remove(
            'operator-nav-update'
        );
        statusButton.classList.add(
            'operator-nav-delete'
        );
        statusButton.disabled =
            operatorIsCurrent;
    }

    selectionForm
        .querySelectorAll(
            'input[name="id"]'
        )
        .forEach(function (radioButton) {
            radioButton.addEventListener(
                'change',
                updateOperatorButtons
            );
        });

    selectionForm.addEventListener(
        'reset',
        function () {
            window.setTimeout(
                updateOperatorButtons,
                0
            );
        }
    );

    updateOperatorButtons();
});
</script>

<?php
require __DIR__ . '/../includes/footer.php';
?>