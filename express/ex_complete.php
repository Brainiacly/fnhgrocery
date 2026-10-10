<?php // express/ex_complete.php

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
$expressOrderID =
    (int) (
        $_GET['id']
        ?? 0
    );

$errorMessage = '';

$order = null;
$saleItems = [];

try {
    $orderStatement =
        $db->prepare(
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

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage =
        'The completed Express receipt could not be loaded. Please try again.';
}

if ($errorMessage === '') {
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
        !canSupervise()
    ) {
        showAccessDeniedPage(
            "You cannot view another Personal Shopper's Express receipt."
        );
    }
}

if ($errorMessage === '') {
    try {
        $itemStatement =
            $db->prepare(
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

    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $errorMessage =
            'The completed Express receipt items could not be loaded. Please try again.';
    }
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
                <?= transactionNumberHtml($order['TransactionNumber']) ?>
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

            <?php if (($order['PaymentMethod'] ?? '') === 'Charge'): ?>
                <div>
                    <span>Payment</span>
                    <strong>Charge, paid in advance</strong>
                </div>
            <?php else: ?>
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
            <?php endif; ?>

        </div>


        <div class="express-actions">

            <a href="<?= APPLICATION_URL ?>/express/ex_home.php" class="button button-primary">
                Express Home
            </a>

            <a href="<?= APPLICATION_URL ?>/express/ex_orders.php" class="button button-secondary">
                Today&apos;s Orders
            </a>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>