<?php // express/order.php

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
    header('Location: ' . APPLICATION_URL . '/express/orders.php');
    exit;
}

function loadExpressOrder(PDO $databaseConnection, int $expressOrderID, int $storeID)
{
    $statement = $databaseConnection->prepare(
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
        $databaseConnection,
        $expressOrderID,
        $storeID
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('The Express order could not be loaded.');
}

if (!$order) {
    header('Location: ' . APPLICATION_URL . '/express/orders.php');
    exit;
}

if (
    (int) $order['PersonalShopperID'] !== $operatorID
    &&
    !operatorIsAdministrator()
) {
    http_response_code(403);
    exit('You cannot open another personal shopper\'s Express order');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedSecurityToken =
        $_POST['form_security_token']
        ?? '';

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {

        $errorMessage =
            'The form expired. Please try again.';

    } elseif (isset($_POST['add_item'])) {

        $productID =
            (int) ($_POST['product_id'] ?? 0);

        $quantity =
            filter_var(
                $_POST['quantity'] ?? null,
                FILTER_VALIDATE_FLOAT
            );

        if (
            $productID <= 0
            ||
            $quantity === false
            ||
            $quantity <= 0
        ) {

            $errorMessage =
                'Select a product and enter a quantity greater than zero.';

        } else {

            try {
                $productStatement =
                    $databaseConnection->prepare(
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
                    $statement =
                        $databaseConnection->prepare(
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

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/express/order.php?id='
                        . $expressOrderID
                        . '&added=1'
                    );

                    exit;
                }

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
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
                    $databaseConnection->prepare(
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
                    . '/express/order.php?id='
                    . $expressOrderID
                    . '&removed=1'
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
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
                $databaseConnection->prepare(
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
                . '/express/order.php?id='
                . $expressOrderID
                . '&updated=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                getSafeDatabaseErrorMessage(
                    $exception,
                    'The Express order status could not be updated.'
                );
        }

    } elseif (isset($_POST['cancel_order'])) {

        try {

            $statement =
                $databaseConnection->prepare(
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
                . '/express/orders.php?cancelled=1'
            );

            exit;

        } catch (PDOException $exception) {

            $errorMessage =
                getSafeDatabaseErrorMessage(
                    $exception,
                    'The Express order could not be cancelled.'
                );
        }
    }
}

if (isset($_GET['added'])) {
    $successMessage = 'The item was added to the Express order.';
} elseif (isset($_GET['removed'])) {
    $successMessage = 'The item quantity was reduced and inventory was restored.';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'The Express order status was updated.';
}

if (isset($_GET['address_warning'])) {
    $warningMessage =
        'The Express order was created, but the saved address could not be updated. The delivery address on this order is still correct.';
}

try {
    $order = loadExpressOrder(
        $databaseConnection,
        $expressOrderID,
        $storeID
    );

    if (!$order) {
        header('Location: ' . APPLICATION_URL . '/express/orders.php');
        exit;
    }

    $lineStatement =
        $databaseConnection->prepare(
            '
            SELECT
                ReceiptLineID,
                LineNumber,
                ProductID,
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

    $lineStatement->execute([
        ':receiptID' =>
            (int) $order['ReceiptID']
    ]);

    $lines =
        $lineStatement->fetchAll();

    $stockStatement =
        $databaseConnection->prepare(
            '
            SELECT
                ProductID,
                ProductName,
                UnitType,
                RetailPrice,
                StockQuantity
            FROM vw_store_stock
            WHERE StoreID = :storeID
            ORDER BY ProductName
            '
        );

    $stockStatement->execute([
        ':storeID' =>
            $storeID
    ]);

    $stockRows =
        $stockStatement->fetchAll();

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage =
        'Some Express order information could not be loaded. Please try again.';
    $lines = [];
    $stockRows = [];
}


$merchandiseSubtotal = 0.00;

foreach ($lines as $line) {
    $merchandiseSubtotal +=
        (float) $line['LineTotal'];
}

$deliveryFee =
    (float) $order['DeliveryFee'];

$pageTitle =
    'Express Order';

$currentSection =
    'express';

$currentPage =
    'express-order';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel express-panel">

    <div class="page-intro">

        <h1>
            Express Order
        </h1>

        <p>
            <?= escapeOutput($order['TransactionNumber']) ?>
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


    <?php if ($order['ReceiptStatus'] === 'Open'): ?>

        <section class="express-entry-panel">

            <h2>
                Add Grocery Item
            </h2>

            <form
                method="post"
                class="express-item-form"
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

                    <label for="product_id">
                        Product
                    </label>

                    <select
                        id="product_id"
                        name="product_id"
                        required
                    >

                        <option value="">
                            Select Product
                        </option>


                        <?php foreach ($stockRows as $stockRow): ?>

                            <option
                                value="<?= (int) $stockRow['ProductID'] ?>"
                                data-unit-type="<?= escapeOutput($stockRow['UnitType']) ?>"
                            >
                                <?= escapeOutput($stockRow['ProductName']) ?>
                                |
                                <?= escapeOutput($stockRow['UnitType']) ?>
                                |
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $stockRow['RetailPrice'],
                                        2
                                    )
                                ) ?>
                                |
                                Stock:
                                <?= escapeOutput($stockRow['StockQuantity']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-field">

                    <label
                        for="quantity"
                        id="express_quantity_label"
                    >
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        step="1"
                        value="1"
                        inputmode="decimal"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="add_item"
                    value="1"
                    class="button button-primary"
                >
                    Add Item
                </button>

            </form>

        </section>

    <?php endif; ?>


    <h2>
        Order Items
    </h2>


    <?php if (!$lines): ?>

        <p class="express-empty">
            No groceries have been added to this order yet.
        </p>

    <?php else: ?>

        <div class="express-table-container">

            <table class="express-table">

                <thead>

                    <tr>
                        <th>Line</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Line Total</th>

                        <?php if ($order['ReceiptStatus'] === 'Open'): ?>
                            <th>Remove</th>
                        <?php endif; ?>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($lines as $line): ?>

                        <tr>

                            <td>
                                <?= escapeOutput($line['LineNumber']) ?>
                            </td>

                            <td>
                                <?= escapeOutput($line['ProductName']) ?>
                            </td>

                            <td>
                                <?= escapeOutput($line['UnitType']) ?>
                            </td>

                            <td>
                                <?= escapeOutput($line['Quantity']) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $line['UnitPrice'],
                                        2
                                    )
                                ) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $line['LineTotal'],
                                        2
                                    )
                                ) ?>
                            </td>


                            <?php if ($order['ReceiptStatus'] === 'Open'): ?>

                                <td>

                                    <?php if ($line['UnitType'] === 'Pound'): ?>

                                        <?php
                                        $lineWeight =
                                            (float) $line['Quantity'];

                                        $defaultRemoveWeight =
                                            min(0.100, $lineWeight);
                                        ?>

                                        <form
                                            method="post"
                                            class="express-remove-weight-form"
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

                                            <input
                                                type="hidden"
                                                name="receipt_line_id"
                                                value="<?= (int) $line['ReceiptLineID'] ?>"
                                            >

                                            <input
                                                type="number"
                                                name="remove_quantity"
                                                value="<?= escapeOutput(
                                                    number_format(
                                                        $defaultRemoveWeight,
                                                        3,
                                                        '.',
                                                        ''
                                                    )
                                                ) ?>"
                                                min="0.001"
                                                max="<?= escapeOutput(
                                                    number_format(
                                                        $lineWeight,
                                                        3,
                                                        '.',
                                                        ''
                                                    )
                                                ) ?>"
                                                step="0.001"
                                                inputmode="decimal"
                                                class="express-remove-weight-input"
                                                aria-label="Weight to remove in pounds"
                                                title="Weight to remove in pounds"
                                                required
                                            >

                                            <button
                                                type="submit"
                                                name="remove_item"
                                                value="1"
                                                class="button button-secondary"
                                            >
                                                Remove
                                            </button>

                                            <button
                                                type="submit"
                                                name="remove_all_item"
                                                value="1"
                                                class="button button-secondary"
                                            >
                                                All
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <form method="post">

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

                                            <input
                                                type="hidden"
                                                name="receipt_line_id"
                                                value="<?= (int) $line['ReceiptLineID'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="remove_item"
                                                value="1"
                                                class="button button-secondary"
                                                onclick="return confirm('Remove this quantity from the Express order and return it to inventory?');"
                                            >
                                                <?=
                                                    (float) $line['Quantity'] > 1
                                                    ? 'Remove 1'
                                                    : 'Remove Item'
                                                ?>
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>


    <?php if ($order['ReceiptStatus'] === 'Open'): ?>

        <div class="express-order-footer">

            <form
                method="post"
                class="express-status-form"
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

                <label for="status">
                    Picking Status
                </label>

                <select
                    id="status"
                    name="status"
                >
                    <option
                        value="Received"
                        <?= $order['ExpressStatus'] === 'Received' ? 'selected' : '' ?>
                    >
                        Received
                    </option>

                    <option
                        value="Picking"
                        <?= $order['ExpressStatus'] === 'Picking' ? 'selected' : '' ?>
                    >
                        Picking
                    </option>

                    <option
                        value="Ready"
                        <?= $order['ExpressStatus'] === 'Ready' ? 'selected' : '' ?>
                    >
                        Ready
                    </option>
                </select>

                <button
                    type="submit"
                    name="update_status"
                    value="1"
                    class="button button-secondary"
                >
                    Update Status
                </button>

            </form>


            <div class="express-actions">

                <?php if ($lines): ?>

                    <a
                        href="<?= APPLICATION_URL ?>/express/ex_checkout.php?id=<?= $expressOrderID ?>"
                        class="button button-primary"
                    >
                        Checkout Order
                    </a>

                <?php endif; ?>


                <form method="post">

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

                    <button
                        type="submit"
                        name="cancel_order"
                        value="1"
                        class="button button-secondary"
                        onclick="return confirm(
                        'Cancel this Express order? All picked items will be returned to inventory.'
                        );"
                    >
                        Cancel Order
                    </button>

                </form>

            </div>

        </div>

    <?php endif; ?>


    <div class="express-actions">

        <a
            class="button button-secondary"
            href="<?= APPLICATION_URL ?>/express/orders.php"
        >
            Back to Orders
        </a>

    </div>

</section>

<script>
(function () {
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const quantityLabel = document.getElementById('express_quantity_label');

    if (!productSelect || !quantityInput || !quantityLabel) {
        return;
    }

    function updateQuantityField() {
        const selectedOption =
            productSelect.options[productSelect.selectedIndex];

        const unitType =
            selectedOption
                ? selectedOption.dataset.unitType || ''
                : '';

        if (unitType === 'Pound') {
            quantityLabel.textContent = 'Weight (lb)';
            quantityInput.min = '0.001';
            quantityInput.step = '0.001';
            if (Number(quantityInput.value) <= 0) {
                quantityInput.value = '0.100';
            }
        } else {
            quantityLabel.textContent = 'Quantity';
            quantityInput.min = '1';
            quantityInput.step = '1';
            if (
                Number(quantityInput.value) < 1
                ||
                !Number.isInteger(Number(quantityInput.value))
            ) {
                quantityInput.value = '1';
            }
        }
    }

    productSelect.addEventListener('change', updateQuantityField);
    updateQuantityField();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>