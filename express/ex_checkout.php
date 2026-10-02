<?php // express/ex_checkout.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpressAccess();

$databaseConnection = connectDatabase();

$storeID = (int) ($_SESSION['store_id'] ?? 0);
$operatorID = (int) ($_SESSION['operator_id'] ?? 0);
$expressOrderID = (int) ($_GET['id'] ?? $_POST['express_order_id'] ?? 0);

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


if (!$order) {

    header(
        'Location: '
        . APPLICATION_URL
        . '/express/orders.php'
    );

    exit;
}


if (
    (int) $order['PersonalShopperID'] !== $operatorID
    &&
    !operatorIsAdministrator()
) {

    http_response_code(403);

    exit(
        'You cannot check out another personal shopper\'s Express order'
    );
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
    $databaseConnection->prepare(
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


if (!$saleItems) {

    header(
        'Location: '
        . APPLICATION_URL
        . '/express/order.php?id='
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
        * 0.0775,
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

    $submittedSecurityToken =
        $_POST['form_security_token']
        ?? '';

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {

        $errorMessage =
            'The form expired. Please try again.';

    } else {

        $amountTendered =
            filter_var(
                $_POST['amount_tendered']
                ?? null,
                FILTER_VALIDATE_FLOAT
            );

        if (
            $amountTendered === false
            ||
            $amountTendered < 0
        ) {

            $errorMessage =
                'Enter a valid cash amount.';

        } else {

            try {

                $checkoutStatement =
                    $databaseConnection->prepare(
                        '
                        CALL sp_checkout_sale(
                            :receiptID,
                            :amountTendered,
                            :operatorID
                        )
                        '
                    );

                $checkoutStatement->execute([
                    ':receiptID' =>
                        (int) $order['ReceiptID'],

                    ':amountTendered' =>
                        $amountTendered,

                    ':operatorID' =>
                        $operatorID
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
                    getSafeDatabaseErrorMessage(
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
            <?= escapeOutput($order['TransactionNumber']) ?>
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


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


    <form
        method="post"
        class="express-checkout-form"
    >

        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <input
            type="hidden"
            name="express_order_id"
            value="<?= $expressOrderID ?>"
        >


        <div class="form-field">

            <label for="amount_tendered">
                Cash Tendered
            </label>

            <input
                type="number"
                id="amount_tendered"
                name="amount_tendered"
                min="<?= escapeOutput(number_format($totalAmount, 2, '.', '')) ?>"
                step="0.01"
                required
            >

        </div>


        <div class="express-actions">

            <button
                type="submit"
                name="complete_order"
                value="1"
                class="button button-primary"
            >
                Complete Express Sale
            </button>

            <a
                href="<?= APPLICATION_URL ?>/express/order.php?id=<?= $expressOrderID ?>"
                class="button button-secondary"
            >
                Back to Order
            </a>

        </div>

    </form>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>