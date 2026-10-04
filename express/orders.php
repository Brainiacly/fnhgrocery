<?php // express/orders.php

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

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadErrorMessage =
        "Today's Express orders could not be loaded. Please try again.";
}


$successMessage =
    isset($_GET['cancelled'])
        ? 'The Express order was cancelled and any picked inventory was restored.'
        : '';


$pageTitle =
    "Today's Express Orders";

$currentSection =
    'express';

$currentPage =
    'express-orders';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">

    <div class="page-intro">

        <h1>
            Today's Express Orders
        </h1>

        <p>
            <?= $isAdministrator
                ? 'All Express orders placed today at this store.'
                : 'Express orders you have placed today.' ?>
        </p>

    </div>


    <?php if ($loadErrorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($loadErrorMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($successMessage !== ''): ?>

        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>


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
            href="<?= APPLICATION_URL ?>/express/ex_home.php"
        >
            Back to Express Home
        </a>

    </div>


    <?php if (!$orders): ?>

        <p class="express-empty">
            No Express orders have been placed today yet.
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
                            Ordered
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
                            Action
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
                                <?= escapeOutput(
                                    date(
                                        'g:i A',
                                        strtotime(
                                            $order['OrderPlacedDateTime']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?php if ($order['FulfillmentMethod'] === 'Delivery'): ?>

                                    <span class="express-method-delivery">
                                        Home Delivery
                                    </span>

                                <?php else: ?>

                                    <span class="express-method-curbside">
                                        Curbside Pickup
                                    </span>

                                <?php endif; ?>
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

</section>

<?php
require __DIR__ . '/../includes/footer.php';
?>