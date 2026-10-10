<?php // express/ex_home.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpress();

$db = connectDatabase();

$storeID =
    signedInStoreID();

$operatorID =
    signedInOperatorID();

$isSupervisor =
    canSupervise();


$loadErrorMessage = '';
$capacity = [
    'DailyCapacity' => EXPRESS_DAILY_CAPACITY,
    'OrdersUsed' => 0,
    'OrdersRemaining' => 0
];
$orders = [];
$board = ['Received' => [], 'Picking' => [], 'Ready' => []];
$finishedOrders = [];
$boardHints = [
    'Received' => 'The order is taken. Add its items to start picking.',
    'Picking' => 'Items are being picked. Mark the order Ready when done.',
    'Ready' => 'Picked and waiting. Check out to charge the customer.'
];
$weightInStock = 0.0;
$quantityInStock = 0;
$stockRows = [];

try {
    $capacityStatement =
        $db->prepare(
            '
            CALL sp_get_express_capacity(
                :storeID
            )
            '
        );

    $capacityStatement->execute([
        ':storeID' => $storeID
    ]);

    $capacityRecord =
        $capacityStatement->fetch();

    $capacityStatement->closeCursor();

    if ($capacityRecord) {
        $capacity = $capacityRecord;
    }

    if ($isSupervisor) {
        $ordersStatement =
            $db->prepare(
                '
                SELECT
                    eo.*,
                    (
                        SELECT COUNT(*)
                        FROM salesreceiptline sl
                        WHERE sl.ReceiptID = eo.ReceiptID
                    ) AS LineCount
                FROM vw_express_orders eo
                WHERE eo.StoreID = :storeID
                  AND DATE(eo.OrderPlacedDateTime) = CURDATE()
                ORDER BY eo.OrderPlacedDateTime
                '
            );

        $ordersStatement->execute([
            ':storeID' => $storeID
        ]);
    } else {
        $ordersStatement =
            $db->prepare(
                '
                SELECT
                    eo.*,
                    (
                        SELECT COUNT(*)
                        FROM salesreceiptline sl
                        WHERE sl.ReceiptID = eo.ReceiptID
                    ) AS LineCount
                FROM vw_express_orders eo
                WHERE eo.StoreID = :storeID
                  AND eo.PersonalShopperID = :operatorID
                  AND DATE(eo.OrderPlacedDateTime) = CURDATE()
                ORDER BY eo.OrderPlacedDateTime
                '
            );

        $ordersStatement->execute([
            ':storeID' => $storeID,
            ':operatorID' => $operatorID
        ]);
    }

    $orders =
        $ordersStatement->fetchAll();

    // Open orders go on the board by status, the rest are finished
    foreach ($orders as $order) {
        if (
            $order['ReceiptStatus'] === 'Open'
            && isset($board[$order['ExpressStatus']])
        ) {
            $board[$order['ExpressStatus']][] = $order;
        } else {
            $finishedOrders[] = $order;
        }
    }


    $stockStatement =
        $db->prepare(
            '
            SELECT
                DepartmentID,
                DepartmentName,
                ProductID,
                UPC,
                PLUCode,
                ProductName,
                UnitType,
                RetailPrice,
                StockQuantity,
                Aisle,
                SectionName,
                ShelfLocation
            FROM vw_store_stock
            WHERE StoreID = :storeID
            ORDER BY
                DepartmentName,
                ProductName
            '
        );

    $stockStatement->execute([
        ':storeID' => $storeID
    ]);

    $stockRows =
        $stockStatement->fetchAll();

    $weightInStock = 0.0;
    $quantityInStock = 0;

    foreach ($stockRows as $stockRow) {
        if ($stockRow['UnitType'] === 'Pound') {
            $weightInStock += (float) $stockRow['StockQuantity'];
        } else {
            $quantityInStock += (int) floor((float) $stockRow['StockQuantity']);
        }
    }

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadErrorMessage =
        'Express information could not be loaded completely. Please try again.';
}


$pageTitle =
    'Express Orders';

$currentSection =
    'express';

$currentPage =
    'express';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">

    <div class="page-intro">

        <h1>
            Express Orders
        </h1>

        <p>
            Record, pick, prepare, and review today&apos;s Express grocery orders.
        </p>

    </div>


    <?php if ($loadErrorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($loadErrorMessage) ?>
        </div>

    <?php endif; ?>


    <div class="express-capacity">

        <strong>
            <?= escapeOutput($capacity['OrdersRemaining'] ?? 0) ?>
        </strong>

        <span>
            of
            <?= escapeOutput($capacity['DailyCapacity'] ?? 20) ?>
            Express orders still available today
        </span>

    </div>


    <div class="express-actions">

        <?php if ((int) ($capacity['OrdersRemaining'] ?? 0) > 0): ?>

            <a class="button button-primary" href="<?= APPLICATION_URL ?>/express/ex_new.php">
                Take New Order
            </a>

        <?php else: ?>

            <span class="button button-disabled" aria-disabled="true">
                Daily Capacity Full
            </span>

        <?php endif; ?>


        <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/ex_orders.php">
            View Today&apos;s Orders
        </a>

    </div>


    <h2>
        Order Board
    </h2>

    <p class="express-steps">
        1. Take the order. 2. Add the items (the order moves to Picking).
        3. Mark it Ready. 4. Check out (the customer is charged).
    </p>

    <div class="express-board">

        <?php foreach ($board as $statusName => $statusOrders): ?>

            <section class="express-board-column">

                <h3>
                    <?= escapeOutput($statusName) ?>
                    <span class="express-board-count">
                        <?= count($statusOrders) ?>
                    </span>
                </h3>

                <p class="express-board-hint">
                    <?= escapeOutput($boardHints[$statusName]) ?>
                </p>

                <?php if (!$statusOrders): ?>

                    <p class="express-empty">
                        No orders.
                    </p>

                <?php endif; ?>

                <?php foreach ($statusOrders as $order): ?>

                    <?php
                    $belongsToCurrentShopper =
                        (int) $order['PersonalShopperID'] === $operatorID;

                    // A supervisor opening someone else's order chooses View, Assist, or Take Over first
                    $orderURL =
                        $isSupervisor && !$belongsToCurrentShopper
                        ? APPLICATION_URL
                        . '/transactions/tr_view.php?receipt='
                        . (int) $order['ReceiptID']
                        : APPLICATION_URL
                        . '/express/ex_order.php?id='
                        . (int) $order['ExpressOrderID'];

                    $customerName = trim(
                        ($order['CustomerFirstName'] ?? '')
                        . ' '
                        . ($order['CustomerLastName'] ?? '')
                    );
                    ?>

                    <article class="express-card">

                        <?= transactionNumberHtml($order['TransactionNumber'], true) ?>

                        <strong>
                            <?= escapeOutput($customerName) ?>
                        </strong>

                        <span>
                            <?= escapeOutput($order['FulfillmentMethod']) ?>
                            |
                            <?= (int) $order['LineCount'] ?>
                            <?= (int) $order['LineCount'] === 1 ? 'product' : 'products' ?>
                            |
                            $<?= escapeOutput(number_format((float) $order['SubtotalAmount'], 2)) ?>
                        </span>

                        <?php if ($isSupervisor): ?>

                            <span>
                                Shopper:
                                <?= escapeOutput($order['PersonalShopperUsername']) ?>
                            </span>

                        <?php endif; ?>

                        <a class="button button-secondary" href="<?= $orderURL ?>">
                            Open
                        </a>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endforeach; ?>

    </div>

    <h2>
        Finished Today
    </h2>

    <?php if (!$finishedOrders): ?>

        <p class="express-empty">
            No Express orders have been completed or cancelled today.
        </p>

    <?php else: ?>

        <div class="express-table-container">

            <table class="express-table">

                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>View</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($finishedOrders as $order): ?>

                        <?php
                        $orderURL =
                            APPLICATION_URL
                            . '/express/ex_order.php?id='
                            . (int) $order['ExpressOrderID'];
                        ?>

                        <tr>

                            <td>
                                <?= transactionNumberHtml($order['TransactionNumber'], true) ?>
                            </td>

                            <td>
                                <?= escapeOutput(
                                    trim(
                                        ($order['CustomerFirstName'] ?? '')
                                        . ' '
                                        . ($order['CustomerLastName'] ?? '')
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= escapeOutput($order['FulfillmentMethod']) ?>
                            </td>

                            <td>
                                <?= escapeOutput($order['ExpressStatus']) ?>
                            </td>

                            <td>
                                <?= $order['ExpressStatus'] === 'Cancelled'
                                    ? 'Cancelled'
                                    : '$' . escapeOutput(number_format((float) $order['TotalAmount'], 2)) ?>
                            </td>

                            <td>
                                <a class="button button-secondary" href="<?= $orderURL ?>">
                                    View
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

    <h2>
        Store Stock
    </h2>


    <section class="express-stock-total">

        <div>
            <span class="express-stock-total-label">PLU Weight in Stock</span>
            <strong class="express-stock-total-value">
                <?= escapeOutput(number_format($weightInStock, 3)) ?> lb
            </strong>
        </div>

        <div>
            <span class="express-stock-total-label">Quantity in Stock</span>
            <strong class="express-stock-total-value">
                <?= escapeOutput(number_format($quantityInStock, 0)) ?> each
            </strong>
        </div>

    </section>


    <div class="express-table-container">

        <table class="express-table express-stock-table">

            <thead>

                <tr>
                    <th>
                        Department
                    </th>

                    <th>
                        Product
                    </th>

                    <th>
                        Product Code
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Units Available
                    </th>

                    <th>
                        Aisle / Section
                    </th>

                    <th>
                        Shelf
                    </th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($stockRows as $stock): ?>

                    <?php
                    $productCode =
                        $stock['UPC']
                        ?? $stock['PLUCode']
                        ?? '';

                    $stockQuantity =
                        (float) $stock['StockQuantity'];

                    $sectionParts = [];

                    if (trim((string) $stock['Aisle']) !== '') {
                        $sectionParts[] = 'Aisle ' . $stock['Aisle'];
                    }

                    if (trim((string) $stock['SectionName']) !== '') {
                        $sectionParts[] = $stock['SectionName'];
                    }

                    $sectionText = implode(', ', $sectionParts);
                    $shelfText = trim((string) $stock['ShelfLocation']);
                    ?>

                    <tr class="<?=
                        $stockQuantity <= 0
                        ? 'express-stock-out-of-stock'
                        : ''
                        ?>">

                        <td>
                            <?= escapeOutput($stock['DepartmentName']) ?>
                        </td>

                        <td>
                            <?= escapeOutput($stock['ProductName']) ?>
                        </td>

                        <td>
                            <?= escapeOutput($productCode) ?>
                        </td>

                        <td>
                            $<?= escapeOutput(
                                number_format(
                                    (float) $stock['RetailPrice'],
                                    2
                                )
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= escapeOutput(
                                    $stock['UnitType'] === 'Each'
                                    ? number_format(
                                        $stockQuantity,
                                        0
                                    )
                                    : number_format(
                                        $stockQuantity,
                                        3
                                    )
                                ) ?>     <?= $stock['UnitType'] === 'Each' ? 'each' : 'lb' ?>
                            </strong>

                            <?php if ($stockQuantity <= 0): ?>
                                <span class="express-out-of-stock-label">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= escapeOutput($sectionText) ?>
                        </td>

                        <td>
                            <?= escapeOutput($shelfText) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php
require __DIR__ . '/../includes/footer.php';
?>