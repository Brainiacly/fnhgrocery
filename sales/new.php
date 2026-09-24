<?php // sales/new.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAssignedAccess();

$storeID = (int)($_SESSION['store_id'] ?? 0);
$operatorID = (int)($_SESSION['operator_id'] ?? 0);

$receiptID =
    isset($_GET['receipt'])
        ? (int)$_GET['receipt']
        : (int)($_POST['receipt_id'] ?? 0);

$selectedRegisterID =
    isset($_GET['register'])
        ? (int)$_GET['register']
        : (int)($_POST['register_id'] ?? 0);

$errorMessage = '';
$successMessage = '';

$registerRecords = [];
$productRecords = [];
$saleItems = [];

$saleRecord = null;
$resumeSale = null;
$clearSale = null;


function startRegisterSale(
    PDO $databaseConnection,
    int $storeID,
    int $registerID,
    int $operatorID
): int {

    $statement =
        $databaseConnection->prepare(
            '
            CALL sp_start_sale(
                :storeID,
                :registerID,
                :operatorID
            )
            '
        );

    $statement->execute([
        ':storeID' => $storeID,
        ':registerID' => $registerID,
        ':operatorID' => $operatorID
    ]);

    $sale =
        $statement->fetch();

    $statement->closeCursor();

    return
        (int)($sale['ReceiptID'] ?? 0);
}


function voidRegisterSale(
    PDO $databaseConnection,
    int $receiptID,
    int $operatorID
): void {

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
        ':receiptID' => $receiptID,
        ':operatorID' => $operatorID
    ]);

    $statement->closeCursor();
}


try {

    $databaseConnection =
        connectDatabase();


    $registerStatement =
        $databaseConnection->prepare(
            '
            SELECT
                r.RegisterID,
                r.RegisterNumber,
                r.RegisterName,
                sr.ReceiptID
                    AS OpenReceiptID,
                sr.TransactionNumber
                    AS OpenTransactionNumber,
                sr.OperatorID
                    AS OpenOperatorID,
                o.Username
                    AS OpenOperatorUsername
            FROM register r
            LEFT JOIN salesreceipt sr
                ON sr.RegisterID =
                    r.RegisterID
               AND sr.StoreID =
                    r.StoreID
               AND sr.Status =
                    \'Open\'
            LEFT JOIN operator o
                ON o.OperatorID =
                    sr.OperatorID
            WHERE r.StoreID =
                :storeID
              AND r.Active = 1
            ORDER BY
                r.RegisterNumber
            '
        );

    $registerStatement->execute([
        ':storeID' => $storeID
    ]);

    $registerRecords =
        $registerStatement->fetchAll();


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

        } elseif (isset($_POST['select_register'])) {

            $selectedRegister = null;

            foreach ($registerRecords as $registerRecord) {

                if (
                    (int)$registerRecord['RegisterID']
                    ===
                    $selectedRegisterID
                ) {

                    $selectedRegister =
                        $registerRecord;

                    break;
                }
            }


            if (!$selectedRegister) {

                $errorMessage =
                    'Select an available register.';

            } elseif (
                !empty(
                    $selectedRegister['OpenReceiptID']
                )
            ) {

                if (
                    (int)$selectedRegister['OpenOperatorID']
                    ===
                    $operatorID
                ) {

                    $resumeSale =
                        $selectedRegister;

                } else {

                    if (operatorIsAdministrator()) {
                        $clearSale = $selectedRegister;
                    } else {
                        $errorMessage =
                            'That register is currently in use by another operator.';
                    }
                }

            } else {

                $operatorOpenSaleStatement =
                    $databaseConnection->prepare(
                        '
                        SELECT
                            r.RegisterNumber
                        FROM salesreceipt sr
                        JOIN register r
                            ON r.RegisterID =
                                sr.RegisterID
                        WHERE sr.StoreID =
                            :storeID
                          AND sr.OperatorID =
                            :operatorID
                          AND sr.Status =
                            \'Open\'
                        LIMIT 1
                        '
                    );

                $operatorOpenSaleStatement->execute([
                    ':storeID' => $storeID,
                    ':operatorID' => $operatorID
                ]);

                $operatorOpenSale =
                    $operatorOpenSaleStatement->fetch();


                if ($operatorOpenSale) {

                    $errorMessage =
                        'You already have an open session on Register '
                        . $operatorOpenSale['RegisterNumber']
                        . '. Select that register first.';

                } else {

                    try {

                        $newReceiptID =
                            startRegisterSale(
                                $databaseConnection,
                                $storeID,
                                $selectedRegisterID,
                                $operatorID
                            );

                        if ($newReceiptID > 0) {

                            header(
                                'Location: '
                                . APPLICATION_URL
                                . '/sales/new.php?receipt='
                                . $newReceiptID
                            );

                            exit;
                        }

                    } catch (PDOException $exception) {

                        $errorMessage =
                            getSafeDatabaseErrorMessage(
                                $exception,
                                'The register could not be opened.'
                            );
                    }
                }
            }

        } elseif (isset($_POST['clear_register'])) {

            if (!operatorIsAdministrator()) {
                $errorMessage =
                    "Administrator access is required to clear another operator's register.";
            } else {
                $clearReceiptID =
                    (int)($_POST['open_receipt_id'] ?? 0);

                try {
                    voidRegisterSale(
                        $databaseConnection,
                        $clearReceiptID,
                        $operatorID
                    );

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/new.php?cleared=1'
                    );
                    exit;
                } catch (PDOException $exception) {
                    $errorMessage =
                        getSafeDatabaseErrorMessage(
                            $exception,
                            'The register could not be cleared.'
                        );
                }
            }

        } elseif (isset($_POST['continue_sale'])) {

            $continueReceiptID =
                (int)(
                    $_POST['open_receipt_id']
                    ?? 0
                );

            $statement =
                $databaseConnection->prepare(
                    '
                    SELECT ReceiptID
                    FROM salesreceipt
                    WHERE ReceiptID =
                        :receiptID
                      AND StoreID =
                        :storeID
                      AND OperatorID =
                        :operatorID
                      AND RegisterID =
                        :registerID
                      AND Status =
                        \'Open\'
                    LIMIT 1
                    '
                );

            $statement->execute([
                ':receiptID' =>
                    $continueReceiptID,

                ':storeID' =>
                    $storeID,

                ':operatorID' =>
                    $operatorID,

                ':registerID' =>
                    $selectedRegisterID
            ]);


            if ($statement->fetch()) {

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/new.php?receipt='
                    . $continueReceiptID
                );

                exit;
            }


            $errorMessage =
                'The open sale is no longer available.';

        } elseif (isset($_POST['cancel_open_sale'])) {

            $cancelReceiptID =
                (int)(
                    $_POST['open_receipt_id']
                    ?? 0
                );

            try {

                voidRegisterSale(
                    $databaseConnection,
                    $cancelReceiptID,
                    $operatorID
                );

                $newReceiptID =
                    startRegisterSale(
                        $databaseConnection,
                        $storeID,
                        $selectedRegisterID,
                        $operatorID
                    );

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/new.php?receipt='
                    . $newReceiptID
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

        } elseif (
            isset($_POST['add_product'])
            &&
            $receiptID > 0
        ) {

            $productID =
                (int)(
                    $_POST['product_id']
                    ?? 0
                );


            if ($productID <= 0) {

                $productCode =
                    trim(
                        $_POST['product_code']
                        ?? ''
                    );


                if ($productCode === '') {

                    $errorMessage =
                        'Enter or select a product.';

                } else {

                    $statement =
                        $databaseConnection->prepare(
                            '
                            SELECT ProductID
                            FROM vw_pos_products
                            WHERE StoreID =
                                :storeID
                              AND (
                                  UPC =
                                      :productCode
                                  OR
                                  PLUCode =
                                      :productCode
                              )
                            LIMIT 1
                            '
                        );

                    $statement->execute([
                        ':storeID' =>
                            $storeID,

                        ':productCode' =>
                            $productCode
                    ]);

                    $product =
                        $statement->fetch();

                    $productID =
                        (int)(
                            $product['ProductID']
                            ?? 0
                        );


                    if ($productID <= 0) {

                        $errorMessage =
                            'The product code was not found.';
                    }
                }
            }


            $quantity =
                filter_var(
                    $_POST['quantity'] ?? 1,
                    FILTER_VALIDATE_FLOAT
                );

            if (
                $errorMessage === ''
                &&
                (
                    $quantity === false
                    ||
                    $quantity <= 0
                )
            ) {
                $errorMessage =
                    'Enter a quantity greater than zero.';
            }

            if (
                $errorMessage === ''
                &&
                $productID > 0
            ) {

                try {

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
                            $receiptID,

                        ':productID' =>
                            $productID,

                        ':quantity' =>
                            round((float)$quantity, 3),

                        ':operatorID' =>
                            $operatorID
                    ]);

                    $statement->closeCursor();

                    $successMessage =
                        'Product quantity added to the sale.';

                } catch (PDOException $exception) {

                    $errorMessage =
                        getSafeDatabaseErrorMessage(
                            $exception,
                            'The product could not be added.'
                        );
                }
            }

        } elseif (
            isset($_POST['remove_product'])
            &&
            $receiptID > 0
        ) {

            $receiptLineID =
                (int)(
                    $_POST['receipt_line_id']
                    ?? 0
                );

            try {

                $statement =
                    $databaseConnection->prepare(
                        '
                        CALL sp_remove_sale_item(
                            :receiptID,
                            :receiptLineID,
                            :operatorID
                        )
                        '
                    );

                $statement->execute([
                    ':receiptID' =>
                        $receiptID,

                    ':receiptLineID' =>
                        $receiptLineID,

                    ':operatorID' =>
                        $operatorID
                ]);

                $statement->closeCursor();

                $successMessage =
                    'The selected quantity was removed from the sale.';

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
                        $exception,
                        'The product could not be removed.'
                    );
            }

        } elseif (
            isset($_POST['cancel_sale'])
            &&
            $receiptID > 0
        ) {

            $cancelRegisterID =
                (int)(
                    $_POST['register_id']
                    ?? 0
                );

            $cancelDestination =
                trim(
                    $_POST['cancel_destination']
                    ?? ''
                );

            $safeCancelDestination =
                APPLICATION_URL
                . '/sales/new.php';

            if (
                $cancelDestination !== ''
                &&
                str_starts_with(
                    $cancelDestination,
                    APPLICATION_URL . '/'
                )
                &&
                !str_contains(
                    $cancelDestination,
                    "\r"
                )
                &&
                !str_contains(
                    $cancelDestination,
                    "\n"
                )
            ) {
                $safeCancelDestination =
                    $cancelDestination;
            }

            try {

                voidRegisterSale(
                    $databaseConnection,
                    $receiptID,
                    $operatorID
                );

                if ($cancelDestination !== '') {

                    header(
                        'Location: '
                        . $safeCancelDestination
                    );

                } else {

                    $newReceiptID =
                        startRegisterSale(
                            $databaseConnection,
                            $storeID,
                            $cancelRegisterID,
                            $operatorID
                        );

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/new.php?receipt='
                        . $newReceiptID
                        . '&cancelled=1'
                    );
                }

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    getSafeDatabaseErrorMessage(
                        $exception,
                        'The sale could not be cancelled.'
                    );
            }

        } elseif (
            isset($_POST['close_register'])
            &&
            $receiptID > 0
        ) {

            try {

                voidRegisterSale(
                    $databaseConnection,
                    $receiptID,
                    $operatorID
                );

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/new.php?closed=1'
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
        isset($_GET['cleared'])
        &&
        $_GET['cleared'] === '1'
    ) {
        $successMessage =
            'Register cleared. The cancelled transaction remains in the journal.';

    } elseif (
        isset($_GET['cancelled'])
        &&
        $_GET['cancelled'] === '1'
    ) {

        $successMessage =
            'Sale cancelled. The register is ready for the next sale.';

    } elseif (
        isset($_GET['closed'])
        &&
        $_GET['closed'] === '1'
    ) {

        $successMessage =
            'Register closed.';
    }


    if ($receiptID > 0) {

        $statement =
            $databaseConnection->prepare(
                '
                SELECT
                    sr.ReceiptID,
                    sr.TransactionNumber,
                    sr.RegisterID,
                    r.RegisterNumber,
                    r.RegisterName,
                    sr.TransactionDateTime,
                    sr.Status,
                    sr.SubtotalAmount
                FROM salesreceipt sr
                JOIN register r
                    ON r.RegisterID =
                        sr.RegisterID
                WHERE sr.ReceiptID =
                    :receiptID
                  AND sr.StoreID =
                    :storeID
                  AND sr.OperatorID =
                    :operatorID
                LIMIT 1
                '
            );

        $statement->execute([
            ':receiptID' =>
                $receiptID,

            ':storeID' =>
                $storeID,

            ':operatorID' =>
                $operatorID
        ]);

        $saleRecord =
            $statement->fetch();


        if (!$saleRecord) {

            $receiptID = 0;

            $errorMessage =
                'The requested sale was not found.';

        } elseif (
            $saleRecord['Status']
            !==
            'Open'
        ) {

            header(
                'Location: '
                . APPLICATION_URL
                . '/sales/new.php'
            );

            exit;
        }
    }


    if ($receiptID > 0) {

        $statement =
            $databaseConnection->prepare(
                '
                SELECT
                    ReceiptLineID,
                    ProductID,
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

        $statement->execute([
            ':receiptID' =>
                $receiptID
        ]);

        $saleItems =
            $statement->fetchAll();


        $statement =
            $databaseConnection->prepare(
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
                WHERE StoreID =
                    :storeID
                ORDER BY
                    DepartmentName,
                    ProductName
                '
            );

        $statement->execute([
            ':storeID' =>
                $storeID
        ]);

        $productRecords =
            $statement->fetchAll();
    }

} catch (PDOException $exception) {

    error_log(
        $exception->getMessage()
    );

    $errorMessage =
        'The point of sale information could not be loaded.';
}


$pageTitle =
    'New Sale';

$currentSection =
    'sales';

$currentPage =
    'new';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel sales-panel">

    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($successMessage !== ''): ?>

        <div class="message message-success">
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($receiptID <= 0): ?>

        <?php if ($clearSale): ?>

            <section class="sale-resume-panel">
                <h2>Register In Use</h2>
                <p>
                    Register <strong><?= escapeOutput($clearSale['RegisterNumber']) ?></strong>
                    is currently in use by
                    <strong><?= escapeOutput($clearSale['OpenOperatorUsername']) ?></strong>.
                    You cannot continue another operator's transaction.
                    As an administrator, you may clear the register.
                </p>
                <form method="post" class="sale-resume-actions">
                    <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">
                    <input type="hidden" name="register_id" value="<?= (int)$clearSale['RegisterID'] ?>">
                    <input type="hidden" name="open_receipt_id" value="<?= (int)$clearSale['OpenReceiptID'] ?>">
                    <button type="submit" name="clear_register" value="1" class="button button-danger">Clear Register</button>
                    <a href="<?= APPLICATION_URL ?>/sales/new.php" class="button button-secondary">Cancel</a>
                </form>
            </section>

        <?php elseif ($resumeSale): ?>

            <section class="sale-resume-panel">

                <h2>
                    Open Session
                </h2>

                <p>
                    Do you want to continue transaction
                    <strong>
                        <?= escapeOutput(
                            $resumeSale['OpenTransactionNumber']
                        ) ?>
                    </strong>
                    on Register
                    <strong>
                        <?= escapeOutput(
                            $resumeSale['RegisterNumber']
                        ) ?>
                    </strong>?
                </p>


                <form
                    method="post"
                    class="sale-resume-actions"
                >

                    <input
                        type="hidden"
                        name="form_security_token"
                        value="<?= escapeOutput(getFormSecurityToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="register_id"
                        value="<?= (int)$resumeSale['RegisterID'] ?>"
                    >

                    <input
                        type="hidden"
                        name="open_receipt_id"
                        value="<?= (int)$resumeSale['OpenReceiptID'] ?>"
                    >


                    <button
                        type="submit"
                        name="continue_sale"
                        value="1"
                        class="button button-primary"
                    >
                        Continue
                    </button>


                    <button
                        type="submit"
                        name="cancel_open_sale"
                        value="1"
                        class="button button-danger"
                    >
                        Cancel Sale
                    </button>

                </form>

            </section>

        <?php else: ?>

            <section class="sale-start-panel">

                <h2>
                    Select Register
                </h2>


                <form method="post">

                    <input
                        type="hidden"
                        name="form_security_token"
                        value="<?= escapeOutput(getFormSecurityToken()) ?>"
                    >


                    <div class="form-field">

                        <label for="register_id">
                            Register
                        </label>


                        <select
                            id="register_id"
                            name="register_id"
                            required
                        >

                            <option value="">
                                Select Register
                            </option>


                            <?php foreach ($registerRecords as $registerRecord): ?>

                                <?php

                                $openReceiptID =
                                    (int)(
                                        $registerRecord['OpenReceiptID']
                                        ?? 0
                                    );

                                $openOperatorID =
                                    (int)(
                                        $registerRecord['OpenOperatorID']
                                        ?? 0
                                    );

                                $inUseByOtherOperator =
                                    $openReceiptID > 0
                                    &&
                                    $openOperatorID !==
                                        $operatorID;

                                ?>

                                <option
                                    value="<?= (int)$registerRecord['RegisterID'] ?>"
                                    <?=
                                        $selectedRegisterID
                                        ===
                                        (int)$registerRecord['RegisterID']
                                            ? 'selected'
                                            : ''
                                    ?>
                                    <?= $inUseByOtherOperator && !operatorIsAdministrator() ? 'disabled' : '' ?>
                                >
                                    Register <?= escapeOutput($registerRecord['RegisterNumber']) ?>

                                    <?php if (trim((string)$registerRecord['RegisterName']) !== ''): ?>
                                        - <?= escapeOutput($registerRecord['RegisterName']) ?>
                                    <?php endif; ?>

                                    <?php if ($openReceiptID > 0 && !$inUseByOtherOperator): ?>
                                        - Open Session
                                    <?php elseif ($inUseByOtherOperator): ?>
                                        - In Use by <?= escapeOutput($registerRecord['OpenOperatorUsername']) ?>
                                    <?php endif; ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            name="select_register"
                            value="1"
                            class="button button-primary"
                        >
                            Open Register
                        </button>

                    </div>

                </form>

            </section>

        <?php endif; ?>

    <?php else: ?>

        <section class="sale-information">

            <div class="sale-information-transaction">

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
                    Register
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['RegisterNumber']
                    ) ?>
                </strong>

            </div>


            <div>

                <span>
                    Started
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['TransactionDateTime']
                    ) ?>
                </strong>

            </div>

        </section>


        <form
            method="post"
            class="sale-scan-form"
        >

            <input
                type="hidden"
                name="form_security_token"
                value="<?= escapeOutput(getFormSecurityToken()) ?>"
            >

            <input
                type="hidden"
                name="receipt_id"
                value="<?= (int)$receiptID ?>"
            >

            <input
                type="hidden"
                name="product_id"
                value="0"
            >


            <div class="form-field">

                <label for="product_code">
                    Barcode / Product Code
                </label>

                <input
                    type="text"
                    id="product_code"
                    name="product_code"
                    maxlength="20"
                    autocomplete="off"
                    autofocus
                >

            </div>


            <div class="form-field sale-quantity-field">

                <label for="quantity">
                    Quantity
                </label>

                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    value="1"
                    min="0.001"
                    step="0.001"
                    inputmode="decimal"
                    required
                >

            </div>


            <button
                type="submit"
                name="add_product"
                value="1"
                class="button button-primary"
            >
                Add Product
            </button>

        </form>


        <div class="sale-workspace">

            <section class="sale-product-area">

                <h2>
                    Products
                </h2>


                <div class="sale-product-grid">

                    <?php foreach ($productRecords as $productRecord): ?>

                        <form
                            method="post"
                            class="sale-product-form"
                        >

                            <input
                                type="hidden"
                                name="form_security_token"
                                value="<?= escapeOutput(getFormSecurityToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="receipt_id"
                                value="<?= (int)$receiptID ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int)$productRecord['ProductID'] ?>"
                            >

                            <input
                                type="hidden"
                                name="quantity"
                                value="1"
                            >


                            <button
                                type="submit"
                                name="add_product"
                                value="1"
                                class="sale-product-button"
                                <?= (float)$productRecord['StockQuantity'] <= 0 ? 'disabled' : '' ?>
                            >

                                <strong>
                                    <?= escapeOutput(
                                        $productRecord['ProductName']
                                    ) ?>
                                </strong>

                                <span>
                                    <?= escapeOutput(
                                        $productRecord['DepartmentName']
                                    ) ?>
                                </span>

                                <span>
                                    $<?= escapeOutput(
                                        number_format(
                                            (float)$productRecord['RetailPrice'],
                                            2
                                        )
                                    ) ?>
                                </span>


                                <?php if ((int)$productRecord['Taxable'] === 1): ?>

                                    <span>
                                        Taxable
                                    </span>

                                <?php endif; ?>


                                <span>
                                    Stock:
                                    <?= escapeOutput(
                                        number_format(
                                            (float)$productRecord['StockQuantity'],
                                            3
                                        )
                                    ) ?>
                                </span>

                            </button>

                        </form>

                    <?php endforeach; ?>

                </div>

            </section>


            <section class="sale-receipt-area">

                <h2>
                    Current Sale
                </h2>


                <?php if (empty($saleItems)): ?>

                    <div class="sale-empty">
                        No products have been added.
                    </div>

                <?php else: ?>

                    <div class="sale-receipt-table-container">

                        <table class="sale-receipt-table">

                            <thead>

                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Remove</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($saleItems as $saleItem): ?>

                                    <tr>

                                        <td>
                                            <?= escapeOutput(
                                                $saleItem['ProductName']
                                            ) ?>

                                            <?= (int)$saleItem['Taxable'] === 1 ? ' *' : '' ?>
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
                                            $<?= escapeOutput(
                                                number_format(
                                                    (float)$saleItem['LineTotal'],
                                                    2
                                                )
                                            ) ?>
                                        </td>

                                        <td>

                                            <form method="post">

                                                <input
                                                    type="hidden"
                                                    name="form_security_token"
                                                    value="<?= escapeOutput(getFormSecurityToken()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="receipt_id"
                                                    value="<?= (int)$receiptID ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="receipt_line_id"
                                                    value="<?= (int)$saleItem['ReceiptLineID'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="remove_product"
                                                    value="1"
                                                    class="button button-secondary sale-remove-button"
                                                >
                                                    <?=
                                                        (float)$saleItem['Quantity'] > 1
                                                            ? 'Remove 1'
                                                            : 'Remove Item'
                                                    ?>
                                                </button>

                                            </form>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>


                <div class="sale-subtotal">

                    <span>
                        Current Subtotal
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


                <div class="sale-actions">

                    <?php if (!empty($saleItems)): ?>

                        <a
                            href="<?= APPLICATION_URL ?>/sales/checkout.php?receipt=<?= (int)$receiptID ?>"
                            class="button button-primary"
                            data-sale-safe="true"
                        >
                            Checkout
                        </a>

                    <?php endif; ?>


                    <form
                        method="post"
                        id="cancelCurrentSaleForm"
                    >

                        <input
                            type="hidden"
                            name="form_security_token"
                            value="<?= escapeOutput(getFormSecurityToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="receipt_id"
                            value="<?= (int)$receiptID ?>"
                        >

                        <input
                            type="hidden"
                            name="register_id"
                            value="<?= (int)$saleRecord['RegisterID'] ?>"
                        >

                        <input
                            type="hidden"
                            id="sale_cancel_destination"
                            name="cancel_destination"
                            value=""
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
                        >
                            Cancel Sale
                        </button>

                    </form>


                    <form method="post">

                        <input
                            type="hidden"
                            name="form_security_token"
                            value="<?= escapeOutput(getFormSecurityToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="receipt_id"
                            value="<?= (int)$receiptID ?>"
                        >

                        <button
                            type="submit"
                            name="close_register"
                            value="1"
                            class="button button-secondary"
                        >
                            Close Register
                        </button>

                    </form>

                </div>

            </section>

        </div>

        <dialog
            id="saleLeaveDialog"
            class="checkout-leave-dialog"
        >

            <h2>
                Leave Current Sale?
            </h2>

            <p>
                This sale is still open. Choose what should happen.
            </p>

            <div class="checkout-leave-actions">

                <button
                    type="button"
                    id="saleStayButton"
                    class="button button-secondary"
                >
                    Continue Current Sale
                </button>

                <button
                    type="button"
                    id="saleSaveButton"
                    class="button button-primary"
                >
                    Save Sale and Leave
                </button>

                <button
                    type="button"
                    id="saleCancelButton"
                    class="button button-danger"
                >
                    Cancel Sale and Leave
                </button>

            </div>

        </dialog>

    <?php endif; ?>

</section>


<?php if ($receiptID > 0 && $saleRecord): ?>

<script>
(function () {

    const leaveDialog =
        document.getElementById(
            'saleLeaveDialog'
        );

    const cancelSaleForm =
        document.getElementById(
            'cancelCurrentSaleForm'
        );

    const cancelDestinationInput =
        document.getElementById(
            'sale_cancel_destination'
        );

    const stayButton =
        document.getElementById(
            'saleStayButton'
        );

    const saveButton =
        document.getElementById(
            'saleSaveButton'
        );

    const cancelButton =
        document.getElementById(
            'saleCancelButton'
        );

    let pendingDestination = '';
    let allowSaleLeave = false;

    const saleHasItems = <?= !empty($saleItems) ? 'true' : 'false' ?>;


    document.addEventListener(
        'click',
        function (event) {

            if (!saleHasItems) {
                return;
            }

            const link =
                event.target.closest(
                    'a[href]'
                );

            if (!link) {
                return;
            }

            if (
                link.dataset.saleSafe
                ===
                'true'
            ) {
                allowSaleLeave = true;
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

            leaveDialog.close();
        }
    );


    saveButton.addEventListener(
        'click',
        function () {

            allowSaleLeave = true;

            leaveDialog.close();

            window.location.href =
                pendingDestination;
        }
    );


    cancelButton.addEventListener(
        'click',
        function () {

            allowSaleLeave = true;

            cancelDestinationInput.value =
                pendingDestination;

            cancelSaleForm.requestSubmit();
        }
    );


    document.addEventListener(
        'submit',
        function () {

            allowSaleLeave = true;
        }
    );


    window.addEventListener(
        'beforeunload',
        function (event) {

            if (
                !saleHasItems
                ||
                allowSaleLeave
            ) {
                return;
            }

            event.preventDefault();

            event.returnValue = '';
        }
    );

})();
</script>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>