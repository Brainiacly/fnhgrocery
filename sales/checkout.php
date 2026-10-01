<?php // sales/checkout.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireRegularPOSAccess();

$storeID =
    (int) ($_SESSION['store_id'] ?? 0);

$operatorID =
    (int) ($_SESSION['operator_id'] ?? 0);

$receiptID =
    isset($_GET['receipt'])
    ? (int) $_GET['receipt']
    : (int) ($_POST['receipt_id'] ?? 0);

$errorMessage = '';

$saleRecord = null;

$saleItems = [];

$subtotal = 0.00;

$receiptDiscount = 0.00;

$netSubtotal = 0.00;

$taxableSubtotal = 0.00;

$taxAmount = 0.00;

$totalAmount = 0.00;


try {

    $databaseConnection =
        connectDatabase();


    $saleStatement =
        $databaseConnection->prepare(
            '
            SELECT
                ReceiptID,
                TransactionNumber,
                RegisterID,
                TransactionDateTime,
                Status,
                ReceiptDiscountAmount,
                SubtotalAmount
            FROM salesreceipt
            WHERE ReceiptID =
                :receiptID
              AND StoreID =
                :storeID
              AND OperatorID =
                :operatorID
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


    if (
        !$saleRecord
        ||
        $saleRecord['Status'] !== 'Open'
    ) {

        header(
            'Location: '
            . APPLICATION_URL
            . '/sales/new.php'
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
            WHERE ReceiptID =
                :receiptID
            ORDER BY LineNumber
            '
        );

    $itemStatement->execute([
        ':receiptID' =>
            $receiptID
    ]);

    $saleItems =
        $itemStatement->fetchAll();


    if (empty($saleItems)) {

        header(
            'Location: '
            . APPLICATION_URL
            . '/sales/new.php?receipt='
            . $receiptID
        );

        exit;
    }


    $subtotal =
        (float) $saleRecord['SubtotalAmount'];

    $receiptDiscount =
        (float) $saleRecord['ReceiptDiscountAmount'];

    $netSubtotal =
        round(
            max(
                $subtotal - $receiptDiscount,
                0.00
            ),
            2
        );

    $grossTaxableSubtotal = 0.00;


    foreach ($saleItems as $saleItem) {

        if ((int) $saleItem['Taxable'] === 1) {

            $grossTaxableSubtotal +=
                (float) $saleItem['LineTotal'];
        }
    }


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

    $totalAmount =
        round(
            $netSubtotal
            + $taxAmount,
            2
        );


    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        &&
        isset($_POST['cancel_sale'])
    ) {

        $submittedSecurityToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !formSecurityTokenIsValid(
                $submittedSecurityToken
            )
        ) {

            $errorMessage =
                'The form expired. Please try again.';

        } else {

            try {

                $cancelStatement =
                    $databaseConnection->prepare(
                        '
                        CALL sp_void_sale(
                            :receiptID,
                            :operatorID
                        )
                        '
                    );

                $cancelStatement->execute([
                    ':receiptID' =>
                        $receiptID,

                    ':operatorID' =>
                        $operatorID
                ]);

                $cancelStatement->closeCursor();


                $startStatement =
                    $databaseConnection->prepare(
                        '
                        CALL sp_start_sale(
                            :storeID,
                            :registerID,
                            :operatorID
                        )
                        '
                    );

                $startStatement->execute([
                    ':storeID' =>
                        $storeID,

                    ':registerID' =>
                        (int) $saleRecord['RegisterID'],

                    ':operatorID' =>
                        $operatorID
                ]);

                $newSale =
                    $startStatement->fetch();

                $startStatement->closeCursor();


                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/new.php?receipt='
                    . (int) $newSale['ReceiptID']
                    . '&cancelled=1'
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
                        $exception,
                        'The sale could not be cancelled.'
                    );
            }
        }
    }


    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        &&
        isset($_POST['close_register'])
    ) {

        $submittedSecurityToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !formSecurityTokenIsValid(
                $submittedSecurityToken
            )
        ) {

            $errorMessage =
                'The form expired. Please try again.';

        } else {

            $closeDestination =
                trim(
                    $_POST['close_destination']
                    ?? ''
                );

            $safeCloseDestination =
                APPLICATION_URL
                . '/sales/new.php?closed=1';

            if (
                $closeDestination !== ''
                &&
                str_starts_with(
                    $closeDestination,
                    APPLICATION_URL . '/'
                )
                &&
                !str_contains(
                    $closeDestination,
                    "\r"
                )
                &&
                !str_contains(
                    $closeDestination,
                    "\n"
                )
            ) {

                $safeCloseDestination =
                    $closeDestination;
            }

            try {

                $closeStatement =
                    $databaseConnection->prepare(
                        '
                        CALL sp_void_sale(
                            :receiptID,
                            :operatorID
                        )
                        '
                    );

                $closeStatement->execute([
                    ':receiptID' =>
                        $receiptID,

                    ':operatorID' =>
                        $operatorID
                ]);

                $closeStatement->closeCursor();


                header(
                    'Location: '
                    . $safeCloseDestination
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
                        $exception,
                        'The register could not be closed.'
                    );
            }
        }
    }


    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        &&
        isset($_POST['complete_sale'])
    ) {

        $submittedSecurityToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !formSecurityTokenIsValid(
                $submittedSecurityToken
            )
        ) {

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
                            $receiptID,

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
                        . '/sales/complete.php?receipt='
                        . $receiptID
                    );

                    exit;

                } catch (PDOException $exception) {

                    $errorMessage =
                        getSafeDatabaseErrorMessage(
                            $exception,
                            'The checkout could not be completed.'
                        );
                }
            }
        }
    }

} catch (PDOException $exception) {

    error_log(
        $exception->getMessage()
    );

    $errorMessage =
        'The sale information could not be loaded.';
}


$pageTitle =
    'Checkout';

$currentSection =
    'sales';

$currentPage =
    'checkout';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel checkout-panel">

    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


    <div class="checkout-transaction-number">

        <strong>
            Transaction:
        </strong>

        <?= escapeOutput(
            $saleRecord['TransactionNumber']
            ?? ''
        ) ?>

    </div>


    <div class="sale-receipt-table-container">

        <table class="sale-receipt-table">

            <thead>

                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
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
                                    (float) $saleItem['Quantity'],
                                    0
                                )
                                : number_format(
                                    (float) $saleItem['Quantity'],
                                    3
                                )
                            ) ?>
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
                            <?= (int) $saleItem['Taxable'] === 1
                                ? 'Taxable'
                                : 'No Tax'
                            ?>
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


    <section class="checkout-totals">

        <div class="checkout-total-row">

            <span>
                Subtotal
            </span>

            <strong>
                $<?= escapeOutput(
                    number_format(
                        $subtotal,
                        2
                    )
                ) ?>
            </strong>

        </div>


        <?php if ($receiptDiscount > 0): ?>

            <div class="checkout-total-row">

                <span>
                    Receipt Discount
                </span>

                <strong>
                    -$<?= escapeOutput(
                        number_format(
                            $receiptDiscount,
                            2
                        )
                    ) ?>
                </strong>

            </div>


            <div class="checkout-total-row">

                <span>
                    Subtotal After Discount
                </span>

                <strong>
                    $<?= escapeOutput(
                        number_format(
                            $netSubtotal,
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
                        $taxableSubtotal,
                        2
                    )
                ) ?>
            </strong>

        </div>


        <div class="checkout-total-row">

            <span>
                Sales Tax (7.75%)
            </span>

            <strong>
                $<?= escapeOutput(
                    number_format(
                        $taxAmount,
                        2
                    )
                ) ?>
            </strong>

        </div>


        <div class="checkout-total-row checkout-grand-total">

            <span>
                Total Due
            </span>

            <strong>
                $<?= escapeOutput(
                    number_format(
                        $totalAmount,
                        2
                    )
                ) ?>
            </strong>

        </div>

    </section>


    <form
        method="post"
        id="checkoutPaymentForm"
        class="checkout-payment-form"
    >

        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <input
            type="hidden"
            name="receipt_id"
            value="<?= (int) $receiptID ?>"
        >


        <div class="form-field">

            <label for="amount_tendered">
                Cash Tendered
            </label>

            <input
                type="number"
                id="amount_tendered"
                name="amount_tendered"
                min="<?= escapeOutput(
                    number_format(
                        $totalAmount,
                        2,
                        '.',
                        ''
                    )
                ) ?>"
                step="0.01"
                inputmode="decimal"
                required
            >

        </div>


        <div class="form-actions">

            <button
                type="submit"
                name="complete_sale"
                value="1"
                class="button button-primary"
            >
                Complete Sale
            </button>


            <a
                href="<?= APPLICATION_URL ?>/sales/new.php?receipt=<?= (int) $receiptID ?>"
                class="button button-secondary"
                data-checkout-safe="true"
            >
                Return to Sale
            </a>

        </div>

    </form>


    <form
        method="post"
        id="cancelCheckoutSaleForm"
        class="checkout-cancel-form"
    >

        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <input
            type="hidden"
            name="receipt_id"
            value="<?= (int) $receiptID ?>"
        >

        <input
            type="hidden"
            name="cancel_sale"
            value="1"
        >


        <button
            type="submit"
            name="cancel_sale"
            value="1"
            class="button button-danger"
            onclick="return window.confirm('Cancel this sale? All scanned items will be returned to inventory.');"
        >
            Cancel Sale
        </button>

    </form>


    <form
        method="post"
        id="closeCheckoutRegisterForm"
        hidden
    >

        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(getFormSecurityToken()) ?>"
        >

        <input
            type="hidden"
            name="receipt_id"
            value="<?= (int) $receiptID ?>"
        >

        <input
            type="hidden"
            id="checkout_close_destination"
            name="close_destination"
            value=""
        >

        <input
            type="hidden"
            name="close_register"
            value="1"
        >

    </form>


    <dialog
        id="checkoutLeaveDialog"
        class="checkout-leave-dialog"
    >

        <h2>
            Leave Checkout?
        </h2>

        <p>
            This transaction has not been paid. Choose what should happen before leaving this screen.
        </p>


        <div class="checkout-leave-actions">

            <button
                type="button"
                id="checkoutStayButton"
                class="button button-secondary"
            >
                Stay on Checkout
            </button>

            <button
                type="button"
                id="checkoutSaveButton"
                class="button button-primary"
            >
                Save Transaction and Leave
            </button>

            <button
                type="button"
                id="checkoutCloseButton"
                class="button button-danger"
            >
                Close Register and Leave
            </button>

        </div>

    </dialog>

</section>


<script>
(function () {

    const leaveDialog =
        document.getElementById(
            'checkoutLeaveDialog'
        );

    const cancelSaleForm =
        document.getElementById(
            'cancelCheckoutSaleForm'
        );

    const closeRegisterForm =
        document.getElementById(
            'closeCheckoutRegisterForm'
        );

    const closeDestinationInput =
        document.getElementById(
            'checkout_close_destination'
        );

    const paymentForm =
        document.getElementById(
            'checkoutPaymentForm'
        );

    const stayButton =
        document.getElementById(
            'checkoutStayButton'
        );

    const saveButton =
        document.getElementById(
            'checkoutSaveButton'
        );

    const closeButton =
        document.getElementById(
            'checkoutCloseButton'
        );

    let pendingDestination = '';

    let allowCheckoutLeave = false;


    document.addEventListener(
        'click',
        function (event) {

            const link =
                event.target.closest(
                    'a[href]'
                );

            if (!link) {
                return;
            }

            if (
                link.dataset.checkoutSafe
                ===
                'true'
            ) {

                allowCheckoutLeave = true;

                return;
            }

            const destination =
                link.getAttribute('href');

            if (
                !destination
                ||
                destination.startsWith('#')
                ||
                destination.startsWith('mailto:')
            ) {

                return;
            }

            event.preventDefault();

            pendingDestination =
                destination;

            leaveDialog.showModal();
        }
    );


    stayButton.addEventListener(
        'click',
        function () {

            pendingDestination = '';

            leaveDialog.close();
        }
    );


    saveButton.addEventListener(
        'click',
        function () {

            if (pendingDestination === '') {

                leaveDialog.close();

                return;
            }

            allowCheckoutLeave = true;

            leaveDialog.close();

            window.location.href =
                pendingDestination;
        }
    );


    closeButton.addEventListener(
        'click',
        function () {

            if (pendingDestination === '') {

                leaveDialog.close();

                return;
            }

            const confirmed =
                window.confirm(
                    'Close this register? The current transaction will be cancelled and all scanned items will be returned to inventory.'
                );

            if (!confirmed) {
                return;
            }

            allowCheckoutLeave = true;

            closeDestinationInput.value =
                pendingDestination;

            closeRegisterForm.requestSubmit();
        }
    );


    paymentForm.addEventListener(
        'submit',
        function () {

            allowCheckoutLeave = true;
        }
    );


    cancelSaleForm.addEventListener(
        'submit',
        function () {

            allowCheckoutLeave = true;
        }
    );


    closeRegisterForm.addEventListener(
        'submit',
        function () {

            allowCheckoutLeave = true;
        }
    );


    window.addEventListener(
        'beforeunload',
        function (event) {

            if (allowCheckoutLeave) {
                return;
            }

            event.preventDefault();

            event.returnValue = '';
        }
    );

})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>