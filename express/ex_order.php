<?php // express/ex_order.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../includes/item_entry.php';

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
$successMessage = '';
$warningMessage = '';

if ($expressOrderID <= 0) {
    header('Location: ' . APPLICATION_URL . '/express/ex_orders.php');
    exit;
}

function loadExpressOrder(PDO $db, int $expressOrderID, int $storeID)
{
    $statement = $db->prepare(
        '
        SELECT *
        FROM vw_express_orders
        WHERE ExpressOrderID = :expressOrderID
          AND StoreID = :storeID
        LIMIT 1
        '
    );

    $statement->execute([
        ':expressOrderID' => $expressOrderID,
        ':storeID' => $storeID
    ]);

    return $statement->fetch();
}

try {
    $order = loadExpressOrder(
        $db,
        $expressOrderID,
        $storeID
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('The Express order could not be loaded.');
}

if (!$order) {
    header('Location: ' . APPLICATION_URL . '/express/ex_orders.php');
    exit;
}

if (
    (int) $order['PersonalShopperID'] !== $operatorID
    &&
    !isAssisting($order['ReceiptID'])
) {
    showAccessDeniedPage(
        'You cannot open another Personal Shopper\'s Express order.'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken =
        $_POST['form_security_token']
        ?? '';

    if (!tokenIsValid($submittedToken)) {

        $errorMessage =
            'The form expired. Please try again.';

    } elseif (isset($_POST['add_item'])) {

        $productID = (int) ($_POST['product_id'] ?? 0);
        $productCode = trim((string) ($_POST['product_code'] ?? ''));
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $codeNotFound = false;
        if ($productID <= 0 && $productCode !== '') {
            try {
                $codeStatement = $db->prepare(
                    '
                    SELECT ProductID
                    FROM vw_pos_products
                    WHERE StoreID = :storeID
                      AND (UPC = :upcCode OR PLUCode = :pluCode)
                    LIMIT 1
                    '
                );
                $codeStatement->execute([
                    ':storeID' => $storeID,
                    ':upcCode' => $productCode,
                    ':pluCode' => $productCode
                ]);
                $productID = (int) ($codeStatement->fetchColumn() ?: 0);
                $codeNotFound = $productID <= 0;
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $codeNotFound = true;
            }
        }
        if ($codeNotFound) {
            $errorMessage = 'The product code was not found.';
        } elseif ($productID <= 0 || $quantity === false || $quantity <= 0) {

            $errorMessage =
                'Select a product and enter a quantity greater than zero.';

        } else {

            try {
                $productStatement =
                    $db->prepare(
                        '
                        SELECT
                            ProductID,
                            UnitType
                        FROM vw_store_stock
                        WHERE StoreID = :storeID
                          AND ProductID = :productID
                        LIMIT 1
                        '
                    );

                $productStatement->execute([
                    ':storeID' => $storeID,
                    ':productID' => $productID
                ]);

                $selectedProduct =
                    $productStatement->fetch();

                if (!$selectedProduct) {
                    $errorMessage =
                        'The selected product is not available at this store.';
                } elseif (
                    $selectedProduct['UnitType'] === 'Each'
                    &&
                    abs((float) $quantity - round((float) $quantity)) > 0.000001
                ) {
                    $errorMessage =
                        'Products sold by the item require a whole-number quantity.';
                } else {
                    [$quantity, $stockCapNotice] = capRequestedStock($db, $storeID, $productID, (float) $quantity);
                    $statement =
                        $db->prepare(
                            '
                            CALL sp_add_sale_item(
                                :receiptID,
                                :productID,
                                :quantity,
                                :operatorID
                            )
                            '
                        );

                    $statement->execute([
                        ':receiptID' =>
                            (int) $order['ReceiptID'],

                        ':productID' =>
                            $productID,

                        ':quantity' =>
                            round((float) $quantity, 3),

                        ':operatorID' =>
                            $operatorID
                    ]);

                    $statement->closeCursor();
                    if ($stockCapNotice !== '') {
                        $_SESSION['stock_cap_notice'] = $stockCapNotice;
                    }

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/express/ex_order.php?id='
                        . $expressOrderID
                        . '&added=1'
                    );

                    exit;
                }

            } catch (StockLimitException $exception) {
                $errorMessage = $exception->getMessage();
            } catch (PDOException $exception) {

                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The item could not be added to the Express order.'
                    );
            }
        }

    } elseif (
        isset($_POST['remove_item'])
        ||
        isset($_POST['remove_all_item'])
    ) {

        $receiptLineID =
            (int) ($_POST['receipt_line_id'] ?? 0);

        $removeQuantityText =
            trim(
                (string) (
                    $_POST['remove_quantity']
                    ?? ''
                )
            );

        if (isset($_POST['remove_all_item'])) {
            $removeQuantity = 999999;
        } elseif ($removeQuantityText !== '') {
            $removeQuantity =
                filter_var(
                    $removeQuantityText,
                    FILTER_VALIDATE_FLOAT
                );
        } else {
            $removeQuantity = null;
        }

        if (
            $receiptLineID <= 0
            ||
            $removeQuantity === false
            ||
            (
                $removeQuantity !== null
                &&
                $removeQuantity <= 0
            )
        ) {
            $errorMessage =
                'Enter a quantity or weight greater than zero.';
        } else {
            try {

                $statement =
                    $db->prepare(
                        '
                        CALL sp_remove_sale_item(
                            :receiptID,
                            :receiptLineID,
                            :operatorID,
                            :removeQuantity
                        )
                        '
                    );

                $statement->execute([
                    ':receiptID' =>
                        (int) $order['ReceiptID'],

                    ':receiptLineID' =>
                        $receiptLineID,

                    ':removeQuantity' =>
                        $removeQuantity,

                    ':operatorID' =>
                        $operatorID
                ]);

                $statement->closeCursor();

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/express/ex_order.php?id='
                    . $expressOrderID
                    . '&removed=1'
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The item could not be removed from the Express order.'
                    );
            }
        }

    } elseif (isset($_POST['update_status'])) {

        $newStatus =
            $_POST['status']
            ?? '';

        try {

            $statement =
                $db->prepare(
                    '
                    CALL sp_set_express_order_status(
                        :expressOrderID,
                        :operatorID,
                        :status
                    )
                    '
                );

            $statement->execute([
                ':expressOrderID' =>
                    $expressOrderID,

                ':operatorID' =>
                    $operatorID,

                ':status' =>
                    $newStatus
            ]);

            $statement->closeCursor();

            header(
                'Location: '
                . APPLICATION_URL
                . '/express/ex_order.php?id='
                . $expressOrderID
                . '&updated=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                databaseMessage(
                    $exception,
                    'The Express order status could not be updated.'
                );
        }

    } elseif (isset($_POST['cancel_order'])) {

        try {

            $statement =
                $db->prepare(
                    '
                    CALL sp_void_sale(
                        :receiptID,
                        :operatorID
                    )
                    '
                );

            $statement->execute([
                ':receiptID' =>
                    (int) $order['ReceiptID'],

                ':operatorID' =>
                    $operatorID
            ]);

            $statement->closeCursor();

            header(
                'Location: '
                . APPLICATION_URL
                . '/express/ex_orders.php?cancelled=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                databaseMessage(
                    $exception,
                    'The Express order could not be cancelled.'
                );
        }
    }
}

if (isset($_GET['added'])) {
    $successMessage = 'The item was added to the Express order.';
    if (!empty($_SESSION['stock_cap_notice'])) {
        $successMessage = $_SESSION['stock_cap_notice'];
        unset($_SESSION['stock_cap_notice']);
    }
} elseif (isset($_GET['removed'])) {
    $successMessage = 'The item quantity was reduced and inventory was restored.';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'The Express order status was updated.';
} elseif (isset($_GET['notready'])) {
    $errorMessage = 'Mark the order Ready before checkout.';
}

if (isset($_GET['address_warning'])) {
    $warningMessage =
        'The Express order was created, but the saved address could not be updated. '
        . 'The delivery address on this order is still correct.';
}

try {
    $order = loadExpressOrder(
        $db,
        $expressOrderID,
        $storeID
    );

    if (!$order) {
        header('Location: ' . APPLICATION_URL . '/express/ex_orders.php');
        exit;
    }

    $lineStatement =
        $db->prepare(
            '
            SELECT
                sd.ReceiptLineID,
                sd.LineNumber,
                sd.ProductID,
                sd.ProductName,
                sd.UnitType,
                sd.Taxable,
                sd.Quantity,
                sd.UnitPrice,
                sd.LineTotal,
                p.UPC,
                p.PLUCode
            FROM vw_sale_detail sd
            JOIN product p
                ON p.ProductID = sd.ProductID
            WHERE sd.ReceiptID = :receiptID
            ORDER BY sd.LineNumber
            '
        );

    $lineStatement->execute([
        ':receiptID' =>
            (int) $order['ReceiptID']
    ]);

    $lines =
        $lineStatement->fetchAll();

    $stockStatement =
        $db->prepare(
            '
            SELECT
                ProductID,
                DepartmentName,
                UPC,
                PLUCode,
                ProductName,
                UnitType,
                RetailPrice,
                Taxable,
                StockQuantity
            FROM vw_pos_products
            WHERE StoreID = :storeID
            ORDER BY
                DepartmentName,
                ProductName
            '
        );

    $stockStatement->execute([
        ':storeID' =>
            $storeID
    ]);

    $productRecords =
        $stockStatement->fetchAll();

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage =
        'Some Express order information could not be loaded. Please try again.';
    $lines = [];
    $productRecords = [];
}


$merchandiseSubtotal = 0.00;

foreach ($lines as $line) {
    $merchandiseSubtotal +=
        (float) $line['LineTotal'];
}

$deliveryFee =
    (float) $order['DeliveryFee'];

// A new error replaces any message left over from the page address
if ($errorMessage !== '') {
    $successMessage = '';
}

// A supervisor assisting works on an order that stays with its Personal Shopper
$assistingOther = (int) $order['PersonalShopperID'] !== $operatorID;

$pageTitle =
    'Express Order';

$currentSection =
    'express';

$currentPage =
    'express-order';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel register-panel">

    <div class="page-intro">

        <h1>
            Express Order
        </h1>

        <p>
            <?= transactionNumberHtml($order['TransactionNumber']) ?>
        </p>

    </div>


    <?php if ($successMessage !== ''): ?>

        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($warningMessage !== ''): ?>

        <div class="message message-warning">
            <?= escapeOutput($warningMessage) ?>
        </div>

    <?php endif; ?>


    <div class="express-order-summary">

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
            <strong>Personal Shopper:</strong>
            <?= escapeOutput($order['PersonalShopperUsername']) ?>
        </div>

        <div>
            <strong>Fulfillment:</strong>
            <?= escapeOutput($order['FulfillmentMethod']) ?>
        </div>

        <div>
            <strong>Status:</strong>
            <?= escapeOutput($order['ExpressStatus']) ?>
        </div>

        <div>
            <strong>Merchandise:</strong>
            $<?= escapeOutput(
                number_format(
                    $merchandiseSubtotal,
                    2
                )
            ) ?>
        </div>

        <div>
            <strong>Delivery Fee:</strong>
            $<?= escapeOutput(
                number_format(
                    $deliveryFee,
                    2
                )
            ) ?>
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


    <?php if ($assistingOther): ?>
        <div class="message message-information">
            You are assisting <?= escapeOutput($order['PersonalShopperUsername']) ?>.
            The order stays with <?= escapeOutput($order['PersonalShopperUsername']) ?>, who can still resume it.
            To mark it Ready or check it out, take it over from the
            <a href="<?= APPLICATION_URL ?>/transactions/tr_view.php?receipt=<?= (int) $order['ReceiptID'] ?>">
                transaction details</a>.
        </div>
    <?php endif; ?>

    <?php if ($order['ReceiptStatus'] === 'Open' && !$assistingOther): ?>

        <div class="express-order-footer">

            <p class="express-next-step">
                <?php if (!$lines): ?>
                    Add the items for this order. It moves to Picking when the first item is added.
                <?php elseif ($order['ExpressStatus'] !== 'Ready'): ?>
                    Mark the order Ready when everything is picked. You can still add or remove items first.
                <?php else: ?>
                    Ready to check out. If an item was forgotten, add it here. The order goes back to Picking,
                    then mark it Ready again.
                <?php endif; ?>
            </p>


            <div class="express-actions">

                <?php if ($order['ExpressStatus'] !== 'Ready' && $lines): ?>
                    <form method="post">
                        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">
                        <input type="hidden" name="express_order_id" value="<?= $expressOrderID ?>">
                        <input type="hidden" name="status" value="Ready">
                        <button type="submit" name="update_status" value="1" class="button button-primary">
                            Mark Ready
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($order['ExpressStatus'] === 'Ready'): ?>
                    <a href="<?= APPLICATION_URL ?>/express/ex_checkout.php?id=<?= $expressOrderID ?>"
                        class="button button-primary">
                        Checkout
                    </a>
                <?php endif; ?>


                <form method="post">

                    <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

                    <input type="hidden" name="express_order_id" value="<?= $expressOrderID ?>">

                    <button type="submit" name="cancel_order" value="1" class="button button-secondary" onclick="return confirm(
                        'Cancel this Express order? All picked items will be returned to inventory.'
                        );">
                        Cancel Order
                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>

    <div class="sale-workspace<?= $order['ReceiptStatus'] === 'Open' ? '' : ' sale-workspace-single' ?>">

        <?php if ($order['ReceiptStatus'] === 'Open'): ?>

            <section class="sale-product-area">

                <div class="sale-entry-panel">

                    <form method="post" class="sale-scan-form" id="saleScanForm">

                        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

                        <input type="hidden" name="express_order_id" value="<?= $expressOrderID ?>">

                        <input type="hidden" id="scanner_product_id" name="product_id" value="0">

                        <div class="form-field sale-barcode-field">

                            <label for="product_code">
                                Barcode / Product Code
                            </label>

                            <input type="text" id="product_code" name="product_code" value="" maxlength="20"
                                autocomplete="off">

                        </div>

                        <?php printQuantityPicker(); ?>
                        <div class="form-field sale-quantity-field" id="saleWeightField">

                            <label for="quantity" id="sale_quantity_label">
                                Quantity
                            </label>

                            <input type="number" id="quantity" name="quantity" value="1" min="1" step="1"
                                inputmode="decimal">

                        </div>

                        <button type="submit" name="add_item" value="1" class="button button-primary">
                            Add Item
                        </button>

                    </form>


                </div>

                <h2>
                    Products
                </h2>

                <?php
                printProductTiles(
                    $productRecords,
                    ['express_order_id' => $expressOrderID],
                    'add_item'
                );
                ?>

            </section>

        <?php endif; ?>

        <section class="sale-receipt-area">

            <div class="sale-receipt-header">

                <h2>
                    Order Items
                </h2>

                <?php printCodesButton(); ?>

            </div>

            <?php if (!$lines): ?>

                <div class="sale-empty">
                    No groceries have been added to this order yet.
                </div>

            <?php else: ?>

                <?php
                printSaleLines(
                    $lines,
                    ['express_order_id' => $expressOrderID],
                    $order['ReceiptStatus'] === 'Open',
                    [
                        'add' => 'add_item',
                        'remove' => 'remove_item',
                        'removeAll' => 'remove_all_item'
                    ]
                );
                ?>

            <?php endif; ?>

        </section>

    </div>


    <div class="express-actions">

        <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/ex_orders.php">
            Back to Orders
        </a>

    </div>

</section>

<?php if ($order['ReceiptStatus'] === 'Open'): ?>
    <?php printItemEntryScript(); ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>