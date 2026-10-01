<?php // express/orders.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpressAccess();

$databaseConnection = connectDatabase();

$storeID = (int) ($_SESSION['store_id'] ?? 0);
$operatorID = (int) ($_SESSION['operator_id'] ?? 0);
$isAdministrator = operatorIsAdministrator();

if ($isAdministrator) {
    $ordersStatement = $databaseConnection->prepare(
        'SELECT *
         FROM vw_express_orders
         WHERE StoreID = :storeID
           AND DATE(OrderPlacedDateTime) = CURDATE()
         ORDER BY OrderPlacedDateTime DESC'
    );
    $ordersStatement->execute([':storeID' => $storeID]);
} else {
    $ordersStatement = $databaseConnection->prepare(
        'SELECT *
         FROM vw_express_orders
         WHERE StoreID = :storeID
           AND PersonalShopperID = :operatorID
           AND DATE(OrderPlacedDateTime) = CURDATE()
         ORDER BY OrderPlacedDateTime DESC'
    );
    $ordersStatement->execute([
        ':storeID' => $storeID,
        ':operatorID' => $operatorID
    ]);
}

$orders = $ordersStatement->fetchAll();

$pageTitle = "Today's Express Orders";
$currentSection = 'express';
$currentPage = 'express-orders';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">
    <div class="page-intro">
        <h1>Today's Express Orders</h1>
        <p>
            <?= $isAdministrator
                ? 'All Express orders placed today at this store.'
                : 'Express orders you have placed today.' ?>
        </p>
    </div>

    <div class="express-actions">
        <a class="button button-primary" href="<?= APPLICATION_URL ?>/express/new.php">New Express Order</a>
        <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/index.php">Back to Express Home</a>
    </div>

    <?php if (!$orders): ?>

        <p class="express-empty">No Express orders have been placed today yet.</p>

    <?php else: ?>

        <div class="express-table-container">
            <table class="express-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Ordered</th>
                        <th>Method</th>
                        <?php if ($isAdministrator): ?>
                            <th>Personal Shopper</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= escapeOutput($order['TransactionNumber']) ?></td>
                            <td>
                                <?= escapeOutput(
                                    trim(
                                        ($order['CustomerFirstName'] ?? '')
                                        . ' '
                                        . ($order['CustomerLastName'] ?? '')
                                    )
                                ) ?>
                            </td>
                            <td><?= escapeOutput(date('g:i A', strtotime($order['OrderPlacedDateTime']))) ?></td>
                            <td>
                                <?php if ($order['FulfillmentMethod'] === 'Delivery'): ?>
                                    <span class="express-method-delivery">Home Delivery</span>
                                <?php else: ?>
                                    <span class="express-method-curbside">Curbside Pickup</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdministrator): ?>
                                <td><?= escapeOutput($order['PersonalShopperUsername']) ?></td>
                            <?php endif; ?>
                            <td><?= escapeOutput($order['ExpressStatus']) ?></td>
                            <td>
                                <a
                                    class="button button-secondary"
                                    href="<?= APPLICATION_URL ?>/express/order.php?id=<?= (int) $order['ExpressOrderID'] ?>"
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

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>