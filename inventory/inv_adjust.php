<?php // inventory/inv_adjust.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireSupervisor();

$storeID = signedInStoreID();
$operatorID = signedInOperatorID();
$productID = isset($_GET['product'])
    ? (int) $_GET['product']
    : (int) ($_POST['product_id'] ?? 0);

$errorMessage = '';
$successMessage = '';
$productRecord = null;

$adjustmentType = (string) ($_POST['adjustment_type'] ?? 'Add');
$quantity = trim((string) ($_POST['quantity'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? ''));
$aisle = trim((string) ($_POST['aisle'] ?? ''));
$sectionName = trim((string) ($_POST['section_name'] ?? ''));
$shelfLocation = trim((string) ($_POST['shelf_location'] ?? ''));

if ($productID <= 0) {
    showAccessDeniedPage('Select a valid product from Manage Inventory before adjusting stock.');
}

try {
    $db = connectDatabase();

    $loadProduct = static function ($connection, $store, $product) {
        $statement = $connection->prepare(
            '
            SELECT
                p.ProductID,
                p.ProductName,
                p.UnitType,
                p.RetailPrice,
                p.Active,
                d.DepartmentName,
                si.StockQuantity,
                si.Aisle,
                si.SectionName,
                si.ShelfLocation,
                si.LastCountedAt
            FROM product p
            JOIN department d
                ON d.DepartmentID = p.DepartmentID
            JOIN storeinventory si
                ON si.ProductID = p.ProductID
               AND si.StoreID = :storeID
            WHERE p.ProductID = :productID
            LIMIT 1
            '
        );

        $statement->execute([
            ':storeID' => $store,
            ':productID' => $product
        ]);

        return $statement->fetch();
    };

    $productRecord = $loadProduct($db, $storeID, $productID);

    if (!$productRecord) {
        showAccessDeniedPage('The selected product was not found at your store.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $aisle = (string) ($productRecord['Aisle'] ?? '');
        $sectionName = (string) ($productRecord['SectionName'] ?? '');
        $shelfLocation = (string) ($productRecord['ShelfLocation'] ?? '');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = $_POST['form_security_token'] ?? '';

        if (!tokenIsValid($submittedToken)) {
            $errorMessage = 'The form expired. Please try again.';
        } elseif (!in_array($adjustmentType, ['Add', 'Remove', 'Set'], true)) {
            $errorMessage = 'Choose Add, Remove, or Set inventory.';
        } elseif ($quantity === '' || !is_numeric($quantity)) {
            $errorMessage = 'Enter a valid inventory quantity.';
        } elseif ((float) $quantity < 0) {
            $errorMessage = 'Inventory quantity cannot be negative.';
        } elseif (
            in_array($adjustmentType, ['Add', 'Remove'], true)
            && (float) $quantity <= 0
        ) {
            $errorMessage = 'Add and Remove require a quantity greater than zero.';
        } elseif (
            $productRecord['UnitType'] === 'Each'
            && (float) $quantity !== floor((float) $quantity)
        ) {
            $errorMessage = 'Each-count products require a whole-number quantity.';
        } elseif ($reason === '') {
            $errorMessage = 'Enter a reason for the inventory adjustment.';
        } elseif (strlen($reason) > 255) {
            $errorMessage = 'The adjustment reason cannot contain more than 255 characters.';
        } elseif (strlen($aisle) > 20) {
            $errorMessage = 'Aisle cannot contain more than 20 characters.';
        } elseif (strlen($sectionName) > 50) {
            $errorMessage = 'Section cannot contain more than 50 characters.';
        } elseif (strlen($shelfLocation) > 30) {
            $errorMessage = 'Shelf location cannot contain more than 30 characters.';
        } else {
            try {
                $adjustStatement = $db->prepare(
                    'CALL sp_adjust_inventory(?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $adjustStatement->execute([
                    $storeID,
                    $operatorID,
                    $productID,
                    $adjustmentType,
                    (float) $quantity,
                    $reason,
                    $aisle,
                    $sectionName,
                    $shelfLocation
                ]);

                $adjustStatement->closeCursor();

                $productRecord = $loadProduct($db, $storeID, $productID);
                $successMessage = 'Inventory was adjusted successfully.';
                $quantity = '';
                $reason = '';
                $aisle = (string) ($productRecord['Aisle'] ?? '');
                $sectionName = (string) ($productRecord['SectionName'] ?? '');
                $shelfLocation = (string) ($productRecord['ShelfLocation'] ?? '');

            } catch (PDOException $exception) {
                $errorMessage = databaseMessage(
                    $exception,
                    'Inventory could not be adjusted.'
                );
            }
        }
    }

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'The selected inventory record could not be loaded.';
}

$pageTitle = 'Adjust Inventory';
$currentSection = 'inventory';
$currentPage = 'adjust';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel form-panel inventory-adjust-panel">

    <div class="page-intro inventory-compact-intro">
        <h1>Adjust Inventory</h1>
        <p>Increase, decrease, or set the on-hand quantity without changing product pricing.</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="message message-success"><?= escapeOutput($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($productRecord): ?>

        <section class="inventory-product-summary">
            <div>
                <span class="inventory-summary-label">Product</span>
                <strong><?= escapeOutput($productRecord['ProductName']) ?></strong>
            </div>
            <div>
                <span class="inventory-summary-label">Unit Type</span>
                <strong><?= escapeOutput($productRecord['UnitType']) ?></strong>
            </div>
            <div>
                <span class="inventory-summary-label">Current Stock</span>
                <strong>
                    <?= escapeOutput(formatStock($productRecord['StockQuantity'], $productRecord['UnitType'])) ?>
                </strong>
            </div>
            <div>
                <span class="inventory-summary-label">Status</span>
                <strong><?= (int) $productRecord['Active'] === 1 ? 'Active' : 'Inactive' ?></strong>
            </div>
        </section>

        <form method="post">
            <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">
            <input type="hidden" name="product_id" value="<?= (int) $productID ?>">

            <div class="form-grid">
                <div class="form-field">
                    <label for="adjustment_type">Adjustment *</label>
                    <select id="adjustment_type" name="adjustment_type" required>
                        <option value="Add" <?= $adjustmentType === 'Add' ? 'selected' : '' ?>>Add to current stock</option>
                        <option value="Remove" <?= $adjustmentType === 'Remove' ? 'selected' : '' ?>>Remove from current stock
                        </option>
                        <option value="Set" <?= $adjustmentType === 'Set' ? 'selected' : '' ?>>Set exact on-hand quantity
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0"
                        step="<?= $productRecord['UnitType'] === 'Each' ? '1' : '0.001' ?>"
                        value="<?= escapeOutput($quantity) ?>" required inputmode="decimal">
                    <div class="field-help">
                        <?= $productRecord['UnitType'] === 'Each'
                            ? 'Enter whole units for this product.'
                            : 'Weight may be entered to three decimal places.' ?>
                    </div>
                </div>

                <div class="form-field form-field-full-width">
                    <label for="reason">Reason *</label>
                    <input type="text" id="reason" name="reason" maxlength="255" value="<?= escapeOutput($reason) ?>"
                        placeholder="Example: Delivery received, damaged item, cycle count correction" required>
                </div>

                <div class="form-field">
                    <label for="aisle">Aisle</label>
                    <input type="text" id="aisle" name="aisle" maxlength="20" value="<?= escapeOutput($aisle) ?>">
                </div>

                <div class="form-field">
                    <label for="section_name">Section</label>
                    <input type="text" id="section_name" name="section_name" maxlength="50"
                        value="<?= escapeOutput($sectionName) ?>">
                </div>

                <div class="form-field">
                    <label for="shelf_location">Shelf Location</label>
                    <input type="text" id="shelf_location" name="shelf_location" maxlength="30"
                        value="<?= escapeOutput($shelfLocation) ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    Save Adjustment
                </button>
                <a href="<?= APPLICATION_URL ?>/inventory/inv_manage.php" class="button button-secondary">
                    Back to Inventory
                </a>
            </div>
        </form>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>