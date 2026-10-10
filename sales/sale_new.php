<?php // sales/sale_new.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../includes/discounts.php';
require_once __DIR__ . '/../includes/item_entry.php';

requireRegister();

$storeID =
    signedInStoreID();
$operatorID =
    signedInOperatorID();

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
$assistingOther = false;

$saleTotals = [];
$discountRecords = [];
$couponRecords = [];
$discountReasons = [];
$discountForm = defaultDiscountForm();
$discountPanelOpen = false;

function startRegisterSale(
    PDO $db,
    int $storeID,
    int $registerID,
    int $operatorID
): int {

    $statement =
        $db->prepare(
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
    PDO $db,
    int $receiptID,
    int $operatorID
): void {

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
        ':receiptID' => $receiptID,
        ':operatorID' => $operatorID
    ]);

    $statement->closeCursor();
}


try {

    $db =
        connectDatabase();

    $registerStatement =
        $db->prepare(
            '
            SELECT
                r.RegisterID,
                r.RegisterNumber,
                r.RegisterName,
                r.RegisterType,
                sr.ReceiptID
                    AS OpenReceiptID,
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
                !canSupervise()
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
                        $db,
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
                        databaseMessage(
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
                ($selectedRegister['RegisterType'] ?? 'Regular') !== 'Regular'
                || (int) $selectedRegister['RegisterNumber'] === 1
            ) {
                $errorMessage =
                    'Reserved for Express. Select a Regular register.';

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

                    if (canSupervise()) {

                        $errorMessage =
                            'That register is currently in use. Use Close Register to release it.';

                    } else {

                        $errorMessage =
                            'That register is currently in use by another employee.';
                    }
                }

            } else {

                $ownSaleStatement =
                    $db->prepare(
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

                $ownSaleStatement->execute([
                    ':storeID' => $storeID,
                    ':operatorID' => $operatorID
                ]);

                $operatorOpenSale =
                    $ownSaleStatement->fetch();

                if ($operatorOpenSale) {

                    $errorMessage =
                        'You already have an open session on Register '
                        . $operatorOpenSale['RegisterNumber']
                        . '. Select that register first.';

                } else {

                    try {

                        $newReceiptID =
                            startRegisterSale(
                                $db,
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
                            databaseMessage(
                                $exception,
                                'The register could not be opened.'
                            );
                    }
                }
            }

        } elseif (isset($_POST['clear_register'])) {

            if (!canSupervise()) {

                $errorMessage =
                    "Supervisor access is required to clear another employee's register.";

            } else {

                $clearReceiptID =
                    (int) (
                        $_POST['open_receipt_id']
                        ?? 0
                    );

                try {

                    voidRegisterSale(
                        $db,
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
                        databaseMessage(
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
                            $db->prepare(
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

                        if ($productID <= 0 && isCouponCode($productCode)) {
                            $couponStatement =
                                $db->prepare(
                                    'CALL sp_add_coupon_to_sale(:receiptID, :operatorID, :couponCode)'
                                );
                            $couponStatement->execute([
                                ':receiptID' => $receiptID,
                                ':operatorID' => $operatorID,
                                ':couponCode' => $productCode
                            ]);
                            $couponStatement->closeCursor();
                            header(
                                'Location: '
                                . APPLICATION_URL
                                . '/sales/sale_new.php?receipt='
                                . $receiptID
                                . '&coupon=added'
                            );
                            exit;
                        } elseif ($productID <= 0) {
                            $errorMessage =
                                'The product code was not found.';
                        }

                    } catch (PDOException $exception) {

                        error_log(
                            $exception->getMessage()
                        );

                        $errorMessage =
                            databaseMessage(
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
                    if ($stockCapNotice !== '') {
                        $_SESSION['stock_cap_notice'] = $stockCapNotice;
                    }

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?receipt='
                        . $receiptID
                        . '&added=1'
                    );

                    exit;

                } catch (StockLimitException $exception) {
                    $errorMessage = $exception->getMessage();
                } catch (PDOException $exception) {

                    $errorMessage =
                        databaseMessage(
                            $exception,
                            'The product could not be added.'
                        );
                }
            }

        } elseif (
            isset($_POST['remove_coupon'])
            &&
            $receiptID > 0
        ) {
            try {
                $statement =
                    $db->prepare(
                        'CALL sp_remove_coupon_from_sale(:receiptID, :operatorID, :saleCouponID)'
                    );
                $statement->execute([
                    ':receiptID' => $receiptID,
                    ':operatorID' => $operatorID,
                    ':saleCouponID' => (int) ($_POST['sale_coupon_id'] ?? 0)
                ]);
                $statement->closeCursor();
                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/sale_new.php?receipt='
                    . $receiptID
                    . '&coupon=removed'
                );
                exit;
            } catch (PDOException $exception) {
                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The coupon could not be removed.'
                    );
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

            $removeQuantityText =
                trim(
                    (string) (
                        $_POST['remove_quantity']
                        ?? ''
                    )
                );

            // Remove All sends a large amount, blank means Remove 1
            if (isset($_POST['remove_all_product'])) {
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
                $removeQuantity === false
                ||
                (
                    $removeQuantity !== null
                    &&
                    $removeQuantity <= 0
                )
            ) {

                $errorMessage =
                    'Enter a weight greater than zero.';

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
                            $receiptID,

                        ':receiptLineID' =>
                            $receiptLineID,

                        ':operatorID' =>
                            $operatorID,

                        ':removeQuantity' =>
                            $removeQuantity
                    ]);

                    $statement->closeCursor();

                    $successMessage =
                        'The selected quantity was removed from the sale.';

                } catch (PDOException $exception) {

                    $errorMessage =
                        databaseMessage(
                            $exception,
                            'The product could not be removed.'
                        );
                }
            }

        } elseif (
            isset($_POST['apply_discount'])
            &&
            $receiptID > 0
        ) {

            $discountForm = readDiscountForm($_POST);

            $discountPanelOpen = true;

            $discountCheck =
                checkDiscountForm(
                    $db,
                    $discountForm
                );

            if ($discountCheck['error'] !== '') {

                $errorMessage =
                    $discountCheck['error'];

            } else {

                try {

                    $statement =
                        $db->prepare(
                            '
                            CALL sp_apply_discount(
                                :receiptID,
                                :operatorID,
                                :discountKind,
                                :taxTiming,
                                :discountValue,
                                :reason,
                                :saveReason
                            )
                            '
                        );

                    $statement->execute([
                        ':receiptID' =>
                            $receiptID,

                        ':operatorID' =>
                            $operatorID,

                        ':discountKind' =>
                            $discountForm['kind'],

                        ':taxTiming' =>
                            $discountForm['timing'],

                        ':discountValue' =>
                            $discountForm['value'],

                        ':reason' =>
                            $discountCheck['reason'],

                        ':saveReason' =>
                            $discountCheck['save']
                    ]);

                    $statement->closeCursor();

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/sales/sale_new.php?receipt='
                        . $receiptID
                        . '&discount=added'
                    );

                    exit;

                } catch (PDOException $exception) {

                    $errorMessage =
                        databaseMessage(
                            $exception,
                            'The discount could not be applied.'
                        );
                }
            }

        } elseif (
            isset($_POST['remove_discount'])
            &&
            $receiptID > 0
        ) {

            try {

                $statement =
                    $db->prepare(
                        '
                        CALL sp_remove_discount(
                            :receiptID,
                            :operatorID,
                            :discountID
                        )
                        '
                    );

                $statement->execute([
                    ':receiptID' =>
                        $receiptID,

                    ':operatorID' =>
                        $operatorID,

                    ':discountID' =>
                        (int) ($_POST['discount_id'] ?? 0)
                ]);

                $statement->closeCursor();

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/sales/sale_new.php?receipt='
                    . $receiptID
                    . '&discount=removed'
                );

                exit;

            } catch (PDOException $exception) {

                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The discount could not be removed.'
                    );
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

            $itemCountStatement =
                $db->prepare(
                    '
                    SELECT COUNT(*)
                    FROM salesreceiptline
                    WHERE ReceiptID =
                        :receiptID
                    '
                );

            $itemCountStatement->execute([
                ':receiptID' =>
                    $receiptID
            ]);

            $cancelItemCount =
                (int) $itemCountStatement->fetchColumn();

            if ($cancelItemCount <= 0) {

                $errorMessage =
                    'A sale cannot be cancelled until at least one item has been added.';

            } else {

                try {

                    voidRegisterSale(
                        $db,
                        $receiptID,
                        $operatorID
                    );

                    $newReceiptID =
                        startRegisterSale(
                            $db,
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
                        databaseMessage(
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
                    $db,
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
                    databaseMessage(
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
    if (!empty($_SESSION['stock_cap_notice'])) {
        $successMessage = $_SESSION['stock_cap_notice'];
        unset($_SESSION['stock_cap_notice']);
    }

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

    } elseif (
        isset($_GET['coupon'])
        &&
        $_GET['coupon'] === 'added'
    ) {
        $successMessage =
            'The coupon was added to the sale.';
    } elseif (
        isset($_GET['coupon'])
        &&
        $_GET['coupon'] === 'removed'
    ) {
        $successMessage =
            'The coupon was removed from the sale.';
    } elseif (
        isset($_GET['discount'])
        &&
        $_GET['discount'] === 'added'
    ) {

        $successMessage =
            'The discount was added to the sale.';

    } elseif (
        isset($_GET['discount'])
        &&
        $_GET['discount'] === 'removed'
    ) {

        $successMessage =
            'The discount was removed from the sale.';
    }

    if ($receiptID > 0) {

        $statement =
            $db->prepare(
                '
                SELECT
                    sr.ReceiptID,
                    sr.TransactionNumber,
                    sr.RegisterID,
                    r.RegisterNumber,
                    r.RegisterName,
                    sr.TransactionDateTime,
                    sr.Status,
                    sr.SubtotalAmount,
                    sr.OperatorID AS OwnerID,
                    CONCAT(ow.FirstName, \' \', ow.LastName) AS OwnerName
                FROM salesreceipt sr
                JOIN register r
                    ON r.RegisterID =
                        sr.RegisterID
                JOIN operator ow
                    ON ow.OperatorID =
                        sr.OperatorID
                WHERE sr.ReceiptID =
                    :receiptID
                  AND sr.StoreID =
                    :storeID
                  AND (
                      sr.OperatorID = :operatorID
                      OR :assisting = 1
                  )
                LIMIT 1
                '
            );

        $statement->execute([
            ':receiptID' =>
                $receiptID,

            ':storeID' =>
                $storeID,

            ':operatorID' =>
                $operatorID,

            // A supervisor who chose Assist for this one sale may work on it
            ':assisting' =>
                isAssisting($receiptID) ? 1 : 0
        ]);

        $saleRecord =
            $statement->fetch();

        $assistingOther =
            $saleRecord
            && (int) $saleRecord['OwnerID'] !== $operatorID;

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
            $db->prepare(
                '
                SELECT
                    sd.ReceiptLineID,
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
                WHERE sd.ReceiptID =
                    :receiptID
                ORDER BY sd.LineNumber
                '
            );

        $statement->execute([
            ':receiptID' =>
                $receiptID
        ]);

        $saleItems =
            $statement->fetchAll();

        $saleTotals =
            fetchSaleTotals(
                $db,
                $receiptID
            );

        $discountRecords = fetchSaleDiscounts( $db, $receiptID );
        $couponRecords = fetchSaleCoupons($db, $receiptID);
        $discountReasons =
            fetchDiscountReasons(
                $db
            );

        $statement =
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

// A new error replaces any message left over from the page address
if ($errorMessage !== '') {
    $successMessage = '';
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

<section class="content-panel sales-panel register-panel">

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

                <input
                    type="hidden"
                    name="form_security_token"
                    value="<?= escapeOutput(formToken()) ?>"
                >

                <div class="form-field">

                    <label for="register_id">
                        Checkout Station
                    </label>

                    <select
                        id="register_id"
                        name="register_id"
                        required
                    >

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

                            $reservedForExpress =
                                ($registerRecord['RegisterType'] ?? 'Regular') === 'Express'
                                || (int) $registerRecord['RegisterNumber'] === 1;

                            $inUseByOtherOperator =
                                $openReceiptID > 0
                                &&
                                $openOperatorID !==
                                $operatorID;

                            ?>

                            <option
                                value="<?= (int) $registerRecord['RegisterID'] ?>"
                                data-open-receipt-id="<?= $openReceiptID ?>"
                                data-open-operator-id="<?= $openOperatorID ?>"
                                <?=
                                $selectedRegisterID
                                ===
                                (int) $registerRecord['RegisterID']
                                ? 'selected'
                                : ''
                                ?>
                                <?= $reservedForExpress || $inUseByOtherOperator ? 'disabled' : '' ?>
                            >

                                Register #<?= escapeOutput($registerRecord['RegisterNumber']) ?>

                                <?php if (trim((string) $registerRecord['RegisterName']) !== ''): ?>

                                    - <?= escapeOutput($registerRecord['RegisterName']) ?>

                                <?php endif; ?>

                                <?php if ($reservedForExpress): ?>
                                    - Reserved for Express
                                <?php elseif ($openReceiptID > 0 && !$inUseByOtherOperator): ?>

                                    - Open Session

                                <?php elseif ($inUseByOtherOperator): ?>

                                    - In Use By: <?= escapeOutput($registerRecord['OpenOperatorUsername']) ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-actions sale-register-actions">

                    <button
                        type="submit"
                        id="openSelectedRegisterButton"
                        name="select_register"
                        value="1"
                        class="button button-primary"
                    >
                        Open Register
                    </button>
                    <button
                        type="submit"
                        id="closeSelectedRegisterButton"
                        name="close_selected_register"
                        value="1"
                        class="button button-danger"
                        hidden
                        onclick="return window.confirm(
                            'Close this register? '
                            + 'Any open transaction will be cancelled '
                            + 'and its items will be returned to inventory.'
                        );"
                    >
                        Close Register
                    </button>

                </div>

            </form>

        </section>

    <?php else: ?>

        <?php if ($assistingOther): ?>
            <div class="message message-information">
                You are assisting <?= escapeOutput($saleRecord['OwnerName']) ?>.
                The sale stays with <?= escapeOutput($saleRecord['OwnerName']) ?>, who can still resume it.
                To check it out, take it over from the
                <a href="<?= APPLICATION_URL ?>/transactions/tr_view.php?receipt=<?= (int) $receiptID ?>">
                    transaction details</a>.
            </div>
        <?php endif; ?>

        <section class="sale-information">

            <div class="sale-information-transaction">

                <span>
                    Transaction
                </span>

                <strong>
                    <?= transactionNumberHtml(
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

        <div class="sale-workspace">

            <section class="sale-product-area">

                <div class="sale-entry-panel">

                <form
                    method="post"
                    class="sale-scan-form"
                    id="saleScanForm"
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
                        id="scanner_product_id"
                        name="product_id"
                        value="0"
                    >


                    <div class="form-field sale-barcode-field">

                        <label for="product_code">
                            Barcode / Product Code
                        </label>

                        <input
                            type="text"
                            id="product_code"
                            name="product_code"
                            value=""
                            maxlength="20"
                            autocomplete="off"
                            autofocus
                        >

                    </div>

                    <?php printQuantityPicker(); ?>
                    <div
                        class="form-field sale-quantity-field"
                        id="saleWeightField"
                    >

                        <label
                            for="quantity"
                            id="sale_quantity_label"
                        >
                            Quantity
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            step="1"
                            inputmode="decimal"
                        >

                    </div>

                    <button
                        type="submit"
                        name="add_product"
                        value="1"
                        class="button button-primary"
                    >
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
                    ['receipt_id' => $receiptID],
                    'add_product'
                );
                ?>

            </section>

            <section class="sale-receipt-area">

                <div class="sale-receipt-header">
                    <h2>
                        Current Sale
                    </h2>
                    <div class="sale-header-buttons">
                    <?php if (!$assistingOther): ?>
                    <form
                        method="post"
                        id="closeCurrentRegisterForm"
                        class="sale-close-register-form"
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
                            id="sale_close_destination"
                            name="close_destination"
                            value=""
                        >

                        <input
                            type="hidden"
                            name="close_register"
                            value="1"
                        >

                        <button
                            type="submit"
                            name="close_register"
                            value="1"
                            class="button button-secondary"
                            onclick="return window.confirm(
                                'Close this register? '
                                + 'The current transaction will be cancelled '
                                + 'and all scanned items will be returned to inventory.'
                            );"
                        >
                            Close Register
                        </button>

                    </form>
                    <?php endif; ?>
                        <?php printCodesButton(); ?>
                    </div>
                </div>

                <?php if (empty($saleItems)): ?>

                    <div class="sale-empty">
                        No products have been added.
                    </div>

                <?php else: ?>

                    <?php
                    printSaleLines(
                        $saleItems,
                        ['receipt_id' => $receiptID],
                        true,
                        [
                            'add' => 'add_product',
                            'remove' => 'remove_product',
                            'removeAll' => 'remove_all_product'
                        ]
                    );
                    ?>

                <?php endif; ?>

                <section class="sale-totals-panel">

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

                    <?php if (!empty($couponRecords)): ?>
                        <ul class="sale-discount-list">
                            <?php foreach ($couponRecords as $couponRecord): ?>
                                <li class="sale-discount-item">
                                    <div class="sale-discount-text">
                                        <strong>
                                            Coupon: <?= escapeOutput($couponRecord['Description']) ?>
                                        </strong>
                                        <?php if ((int) $couponRecord['UnitsCovered'] === 0): ?>
                                            <em>
                                                Needs <?= (int) $couponRecord['RequiredQuantity'] ?>
                                                <?= escapeOutput($couponRecord['ProductName']) ?>
                                                that no other coupon is using.
                                            </em>
                                        <?php endif; ?>
                                    </div>
                                    <span class="sale-discount-amount">
                                        -$<?= escapeOutput(number_format((float) $couponRecord['AppliedAmount'], 2)) ?>
                                    </span>
                                    <form
                                        method="post"
                                        class="sale-discount-remove-form"
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
                                            name="sale_coupon_id"
                                            value="<?= (int) $couponRecord['SaleCouponID'] ?>"
                                        >
                                        <button
                                            type="submit"
                                            name="remove_coupon"
                                            value="1"
                                            class="button button-secondary symbol-button"
                                            title="Remove this coupon"
                                            aria-label="Remove this coupon"
                                        >
                                            &times;
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (empty($discountRecords)): ?>
                            <div class="sale-subtotal sale-estimated-total">
                                <span>
                                    Estimated Total With Tax
                                </span>
                                <strong>
                                    $<?= escapeOutput(number_format((float) ($saleTotals['TotalDue'] ?? 0), 2)) ?>
                                </strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($discountRecords)): ?> <ul class="sale-discount-list">

                            <?php foreach ($discountRecords as $discountRecord): ?>

                                <li class="sale-discount-item">

                                    <div class="sale-discount-text">

                                        <strong>
                                            <?= escapeOutput(describeDiscount($discountRecord)) ?>
                                        </strong>

                                        <span>
                                            <?= escapeOutput($discountRecord['Reason']) ?>
                                        </span>

                                        <?php if (discountWasLimited($discountRecord)): ?>

                                            <em>
                                                Limited to the amount that was left to discount.
                                            </em>

                                        <?php endif; ?>

                                    </div>

                                    <span class="sale-discount-amount">
                                        -$<?= escapeOutput(
                                            number_format(
                                                (float) $discountRecord['AppliedAmount'],
                                                2
                                            )
                                        ) ?>
                                    </span>

                                    <form
                                        method="post"
                                        class="sale-discount-remove-form"
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
                                            name="discount_id"
                                            value="<?= (int) $discountRecord['DiscountID'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="remove_discount"
                                            value="1"
                                            class="button button-secondary sale-discount-remove-button"
                                            title="Remove this discount"
                                            aria-label="Remove this discount"
                                        >
                                            &times;
                                        </button>

                                    </form>

                                </li>

                            <?php endforeach; ?>

                        </ul>

                        <div class="sale-subtotal sale-estimated-total">

                            <span>
                                Estimated Total With Tax
                            </span>

                            <strong>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) ($saleTotals['TotalDue'] ?? 0),
                                        2
                                    )
                                ) ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </section>

                <?php if (!empty($saleItems)): ?>

                    <details
                        class="sale-discount-details"
                        <?= $discountPanelOpen ? 'open' : '' ?>
                    >

                        <summary class="button button-secondary sale-discount-summary">
                            Discount
                        </summary>

                        <?php if (count($discountRecords) >= 5): ?>

                            <div class="message message-information">
                                This sale already has the most discounts allowed.
                            </div>

                        <?php else: ?>

                            <form
                                method="post"
                                class="sale-discount-form"
                                id="saleDiscountForm"
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

                                <div class="sale-discount-grid">

                                    <div class="form-field">

                                        <label for="discount_kind">
                                            Type
                                        </label>

                                        <select
                                            id="discount_kind"
                                            name="discount_kind"
                                        >
                                            <option
                                                value="Percent"
                                                <?= $discountForm['kind'] === 'Percent' ? 'selected' : '' ?>
                                            >
                                                Percent off (%)
                                            </option>
                                            <option
                                                value="Dollar"
                                                <?= $discountForm['kind'] === 'Dollar' ? 'selected' : '' ?>
                                            >
                                                Dollar amount off ($)
                                            </option>
                                        </select>

                                    </div>

                                    <div class="form-field">

                                        <label for="tax_timing">
                                            Apply
                                        </label>

                                        <select
                                            id="tax_timing"
                                            name="tax_timing"
                                        >
                                            <option
                                                value="Before Tax"
                                                <?= $discountForm['timing'] === 'Before Tax' ? 'selected' : '' ?>
                                            >
                                                Before tax
                                            </option>
                                            <option
                                                value="After Tax"
                                                <?= $discountForm['timing'] === 'After Tax' ? 'selected' : '' ?>
                                            >
                                                After tax
                                            </option>
                                        </select>

                                    </div>

                                    <div class="form-field">

                                        <label for="discount_value">
                                            Amount
                                        </label>

                                        <input
                                            type="number"
                                            id="discount_value"
                                            name="discount_value"
                                            min="0.01"
                                            step="0.01"
                                            inputmode="decimal"
                                            value="<?= escapeOutput($discountForm['value']) ?>"
                                            required
                                        >

                                        <div
                                            class="field-help"
                                            id="discountValueHelp"
                                        >
                                            Enter a percent or a dollar amount.
                                        </div>

                                    </div>

                                    <div class="form-field">

                                        <label for="reason_choice">
                                            Reason
                                        </label>

                                        <select
                                            id="reason_choice"
                                            name="reason_choice"
                                            required
                                        >
                                            <option value="">
                                                Choose a reason
                                            </option>

                                            <?php foreach ($discountReasons as $discountReason): ?>
                                                <?php
                                                $reasonIsChosen =
                                                    $discountForm['reason_choice']
                                                    === (string) $discountReason['ReasonID'];
                                                ?>
                                                <option
                                                    value="<?= (int) $discountReason['ReasonID'] ?>"
                                                    <?= $reasonIsChosen ? 'selected' : '' ?>
                                                >
                                                    <?= escapeOutput($discountReason['ReasonText']) ?>
                                                </option>
                                            <?php endforeach; ?>

                                            <option
                                                value="new"
                                                <?= $discountForm['reason_choice'] === 'new' ? 'selected' : '' ?>
                                            >
                                                Other (type a new reason)
                                            </option>
                                        </select>

                                    </div>

                                </div>

                                <div
                                    class="form-field sale-discount-new-reason"
                                    id="saleDiscountNewReason"
                                >

                                    <label for="reason_text">
                                        New reason
                                    </label>

                                    <input
                                        type="text"
                                        id="reason_text"
                                        name="reason_text"
                                        maxlength="255"
                                        value="<?= escapeOutput($discountForm['reason_text']) ?>"
                                        placeholder="Example: Senior morning special"
                                    >

                                    <label class="sale-discount-save-reason">

                                        <input
                                            type="checkbox"
                                            name="save_reason"
                                            value="1"
                                            <?= $discountForm['save'] ? 'checked' : '' ?>
                                        >

                                        Save this reason so it appears in the list next time

                                    </label>

                                </div>

                                <div class="sale-discount-actions">

                                    <button
                                        type="submit"
                                        name="apply_discount"
                                        value="1"
                                        class="button button-primary"
                                    >
                                        Apply Discount
                                    </button>

                                </div>

                            </form>

                            <script>
                                (function () {

                                    const kindSelect =
                                        document.getElementById('discount_kind');

                                    const valueInput =
                                        document.getElementById('discount_value');

                                    const valueHelp =
                                        document.getElementById('discountValueHelp');

                                    const reasonSelect =
                                        document.getElementById('reason_choice');

                                    const newReasonField =
                                        document.getElementById('saleDiscountNewReason');

                                    const newReasonInput =
                                        document.getElementById('reason_text');

                                    // Percent discounts stop at 100
                                    function updateAmountRules() {

                                        if (kindSelect.value === 'Percent') {

                                            valueInput.max = '100';

                                            valueHelp.textContent =
                                                'Enter a percent from 0.01 to 100.';

                                        } else {

                                            valueInput.removeAttribute('max');

                                            valueHelp.textContent =
                                                'Enter a dollar amount, such as 2.50.';
                                        }
                                    }

                                    // Show the new reason box only when it is needed
                                    function updateReasonFields() {

                                        const needsNewReason =
                                            reasonSelect.value === 'new';

                                        newReasonField.hidden =
                                            !needsNewReason;

                                        newReasonInput.required =
                                            needsNewReason;

                                        if (!needsNewReason) {

                                            newReasonInput.value = '';
                                        }
                                    }

                                    kindSelect.addEventListener(
                                        'change',
                                        updateAmountRules
                                    );

                                    reasonSelect.addEventListener(
                                        'change',
                                        updateReasonFields
                                    );

                                    updateAmountRules();

                                    updateReasonFields();

                                })();
                            </script>

                        <?php endif; ?>

                    </details>

                <?php endif; ?>

                <div class="sale-actions">

                    <?php if (!empty($saleItems) && !$assistingOther): ?>

                        <a
                            href="<?= APPLICATION_URL ?>/sales/sale_checkout.php?receipt=<?= (int) $receiptID ?>"
                            class="button button-primary"
                            data-sale-safe="true"
                        >
                            Checkout
                        </a>

                    <?php endif; ?>

                    <?php if (!empty($saleItems) && !$assistingOther): ?>

                        <form
                            method="post"
                            id="cancelCurrentSaleForm"
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
                                name="register_id"
                                value="<?= (int) $saleRecord['RegisterID'] ?>"
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
                                onclick="return window.confirm(
                                    'Cancel this sale? '
                                    + 'All scanned items will be returned to inventory.'
                                );"
                            >
                                Cancel Sale
                            </button>

                        </form>

                    <?php endif; ?>


                </div>

            </section>

        </div>

        <?php if (!$assistingOther): ?>
        <dialog
            id="saleLeaveDialog"
            class="checkout-leave-dialog"
        >

            <h2>
                Leave Current Transaction?
            </h2>

            <p>
                Items have already been scanned. Choose what should happen before leaving this screen.
            </p>

            <div class="checkout-leave-actions">
                <div class="checkout-leave-choice">
                    <button
                        type="button"
                        id="saleStayButton"
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
                        id="saleSaveButton"
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
                        id="saleCloseButton"
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
        <?php endif; ?>

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

            const currentOperatorCanSupervise =
                <?= canSupervise() ? 'true' : 'false' ?>;

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

                const supervisorViewingOtherOperator =
                    hasSelectedRegister
                    &&
                    registerIsOpen
                    &&
                    !belongsToCurrentOperator
                    &&
                    currentOperatorCanSupervise;

                if (openButton) {
                    openButton.hidden =
                        supervisorViewingOtherOperator;

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

                    const supervisorCanClose =
                        supervisorViewingOtherOperator;

                    closeButton.hidden =
                        !(
                            ownerCanClose
                            ||
                            supervisorCanClose
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

<?php if ($receiptID > 0 && $saleRecord && !$assistingOther): ?>

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
                            'Close this register? '
                            + 'The current transaction will be cancelled '
                            + 'and all scanned items will be returned to inventory.'
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

    <?php printItemEntryScript(); ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>