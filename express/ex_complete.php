<?php // express/ex_complete.php

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
$expressOrderID =
    (int) (
        $_GET['id']
        ?? 0
    );

$errorMessage = '';

$orderStatement =
    $databaseConnection->prepare(
        '
        SELECT *
        FROM vw_express_orders
        WHERE ExpressOrderID = :expressOrderID
          AND StoreID = :storeID
        LIMIT 1
        '
    );

$orderStatement->execute([
    ':expressOrderID' =>
        $expressOrderID,

    ':storeID' =>
        $storeID
]);

$order =
    $orderStatement->fetch();


if (
    !$order
    ||
    $order['ReceiptStatus'] !== 'Paid'
) {

    $errorMessage =
        'The completed Express receipt could not be found.';

} elseif (
    (int) $order['PersonalShopperID'] !== $operatorID
    &&
    !operatorIsAdministrator()
) {

    http_response_code(403);

    exit(
        'You cannot view another personal shopper\'s Express receipt'
    );
}


$saleItems = [];

if ($errorMessage === '') {

    $itemStatement =
        $databaseConnection->prepare(
            '
            SELECT
                ProductName,
                UnitType,
                Quantity,
                UnitPrice,
                LineTotal
            FROM vw_sale_detail
            WHERE ReceiptID = :receiptID
            ORDER BY LineNumber
            '
        );

    $itemStatement->execute([
        ':receiptID' =>
            (int) $order['ReceiptID']
    ]);

    $saleItems =
        $itemStatement->fetchAll();
}


$pageTitle =
    'Express Sale Complete';

$currentSection =
    'express';

$currentPage =
    'express-complete';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">

    <div class="page-intro">

        <h1>
            Express Sale Complete
        </h1>

        <p>
            The Express transaction has been recorded.
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php else: ?>

        <div class="message message-success">
            Payment accepted. The Express order is complete.
        </div>


        <div class="express-order-summary">

            <div>
                <strong>Transaction:</strong>
                <?= escapeOutput($order['TransactionNumber']) ?>
            </div>

            <div>
                <strong>Customer:</strong>
                <?= escapeOutput(
                    trim(
                        ($order['CustomerFirstName'] ?? '')
                        . ' '
                        . ($order['CustomerLastName'] ?? '')
                    )
                ) ?>
            </div>

            <div>
                <strong>Fulfillment:</strong>
                <?= escapeOutput($order['FulfillmentMethod']) ?>
            </div>

            <div>
                <strong>Order Status:</strong>
                <?= escapeOutput($order['ExpressStatus']) ?>
            </div>


            <?php if ($order['FulfillmentMethod'] === 'Delivery'): ?>

                <div class="express-address">

                    <strong>
                        Delivery Address:
                    </strong>

                    <?= escapeOutput($order['DeliveryAddressLine1']) ?>

                    <?php if (!empty($order['DeliveryAddressLine2'])): ?>
                        ,
                        <?= escapeOutput($order['DeliveryAddressLine2']) ?>
                    <?php endif; ?>

                    ,
                    <?= escapeOutput($order['DeliveryCity']) ?>
                    ,
                    <?= escapeOutput($order['DeliveryStateCode']) ?>
                    <?= escapeOutput($order['DeliveryPostalCode']) ?>

                </div>

            <?php endif; ?>

        </div>


        <div class="express-table-container">

            <table class="express-table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($saleItems as $saleItem): ?>

                        <tr>

                            <td>
                                <?= escapeOutput($saleItem['ProductName']) ?>
                            </td>

                            <td>
                                <?= escapeOutput($saleItem['Quantity']) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $saleItem['UnitPrice'],
                                        2
                                    )
                                ) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $saleItem['LineTotal'],
                                        2
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <div class="express-checkout-totals">

            <div>
                <span>Merchandise Subtotal</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['SubtotalAmount'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

            <div>
                <span>Sales Tax</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['TaxAmount'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

            <div>
                <span>Delivery Fee</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['DeliveryFee'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

            <div class="express-checkout-grand-total">
                <span>Total</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['TotalAmount'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

            <div>
                <span>Cash Tendered</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['AmountTendered'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

            <div>
                <span>Change Due</span>
                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float) $order['ChangeDue'],
                            2
                        )
                    ) ?>
                </strong>
            </div>

        </div>


        <div class="express-actions">

            <a
                href="<?= APPLICATION_URL ?>/express/ex_home.php"
                class="button button-primary"
            >
                Express Home
            </a>

            <a
                href="<?= APPLICATION_URL ?>/express/orders.php"
                class="button button-secondary"
            >
                Today&apos;s Orders
            </a>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>


========================================
FILE PATH: C:\xampp\htdocs\CSC_680\express\ex_home.php
FILENAME: ex_home.php
SIZE_BYTES: 4985
EXTENSION: .php
----------------------------------------
<?php // express/ex_home.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpressAccess();

$databaseConnection = connectDatabase();

$capacityStatement = $databaseConnection->prepare('CALL sp_get_express_capacity(?)');
$capacityStatement->execute([(int) $_SESSION['store_id']]);
$capacity = $capacityStatement->fetch();
$capacityStatement->closeCursor();

$ordersStatement = $databaseConnection->prepare(
    'SELECT *
     FROM vw_express_orders
     WHERE StoreID = ?
       AND DATE(OrderPlacedDateTime) = CURDATE()
     ORDER BY OrderPlacedDateTime DESC'
);
$ordersStatement->execute([(int) $_SESSION['store_id']]);
$orders = $ordersStatement->fetchAll();

$stockStatement = $databaseConnection->prepare(
    'SELECT *
     FROM vw_store_stock
     WHERE StoreID = ?
     ORDER BY DepartmentName, ProductName'
);
$stockStatement->execute([(int) $_SESSION['store_id']]);
$stockRows = $stockStatement->fetchAll();

$pageTitle = 'Express Orders';
$currentSection = 'express';
$currentPage = 'express';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">
    <div class="page-intro">
        <h1>Express Orders</h1>
        <p>Record, pick, prepare, and review today&apos;s Express grocery orders.</p>
    </div>

    <div class="express-capacity">
        <strong><?= escapeOutput($capacity['OrdersRemaining'] ?? 0) ?></strong>
        <span>of <?= escapeOutput($capacity['DailyCapacity'] ?? 20) ?> Express orders still available today</span>
    </div>

    <div class="express-actions">
        <?php if ((int) ($capacity['OrdersRemaining'] ?? 0) > 0): ?>
            <a class="button button-primary" href="<?= APPLICATION_URL ?>/express/ex_new.php">New Express Order</a>
        <?php else: ?>
            <span class="button button-disabled" aria-disabled="true">Daily Capacity Full</span>
        <?php endif; ?>

        <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/orders.php">View Today&apos;s Orders</a>
    </div>

    <h2>Today&apos;s Express Orders</h2>

    <?php if (!$orders): ?>
        <p class="express-empty">No Express orders have been recorded today.</p>
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
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= escapeOutput($order['TransactionNumber']) ?></td>
                            <td>
                                <?= escapeOutput(trim(($order['CustomerFirstName'] ?? '') . ' ' . ($order['CustomerLastName'] ?? ''))) ?>
                            </td>
                            <td><?= escapeOutput($order['FulfillmentMethod']) ?></td>
                            <td><?= escapeOutput($order['ExpressStatus']) ?></td>
                            <td>$<?= number_format((float) $order['TotalAmount'], 2) ?></td>
                            <td>
                                <a class="button button-secondary"
                                   href="<?= APPLICATION_URL ?>/express/order.php?id=<?= (int) $order['ExpressOrderID'] ?>">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Store Stock</h2>

    <div class="express-table-container">
        <table class="express-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockRows as $stock): ?>
                    <tr>
                        <td><?= escapeOutput($stock['DepartmentName']) ?></td>
                        <td><?= escapeOutput($stock['ProductName']) ?></td>
                        <td><?= escapeOutput($stock['UnitType']) ?></td>
                        <td>$<?= number_format((float) $stock['RetailPrice'], 2) ?></td>
                        <td><?= escapeOutput($stock['StockQuantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>