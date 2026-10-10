<?php // sales/sale_checkout.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../includes/discounts.php';

requireRegister();

$storeID =
    signedInStoreID();

$operatorID =
    signedInOperatorID();

$receiptID =
    isset($_GET['receipt'])
    ? (int) $_GET['receipt']
    : (int) ($_POST['receipt_id'] ?? 0);

$errorMessage = '';

$saleRecord = null;

$saleItems = [];

$subtotal = 0.00;

$couponRecords = [];

$couponTotal = 0.00;

$receiptDiscount = 0.00;

$netSubtotal = 0.00;

$taxableSubtotal = 0.00;

$taxAmount = 0.00;

$totalAmount = 0.00;

$postTaxDiscount = 0.00;

$discountRecords = [];


try {

    $db =
        connectDatabase();


    $saleStatement =
        $db->prepare(
            '
            SELECT
                ReceiptID,
                TransactionNumber,
                RegisterID,
                TransactionDateTime,
                Status,
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
            . '/sales/sale_new.php'
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
            . '/sales/sale_new.php?receipt='
            . $receiptID
        );

        exit;
    }


    $saleTotals =
        fetchSaleTotals(
            $db,
            $receiptID
        );

    $discountRecords = fetchSaleDiscounts( $db, $receiptID );
    $couponRecords = fetchSaleCoupons($db, $receiptID);
    $couponTotal = array_sum(array_map('floatval', array_column($couponRecords, 'AppliedAmount')));

    $subtotal =
        (float) $saleTotals['GrossSubtotal'];

    $receiptDiscount =
        (float) $saleTotals['PreTaxDiscount'];

    $netSubtotal =
        (float) $saleTotals['SubtotalAfterDiscount'];

    $taxableSubtotal =
        (float) $saleTotals['TaxableSubtotal'];

    $taxAmount =
        (float) $saleTotals['TaxAmount'];

    $postTaxDiscount =
        (float) $saleTotals['PostTaxDiscount'];

    $totalAmount =
        (float) $saleTotals['TotalDue'];


    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        &&
        isset($_POST['cancel_sale'])
    ) {

        $submittedToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !tokenIsValid(
                $submittedToken
            )
        ) {

            $errorMessage =
                'The form expired. Please try again.';

        } else {

            try {

                $cancelStatement =
                    $db->prepare(
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
                    $db->prepare(
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
                    . '/sales/sale_new.php?receipt='
                    . (int) $newSale['ReceiptID']
                    . '&cancelled=1'
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    databaseMessage(
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

        $submittedToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !tokenIsValid(
                $submittedToken
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
                . '/sales/sale_new.php?closed=1';

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
                    $db->prepare(
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
                    databaseMessage(
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

        $submittedToken =
            $_POST['form_security_token']
            ?? '';


        if (
            !tokenIsValid(
                $submittedToken
            )
        ) {

            $errorMessage =
                'The form expired. Please try again.';

        } else {

            $paymentMethod =
                ($_POST['payment_method'] ?? 'Cash') === 'Charge'
                    ? 'Charge'
                    : 'Cash';
            // A charge is for exactly the total, so no cash amount is needed
            $amountTendered =
                $paymentMethod === 'Charge'
                    ? $totalAmount
                    : filter_var(
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
                            $receiptID,
                        ':amountTendered' =>
                            $amountTendered,
                        ':operatorID' =>
                            $operatorID,
                        ':paymentMethod' =>
                            $paymentMethod
                    ]);

                    $checkoutStatement->fetch();

                    $checkoutStatement->closeCursor();


                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_complete.php?receipt='
                        . $receiptID
                    );

                    exit;

                } catch (PDOException $exception) {

                    $errorMessage =
                        databaseMessage(
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


    <div class="checkout-layout">

    <div class="checkout-items">

    <div class="checkout-transaction-number">

        <strong>
            Transaction:
        </strong>

        <?= transactionNumberHtml(
            $saleRecord['TransactionNumber'] ?? ''
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


    </div>

    <div class="checkout-payment">

    <section class="checkout-totals">
        <?php if (!empty($couponRecords)): ?>
            <div class="checkout-total-row">
                <span>
                    Items
                </span>
                <strong>
                    $<?= escapeOutput(number_format($subtotal + $couponTotal, 2)) ?>
                </strong>
            </div>
            <?php foreach ($couponRecords as $couponRecord): ?>
                <div class="checkout-total-row checkout-discount-row">
                    <span>
                        Coupon
                        <small>
                            <?= escapeOutput($couponRecord['Description']) ?>
                        </small>
                    </span>
                    <strong>
                        -$<?= escapeOutput(number_format((float) $couponRecord['AppliedAmount'], 2)) ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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


        <?php foreach ($discountRecords as $discountRecord): ?>

            <?php if ($discountRecord['TaxTiming'] === 'Before Tax'): ?>

                <div class="checkout-total-row checkout-discount-row">

                    <span>
                        <?= escapeOutput(describeDiscount($discountRecord)) ?>
                        <small>
                            <?= escapeOutput($discountRecord['Reason']) ?>
                        </small>
                    </span>

                    <strong>
                        -$<?= escapeOutput(
                            number_format(
                                (float) $discountRecord['AppliedAmount'],
                                2
                            )
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>


        <?php if ($receiptDiscount > 0): ?>

            <div class="checkout-total-row">

                <span>
                    Subtotal After Discounts
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
                Sales Tax (<?= escapeOutput(number_format(SALES_TAX_RATE * 100, 2)) ?>%)
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


        <?php foreach ($discountRecords as $discountRecord): ?>

            <?php if ($discountRecord['TaxTiming'] === 'After Tax'): ?>

                <div class="checkout-total-row checkout-discount-row">

                    <span>
                        <?= escapeOutput(describeDiscount($discountRecord)) ?>
                        <small>
                            <?= escapeOutput($discountRecord['Reason']) ?>
                        </small>
                    </span>

                    <strong>
                        -$<?= escapeOutput(
                            number_format(
                                (float) $discountRecord['AppliedAmount'],
                                2
                            )
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>


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
            value="<?= escapeOutput(formToken()) ?>"
        >

        <input
            type="hidden"
            name="receipt_id"
            value="<?= (int) $receiptID ?>"
        >


        <fieldset class="checkout-method">
            <legend>
                Payment
            </legend>
            <label class="checkout-method-option">
                <input
                    type="radio"
                    name="payment_method"
                    value="Cash"
                    checked
                >
                Cash
            </label>
            <label class="checkout-method-option">
                <input
                    type="radio"
                    name="payment_method"
                    value="Charge"
                >
                Charge
            </label>
        </fieldset>
        <div
            class="form-field"
            id="checkoutCashField"
        >

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
        <p
            class="checkout-charge-note"
            id="checkoutChargeNote"
            hidden
        >
            The total is charged to the customer's card. No cash is entered and no change is given.
        </p>


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
                href="<?= APPLICATION_URL ?>/sales/sale_new.php?receipt=<?= (int) $receiptID ?>"
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
            value="<?= escapeOutput(formToken()) ?>"
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
            value="<?= escapeOutput(formToken()) ?>"
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


    </div>

    </div>

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
                <div class="checkout-leave-choice">
                    <button
                        type="button"
                        id="checkoutStayButton"
                        class="button button-secondary"
                    >
                        Stay
                    </button>
                    <p class="checkout-leave-note">
                        Keep working on this transaction.
                    </p>
                </div>
                <div class="checkout-leave-choice">
                    <button
                        type="button"
                        id="checkoutSaveButton"
                        class="button button-primary"
                    >
                        Save
                    </button>
                    <p class="checkout-leave-note">
                        Leave now. The transaction stays saved on this register.
                    </p>
                </div>
                <div class="checkout-leave-choice">
                    <button
                        type="button"
                        id="checkoutCloseButton"
                        class="button button-danger"
                    >
                        Close
                    </button>
                    <p class="checkout-leave-note">
                        Cancel the transaction, return the items to stock, and close the register.
                    </p>
                </div>
            </div>
        </dialog>

    <script>
        (function () {

            const cashField = document.getElementById('checkoutCashField');
            const cashInput = document.getElementById('amount_tendered');
            const chargeNote = document.getElementById('checkoutChargeNote');
            const methods = document.querySelectorAll('input[name="payment_method"]');

            // A charge needs no cash amount, so the cash box is hidden and not required
            function showMethod() {

                const isCharge = document.querySelector('input[name="payment_method"]:checked').value === 'Charge';

                cashField.hidden = isCharge;
                chargeNote.hidden = !isCharge;
                cashInput.required = !isCharge;
            }

            methods.forEach(function (method) {
                method.addEventListener('change', showMethod);
            });

            showMethod();

        })();
    </script>


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
                    'Close this register? '
                    + 'The current transaction will be cancelled '
                    + 'and all scanned items will be returned to inventory.'
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