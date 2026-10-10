<?php // inventory/inv_coupons.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';
require_once __DIR__ . '/../includes/discounts.php';

requireAdministrator();

$operatorID = signedInOperatorID();
$errorMessage = '';
$successMessage = '';
$couponRecords = [];
$productRecords = [];

$form = [
    'product_id' => '',
    'required_quantity' => '1',
    'discount_kind' => 'Dollar',
    'discount_value' => '',
    'description' => '',
    'start_date' => '',
    'end_date' => ''
];

try {
    $db = connectDatabase();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!tokenIsValid($_POST['form_security_token'] ?? '')) {
            $errorMessage = 'The form expired. Please try again.';
        } elseif (isset($_POST['set_active'])) {
            $statement = $db->prepare('CALL sp_set_coupon_active(:operatorID, :couponID, :active)');

            $statement->execute([
                ':operatorID' => $operatorID,
                ':couponID' => (int) ($_POST['coupon_id'] ?? 0),
                ':active' => (int) ($_POST['active'] ?? 0) === 1 ? 1 : 0
            ]);

            $statement->closeCursor();

            $successMessage = 'The coupon was updated.';
        } elseif (isset($_POST['create_coupon'])) {
            foreach (array_keys($form) as $fieldName) {
                $form[$fieldName] = trim((string) ($_POST[$fieldName] ?? ''));
            }

            $value = filter_var($form['discount_value'], FILTER_VALIDATE_FLOAT);

            if ($value === false) {
                $errorMessage = 'Enter a coupon amount greater than zero.';
            } else {
                $statement = $db->prepare(
                    'CALL sp_create_coupon(
                        :operatorID, :code, :productID, :requiredQuantity,
                        :kind, :value, :description, :startDate, :endDate
                    )'
                );

                $statement->execute([
                    ':operatorID' => $operatorID,
                    ':code' => newCouponCode($db),
                    ':productID' => (int) $form['product_id'],
                    ':requiredQuantity' => (int) $form['required_quantity'],
                    ':kind' => $form['discount_kind'],
                    ':value' => $value,
                    ':description' => $form['description'],
                    ':startDate' => $form['start_date'] !== '' ? $form['start_date'] : null,
                    ':endDate' => $form['end_date'] !== '' ? $form['end_date'] : null
                ]);

                $statement->closeCursor();

                $successMessage = 'The coupon was created. Its barcode is in the list below.';

                $form['description'] = '';
                $form['discount_value'] = '';
            }
        }
    }

    $productRecords = $db->query(
        "
        SELECT
            ProductID,
            ProductName,
            RetailPrice
        FROM product
        WHERE UnitType = 'Each'
          AND Active = 1
        ORDER BY ProductName
        "
    )->fetchAll();

    $couponRecords = $db->query(
        "
        SELECT
            c.CouponID,
            c.CouponCode,
            c.Description,
            c.RequiredQuantity,
            c.DiscountKind,
            c.DiscountValue,
            c.StartDate,
            c.EndDate,
            c.Active,
            p.ProductName,
            (
                SELECT COUNT(*)
                FROM salesreceiptcoupon sc
                JOIN salesreceipt sr
                    ON sr.ReceiptID = sc.ReceiptID
                WHERE sc.CouponID = c.CouponID
                  AND sr.Status IN ('Paid', 'Completed')
            ) AS TimesUsed,
            (
                SELECT COALESCE(SUM(sc.UnitsCovered), 0)
                FROM salesreceiptcoupon sc
                JOIN salesreceipt sr ON sr.ReceiptID = sc.ReceiptID
                WHERE sc.CouponID = c.CouponID
                  AND sr.Status IN ('Paid', 'Completed')
            ) AS UnitsUsed
        FROM coupon c
        JOIN product p
            ON p.ProductID = c.ProductID
        ORDER BY c.Active DESC, c.CouponID DESC
        "
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());

    $errorMessage = databaseMessage(
        $exception,
        'The coupons could not be loaded or saved right now.'
    );
}

$pageTitle = 'Coupons';
$currentSection = 'inventory';
$currentPage = 'coupons';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel inventory-panel">

    <div class="page-intro inventory-compact-intro">

        <h1>Coupons</h1>

        <p>
            Each coupon is for one product and has its own barcode. A cashier scans it
            in the Barcode / Product Code box.
        </p>

    </div>

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

    <form method="post" class="coupon-form">

        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

        <div class="form-field coupon-product-field">

            <label for="product_id">
                Product
            </label>

            <select id="product_id" name="product_id" required>

                <option value="">
                    Choose a product
                </option>

                <?php
                foreach ($productRecords as $productRecord) {
                    $couponProductSelectedAttribute =
                        (string) $productRecord['ProductID'] === $form['product_id']
                        ? ' selected="selected"'
                        : '';
                    $couponProductPrice = number_format((float) $productRecord['RetailPrice'], 2);

                    echo '<option value="'
                        . (int) $productRecord['ProductID']
                        . '"'
                        . $couponProductSelectedAttribute
                        . '>'
                        . escapeOutput($productRecord['ProductName'])
                        . ' ($'
                        . escapeOutput($couponProductPrice)
                        . ')</option>';
                }
                ?>

            </select>

        </div>

        <div class="form-field">

            <label for="required_quantity">
                Items needed
            </label>

            <input type="number" id="required_quantity" name="required_quantity"
                value="<?= escapeOutput($form['required_quantity']) ?>" min="1" max="99" step="1" required>

        </div>

        <div class="form-field coupon-kind-field">

            <label for="discount_kind">
                Type
            </label>

            <select id="discount_kind" name="discount_kind">

                <option value="Dollar" <?= $form['discount_kind'] === 'Dollar' ? 'selected' : '' ?>>
                    Dollar amount off ($)
                </option>

                <option value="Percent" <?= $form['discount_kind'] === 'Percent' ? 'selected' : '' ?>>
                    Percent off (%)
                </option>

            </select>

        </div>

        <div class="form-field">

            <label for="discount_value">
                Amount
            </label>

            <input type="number" id="discount_value" name="discount_value"
                value="<?= escapeOutput($form['discount_value']) ?>" min="0.01" step="0.01" required>

        </div>

        <div class="form-field coupon-description-field">

            <label for="description">
                Description
            </label>

            <input type="text" id="description" name="description" value="<?= escapeOutput($form['description']) ?>"
                maxlength="120" required>

        </div>

        <div class="form-field">

            <label for="start_date">
                Starts (optional)
            </label>

            <input type="date" id="start_date" name="start_date" value="<?= escapeOutput($form['start_date']) ?>">

        </div>

        <div class="form-field">

            <label for="end_date">
                Ends (optional)
            </label>

            <input type="date" id="end_date" name="end_date" value="<?= escapeOutput($form['end_date']) ?>">

        </div>

        <button type="submit" name="create_coupon" value="1" class="button button-primary">
            Create Coupon
        </button>

    </form>

    <?php if (!$couponRecords): ?>

        <div class="message message-information">
            No coupons have been created yet.
        </div>

    <?php else: ?>

        <div class="inventory-table-container">

            <table class="inventory-table coupon-table">

                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Coupon</th>
                        <th>Product</th>
                        <th>Needs</th>
                        <th>Dates</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th>Change</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($couponRecords as $couponRecord): ?>

                        <tr>

                            <td class="coupon-barcode">
                                <?= couponBarcodeSvg($couponRecord['CouponCode']) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= escapeOutput($couponRecord['Description']) ?>
                                </strong>
                                <span class="sale-line-code coupon-value">
                                    <?= escapeOutput(describeCouponValue($couponRecord)) ?>
                                </span>
                            </td>

                            <td>
                                <?= escapeOutput($couponRecord['ProductName']) ?>
                            </td>

                            <td>
                                <?= (int) $couponRecord['RequiredQuantity'] ?>
                            </td>

                            <td>
                                <?php if ($couponRecord['StartDate'] === null && $couponRecord['EndDate'] === null): ?>
                                    Always
                                <?php else: ?>
                                    <?= escapeOutput((string) ($couponRecord['StartDate'] ?? 'Any')) ?>
                                    to
                                    <?= escapeOutput((string) ($couponRecord['EndDate'] ?? 'Any')) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= (int) $couponRecord['TimesUsed'] ?> uses /
                                <?= (int) $couponRecord['UnitsUsed'] ?> units
                            </td>

                            <td>
                                <?= (int) $couponRecord['Active'] === 1 ? 'Active' : 'Off' ?>
                            </td>

                            <td>

                                <form method="post">

                                    <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

                                    <input type="hidden" name="coupon_id" value="<?= (int) $couponRecord['CouponID'] ?>">

                                    <input type="hidden" name="active"
                                        value="<?= (int) $couponRecord['Active'] === 1 ? 0 : 1 ?>">

                                    <button type="submit" name="set_active" value="1" class="button button-secondary">
                                        <?= (int) $couponRecord['Active'] === 1 ? 'Turn Off' : 'Turn On' ?>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>