<?php // sales/sale_new.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireRegularPOSAccess();

$storeID = (int) ($_SESSION['store_id'] ?? 0);
$operatorID = (int) ($_SESSION['operator_id'] ?? 0);

$receiptID =
    isset($_GET['receipt'])
    ? (int) $_GET['receipt']
    : (int) ($_POST['receipt_id'] ?? 0);

$selectedRegisterID =
    isset($_GET['register'])
    ? (int) $_GET['register']
    : (int) ($_POST['register_id'] ?? 0);

$errorMessage = '';
$successMessage = '';

$registerRecords = [];
$productRecords = [];
$saleItems = [];

$saleRecord = null;
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
        (int) ($sale['ReceiptID'] ?? 0);
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


function removeWeightedSaleQuantity(
    PDO $databaseConnection,
    int $receiptID,
    int $receiptLineID,
    int $operatorID,
    ?float $requestedQuantity,
    bool $removeAll
): float {

    if (
        !$removeAll
        &&
        (
            $requestedQuantity === null
            ||
            $requestedQuantity <= 0
        )
    ) {
        throw new RuntimeException(
            'Enter a weight greater than zero.'
        );
    }

    try {

        $databaseConnection->beginTransaction();

        $roleStatement =
            $databaseConnection->prepare(
                '
                SELECT Role
                FROM operator
                WHERE OperatorID = :operatorID
                LIMIT 1
                '
            );

        $roleStatement->execute([
            ':operatorID' => $operatorID
        ]);

        $actingOperatorRole =
            (string) $roleStatement->fetchColumn();

        $lineStatement =
            $databaseConnection->prepare(
                '
                SELECT
                    sr.StoreID,
                    sr.OperatorID,
                    sr.Status,
                    srl.ProductID,
                    srl.Quantity,
                    srl.UnitTypeAtSale
                FROM salesreceipt sr
                INNER JOIN salesreceiptline srl
                    ON srl.ReceiptID = sr.ReceiptID
                WHERE sr.ReceiptID = :receiptID
                  AND srl.ReceiptLineID = :receiptLineID
                FOR UPDATE
                '
            );

        $lineStatement->execute([
            ':receiptID' => $receiptID,
            ':receiptLineID' => $receiptLineID
        ]);

        $lineRecord =
            $lineStatement->fetch();

        if (!$lineRecord) {
            throw new RuntimeException(
                'The selected sale item does not exist.'
            );
        }

        if (
            (int) $lineRecord['OperatorID'] !== $operatorID
            &&
            $actingOperatorRole !== 'Administrator'
        ) {
            throw new RuntimeException(
                'You cannot change another operator\'s sale.'
            );
        }

        if ($lineRecord['Status'] !== 'Open') {
            throw new RuntimeException(
                'Items can only be removed from an open sale.'
            );
        }

        if ($lineRecord['UnitTypeAtSale'] !== 'Pound') {
            throw new RuntimeException(
                'Weight removal is only available for weighted items.'
            );
        }

        $currentQuantity =
            round(
                (float) $lineRecord['Quantity'],
                3
            );

        $quantityToRemove =
            $removeAll
            ? $currentQuantity
            : min(
                $currentQuantity,
                round(
                    (float) $requestedQuantity,
                    3
                )
            );

        if ($quantityToRemove <= 0) {
            throw new RuntimeException(
                'Enter a weight greater than zero.'
            );
        }

        if ($currentQuantity > $quantityToRemove) {

            $updateLineStatement =
                $databaseConnection->prepare(
                    '
                    UPDATE salesreceiptline
                    SET Quantity =
                        Quantity - :quantityToRemove
                    WHERE ReceiptID = :receiptID
                      AND ReceiptLineID = :receiptLineID
                    '
                );

            $updateLineStatement->execute([
                ':quantityToRemove' => $quantityToRemove,
                ':receiptID' => $receiptID,
                ':receiptLineID' => $receiptLineID
            ]);

        } else {

            $deleteLineStatement =
                $databaseConnection->prepare(
                    '
                    DELETE FROM salesreceiptline
                    WHERE ReceiptID = :receiptID
                      AND ReceiptLineID = :receiptLineID
                    '
                );

            $deleteLineStatement->execute([
                ':receiptID' => $receiptID,
                ':receiptLineID' => $receiptLineID
            ]);
        }

        $inventoryStatement =
            $databaseConnection->prepare(
                '
                UPDATE storeinventory
                SET StockQuantity =
                    StockQuantity + :quantityToRemove
                WHERE StoreID = :storeID
                  AND ProductID = :productID
                '
            );

        $inventoryStatement->execute([
            ':quantityToRemove' => $quantityToRemove,
            ':storeID' => (int) $lineRecord['StoreID'],
            ':productID' => (int) $lineRecord['ProductID']
        ]);

        $receiptStatement =
            $databaseConnection->prepare(
                '
                UPDATE salesreceipt
                SET
                    SubtotalAmount = (
                        SELECT COALESCE(
                            ROUND(
                                SUM(
                                    (Quantity * UnitPrice)
                                    - LineDiscountAmount
                                ),
                                2
                            ),
                            0.00
                        )
                        FROM salesreceiptline
                        WHERE ReceiptID = :subtotalReceiptID
                    ),
                    TaxableSubtotalAmount = 0.00,
                    TaxAmount = 0.00,
                    TotalAmount = 0.00
                WHERE ReceiptID = :receiptID
                '
            );

        $receiptStatement->execute([
            ':subtotalReceiptID' => $receiptID,
            ':receiptID' => $receiptID
        ]);

        $journalStatement =
            $databaseConnection->prepare(
                '
                UPDATE transactionjournal tj
                INNER JOIN salesreceipt sr
                    ON sr.ReceiptID = tj.ReceiptID
                SET
                    tj.LineCount = (
                        SELECT COUNT(*)
                        FROM salesreceiptline
                        WHERE ReceiptID = :lineCountReceiptID
                    ),
                    tj.ItemQuantity = (
                        SELECT COALESCE(
                            SUM(Quantity),
                            0
                        )
                        FROM salesreceiptline
                        WHERE ReceiptID = :itemQuantityReceiptID
                    ),
                    tj.SubtotalAmount =
                        sr.SubtotalAmount,
                    tj.DiscountAmount =
                        sr.ReceiptDiscountAmount
                WHERE tj.ReceiptID =
                    :journalReceiptID
                '
            );

        $journalStatement->execute([
            ':lineCountReceiptID' => $receiptID,
            ':itemQuantityReceiptID' => $receiptID,
            ':journalReceiptID' => $receiptID
        ]);

        $databaseConnection->commit();

        return $quantityToRemove;

    } catch (Throwable $exception) {

        if ($databaseConnection->inTransaction()) {
            $databaseConnection->rollBack();
        }

        throw $exception;
    }
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
              AND r.RegisterNumber <> 1
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
        } elseif (isset($_POST['close_selected_register'])) {
            $selectedRegister = null;

            foreach ($registerRecords as $registerRecord) {
                if (
                    (int) $registerRecord['RegisterID']
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
                    'Select a register.';

            } elseif (
                empty(
                $selectedRegister['OpenReceiptID']
            )
            ) {
                $errorMessage =
                    'The selected register does not have an open session.';

            } elseif (
                !operatorIsAdministrator()
                &&
                (int) $selectedRegister['OpenOperatorID']
                !==
                $operatorID
            ) {
                $errorMessage =
                    'You can only close a register assigned to your own open session.';

            } else {
                try {
                    voidRegisterSale(
                        $databaseConnection,
                        (int) $selectedRegister['OpenReceiptID'],
                        $operatorID
                    );
                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?closed=1'
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

        } elseif (isset($_POST['select_register'])) {

            $selectedRegister = null;

            foreach ($registerRecords as $registerRecord) {

                if (
                    (int) $registerRecord['RegisterID']
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
                    (int) $selectedRegister['OpenOperatorID']
                    ===
                    $operatorID
                ) {

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?receipt='
                        . (int) $selectedRegister['OpenReceiptID']
                    );

                    exit;

                } else {

                    if (operatorIsAdministrator()) {

                        $errorMessage =
                            'That register is currently in use. Use Close Register to release it.';

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
                                . '/sales/sale_new.php?receipt='
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
                    (int) (
                        $_POST['open_receipt_id']
                        ?? 0
                    );

                try {

                    voidRegisterSale(
                        $databaseConnection,
                        $clearReceiptID,
                        $operatorID
                    );

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?cleared=1'
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

        } elseif (
            isset($_POST['add_product'])
            &&
            $receiptID > 0
        ) {

            $productID =
                (int) (
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

                    try {

                        $statement =
                            $databaseConnection->prepare(
                                '
                                SELECT ProductID
                                FROM vw_pos_products
                                WHERE StoreID =
                                    :storeID
                                  AND (
                                      UPC =
                                          :upcCode
                                      OR
                                      PLUCode =
                                          :pluCode
                                  )
                                LIMIT 1
                                '
                            );

                        $statement->execute([
                            ':storeID' =>
                                $storeID,

                            ':upcCode' =>
                                $productCode,

                            ':pluCode' =>
                                $productCode
                        ]);

                        $product =
                            $statement->fetch();

                        $productID =
                            (int) (
                                $product['ProductID']
                                ?? 0
                            );

                        if ($productID <= 0) {

                            $errorMessage =
                                'The product code was not found.';
                        }

                    } catch (PDOException $exception) {

                        error_log(
                            $exception->getMessage()
                        );

                        $errorMessage =
                            getSafeDatabaseErrorMessage(
                                $exception,
                                'The product code could not be checked.'
                            );
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
                            round(
                                (float) $quantity,
                                3
                            ),

                        ':operatorID' =>
                            $operatorID
                    ]);

                    $statement->closeCursor();

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?receipt='
                        . $receiptID
                        . '&added=1'
                    );

                    exit;

                } catch (PDOException $exception) {

                    $errorMessage =
                        getSafeDatabaseErrorMessage(
                            $exception,
                            'The product could not be added.'
                        );
                }
            }

        } elseif (
            (
                isset($_POST['remove_product'])
                ||
                isset($_POST['remove_all_product'])
            )
            &&
            $receiptID > 0
        ) {

            $receiptLineID =
                (int) (
                    $_POST['receipt_line_id']
                    ?? 0
                );

            $removeAllProduct =
                isset($_POST['remove_all_product']);

            $removeQuantityText =
                trim(
                    (string) (
                        $_POST['remove_quantity']
                        ?? ''
                    )
                );

            $isWeightedRemoval =
                $removeAllProduct
                ||
                $removeQuantityText !== '';

            if ($isWeightedRemoval) {

                $removeQuantity =
                    $removeAllProduct
                    ? null
                    : filter_var(
                        $removeQuantityText,
                        FILTER_VALIDATE_FLOAT
                    );

                if (
                    !$removeAllProduct
                    &&
                    (
                        $removeQuantity === false
                        ||
                        $removeQuantity <= 0
                    )
                ) {

                    $errorMessage =
                        'Enter a weight greater than zero.';

                } else {

                    try {

                        $removedQuantity =
                            removeWeightedSaleQuantity(
                                $databaseConnection,
                                $receiptID,
                                $receiptLineID,
                                $operatorID,
                                $removeQuantity === null
                                ? null
                                : (float) $removeQuantity,
                                $removeAllProduct
                            );

                        $successMessage =
                            number_format(
                                $removedQuantity,
                                3
                            )
                            . ' lb was removed from the sale.';

                    } catch (PDOException $exception) {

                        $errorMessage =
                            getSafeDatabaseErrorMessage(
                                $exception,
                                'The weighted product could not be removed.'
                            );

                    } catch (RuntimeException $exception) {

                        $errorMessage =
                            $exception->getMessage();
                    }
                }

            } else {

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
            }

        } elseif (
            isset($_POST['cancel_sale'])
            &&
            $receiptID > 0
        ) {

            $cancelRegisterID =
                (int) (
                    $_POST['register_id']
                    ?? 0
                );

            $cancelItemCountStatement =
                $databaseConnection->prepare(
                    '
                    SELECT COUNT(*)
                    FROM salesreceiptline
                    WHERE ReceiptID =
                        :receiptID
                    '
                );

            $cancelItemCountStatement->execute([
                ':receiptID' =>
                    $receiptID
            ]);

            $cancelItemCount =
                (int) $cancelItemCountStatement->fetchColumn();

            if ($cancelItemCount <= 0) {

                $errorMessage =
                    'A sale cannot be cancelled until at least one item has been added.';

            } else {

                try {

                    voidRegisterSale(
                        $databaseConnection,
                        $receiptID,
                        $operatorID
                    );

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
                        . '/sales/sale_new.php?receipt='
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
            }

        } elseif (
            isset($_POST['close_register'])
            &&
            $receiptID > 0
        ) {

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

                voidRegisterSale(
                    $databaseConnection,
                    $receiptID,
                    $operatorID
                );

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
        isset($_GET['added'])
        &&
        $_GET['added'] === '1'
    ) {

        $successMessage =
            'Product quantity added to the sale.';

    } elseif (
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
                . '/sales/sale_new.php'
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

    $receiptID = 0;
    $saleRecord = null;
    $saleItems = [];
    $productRecords = [];
}

$pageTitle =
    'Select Register';

if (
    $receiptID > 0
    &&
    $saleRecord
) {

    $pageTitle =
        'Register #'
        . $saleRecord['RegisterNumber'];
}

$currentSection =
    'sales';

$currentPage =
    'new';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel sales-panel">

    <?php if ($receiptID <= 0 || !$saleRecord): ?>

        <?php if ($errorMessage !== ''): ?>

            <div class="message message-error">
                <?= escapeOutput($errorMessage) ?>
            </div>

        <?php endif; ?>

        <section class="sale-start-panel">

            <?php if ($successMessage !== ''): ?>

                <div class="message message-success">
                    <?= escapeOutput($successMessage) ?>
                </div>

            <?php endif; ?>

            <form method="post">

                <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

                <div class="form-field">

                    <label for="register_id">
                        Checkout Station
                    </label>

                    <select id="register_id" name="register_id" required>

                        <option value="">
                            Choose a checkout station
                        </option>

                        <?php foreach ($registerRecords as $registerRecord): ?>

                            <?php

                            $openReceiptID =
                                (int) (
                                    $registerRecord['OpenReceiptID']
                                    ?? 0
                                );

                            $openOperatorID =
                                (int) (
                                    $registerRecord['OpenOperatorID']
                                    ?? 0
                                );

                            $inUseByOtherOperator =
                                $openReceiptID > 0
                                &&
                                $openOperatorID !==
                                $operatorID;

                            ?>

                            <option value="<?= (int) $registerRecord['RegisterID'] ?>"
                                data-open-receipt-id="<?= $openReceiptID ?>" data-open-operator-id="<?= $openOperatorID ?>"
                                <?=
                                    $selectedRegisterID
                                    ===
                                    (int) $registerRecord['RegisterID']
                                    ? 'selected'
                                    : ''
                                    ?>
                                <?= $inUseByOtherOperator && !operatorIsAdministrator() ? 'disabled' : '' ?>>

                                Register #<?= escapeOutput($registerRecord['RegisterNumber']) ?>

                                <?php if (trim((string) $registerRecord['RegisterName']) !== ''): ?>

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

                <div class="form-actions sale-register-actions">

                    <button type="submit" id="openSelectedRegisterButton" name="select_register" value="1"
                        class="button button-primary">
                        Open Register
                    </button>
                    <button type="submit" id="closeSelectedRegisterButton" name="close_selected_register" value="1"
                        class="button button-danger" hidden
                        onclick="return window.confirm('Close this register? Any open transaction will be cancelled and its items will be returned to inventory.');">
                        Close Register
                    </button>

                </div>

            </form>

        </section>

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
                    Started
                </span>

                <strong>
                    <?= escapeOutput(
                        $saleRecord['TransactionDateTime']
                    ) ?>
                </strong>

            </div>

        </section>

        <div class="sale-workspace">

            <section class="sale-product-area">

                <form method="post" class="sale-scan-form">

                    <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

                    <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                    <input type="hidden" id="scanner_product_id" name="product_id" value="0">

                    <?php if ($errorMessage !== ''): ?>
                        <div class="message message-error sale-scan-message">
                            <?= escapeOutput($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage !== ''): ?>
                        <div class="message message-success sale-scan-message">
                            <?= escapeOutput($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-field sale-barcode-field">

                        <label for="product_code">
                            Barcode / Product Code
                        </label>

                        <input type="text" id="product_code" name="product_code" value="" maxlength="20" autocomplete="off"
                            autofocus>

                    </div>

                    <div class="form-field sale-quantity-field">

                        <label for="quantity" id="sale_quantity_label">
                            Quantity
                        </label>

                        <input type="number" id="quantity" name="quantity" value="1" min="0.001" step="0.001"
                            inputmode="decimal" required>

                    </div>

                    <button type="submit" name="add_product" value="1" class="button button-primary">
                        Add Product
                    </button>

                </form>

                <h2>
                    Products
                </h2>

                <div class="sale-product-grid">

                    <?php foreach ($productRecords as $productRecord): ?>

                        <form method="post" class="sale-product-form">

                            <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

                            <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                            <input type="hidden" name="product_id" value="<?= (int) $productRecord['ProductID'] ?>">

                            <input type="hidden" name="quantity" value="1">

                            <?php

                            $productIsWeighted =
                                $productRecord['UnitType']
                                ===
                                'Pound';

                            $productCodeForButton =
                                trim((string) $productRecord['UPC']) !== ''
                                ? $productRecord['UPC']
                                : $productRecord['PLUCode'];

                            ?>

                            <button type="<?= $productIsWeighted ? 'button' : 'submit' ?>"
                                name="<?= $productIsWeighted ? '' : 'add_product' ?>"
                                value="<?= $productIsWeighted ? '' : '1' ?>"
                                class="sale-product-button<?= $productIsWeighted ? ' sale-weighted-product-button' : '' ?>"
                                data-product-id="<?= (int) $productRecord['ProductID'] ?>"
                                data-product-code="<?= escapeOutput($productCodeForButton) ?>"
                                data-upc="<?= escapeOutput((string) $productRecord['UPC']) ?>"
                                data-plu="<?= escapeOutput((string) $productRecord['PLUCode']) ?>"
                                data-unit-type="<?= escapeOutput($productRecord['UnitType']) ?>" <?= (float) $productRecord['StockQuantity'] <= 0 ? 'disabled' : '' ?>>

                                <strong>
                                    <?= escapeOutput(
                                        $productRecord['ProductName']
                                    ) ?>
                                </strong>

                                <?php if (trim((string) $productRecord['UPC']) !== ''): ?>

                                    <span class="sale-product-code">
                                        UPC# <?= escapeOutput($productRecord['UPC']) ?>
                                    </span>

                                <?php elseif (trim((string) $productRecord['PLUCode']) !== ''): ?>

                                    <span class="sale-product-code">
                                        PLU# <?= escapeOutput($productRecord['PLUCode']) ?>
                                    </span>

                                <?php endif; ?>

                                <span>
                                    <?= escapeOutput(
                                        $productRecord['DepartmentName']
                                    ) ?>
                                </span>

                                <span>
                                    $<?= escapeOutput(
                                        number_format(
                                            (float) $productRecord['RetailPrice'],
                                            2
                                        )
                                    ) ?>
                                </span>

                                <?php if ((int) $productRecord['Taxable'] === 1): ?>

                                    <span>
                                        Taxable
                                    </span>

                                <?php endif; ?>

                                <span>
                                    Stock:
                                    <?= escapeOutput(
                                        number_format(
                                            (float) $productRecord['StockQuantity'],
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

                                            <?= (int) $saleItem['Taxable'] === 1 ? ' *' : '' ?>

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

                                            $<?= escapeOutput(
                                                number_format(
                                                    (float) $saleItem['LineTotal'],
                                                    2
                                                )
                                            ) ?>

                                        </td>

                                        <td>

                                            <?php if ($saleItem['UnitType'] === 'Pound'): ?>

                                                <?php
                                                $saleItemWeight =
                                                    round(
                                                        (float) $saleItem['Quantity'],
                                                        3
                                                    );

                                                $defaultRemoveWeight =
                                                    min(
                                                        0.1,
                                                        $saleItemWeight
                                                    );
                                                ?>

                                                <form method="post" class="sale-weight-remove-form">

                                                    <input type="hidden" name="form_security_token"
                                                        value="<?= escapeOutput(getFormSecurityToken()) ?>">

                                                    <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                                                    <input type="hidden" name="receipt_line_id"
                                                        value="<?= (int) $saleItem['ReceiptLineID'] ?>">

                                                    <input type="number" name="remove_quantity"
                                                        value="<?= escapeOutput(number_format($defaultRemoveWeight, 3, '.', '')) ?>"
                                                        min="<?= escapeOutput(number_format($defaultRemoveWeight, 3, '.', '')) ?>"
                                                        max="<?= escapeOutput(number_format($saleItemWeight, 3, '.', '')) ?>"
                                                        step="0.1" inputmode="decimal" class="sale-remove-weight-input"
                                                        aria-label="Weight to remove in pounds"
                                                        title="Weight to remove in pounds">

                                                    <button type="submit" name="remove_product" value="1"
                                                        class="button button-secondary sale-remove-weight-button">
                                                        Remove
                                                    </button>

                                                    <button type="submit" name="remove_all_product" value="1"
                                                        class="button button-secondary sale-remove-all-button"
                                                        title="Remove all weight for this item">
                                                        All
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <form method="post">

                                                    <input type="hidden" name="form_security_token"
                                                        value="<?= escapeOutput(getFormSecurityToken()) ?>">

                                                    <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                                                    <input type="hidden" name="receipt_line_id"
                                                        value="<?= (int) $saleItem['ReceiptLineID'] ?>">

                                                    <button type="submit" name="remove_product" value="1"
                                                        class="button button-secondary sale-remove-button">
                                                        <?=
                                                            (float) $saleItem['Quantity'] > 1
                                                            ? 'Remove 1'
                                                            : 'Remove Item'
                                                            ?>
                                                    </button>

                                                </form>

                                            <?php endif; ?>

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
                                (float) ($saleRecord['SubtotalAmount'] ?? 0),
                                2
                            )
                        ) ?>
                    </strong>

                </div>

                <div class="sale-actions">

                    <?php if (!empty($saleItems)): ?>

                        <a href="<?= APPLICATION_URL ?>/sales/sale_checkout.php?receipt=<?= (int) $receiptID ?>"
                            class="button button-primary" data-sale-safe="true">
                            Checkout
                        </a>

                    <?php endif; ?>

                    <?php if (!empty($saleItems)): ?>

                        <form method="post" id="cancelCurrentSaleForm">

                            <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

                            <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                            <input type="hidden" name="register_id" value="<?= (int) $saleRecord['RegisterID'] ?>">

                            <input type="hidden" name="cancel_sale" value="1">

                            <button type="submit" name="cancel_sale" value="1" class="button button-danger"
                                onclick="return window.confirm('Cancel this sale? All scanned items will be returned to inventory.');">
                                Cancel Sale
                            </button>

                        </form>

                    <?php endif; ?>

                    <form method="post" id="closeCurrentRegisterForm" class="sale-close-register-form">

                        <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

                        <input type="hidden" name="receipt_id" value="<?= (int) $receiptID ?>">

                        <input type="hidden" id="sale_close_destination" name="close_destination" value="">

                        <input type="hidden" name="close_register" value="1">

                        <button type="submit" name="close_register" value="1" class="button button-secondary"
                            onclick="return window.confirm('Close this register? The current transaction will be cancelled and all scanned items will be returned to inventory.');">
                            Close Register
                        </button>

                    </form>

                </div>

            </section>

        </div>

        <dialog id="saleLeaveDialog" class="checkout-leave-dialog">

            <h2>
                Leave Current Transaction?
            </h2>

            <p>
                Items have already been scanned. Choose what should happen before leaving this screen.
            </p>

            <div class="checkout-leave-actions">

                <button type="button" id="saleStayButton" class="button button-secondary">
                    Stay on Transaction
                </button>

                <button type="button" id="saleSaveButton" class="button button-primary">
                    Save Transaction and Leave
                </button>

                <button type="button" id="saleCloseButton" class="button button-danger">
                    Close Register and Leave
                </button>

            </div>

        </dialog>

    <?php endif; ?>

</section>

<?php if ($receiptID <= 0): ?>

    <script>
        (function () {

            const registerSelect =
                document.getElementById(
                    'register_id'
                );

            const openButton =
                document.getElementById(
                    'openSelectedRegisterButton'
                );

            const closeButton =
                document.getElementById(
                    'closeSelectedRegisterButton'
                );

            const currentOperatorID =
                <?= $operatorID ?>;

            const currentOperatorIsAdministrator =
                <?= operatorIsAdministrator() ? 'true' : 'false' ?>;

            function updateRegisterActions() {

                if (!registerSelect) {
                    return;
                }

                const selectedOption =
                    registerSelect.options[
                    registerSelect.selectedIndex
                    ];

                const openReceiptID =
                    parseInt(
                        selectedOption?.dataset.openReceiptId
                        ||
                        '0',
                        10
                    );

                const openOperatorID =
                    parseInt(
                        selectedOption?.dataset.openOperatorId
                        ||
                        '0',
                        10
                    );

                const registerIsOpen =
                    openReceiptID > 0;

                const belongsToCurrentOperator =
                    registerIsOpen
                    &&
                    openOperatorID === currentOperatorID;

                const hasSelectedRegister =
                    registerSelect.value !== '';

                const administratorViewingOtherOperator =
                    hasSelectedRegister
                    &&
                    registerIsOpen
                    &&
                    !belongsToCurrentOperator
                    &&
                    currentOperatorIsAdministrator;

                if (openButton) {
                    openButton.hidden =
                        administratorViewingOtherOperator;

                    openButton.disabled =
                        !hasSelectedRegister
                        ||
                        (
                            registerIsOpen
                            &&
                            !belongsToCurrentOperator
                        );
                }

                if (closeButton) {
                    const ownerCanClose =
                        hasSelectedRegister
                        &&
                        registerIsOpen
                        &&
                        belongsToCurrentOperator;

                    const administratorCanClose =
                        administratorViewingOtherOperator;

                    closeButton.hidden =
                        !(
                            ownerCanClose
                            ||
                            administratorCanClose
                        );
                }
            }

            if (registerSelect) {

                registerSelect.addEventListener(
                    'change',
                    updateRegisterActions
                );

                updateRegisterActions();
            }
        })();
    </script>

<?php endif; ?>

<?php if ($receiptID > 0 && $saleRecord): ?>

    <script>
        (function () {

            const leaveDialog =
                document.getElementById(
                    'saleLeaveDialog'
                );

            const closeRegisterForm =
                document.getElementById(
                    'closeCurrentRegisterForm'
                );

            const closeDestinationInput =
                document.getElementById(
                    'sale_close_destination'
                );

            const stayButton =
                document.getElementById(
                    'saleStayButton'
                );

            const saveButton =
                document.getElementById(
                    'saleSaveButton'
                );

            const closeButton =
                document.getElementById(
                    'saleCloseButton'
                );

            let pendingDestination = '';

            let allowSaleLeave = false;

            const saleHasItems =
                <?= !empty($saleItems) ? 'true' : 'false' ?>;

            const scannerProductID =
                document.getElementById(
                    'scanner_product_id'
                );

            const productCodeInput =
                document.getElementById(
                    'product_code'
                );

            const quantityInput =
                document.getElementById(
                    'quantity'
                );

            const quantityLabel =
                document.getElementById(
                    'sale_quantity_label'
                );

            const productButtons =
                document.querySelectorAll(
                    '.sale-product-button'
                );

            const weightedProductButtons =
                document.querySelectorAll(
                    '.sale-weighted-product-button'
                );

            let quantityIsWeighted = false;

            function setQuantityMode(
                isWeighted,
                clearWeightValue
            ) {

                if (isWeighted) {

                    quantityLabel.textContent =
                        'Weight (lb)';

                    quantityInput.min =
                        '0.1';

                    quantityInput.step =
                        '0.1';

                    if (
                        !quantityIsWeighted
                        &&
                        clearWeightValue
                    ) {
                        quantityInput.value =
                            '';
                    }

                    quantityInput.placeholder =
                        '0.0';

                } else {

                    quantityLabel.textContent =
                        'Quantity';

                    quantityInput.min =
                        '0.001';

                    quantityInput.step =
                        '0.001';

                    if (
                        quantityIsWeighted
                        &&
                        quantityInput.value === ''
                    ) {
                        quantityInput.value =
                            '1';
                    }

                    quantityInput.placeholder =
                        '';
                }

                quantityIsWeighted =
                    isWeighted;
            }

            function findProductButtonByCode(
                productCode
            ) {

                const normalizedCode =
                    productCode.trim();

                if (normalizedCode === '') {
                    return null;
                }

                for (const button of productButtons) {

                    const upc =
                        (
                            button.dataset.upc
                            ||
                            ''
                        ).trim();

                    const plu =
                        (
                            button.dataset.plu
                            ||
                            ''
                        ).trim();

                    if (
                        normalizedCode === upc
                        ||
                        normalizedCode === plu
                    ) {
                        return button;
                    }
                }

                return null;
            }

            function syncQuantityModeFromCode() {

                const matchingButton =
                    findProductButtonByCode(
                        productCodeInput.value
                    );

                scannerProductID.value =
                    matchingButton
                    ? (
                        matchingButton.dataset.productId
                        ||
                        '0'
                    )
                    : '0';

                setQuantityMode(
                    matchingButton !== null
                    &&
                    matchingButton.dataset.unitType
                    ===
                    'Pound',
                    true
                );
            }

            weightedProductButtons.forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            scannerProductID.value =
                                button.dataset.productId
                                ||
                                '0';

                            productCodeInput.value =
                                button.dataset.productCode
                                ||
                                '';

                            setQuantityMode(
                                true,
                                true
                            );

                            quantityInput.focus();
                        }
                    );
                }
            );

            productCodeInput.addEventListener(
                'input',
                syncQuantityModeFromCode
            );

            quantityInput.addEventListener(
                'focus',
                syncQuantityModeFromCode
            );

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

                    allowSaleLeave = true;

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

                    allowSaleLeave = true;

                    closeDestinationInput.value =
                        pendingDestination;

                    closeRegisterForm.requestSubmit();
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