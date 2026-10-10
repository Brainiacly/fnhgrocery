<?php // express/ex_checkout.php

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
        ?? $_POST['express_order_id']
        ?? 0
    );

$errorMessage = '';

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

    if (!$order) {
        header(
            'Location: '
            . APPLICATION_URL
            . '/express/ex_orders.php'
        );

        exit;
    }

    if (
        (int) $order['PersonalShopperID'] !== $operatorID
    ) {
        showAccessDeniedPage(
            "Take this order over from its transaction details before checking it out."
        );
    }

    if (
        $order['ReceiptStatus'] === 'Open'
        && $order['ExpressStatus'] !== 'Ready'
    ) {
        header(
            'Location: '
            . APPLICATION_URL
            . '/express/ex_order.php?id='
            . $expressOrderID
            . '&notready=1'
        );
        exit;
    }
    if ($order['ReceiptStatus'] !== 'Open') {
        header(
            'Location: '
            . APPLICATION_URL
            . '/express/ex_complete.php?id='
            . $expressOrderID
        );

        exit;
    }

    $itemStatement =
        $db->prepare(
            '
            SELECT
                ProductName,
                UnitType,
                Taxable,
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
    http_response_code(500);
    exit('The Express checkout information could not be loaded.');
}


if (!$saleItems) {
    header(
        'Location: '
        . APPLICATION_URL
        . '/express/ex_order.php?id='
        . $expressOrderID
    );

    exit;
}


$subtotal = 0.00;
$grossTaxableSubtotal = 0.00;

foreach ($saleItems as $saleItem) {

    $lineTotal =
        (float) $saleItem['LineTotal'];

    $subtotal +=
        $lineTotal;

    if ((int) $saleItem['Taxable'] === 1) {

        $grossTaxableSubtotal +=
            $lineTotal;
    }
}


$receiptDiscount =
    (float) $order['ReceiptDiscountAmount'];

$netSubtotal =
    round(
        max(
            $subtotal - $receiptDiscount,
            0.00
        ),
        2
    );

$taxableRatio =
    $subtotal > 0
    ? $grossTaxableSubtotal / $subtotal
    : 0.00;

$taxableDiscount =
    round(
        $receiptDiscount
        * $taxableRatio,
        2
    );

$taxableSubtotal =
    round(
        max(
            $grossTaxableSubtotal
            - $taxableDiscount,
            0.00
        ),
        2
    );

$taxableSubtotal =
    min(
        $taxableSubtotal,
        $netSubtotal
    );

$taxAmount =
    round(
        $taxableSubtotal
        * SALES_TAX_RATE,
        2
    );

$deliveryFee =
    (float) $order['DeliveryFee'];

$totalAmount =
    round(
        $netSubtotal
        + $taxAmount
        + $deliveryFee,
        2
    );


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['complete_order'])
) {

    $submittedToken =
        $_POST['form_security_token']
        ?? '';

    if (!tokenIsValid($submittedToken)) {

        $errorMessage =
            'The form expired. Please try again.';

    } else {

        // Express orders are paid in advance by charge, so no cash is entered
        $amountTendered = $totalAmount;
        if ($amountTendered < 0) {
            $errorMessage =
                'The total due is not valid.';

        } else {

            try {

                $checkoutStatement =
                    $db->prepare(
                        '
                        CALL sp_checkout_sale(
                            :receiptID,
                            :amountTendered,
                            :operatorID,
                            :paymentMethod
                        )
                        '
                    );

                $checkoutStatement->execute([
                    ':receiptID' =>
                        (int) $order['ReceiptID'],
                    ':amountTendered' =>
                        $amountTendered,
                    ':operatorID' =>
                        $operatorID,
                    ':paymentMethod' =>
                        'Charge'
                ]);

                $checkoutStatement->fetch();

                $checkoutStatement->closeCursor();


                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/express/ex_complete.php?id='
                    . $expressOrderID
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The Express checkout could not be completed.'
                    );
            }
        }
    }
}


$pageTitle =
    'Express Checkout';

$currentSection =
    'express';

$currentPage =
    'express-checkout';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">

    <div class="page-intro">

        <h1>
            Express Checkout
        </h1>

        <p>
            <?= transactionNumberHtml($order['TransactionNumber']) ?>
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


    <div class="checkout-layout">

        <div class="checkout-items">

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


        </div>

        <div class="checkout-payment">

            <div class="express-checkout-totals">

                <div>
                    <span>Merchandise Subtotal</span>
                    <strong>$<?= escapeOutput(number_format($subtotal, 2)) ?></strong>
                </div>

                <div>
                    <span>Sales Tax</span>
                    <strong>$<?= escapeOutput(number_format($taxAmount, 2)) ?></strong>
                </div>

                <div>
                    <span>Delivery Fee</span>
                    <strong>$<?= escapeOutput(number_format($deliveryFee, 2)) ?></strong>
                </div>

                <div class="express-checkout-grand-total">
                    <span>Total Due</span>
                    <strong>$<?= escapeOutput(number_format($totalAmount, 2)) ?></strong>
                </div>

            </div>


            <form method="post" class="express-checkout-form">

                <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

                <input type="hidden" name="express_order_id" value="<?= $expressOrderID ?>">


                <div class="express-charge-note">
                    <strong>
                        Payment: Charge
                    </strong>
                    <span>
                        The customer pays in advance. The total is charged to the customer's card and recorded
                        as a Charge. No cash is taken and no change is given.
                    </span>
                </div>


                <div class="express-actions">

                    <button type="submit" name="complete_order" value="1" class="button button-primary"> Charge Order
                    </button>

                    <a href="<?= APPLICATION_URL ?>/express/ex_order.php?id=<?= $expressOrderID ?>"
                        class="button button-secondary">
                        Back to Order
                    </a>

                </div>

            </form>


        </div>

    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>