<?php // express/ex_home.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpressAccess();

$databaseConnection = connectDatabase();

$storeID =
    (int) (
        $_SESSION['store_id']
        ?? 0
    );

$operatorID =
    (int) (
        $_SESSION['operator_id']
        ?? 0
    );

$isAdministrator =
    operatorIsAdministrator();


$loadErrorMessage = '';
$capacity = [
    'DailyCapacity' => 20,
    'OrdersUsed' => 0,
    'OrdersRemaining' => 0
];
$orders = [];
$totalStockQuantity = 0.00;
$stockRows = [];

try {
    $capacityStatement =
        $databaseConnection->prepare(
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

    if ($isAdministrator) {
        $ordersStatement =
            $databaseConnection->prepare(
                '
                SELECT *
                FROM vw_express_orders
                WHERE StoreID = :storeID
                  AND DATE(OrderPlacedDateTime) = CURDATE()
                ORDER BY OrderPlacedDateTime DESC
                '
            );

        $ordersStatement->execute([
            ':storeID' => $storeID
        ]);
    } else {
        $ordersStatement =
            $databaseConnection->prepare(
                '
                SELECT *
                FROM vw_express_orders
                WHERE StoreID = :storeID
                  AND PersonalShopperID = :operatorID
                  AND DATE(OrderPlacedDateTime) = CURDATE()
                ORDER BY OrderPlacedDateTime DESC
                '
            );

        $ordersStatement->execute([
            ':storeID' => $storeID,
            ':operatorID' => $operatorID
        ]);
    }

    $orders =
        $ordersStatement->fetchAll();

    $totalStockStatement =
        $databaseConnection->prepare(
            '
            SELECT
                TotalStockQuantity
            FROM vw_store_stock_total
            WHERE StoreID = :storeID
            LIMIT 1
            '
        );

    $totalStockStatement->execute([
        ':storeID' => $storeID
    ]);

    $totalStockRecord =
        $totalStockStatement->fetch();

    $totalStockQuantity =
        $totalStockRecord
            ? (float) $totalStockRecord['TotalStockQuantity']
            : 0.00;

    $stockStatement =
        $databaseConnection->prepare(
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

            <a
                class="button button-primary"
                href="<?= APPLICATION_URL ?>/express/ex_new.php"
            >
                New Express Order
            </a>

        <?php else: ?>

            <span
                class="button button-disabled"
                aria-disabled="true"
            >
                Daily Capacity Full
            </span>

        <?php endif; ?>


        <a
            class="button button-secondary"
            href="<?= APPLICATION_URL ?>/express/orders.php"
        >
            View Today&apos;s Orders
        </a>

    </div>


    <h2>
        Today&apos;s Express Orders
    </h2>


    <?php if (!$orders): ?>

        <p class="express-empty">
            No Express orders have been recorded today.
        </p>

    <?php else: ?>

        <div class="express-table-container">

            <table class="express-table">

                <thead>

                    <tr>
                        <th>
                            Order
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Method
                        </th>

                        <?php if ($isAdministrator): ?>
                            <th>
                                Personal Shopper
                            </th>
                        <?php endif; ?>

                        <th>
                            Status
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Open
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($orders as $order): ?>

                        <?php
                        $orderURL =
                            APPLICATION_URL
                            . '/express/order.php?id='
                            . (int) $order['ExpressOrderID'];
                        ?>

                        <tr>

                            <td>
                                <?= escapeOutput($order['TransactionNumber']) ?>
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

                            <?php if ($isAdministrator): ?>
                                <td>
                                    <?= escapeOutput($order['PersonalShopperUsername']) ?>
                                </td>
                            <?php endif; ?>

                            <td>
                                <?= escapeOutput($order['ExpressStatus']) ?>
                            </td>

                            <td>
                                <?php if ($order['ExpressStatus'] === 'Cancelled'): ?>
                                    Cancelled
                                <?php elseif ($order['ReceiptStatus'] === 'Open'): ?>
                                    Pending
                                <?php else: ?>
                                    $<?= escapeOutput(
                                        number_format(
                                            (float) $order['TotalAmount'],
                                            2
                                        )
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    class="button button-secondary"
                                    href="<?= escapeOutput($orderURL) ?>"
                                >
                                    Open
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

        <span class="express-stock-total-label">
            Total Units Currently in Stock
        </span>

        <strong class="express-stock-total-value">
            <?= escapeOutput(
                number_format(
                    $totalStockQuantity,
                    3
                )
            ) ?>
        </strong>

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
                        Location
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

                    $locationParts = [];

                    if (
                        trim(
                            (string) $stock['Aisle']
                        ) !== ''
                    ) {
                        $locationParts[] =
                            'Aisle '
                            . $stock['Aisle'];
                    }

                    if (
                        trim(
                            (string) $stock['SectionName']
                        ) !== ''
                    ) {
                        $locationParts[] =
                            $stock['SectionName'];
                    }

                    if (
                        trim(
                            (string) $stock['ShelfLocation']
                        ) !== ''
                    ) {
                        $locationParts[] =
                            $stock['ShelfLocation'];
                    }
                    ?>

                    <tr
                        class="<?=
                        $stockQuantity <= 0
                        ? 'express-stock-out-of-stock'
                        : ''
                        ?>"
                    >

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
                                ) ?>
                            </strong>

                            <?php if ($stockQuantity <= 0): ?>
                                <span class="express-out-of-stock-label">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= escapeOutput(
                                implode(
                                    ' | ',
                                    $locationParts
                                )
                            ) ?>
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