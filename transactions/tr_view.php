<?php // transactions/tr_view.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../includes/discounts.php';

requireAccess();

$storeID = signedInStoreID();
$operatorID = signedInOperatorID();
$receiptID = isset($_GET['receipt']) ? (int) $_GET['receipt'] : 0;

if ($receiptID <= 0) {
    showAccessDeniedPage('Select a transaction from the Transaction Viewer before opening transaction details.');
}

$transactionRecord = null;
$lineRecords = [];
$discountRecords = [];
$couponRecords = [];
$errorMessage = '';

$successMessage = '';
$accessRecords = [];
$lastTakeOver = null;

if (isset($_GET['cancelled'])) {
    $successMessage = 'The transaction was cancelled and its items were returned to stock.';
} elseif (isset($_GET['viewed'])) {
    $successMessage = 'Viewing only. Nothing was changed.';
} elseif (isset($_GET['handedback'])) {
    $successMessage = 'The transaction was handed back.';
}

// Where each kind of transaction is worked on
function transactionWorkUrl(array $row): string
{
    return $row['SaleType'] === 'Express'
        ? APPLICATION_URL . '/express/ex_order.php?id=' . (int) $row['ExpressOrderID']
        : APPLICATION_URL . '/sales/sale_new.php?receipt=' . (int) $row['ReceiptID'];
}

// A supervisor chooses how to open someone else's open transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && canSupervise()) {
    $action = (string) ($_POST['action'] ?? '');

    if (!tokenIsValid($_POST['form_security_token'] ?? '')) {
        $errorMessage = 'The form expired. Please try again.';
    } elseif (!in_array($action, ['view', 'assist', 'takeover', 'handback', 'cancel'], true)) {
        $errorMessage = 'Choose one of the options.';
    } else {
        try {
            $actionDb = connectDatabase();

            $lookup = $actionDb->prepare(
                'SELECT sr.ReceiptID, sr.SaleType, eo.ExpressOrderID
                 FROM salesreceipt sr
                 LEFT JOIN expressorder eo ON eo.ReceiptID = sr.ReceiptID
                 WHERE sr.ReceiptID = :receiptID AND sr.StoreID = :storeID'
            );
            $lookup->execute([':receiptID' => $receiptID, ':storeID' => $storeID]);
            $workRow = $lookup->fetch();
            $lookup->closeCursor();

            if (!$workRow) {
                throw new RuntimeException('The transaction is not in your store.');
            }

            $callSql = [
                'view' => "CALL sp_record_sale_access(:receiptID, :operatorID, 'View')",
                'assist' => "CALL sp_record_sale_access(:receiptID, :operatorID, 'Assist')",
                'takeover' => 'CALL sp_take_over_sale(:receiptID, :operatorID)',
                'handback' => 'CALL sp_hand_back_sale(:receiptID, :operatorID)',
                'cancel' => 'CALL sp_void_sale(:receiptID, :operatorID)'
            ];

            $actionStatement = $actionDb->prepare($callSql[$action]);
            $actionStatement->execute([':receiptID' => $receiptID, ':operatorID' => $operatorID]);
            $actionStatement->closeCursor();

            $detailsUrl = APPLICATION_URL . '/transactions/tr_view.php?receipt=' . $receiptID;

            if ($action === 'assist') {
                // Only this one transaction is open for assistance in this session
                $_SESSION['supervisor_assist_receipt'] = $receiptID;
                header('Location: ' . transactionWorkUrl($workRow));
            } elseif ($action === 'takeover') {
                unset($_SESSION['supervisor_assist_receipt']);
                header('Location: ' . transactionWorkUrl($workRow));
            } elseif ($action === 'handback') {
                unset($_SESSION['supervisor_assist_receipt']);
                header('Location: ' . $detailsUrl . '&handedback=1');
            } elseif ($action === 'cancel') {
                header('Location: ' . $detailsUrl . '&cancelled=1');
            } else {
                header('Location: ' . $detailsUrl . '&viewed=1');
            }

            exit;
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        } catch (PDOException $exception) {
            $errorMessage = databaseMessage($exception, 'The transaction could not be changed.');
        }
    }
}

try {
    $db = connectDatabase();

    $whereParts = [
        'sr.StoreID = :storeID',
        'sr.ReceiptID = :receiptID'
    ];

    $parameters = [
        ':storeID' => $storeID,
        ':receiptID' => $receiptID
    ];

    if (isOperator()) {
        $whereParts[] = '(sr.OperatorID = :operatorID OR tj.OpenedByOperatorID = :openedByID)';
        $whereParts[] = "sr.SaleType = 'Regular'";
        $parameters[':operatorID'] = $operatorID;
        $parameters[':openedByID'] = $operatorID;
    } elseif (isPersonalShopper()) {
        $whereParts[] = '(eo.PersonalShopperID = :operatorID OR tj.OpenedByOperatorID = :openedByID)';
        $whereParts[] = "sr.SaleType = 'Express'";
        $parameters[':operatorID'] = $operatorID;
        $parameters[':openedByID'] = $operatorID;
    }

    $transactionStatement = $db->prepare(
        '
        SELECT
            sr.ReceiptID,
            sr.TransactionNumber,
            sr.StoreID,
            sr.RegisterID,
            sr.OperatorID,
            sr.CustomerID,
            sr.SaleType,
            sr.TransactionDateTime,
            sr.CheckoutDateTime,
            sr.Status AS ReceiptStatus,
            sr.ReceiptDiscountAmount,
            sr.SubtotalAmount,
            sr.TaxableSubtotalAmount,
            sr.TaxAmount,
            sr.PostTaxDiscountAmount,
            sr.TotalAmount,
            sr.PaymentMethod,
            sr.AmountTendered,
            sr.ChangeDue,
            r.RegisterNumber,
            r.RegisterName,
            o.Username,
            o.EmployeeNumber,
            CONCAT(o.FirstName, \' \', o.LastName) AS OperatorName,
            CONCAT(op.FirstName, \' \', op.LastName) AS OpenedByName,
            CONCAT(cl.FirstName, \' \', cl.LastName) AS ClosedByName,
            c.LoyaltyNumber,
            c.FirstName AS CustomerFirstName,
            c.LastName AS CustomerLastName,
            c.Email AS CustomerEmail,
            c.Phone AS CustomerPhone,
            eo.ExpressOrderID,
            eo.PersonalShopperID,
            eo.OrderPlacedDateTime,
            eo.FulfillmentMethod,
            eo.DeliveryFee,
            eo.DeliveryAddressLine1,
            eo.DeliveryAddressLine2,
            eo.DeliveryCity,
            eo.DeliveryStateCode,
            eo.DeliveryPostalCode,
            eo.Status AS ExpressStatus
        FROM salesreceipt sr
        JOIN register r
            ON r.StoreID = sr.StoreID
           AND r.RegisterID = sr.RegisterID
        JOIN operator o
            ON o.OperatorID = sr.OperatorID
        LEFT JOIN customer c
            ON c.CustomerID = sr.CustomerID
        LEFT JOIN expressorder eo
            ON eo.ReceiptID = sr.ReceiptID
        LEFT JOIN transactionjournal tj
            ON tj.ReceiptID = sr.ReceiptID
        LEFT JOIN operator op
            ON op.OperatorID = tj.OpenedByOperatorID
        LEFT JOIN operator cl
            ON cl.OperatorID = tj.ClosedByOperatorID
        WHERE ' . implode(' AND ', $whereParts) . '
        LIMIT 1
        '
    );

    $transactionStatement->execute($parameters);
    $transactionRecord = $transactionStatement->fetch();

    if ($transactionRecord && canSupervise()) {
        $accessStatement = $db->prepare(
            '
            SELECT
                sl.AccessMode,
                sl.AccessedAt,
                sl.PreviousOperatorID,
                sl.NewOperatorID,
                CONCAT(a.FirstName, \' \', a.LastName) AS ActingName,
                CONCAT(p.FirstName, \' \', p.LastName) AS PreviousName,
                CONCAT(n.FirstName, \' \', n.LastName) AS NewName
            FROM saleaccesslog sl
            JOIN operator a
                ON a.OperatorID = sl.ActingOperatorID
            LEFT JOIN operator p
                ON p.OperatorID = sl.PreviousOperatorID
            LEFT JOIN operator n
                ON n.OperatorID = sl.NewOperatorID
            WHERE sl.ReceiptID = :receiptID
            ORDER BY sl.AccessID
            '
        );

        $accessStatement->execute([':receiptID' => $receiptID]);
        $accessRecords = $accessStatement->fetchAll();

        foreach ($accessRecords as $accessRecord) {
            if ($accessRecord['AccessMode'] === 'Take Over') {
                $lastTakeOver = $accessRecord;
            }
        }
    }

    if (!$transactionRecord) {
        showAccessDeniedPage('The requested transaction was not found or is not available to your account.');
    }

    $lineStatement = $db->prepare(
        '
        SELECT
            ReceiptLineID,
            LineNumber,
            ProductID,
            ProductNameAtSale,
            UnitTypeAtSale,
            TaxableAtSale,
            Quantity,
            UnitPrice,
            LineDiscountAmount,
            ROUND(
                Quantity * UnitPrice - LineDiscountAmount,
                2
            ) AS LineTotal
        FROM salesreceiptline
        WHERE ReceiptID = :receiptID
        ORDER BY LineNumber
        '
    );

    $lineStatement->execute([
        ':receiptID' => $receiptID
    ]);

    $lineRecords = $lineStatement->fetchAll();

    $discountRecords = fetchSaleDiscounts($db, $receiptID);
    $couponRecords = fetchSaleCoupons($db, $receiptID);

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'Transaction details could not be loaded.';
}

$pageTitle = 'Transaction Details';
$currentSection = 'transactions';
$currentPage = 'detail';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel transaction-panel transaction-detail-panel">

    <div class="page-intro transaction-compact-intro">
        <h1>Transaction Details</h1>
        <p><?= transactionNumberHtml($transactionRecord['TransactionNumber'] ?? '') ?></p>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="message message-success"><?= escapeOutput($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($transactionRecord && $transactionRecord['ReceiptStatus'] === 'Open'): ?>
        <?php
        $ownsIt = (int) $transactionRecord['OperatorID'] === $operatorID;
        $canHandBack = $lastTakeOver
            && (int) $lastTakeOver['NewOperatorID'] === (int) $transactionRecord['OperatorID'];
        ?>
        <section class="transaction-open-panel">
            <?php if ($ownsIt): ?>
                <p>This open transaction is yours.</p>
                <div class="form-actions">
                    <a
                        href="<?= transactionWorkUrl($transactionRecord) ?>"
                        class="button button-primary"
                    >
                        Resume
                    </a>
                </div>
            <?php elseif (canSupervise()): ?>
                <p>
                    This open transaction belongs to
                    <strong><?= escapeOutput($transactionRecord['OperatorName']) ?></strong>.
                    Choose how to open it.
                </p>
                <ul>
                    <li><strong>View Only</strong> changes nothing.</li>
                    <li>
                        <strong>Assist</strong> lets you add items, discounts, and coupons.
                        The transaction stays with <?= escapeOutput($transactionRecord['OperatorName']) ?>,
                        who can still resume it.
                    </li>
                    <li>
                        <strong>Take Over</strong> assigns the transaction to you so you can check it out.
                        You can hand it back.
                    </li>
                </ul>
                <form
                    method="post"
                    class="form-actions"
                >
                    <input
                        type="hidden"
                        name="form_security_token"
                        value="<?= escapeOutput(formToken()) ?>"
                    >
                    <button
                        type="submit"
                        name="action"
                        value="view"
                        class="button button-secondary"
                    >
                        View Only
                    </button>
                    <button
                        type="submit"
                        name="action"
                        value="assist"
                        class="button button-secondary"
                    >
                        Assist
                    </button>
                    <button
                        type="submit"
                        name="action"
                        value="takeover"
                        class="button button-primary"
                        onclick="return confirm('Take over this transaction? It will be assigned to you.');"
                    >
                        Take Over
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($ownsIt && $canHandBack && canSupervise()): ?>
                <form
                    method="post"
                    class="form-actions"
                >
                    <input
                        type="hidden"
                        name="form_security_token"
                        value="<?= escapeOutput(formToken()) ?>"
                    >
                    <button
                        type="submit"
                        name="action"
                        value="handback"
                        class="button button-secondary"
                    >
                        Hand Back to <?= escapeOutput($lastTakeOver['PreviousName']) ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if (canSupervise()): ?>
                <form
                    method="post"
                    class="form-actions"
                >
                    <input
                        type="hidden"
                        name="form_security_token"
                        value="<?= escapeOutput(formToken()) ?>"
                    >
                    <button
                        type="submit"
                        name="action"
                        value="cancel"
                        class="button button-secondary"
                        onclick="return confirm('Cancel this open transaction? Its items go back in stock.');"
                    >
                        Cancel Transaction
                    </button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($transactionRecord): ?>
        <?php
        $customerName = trim(
            (string) ($transactionRecord['CustomerFirstName'] ?? '')
            . ' '
            . (string) ($transactionRecord['CustomerLastName'] ?? '')
        );
        if ($customerName === '') {
            $customerName = 'Walk-in Customer';
        }
        ?>

        <section class="transaction-detail-summary">
            <div>
                <span>Type</span>
                <strong><?= escapeOutput($transactionRecord['SaleType']) ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong><?= escapeOutput($transactionRecord['ReceiptStatus']) ?></strong>
            </div>
            <div>
                <span>Register</span>
                <strong>
                    #<?= escapeOutput($transactionRecord['RegisterNumber']) ?>
                    - <?= escapeOutput($transactionRecord['RegisterName']) ?>
                </strong>
            </div>
            <div>
                <span>Opened by</span>
                <strong>
                    <?= escapeOutput($transactionRecord['OpenedByName'] ?? $transactionRecord['OperatorName']) ?>
                </strong>
            </div>
            <?php if (!empty($transactionRecord['ClosedByName'])): ?>
                <div>
                    <span>Closed by</span>
                    <strong><?= escapeOutput($transactionRecord['ClosedByName']) ?></strong>
                </div>
            <?php endif; ?>
            <div>
                <span>Opened</span>
                <strong>
                    <?= escapeOutput(date('m/d/Y g:i A', strtotime($transactionRecord['TransactionDateTime']))) ?>
                </strong>
            </div>
            <div>
                <span>Checkout</span>
                <strong>
                    <?= $transactionRecord['CheckoutDateTime']
                        ? escapeOutput(date('m/d/Y g:i A', strtotime($transactionRecord['CheckoutDateTime'])))
                        : 'Not completed' ?>
                </strong>
            </div>
            <div>
                <span>Employee</span>
                <strong><?= escapeOutput($transactionRecord['Username']) ?></strong>
            </div>
            <div>
                <span>Customer</span>
                <strong><?= escapeOutput($customerName) ?></strong>
            </div>
        </section>

        <?php if ($transactionRecord['SaleType'] === 'Express'): ?>
            <section class="transaction-express-summary">
                <div>
                    <span>Express Status</span>
                    <strong><?= escapeOutput($transactionRecord['ExpressStatus'] ?? '') ?></strong>
                </div>
                <div>
                    <span>Fulfillment</span>
                    <strong><?= escapeOutput($transactionRecord['FulfillmentMethod'] ?? '') ?></strong>
                </div>
                <div>
                    <span>Delivery Fee</span>
                    <strong>
                        $<?= escapeOutput(number_format((float) ($transactionRecord['DeliveryFee'] ?? 0), 2)) ?>
                    </strong>
                </div>

                <?php if (($transactionRecord['FulfillmentMethod'] ?? '') === 'Delivery'): ?>
                    <div class="transaction-address-block">
                        <span>Delivery Address</span>
                        <strong>
                            <?= escapeOutput($transactionRecord['DeliveryAddressLine1'] ?? '') ?>
                            <?php if (trim((string) ($transactionRecord['DeliveryAddressLine2'] ?? '')) !== ''): ?>
                                <br><?= escapeOutput($transactionRecord['DeliveryAddressLine2']) ?>
                            <?php endif; ?>
                            <br>
                            <?= escapeOutput($transactionRecord['DeliveryCity'] ?? '') ?>,
                            <?= escapeOutput($transactionRecord['DeliveryStateCode'] ?? '') ?>
                            <?= escapeOutput($transactionRecord['DeliveryPostalCode'] ?? '') ?>
                        </strong>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <div
            class="transaction-table-container"
            tabindex="0"
        >
            <table class="transaction-table transaction-line-table">
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Taxable</th>
                        <th>Discount</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$lineRecords): ?>
                        <tr>
                            <td
                                colspan="8"
                                class="transaction-empty-row"
                            >No items are recorded on this transaction.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($lineRecords as $lineRecord): ?>
                        <tr>
                            <td><?= (int) $lineRecord['LineNumber'] ?></td>
                            <td><?= escapeOutput($lineRecord['ProductNameAtSale']) ?></td>
                            <td><?= escapeOutput(number_format((float) $lineRecord['Quantity'], 3)) ?></td>
                            <td><?= escapeOutput($lineRecord['UnitTypeAtSale']) ?></td>
                            <td>$<?= escapeOutput(number_format((float) $lineRecord['UnitPrice'], 2)) ?></td>
                            <td><?= (int) $lineRecord['TaxableAtSale'] === 1 ? 'Yes' : 'No' ?></td>
                            <td>$<?= escapeOutput(number_format((float) $lineRecord['LineDiscountAmount'], 2)) ?></td>
                            <td>$<?= escapeOutput(number_format((float) $lineRecord['LineTotal'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($couponRecords)): ?>
            <h2 class="transaction-subheading">Coupons</h2>
            <div
                class="transaction-table-container"
                tabindex="0"
            >
                <table class="transaction-table transaction-discount-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Coupon</th>
                            <th>Product</th>
                            <th>Items</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($couponRecords as $couponRecord): ?>
                            <tr>
                                <td><?= (int) $couponRecord['CouponNumber'] ?></td>
                                <td><?= escapeOutput($couponRecord['Description']) ?></td>
                                <td><?= escapeOutput($couponRecord['ProductName']) ?></td>
                                <td><?= (int) $couponRecord['UnitsCovered'] ?></td>
                                <td>
                                    $<?= escapeOutput(number_format((float) $couponRecord['AppliedAmount'], 2)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (!empty($discountRecords)): ?>
            <h2 class="transaction-subheading">Discounts</h2>

            <div
                class="transaction-table-container"
                tabindex="0"
            >
                <table class="transaction-table transaction-discount-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Discount</th>
                            <th>Reason</th>
                            <th>Applied By</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discountRecords as $discountRecord): ?>
                            <tr>
                                <td><?= (int) $discountRecord['DiscountNumber'] ?></td>
                                <td><?= escapeOutput(describeDiscount($discountRecord)) ?></td>
                                <td><?= escapeOutput($discountRecord['Reason']) ?></td>
                                <td><?= escapeOutput($discountRecord['AppliedByUsername']) ?></td>
                                <td>
                                    $<?= escapeOutput(number_format((float) $discountRecord['AppliedAmount'], 2)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <section class="transaction-payment-summary">
            <div>
                <span>Merchandise Subtotal</span>
                <strong>$<?= escapeOutput(number_format((float) $transactionRecord['SubtotalAmount'], 2)) ?></strong>
            </div>
            <div>
                <span>Before-Tax Discounts</span>
                <strong>
                    $<?= escapeOutput(number_format((float) $transactionRecord['ReceiptDiscountAmount'], 2)) ?>
                </strong>
            </div>
            <div>
                <span>Taxable Subtotal</span>
                <strong>
                    $<?= escapeOutput(number_format((float) $transactionRecord['TaxableSubtotalAmount'], 2)) ?>
                </strong>
            </div>
            <div>
                <span>Sales Tax</span>
                <strong>$<?= escapeOutput(number_format((float) $transactionRecord['TaxAmount'], 2)) ?></strong>
            </div>
            <div>
                <span>After-Tax Discounts</span>
                <strong>
                    $<?= escapeOutput(number_format((float) $transactionRecord['PostTaxDiscountAmount'], 2)) ?>
                </strong>
            </div>
            <?php if ($transactionRecord['SaleType'] === 'Express'): ?>
                <div>
                    <span>Delivery Fee</span>
                    <strong>
                        $<?= escapeOutput(number_format((float) ($transactionRecord['DeliveryFee'] ?? 0), 2)) ?>
                    </strong>
                </div>
            <?php endif; ?>
            <div class="transaction-total-row">
                <span>Total</span>
                <strong>$<?= escapeOutput(number_format((float) $transactionRecord['TotalAmount'], 2)) ?></strong>
            </div>
            <div>
                <span>Payment Method</span>
                <strong><?= escapeOutput($transactionRecord['PaymentMethod']) ?></strong>
            </div>
            <div>
                <span>
                    <?= $transactionRecord['PaymentMethod'] === 'Charge'
                        ? 'Amount Charged'
                        : 'Amount Tendered' ?>
                </span>
                <strong>
                    <?= $transactionRecord['AmountTendered'] === null
                        ? 'None'
                        : '$' . escapeOutput(number_format((float) $transactionRecord['AmountTendered'], 2)) ?>
                </strong>
            </div>
            <?php if ($transactionRecord['PaymentMethod'] !== 'Charge'): ?>
            <div>
                <span>Change Due</span>
                <strong>
                    <?= $transactionRecord['ChangeDue'] === null
                        ? 'None'
                        : '$' . escapeOutput(number_format((float) $transactionRecord['ChangeDue'], 2)) ?>
                </strong>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($accessRecords): ?>
            <h2 class="transaction-subheading">Who Worked On This Transaction</h2>
            <div
                class="transaction-table-container"
                tabindex="0"
            >
                <table class="transaction-table transaction-discount-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Who</th>
                            <th>What</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accessRecords as $accessRecord): ?>
                            <tr>
                                <td><?= escapeOutput($accessRecord['AccessedAt']) ?></td>
                                <td><?= escapeOutput($accessRecord['ActingName']) ?></td>
                                <td>
                                    <?php if ($accessRecord['AccessMode'] === 'View'): ?>
                                        Viewed it
                                    <?php elseif ($accessRecord['AccessMode'] === 'Assist'): ?>
                                        Assisted (it stayed with <?= escapeOutput($accessRecord['PreviousName']) ?>)
                                    <?php elseif ($accessRecord['AccessMode'] === 'Take Over'): ?>
                                        Took it over from <?= escapeOutput($accessRecord['PreviousName']) ?>
                                    <?php else: ?>
                                        Handed it back to <?= escapeOutput($accessRecord['NewName']) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="page-main-actions transaction-detail-actions">
            <a
                href="<?= APPLICATION_URL ?>/transactions/tr_list.php"
                class="button button-secondary"
            >
                Back to Transactions
            </a>
        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>