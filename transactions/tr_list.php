<?php // transactions/tr_list.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAccess();

$storeID = signedInStoreID();
$operatorID = signedInOperatorID();

$typeFilter = (string) ($_GET['type'] ?? 'all');
$statusFilter = (string) ($_GET['status'] ?? 'all');
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

if (!in_array($typeFilter, ['all', 'Regular', 'Express'], true)) {
    $typeFilter = 'all';
}

$validStatuses = ['all', 'Open', 'Paid', 'Completed', 'Voided', 'Refunded'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

if (isOperator()) {
    $typeFilter = 'Regular';
} elseif (isPersonalShopper()) {
    $typeFilter = 'Express';
}

$transactionRecords = [];
$filteredOperator = null;
$operatorFilter = canSupervise()
    ? (int) filter_var($_GET['employee'] ?? 0, FILTER_VALIDATE_INT)
    : 0;
$errorMessage = '';

try {
    $db = connectDatabase();

    $whereParts = ['sr.StoreID = :storeID'];
    $parameters = [':storeID' => $storeID];

    if (isOperator()) {
        $whereParts[] = '(sr.OperatorID = :operatorID OR tj.OpenedByOperatorID = :openedByID)';
        $whereParts[] = "sr.SaleType = 'Regular'";
        $parameters[':operatorID'] = $operatorID;
        $parameters[':openedByID'] = $operatorID;
    } elseif (isPersonalShopper()) {
        $whereParts[] = '(eo.PersonalShopperID = :operatorID OR tj.OpenedByOperatorID = :openedByID)';
        $whereParts[] = "sr.SaleType = 'Express'";
        $parameters[':operatorID'] = $operatorID;
        $parameters[':openedByID'] = $operatorID;
    }

    if ($operatorFilter > 0) {
        $filterStatement = $db->prepare(
            '
            SELECT OperatorID, FullName, Role
            FROM vw_operatorlist
            WHERE OperatorID = :operatorID
              AND StoreID = :storeID
            '
        );
        $filterStatement->execute([
            ':operatorID' => $operatorFilter,
            ':storeID' => $storeID
        ]);
        $filteredOperator = $filterStatement->fetch() ?: null;

        // Everything this person opened, worked on, or completed
        if ($filteredOperator) {
            $whereParts[] = '(
                sr.OperatorID = :filterOne
                OR tj.OpenedByOperatorID = :filterTwo
                OR tj.ClosedByOperatorID = :filterThree
                OR eo.PersonalShopperID = :filterFour
            )';
            $parameters[':filterOne'] = $operatorFilter;
            $parameters[':filterTwo'] = $operatorFilter;
            $parameters[':filterThree'] = $operatorFilter;
            $parameters[':filterFour'] = $operatorFilter;
        }
    }
    if ($typeFilter !== 'all' && canSupervise()) {
        $whereParts[] = 'sr.SaleType = :saleType';
        $parameters[':saleType'] = $typeFilter;
    }

    if ($statusFilter !== 'all') {
        $whereParts[] = 'sr.Status = :receiptStatus';
        $parameters[':receiptStatus'] = $statusFilter;
    }

    if ($searchTerm !== '') {
        $whereParts[] = '(
            sr.TransactionNumber LIKE :searchTransaction
            OR o.Username LIKE :searchUsername
            OR CONCAT(o.FirstName, \' \', o.LastName) LIKE :searchOperator
            OR CONCAT(COALESCE(c.FirstName, \'\'), \' \', COALESCE(c.LastName, \'\')) LIKE :searchCustomer
        )';
        $searchValue = '%' . $searchTerm . '%';
        $parameters[':searchTransaction'] = $searchValue;
        $parameters[':searchUsername'] = $searchValue;
        $parameters[':searchOperator'] = $searchValue;
        $parameters[':searchCustomer'] = $searchValue;
    }

    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $whereParts[] = 'sr.TransactionDateTime >= :dateFrom';
        $parameters[':dateFrom'] = $dateFrom . ' 00:00:00';
    }

    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $whereParts[] = 'sr.TransactionDateTime < DATE_ADD(:dateTo, INTERVAL 1 DAY)';
        $parameters[':dateTo'] = $dateTo . ' 00:00:00';
    }

    $transactionStatement = $db->prepare(
        '
        SELECT
            sr.ReceiptID,
            sr.TransactionNumber,
            sr.SaleType,
            sr.TransactionDateTime,
            sr.CheckoutDateTime,
            sr.Status AS ReceiptStatus,
            sr.TotalAmount,
            sr.PaymentMethod,
            r.RegisterNumber,
            r.RegisterName,
            o.Username,
            CONCAT(o.FirstName, \' \', o.LastName) AS OperatorName,
            c.CustomerID,
            c.FirstName AS CustomerFirstName,
            c.LastName AS CustomerLastName,
            eo.ExpressOrderID,
            eo.FulfillmentMethod,
            eo.DeliveryFee,
            eo.Status AS ExpressStatus
        FROM salesreceipt sr
        JOIN register r
            ON r.StoreID = sr.StoreID
           AND r.RegisterID = sr.RegisterID
        JOIN operator o
            ON o.OperatorID = sr.OperatorID
        LEFT JOIN customer c
            ON c.CustomerID = sr.CustomerID
        LEFT JOIN expressorder eo
            ON eo.ReceiptID = sr.ReceiptID
        LEFT JOIN transactionjournal tj
            ON tj.ReceiptID = sr.ReceiptID
        WHERE ' . implode(' AND ', $whereParts) . '
        ORDER BY
            sr.TransactionDateTime DESC,
            sr.ReceiptID DESC
        LIMIT 100
        '
    );

    $transactionStatement->execute($parameters);
    $transactionRecords = $transactionStatement->fetchAll();

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'Transactions could not be loaded.';
}

$pageTitle = 'Transaction Viewer';
$currentSection = 'transactions';
$currentPage = 'list';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel transaction-panel">

    <div class="page-intro transaction-compact-intro">
        <h1>Transaction Viewer</h1>
        <p>
            <?= canSupervise()
                ? 'Review Regular and Express transactions for your store.'
                : (isOperator()
                    ? 'Review your Regular Point of Sale transactions.'
                    : 'Review your FnH Express transactions.') ?>
        </p>
    </div>

    <?php if ($filteredOperator): ?>
        <div class="message message-information">
            Showing every transaction of
            <strong><?= escapeOutput($filteredOperator['FullName']) ?></strong>
            (<?= escapeOutput($filteredOperator['Role']) ?>): open and completed.
            <a href="<?= APPLICATION_URL ?>/transactions/tr_list.php">Show all transactions</a>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <form method="get" class="transaction-filter-form">
        <?php if ($filteredOperator): ?>
            <input type="hidden" name="employee" value="<?= (int) $filteredOperator['OperatorID'] ?>">
        <?php endif; ?>

        <div class="transaction-filter-field transaction-filter-search">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="<?= escapeOutput($searchTerm) ?>"
                placeholder="Transaction, customer, or employee">
        </div>

        <?php if (canSupervise()): ?>
            <div class="transaction-filter-field">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="Regular" <?= $typeFilter === 'Regular' ? 'selected' : '' ?>>Regular</option>
                    <option value="Express" <?= $typeFilter === 'Express' ? 'selected' : '' ?>>Express</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="transaction-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php
                foreach ($validStatuses as $statusOption) {
                    $statusSelectedAttribute = $statusFilter === $statusOption
                        ? ' selected="selected"'
                        : '';
                    $statusOptionLabel = $statusOption === 'all' ? 'All' : $statusOption;

                    echo '<option value="'
                        . escapeOutput($statusOption)
                        . '"'
                        . $statusSelectedAttribute
                        . '>'
                        . escapeOutput($statusOptionLabel)
                        . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="transaction-filter-field">
            <label for="date_from">From</label>
            <input type="date" id="date_from" name="date_from" value="<?= escapeOutput($dateFrom) ?>">
        </div>

        <div class="transaction-filter-field">
            <label for="date_to">To</label>
            <input type="date" id="date_to" name="date_to" value="<?= escapeOutput($dateTo) ?>">
        </div>

        <div class="transaction-filter-actions">
            <button type="submit" class="button button-primary">
                Apply
            </button>
            <a href="<?= APPLICATION_URL ?>/transactions/tr_list.php" class="button button-secondary">
                Clear
            </a>
        </div>

    </form>

    <div class="transaction-count-note">
        Showing up to 100 most recent matching transactions.
    </div>

    <div class="transaction-table-container" tabindex="0">
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Transaction</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Customer</th>
                    <th>Employee</th>
                    <th>Register</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>View</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$transactionRecords): ?>
                    <tr>
                        <td colspan="9" class="transaction-empty-row">No transactions match the selected filters.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($transactionRecords as $transactionRecord): ?>
                    <?php
                    $customerName = trim(
                        (string) ($transactionRecord['CustomerFirstName'] ?? '')
                        . ' '
                        . (string) ($transactionRecord['CustomerLastName'] ?? '')
                    );
                    if ($customerName === '') {
                        $customerName = 'Walk-in';
                    }

                    $typeClass =
                        'transaction-type transaction-type-'
                        . strtolower((string) $transactionRecord['SaleType']);
                    $viewAddress =
                        APPLICATION_URL
                        . '/transactions/tr_view.php?receipt='
                        . (int) $transactionRecord['ReceiptID'];
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <?= transactionNumberHtml($transactionRecord['TransactionNumber'], true) ?>
                            </strong>
                        </td>
                        <td>
                            <?= escapeOutput(
                                date('m/d/Y g:i A', strtotime($transactionRecord['TransactionDateTime']))
                            ) ?>
                        </td>
                        <td>
                            <span class="<?= escapeOutput($typeClass) ?>">
                                <?= escapeOutput($transactionRecord['SaleType']) ?>
                            </span>
                            <?php if (
                                $transactionRecord['SaleType'] === 'Express'
                                && $transactionRecord['FulfillmentMethod']
                            ): ?>
                                <span
                                    class="transaction-substatus"><?= escapeOutput($transactionRecord['FulfillmentMethod']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= escapeOutput($customerName) ?></td>
                        <td><?= escapeOutput($transactionRecord['Username']) ?></td>
                        <td>#<?= escapeOutput($transactionRecord['RegisterNumber']) ?></td>
                        <td>
                            <?= escapeOutput($transactionRecord['ReceiptStatus']) ?>
                            <?php if ($transactionRecord['ExpressStatus']): ?>
                                <span class="transaction-substatus">Express:
                                    <?= escapeOutput($transactionRecord['ExpressStatus']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= escapeOutput(number_format((float) $transactionRecord['TotalAmount'], 2)) ?></td>
                        <td>
                            <a href="<?= escapeOutput($viewAddress) ?>"
                                class="button button-primary transaction-view-button">
                                Open
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>