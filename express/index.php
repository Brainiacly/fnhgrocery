<?php // express/index.php

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
            <a class="button button-primary" href="<?= APPLICATION_URL ?>/express/new.php">New Express Order</a>
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
