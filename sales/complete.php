<?php // sales/complete.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAssignedAccess();

$storeID =
    (int)($_SESSION['store_id'] ?? 0);

$operatorID =
    (int)($_SESSION['operator_id'] ?? 0);

$receiptID =
    (int)(
        $_GET['receipt']
        ??
        0
    );

$saleRecord = null;

$saleItems = [];

$errorMessage = '';


try {

    $databaseConnection =
        connectDatabase();


    $saleStatement =
        $databaseConnection->prepare(
            '
            SELECT
                ReceiptID,
                TransactionNumber,
                TransactionDateTime,
                CheckoutDateTime,
                Status,
                SubtotalAmount,
                ReceiptDiscountAmount,
                TaxableSubtotalAmount,
                TaxAmount,
                TotalAmount,
                PaymentMethod,
                AmountTendered,
                ChangeDue
            FROM salesreceipt
            WHERE ReceiptID = :receiptID
              AND StoreID = :storeID
              AND OperatorID = :operatorID
              AND Status = \'Paid\'
            LIMIT 1
            '
        );


    $saleStatement->execute([
        ':receiptID' =>
            $receiptID,

        ':storeID' =>
            $storeID,

        ':operatorID' =>
            $operatorID
    ]);


    $saleRecord =
        $saleStatement->fetch();


    if (!$saleRecord) {

        $errorMessage =
            'The receipt could not be found.';

    } else {

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
                $receiptID
        ]);


        $saleItems =
            $itemStatement->fetchAll();
    }

} catch (PDOException $exception) {

    error_log(
        $exception->getMessage()
    );

    $errorMessage =
        'The receipt could not be loaded.';
}


$pageTitle =
    'Sale Complete';

$currentSection =
    'sales';

$currentPage =
    'complete';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel sale-complete-panel">

    <div class="page-intro">

        <h1>
            Sale Complete
        </h1>

        <p>
            The transaction has been recorded.
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php else: ?>

        <div class="message message-success">
            Payment accepted. The receipt has been marked paid.
        </div>


        <section class="sale-complete-summary">

            <div>

                <span>
                    Transaction
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['TransactionNumber']
                    ) ?>
                </strong>

            </div>


            <div>

                <span>
                    Checkout Time
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['CheckoutDateTime']
                    ) ?>
                </strong>

            </div>


            <div>

                <span>
                    Status
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['Status']
                    ) ?>
                </strong>

            </div>

        </section>


        <div class="sale-receipt-table-container">

            <table class="sale-receipt-table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($saleItems as $saleItem): ?>

                        <tr>

                            <td>
                                <?= escapeOutput(
                                    $saleItem['ProductName']
                                ) ?>
                            </td>

                            <td>
                                <?= escapeOutput(
                                    $saleItem['UnitType'] === 'Each'
                                        ? number_format(
                                            (float)$saleItem['Quantity'],
                                            0
                                        )
                                        : number_format(
                                            (float)$saleItem['Quantity'],
                                            3
                                        )
                                ) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float)$saleItem['UnitPrice'],
                                        2
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= (int)$saleItem['Taxable'] === 1
                                    ? 'Taxable'
                                    : 'No Tax'
                                ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float)$saleItem['LineTotal'],
                                        2
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <section class="checkout-totals">

            <div class="checkout-total-row">

                <span>
                    Subtotal
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['SubtotalAmount'],
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <?php if ((float)$saleRecord['ReceiptDiscountAmount'] > 0): ?>

                <div class="checkout-total-row">

                    <span>
                        Receipt Discount
                    </span>

                    <strong>
                        -$<?= escapeOutput(
                            number_format(
                                (float)$saleRecord['ReceiptDiscountAmount'],
                                2
                            )
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="checkout-total-row">

                <span>
                    Taxable Subtotal
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['TaxableSubtotalAmount'],
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row">

                <span>
                    Sales Tax
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['TaxAmount'],
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row checkout-grand-total">

                <span>
                    Total
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['TotalAmount'],
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row">

                <span>
                    Payment Method
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['PaymentMethod']
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row">

                <span>
                    Cash Tendered
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['AmountTendered'],
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row checkout-change">

                <span>
                    Change Due
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            (float)$saleRecord['ChangeDue'],
                            2
                        )
                    ) ?>
                </strong>

            </div>

        </section>


        <div class="page-main-actions">

            <a
                href="<?= APPLICATION_URL ?>/sales/new.php"
                class="button button-primary"
            >
                Start New Sale
            </a>

            <a
                href="<?= APPLICATION_URL ?>/index.php"
                class="button button-secondary"
            >
                Main Menu
            </a>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>