<?php // operators/op_list.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireSupervisor();

$db = connectDatabase();

$roleFilter =
    $_GET['role']
    ?? 'all';

if (
    !in_array(
        $roleFilter,
        [
            'all', 'Administrator', 'Manager', 'Operator', 'Personal Shopper'
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
    $db->prepare(
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
            (:allStores = 1 OR StoreID = :storeID)
            AND
            (:showInactive = 1 OR Active = 1)
            AND
            (:showAllRoles = 1 OR Role = :roleFilter)
        ORDER BY
            EmployeeNumber ASC,
            Username ASC
        '
    );

$operatorListStatement->execute([
    ':allStores' => isAdministrator() ? 1 : 0,
    ':storeID' => signedInStoreID(),
    ':showInactive' => $showInactive ? 1 : 0,
    ':showAllRoles' => $showAllRoles ? 1 : 0,
    ':roleFilter' => $roleFilter
]);

$operatorRecords =
    $operatorListStatement->fetchAll();

$successMessage = '';
if (($_GET['access'] ?? '') === 'inactivated') {
    $successMessage =
        'The employee was inactivated. They cannot sign in until they are reactivated.';
} elseif (($_GET['access'] ?? '') === 'reactivated') {
    $successMessage =
        'The employee was reactivated.';
}
if (isset($_GET['created'])) {
    $successMessage =
        'The employee was created successfully.';
}

if (isset($_GET['updated'])) {
    $successMessage =
        'The employee was updated successfully.';
}

if (isset($_GET['deleted'])) {
    $successMessage =
        'The employee was deleted successfully and is now inactive.';
}

if (isset($_GET['reactivated'])) {
    $successMessage =
        'The employee was reactivated successfully.';
}

$pageTitle = 'Employee List';
$currentSection = 'operators';
$currentPage = 'list';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel operator-list-panel">
    <div class="page-intro operator-list-intro">
        <div class="operator-list-heading">
            <h1>
                Employees
            </h1>

            <p>
                <?= isAdministrator()
                    ? 'Select an employee to update, delete, or reactivate.'
                    : 'Select an employee to view their transactions or change their access.' ?>
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
                        value="Manager"
                        <?= $roleFilter === 'Manager' ? 'selected' : '' ?>
                    >
                        Managers
                    </option>

                    <option
                        value="Operator"
                        <?= $roleFilter === 'Operator' ? 'selected' : '' ?>
                    >
                        Operators
                    </option>

                    <option
                        value="Personal Shopper"
                        <?= $roleFilter === 'Personal Shopper' ? 'selected' : '' ?>
                    >
                        Personal Shoppers
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
            value="<?= escapeOutput(formToken()) ?>"
        >

        <?php if (count($operatorRecords) === 0): ?>
            <div class="operator-table-empty">
                No operators were found.
            </div>
        <?php else: ?>
            <div
                class="table-container"
                tabindex="0"
                aria-label="Employee list. Scroll horizontally if needed."
            >
                <table class="operator-table">
                    <colgroup>
                        <col class="operator-column-select">
                        <col class="operator-column-employee">
                        <col class="operator-column-username">
                        <col class="operator-column-name">
                        <col class="operator-column-role">
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
                                Type
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
                                (int) $operatorRecord['OperatorID']
                                ===
                                signedInOperatorID();

                            $isActiveOperator =
                                (int) $operatorRecord['Active']
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

                            $roleCodes =
                                [
                                    'Administrator' => 'A', 'Manager' => 'M', 'Operator' => 'O',
                                    'Personal Shopper' => 'S',
                                    'Pending' => 'P'
                                ];

                            $roleCode =
                                $roleCodes[$operatorRecord['Role']]
                                ?? '?';
                            ?>

                            <tr>
                                <td class="operator-select-cell">
                                    <label
                                        class="operator-radio-label"
                                        for="operator_<?= (int) $operatorRecord['OperatorID'] ?>"
                                    >
                                        <input
                                            type="radio"
                                            id="operator_<?= (int) $operatorRecord['OperatorID'] ?>"
                                            name="id"
                                            value="<?= (int) $operatorRecord['OperatorID'] ?>"
                                            class="<?= $radioClass ?>"
                                            data-active="<?= $isActiveOperator ? '1' : '0' ?>"
                                            data-current="<?= $isCurrentOperator ? '1' : '0' ?>"
                                            data-role="<?= escapeOutput($operatorRecord['Role']) ?>"
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

                                <td class="operator-role-cell">
                                    <span
                                        class="operator-role-code"
                                        title="<?= escapeOutput($operatorRecord['Role']) ?>"
                                    >
                                        <?= escapeOutput($roleCode) ?>
                                    </span>
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

            <div class="operator-role-key">
                <span class="operator-role-key-items">
                    <strong>A</strong> Administrator
                    &nbsp;&nbsp;
                    <strong>M</strong> Manager
                    &nbsp;&nbsp;
                    <strong>O</strong> Operator
                    &nbsp;&nbsp;
                    <strong>S</strong> Personal Shopper
                    &nbsp;&nbsp;
                    <strong>P</strong> Pending
                </span>

                <span class="operator-list-count">
                    <?= count($operatorRecords) ?>
                    employee<?= count($operatorRecords) === 1 ? '' : 's' ?> shown.
                </span>
            </div>
        <?php endif; ?>
    </form>

    <div
        id="operatorListHelp"
        class="operator-list-help"
    >
        <h2>
            Using This List
        </h2>

        <ul>
            <?php if (isAdministrator()): ?>
                <li>
                    Select an active employee to update or delete the account.
                </li>

                <li>
                    Check Inactive to include inactive employees in the list.
                </li>

                <li>
                    Selecting an inactive employee changes Delete Employee to Reactivate Employee.
                </li>

                <li>
                    Delete makes an employee inactive so historical sales records remain connected.
                </li>

                <li>
                    The account you are currently signed in with cannot be deleted.
                </li>

                <li>
                    The final active Administrator cannot be deleted or demoted.
                </li>
            <?php else: ?>
                <li>
                    Select an employee, then press View Transactions to see everything they opened,
                    worked on, or completed.
                </li>

                <li>
                    Inactivate Employee signs the person out on their next click and blocks sign-in
                    until they are reactivated.
                </li>

                <li>
                    Check Inactive to include inactive employees, select one, and press Reactivate Employee.
                </li>

                <li>
                    Open transactions of an inactive employee stay open until someone takes them over or cancels them.
                </li>

                <li>
                    You cannot change an Administrator or your own access.
                </li>
            <?php endif; ?>
        </ul>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const selectionForm = document.getElementById('operatorSelectionForm');
    const updateButton = document.getElementById('operatorUpdateButton');
    const statusButton = document.getElementById('operatorStatusButton');
    const clearButton = document.getElementById('operatorClearButton');
    const transactionsLink = document.getElementById('operatorTransactionsLink');

    if (!selectionForm || !statusButton || !clearButton) {
        return;
    }

    const isManagerView = <?= isManager() ? 'true' : 'false' ?>;
    const deleteAddress = '<?= APPLICATION_URL ?>/operators/op_delete.php';
    const reactivateAddress = '<?= APPLICATION_URL ?>/operators/op_reactivate.php';
    const inactivateAddress = '<?= APPLICATION_URL ?>/operators/op_access.php?to=0';
    const activateAddress = '<?= APPLICATION_URL ?>/operators/op_access.php?to=1';
    const transactionsAddress = '<?= APPLICATION_URL ?>/transactions/tr_list.php?employee=';

    // The status button deletes (Administrator) or inactivates (Manager), then reactivates
    function showStatusButton(label, address, isRemoval) {

        statusButton.textContent = label;
        statusButton.setAttribute('formaction', address);
        statusButton.classList.toggle('operator-nav-delete', isRemoval);
        statusButton.classList.toggle('operator-nav-update', !isRemoval);
    }

    function updateOperatorButtons() {

        const selectedOperator = selectionForm.querySelector('input[name="id"]:checked');

        if (!selectedOperator) {

            if (updateButton) {
                updateButton.disabled = true;
            }

            statusButton.disabled = true;
            clearButton.disabled = true;
            showStatusButton(
                isManagerView ? 'Inactivate Employee' : 'Delete Employee',
                isManagerView ? inactivateAddress : deleteAddress,
                true
            );

            if (transactionsLink) {
                transactionsLink.setAttribute('aria-disabled', 'true');
                transactionsLink.setAttribute('href', '#');
            }

            return;
        }

        if (updateButton) {
            updateButton.disabled = false;
        }

        clearButton.disabled = false;

        if (transactionsLink) {
            transactionsLink.setAttribute('aria-disabled', 'false');
            transactionsLink.setAttribute('href', transactionsAddress + selectedOperator.value);
        }

        const operatorIsActive = selectedOperator.dataset.active === '1';
        const operatorIsCurrent = selectedOperator.dataset.current === '1';
        const operatorIsAdministrator = selectedOperator.dataset.role === 'Administrator';

        if (!operatorIsActive) {

            showStatusButton(
                'Reactivate Employee',
                isManagerView ? activateAddress : reactivateAddress,
                false
            );
            statusButton.removeAttribute('aria-describedby');
            statusButton.disabled = isManagerView && operatorIsAdministrator;
            return;
        }

        showStatusButton(
            isManagerView ? 'Inactivate Employee' : 'Delete Employee',
            isManagerView ? inactivateAddress : deleteAddress,
            true
        );
        statusButton.setAttribute('aria-describedby', 'currentAccountDeleteNote');
        statusButton.disabled = operatorIsCurrent || (isManagerView && operatorIsAdministrator);
    }

    selectionForm
        .querySelectorAll('input[name="id"]')
        .forEach(function (radioButton) {
            radioButton.addEventListener('change', updateOperatorButtons);
        });

    selectionForm.addEventListener('reset', function () {
        window.setTimeout(updateOperatorButtons, 0);
    });

    if (transactionsLink) {
        transactionsLink.addEventListener('click', function (event) {
            if (transactionsLink.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
            }
        });
    }

    updateOperatorButtons();
});
</script>

<?php
require __DIR__ . '/../includes/footer.php';
?>
