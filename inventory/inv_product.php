<?php // inventory/inv_product.php

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
$isEditing = $productID > 0;

$errorMessage = '';
$successMessage = '';
$departmentRecords = [];
$productRecord = null;

$departmentID = 0;
$upc = '';
$pluCode = '';
$productName = '';
$description = '';
$unitType = 'Each';
$unitCost = '';
$retailPrice = '';
$taxable = '0';
$active = '1';
$startingQuantity = '0';
$aisle = '';
$sectionName = '';
$shelfLocation = '';
$currentStockQuantity = null;

try {
    $db = connectDatabase();

    $departmentStatement = $db->query(
        '
        SELECT
            DepartmentID,
            DepartmentName
        FROM department
        ORDER BY DepartmentName
        '
    );
    $departmentRecords = $departmentStatement->fetchAll();

    if ($isEditing) {
        $productStatement = $db->prepare(
            '
            SELECT
                p.ProductID,
                p.DepartmentID,
                p.UPC,
                p.PLUCode,
                p.ProductName,
                p.Description,
                p.UnitType,
                p.UnitCost,
                p.RetailPrice,
                p.Taxable,
                p.Active,
                si.StockQuantity,
                si.Aisle,
                si.SectionName,
                si.ShelfLocation
            FROM product p
            JOIN storeinventory si
                ON si.ProductID = p.ProductID
               AND si.StoreID = :storeID
            WHERE p.ProductID = :productID
            LIMIT 1
            '
        );

        $productStatement->execute([
            ':storeID' => $storeID,
            ':productID' => $productID
        ]);

        $productRecord = $productStatement->fetch();

        if (!$productRecord) {
            showAccessDeniedPage('The selected product was not found at your store.');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $departmentID = (int) $productRecord['DepartmentID'];
            $upc = (string) ($productRecord['UPC'] ?? '');
            $pluCode = (string) ($productRecord['PLUCode'] ?? '');
            $productName = (string) $productRecord['ProductName'];
            $description = (string) ($productRecord['Description'] ?? '');
            $unitType = (string) $productRecord['UnitType'];
            $unitCost = $productRecord['UnitCost'] === null ? '' : (string) $productRecord['UnitCost'];
            $retailPrice = (string) $productRecord['RetailPrice'];
            $taxable = (string) ((int) $productRecord['Taxable']);
            $active = (string) ((int) $productRecord['Active']);
            $aisle = (string) ($productRecord['Aisle'] ?? '');
            $sectionName = (string) ($productRecord['SectionName'] ?? '');
            $shelfLocation = (string) ($productRecord['ShelfLocation'] ?? '');
        }

        $currentStockQuantity = $productRecord['StockQuantity'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $departmentID = (int) ($_POST['department_id'] ?? 0);
        $upc = trim((string) ($_POST['upc'] ?? ''));
        $pluCode = trim((string) ($_POST['plu_code'] ?? ''));
        $productName = trim((string) ($_POST['product_name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $unitType = (string) ($_POST['unit_type'] ?? 'Each');
        $unitCost = trim((string) ($_POST['unit_cost'] ?? ''));
        $retailPrice = trim((string) ($_POST['retail_price'] ?? ''));
        $taxable = (string) ($_POST['taxable'] ?? '0');
        $active = (string) ($_POST['active'] ?? '1');
        $startingQuantity = trim((string) ($_POST['starting_quantity'] ?? '0'));
        $aisle = trim((string) ($_POST['aisle'] ?? ''));
        $sectionName = trim((string) ($_POST['section_name'] ?? ''));
        $shelfLocation = trim((string) ($_POST['shelf_location'] ?? ''));
        $submittedToken = $_POST['form_security_token'] ?? '';

        if (!tokenIsValid($submittedToken)) {
            $errorMessage = 'The form expired. Please try again.';
        } elseif ($departmentID <= 0) {
            $errorMessage = 'Select a department.';
        } elseif ($productName === '') {
            $errorMessage = 'Product name is required.';
        } elseif (strlen($productName) > 120) {
            $errorMessage = 'Product name cannot contain more than 120 characters.';
        } elseif ($upc === '' && $pluCode === '') {
            $errorMessage = 'Enter a UPC or PLU code.';
        } elseif ($upc !== '' && !ctype_digit($upc)) {
            $errorMessage = 'The UPC can contain digits only.';
        } elseif ($pluCode !== '' && !ctype_digit($pluCode)) {
            $errorMessage = 'The PLU code can contain digits only.';
        } elseif (strlen($upc) > 20) {
            $errorMessage = 'UPC cannot contain more than 20 characters.';
        } elseif (strlen($pluCode) > 10) {
            $errorMessage = 'PLU code cannot contain more than 10 characters.';
        } elseif (strlen($description) > 500) {
            $errorMessage = 'Description cannot contain more than 500 characters.';
        } elseif (!in_array($unitType, ['Each', 'Pound'], true)) {
            $errorMessage = 'Choose Each or Pound for the unit type.';
        } elseif ($unitCost !== '' && (!is_numeric($unitCost) || (float) $unitCost < 0)) {
            $errorMessage = 'Unit cost must be blank or a nonnegative amount.';
        } elseif ($retailPrice === '' || !is_numeric($retailPrice) || (float) $retailPrice < 0) {
            $errorMessage = 'Enter a valid nonnegative retail price.';
        } elseif (!in_array($taxable, ['0', '1'], true)) {
            $errorMessage = 'Choose whether the product is taxable.';
        } elseif (!in_array($active, ['0', '1'], true)) {
            $errorMessage = 'Choose whether the product is active.';
        } elseif (
            !$isEditing
            && (
                $startingQuantity === ''
                || !is_numeric($startingQuantity)
                || (float) $startingQuantity < 0
            )
        ) {
            $errorMessage = 'Enter a valid starting inventory quantity.';
        } elseif (
            !$isEditing
            && $unitType === 'Each'
            && (float) $startingQuantity !== floor((float) $startingQuantity)
        ) {
            $errorMessage = 'Each-count products require a whole-number starting quantity.';
        } elseif (strlen($aisle) > 20) {
            $errorMessage = 'Aisle cannot contain more than 20 characters.';
        } elseif (strlen($sectionName) > 50) {
            $errorMessage = 'Section cannot contain more than 50 characters.';
        } elseif (strlen($shelfLocation) > 30) {
            $errorMessage = 'Shelf location cannot contain more than 30 characters.';
        } else {
            try {
                $unitCostValue = $unitCost === '' ? null : (float) $unitCost;

                if ($isEditing) {
                    $saveStatement = $db->prepare(
                        'CALL sp_update_product(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    $saveStatement->execute([
                        $storeID,
                        $operatorID,
                        $productID,
                        $departmentID,
                        $upc,
                        $pluCode,
                        $productName,
                        $description,
                        $unitType,
                        $unitCostValue,
                        (float) $retailPrice,
                        (int) $taxable,
                        (int) $active,
                        $aisle,
                        $sectionName,
                        $shelfLocation
                    ]);
                    $saveStatement->closeCursor();

                    header(
                        'Location: '
                        . APPLICATION_URL
                        . '/inventory/inv_product.php?product=' . $productID
                        . '&updated=1'
                    );
                    exit;
                }

                $saveStatement = $db->prepare(
                    'CALL sp_create_product(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $saveStatement->execute([
                    $storeID,
                    $operatorID,
                    $departmentID,
                    $upc,
                    $pluCode,
                    $productName,
                    $description,
                    $unitType,
                    $unitCostValue,
                    (float) $retailPrice,
                    (int) $taxable,
                    (int) $active,
                    (float) $startingQuantity,
                    $aisle,
                    $sectionName,
                    $shelfLocation
                ]);

                $newProductResult = $saveStatement->fetch();
                $saveStatement->closeCursor();
                $newProductID = (int) ($newProductResult['ProductID'] ?? 0);

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/inventory/inv_product.php?product=' . $newProductID
                    . '&created=1'
                );
                exit;

            } catch (PDOException $exception) {
                $errorMessage = databaseMessage(
                    $exception,
                    $isEditing
                        ? 'The product could not be updated.'
                        : 'The product could not be created.'
                );
            }
        }
    }

    if ($isEditing && isset($_GET['updated'])) {
        $successMessage = 'Product information was updated successfully.';
    } elseif ($isEditing && isset($_GET['created'])) {
        $successMessage = 'The new product was created successfully.';
    }

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'Product information could not be loaded.';
}

$pageTitle = $isEditing ? 'Edit Product' : 'Add Product';
$currentSection = 'inventory';
$currentPage = 'product';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel form-panel inventory-product-form-panel">

    <div class="page-intro inventory-compact-intro">
        <h1><?= $isEditing ? 'Edit Product' : 'Add Product' ?></h1>
        <p>
            <?= $isEditing
                ? 'Update product information. Use Adjust Inventory to change the on-hand quantity.'
                : 'Create a product and its starting inventory record for this store.' ?>
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="message message-success"><?= escapeOutput($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($isEditing && $currentStockQuantity !== null): ?>
        <div class="inventory-current-stock-strip">
            <span>Current Stock</span>
            <strong><?= escapeOutput(formatStock($currentStockQuantity, $unitType)) ?></strong>
            <a
                href="<?= APPLICATION_URL ?>/inventory/inv_adjust.php?product=<?= (int) $productID ?>"
                class="button button-secondary"
            >
                Adjust Stock
            </a>
        </div>
    <?php endif; ?>

    <form method="post">
        <input
            type="hidden"
            name="form_security_token"
            value="<?= escapeOutput(formToken()) ?>"
        >
        <?php if ($isEditing): ?>
            <input
                type="hidden"
                name="product_id"
                value="<?= (int) $productID ?>"
            >
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-field">
                <label for="department_id">Department *</label>
                <select
                    id="department_id"
                    name="department_id"
                    required
                >
                    <option value="">Select Department</option>
                    <?php foreach ($departmentRecords as $departmentRecord): ?>
                        <option
                            value="<?= (int) $departmentRecord['DepartmentID'] ?>"
                            <?= $departmentID === (int) $departmentRecord['DepartmentID'] ? 'selected' : '' ?>
                        >
                            <?= escapeOutput($departmentRecord['DepartmentName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="product_name">Product Name *</label>
                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    maxlength="120"
                    value="<?= escapeOutput($productName) ?>"
                    required
                >
            </div>

            <div class="form-field">
                <label for="upc">UPC</label>
                <input
                    type="text"
                    id="upc"
                    name="upc"
                    maxlength="20"
                    value="<?= escapeOutput($upc) ?>"
                    inputmode="numeric"
                >
                <div
                    class="field-help"
                >Enter a UPC, a PLU, or both. At least one product code is required.</div>
            </div>

            <div class="form-field">
                <label for="plu_code">PLU Code</label>
                <input
                    type="text"
                    id="plu_code"
                    name="plu_code"
                    maxlength="10"
                    value="<?= escapeOutput($pluCode) ?>"
                    inputmode="numeric"
                >
            </div>

            <div class="form-field">
                <label for="unit_type">Sold As *</label>
                <select
                    id="unit_type"
                    name="unit_type"
                    required
                >
                    <option
                        value="Each"
                        <?= $unitType === 'Each' ? 'selected' : '' ?>
                    >Each</option>
                    <option
                        value="Pound"
                        <?= $unitType === 'Pound' ? 'selected' : '' ?>
                    >By Weight (Pound)</option>
                </select>
            </div>

            <div class="form-field">
                <label for="taxable">Tax *</label>
                <select
                    id="taxable"
                    name="taxable"
                    required
                >
                    <option
                        value="0"
                        <?= $taxable === '0' ? 'selected' : '' ?>
                    >Not Taxable</option>
                    <option
                        value="1"
                        <?= $taxable === '1' ? 'selected' : '' ?>
                    >Taxable</option>
                </select>
            </div>

            <div class="form-field">
                <label for="unit_cost">Unit Cost</label>
                <input
                    type="number"
                    id="unit_cost"
                    name="unit_cost"
                    min="0"
                    step="0.01"
                    value="<?= escapeOutput($unitCost) ?>"
                    inputmode="decimal"
                >
            </div>

            <div class="form-field">
                <label for="retail_price">Retail Price *</label>
                <input
                    type="number"
                    id="retail_price"
                    name="retail_price"
                    min="0"
                    step="0.01"
                    value="<?= escapeOutput($retailPrice) ?>"
                    required
                    inputmode="decimal"
                >
            </div>

            <div class="form-field">
                <label for="active">Product Status *</label>
                <select
                    id="active"
                    name="active"
                    required
                >
                    <option
                        value="1"
                        <?= $active === '1' ? 'selected' : '' ?>
                    >Active</option>
                    <option
                        value="0"
                        <?= $active === '0' ? 'selected' : '' ?>
                    >Inactive</option>
                </select>
                <div
                    class="field-help"
                >Inactive products remain in history but are not offered for new sales.</div>
            </div>

            <?php if (!$isEditing): ?>
                <div class="form-field">
                    <label for="starting_quantity">Starting Inventory *</label>
                    <input
                        type="number"
                        id="starting_quantity"
                        name="starting_quantity"
                        min="0"
                        step="0.001"
                        value="<?= escapeOutput($startingQuantity) ?>"
                        required
                        inputmode="decimal"
                    >
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="aisle">Aisle</label>
                <input
                    type="text"
                    id="aisle"
                    name="aisle"
                    maxlength="20"
                    value="<?= escapeOutput($aisle) ?>"
                >
            </div>

            <div class="form-field">
                <label for="section_name">Section</label>
                <input
                    type="text"
                    id="section_name"
                    name="section_name"
                    maxlength="50"
                    value="<?= escapeOutput($sectionName) ?>"
                >
            </div>

            <div class="form-field">
                <label for="shelf_location">Shelf Location</label>
                <input
                    type="text"
                    id="shelf_location"
                    name="shelf_location"
                    maxlength="30"
                    value="<?= escapeOutput($shelfLocation) ?>"
                >
            </div>

            <div class="form-field form-field-full-width">
                <label for="description">Description</label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    maxlength="500"
                    value="<?= escapeOutput($description) ?>"
                >
            </div>
        </div>

        <div class="form-actions">
            <button
                type="submit"
                class="button button-primary"
            >
                <?= $isEditing ? 'Save Product Changes' : 'Create Product' ?>
            </button>
            <a
                href="<?= APPLICATION_URL ?>/inventory/inv_manage.php"
                class="button button-secondary"
            >
                Back to Inventory
            </a>
        </div>
    </form>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>