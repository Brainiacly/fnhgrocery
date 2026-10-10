<?php // inventory/inv_manage.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireSupervisor();

$storeID = signedInStoreID();

$searchTerm = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'all');
$departmentFilter = isset($_GET['department']) ? (int) $_GET['department'] : 0;

if (!in_array($statusFilter, ['all', 'active', 'inactive'], true)) {
    $statusFilter = 'all';
}

$departmentRecords = [];
$inventoryRecords = [];
$adjustmentRecords = [];
$errorMessage = '';

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

    $whereParts = ['si.StoreID = :storeID'];
    $parameters = [':storeID' => $storeID];

    if ($searchTerm !== '') {
        $whereParts[] = '(
            p.ProductName LIKE :searchProduct
            OR p.UPC LIKE :searchUPC
            OR p.PLUCode LIKE :searchPLU
        )';
        $searchValue = '%' . $searchTerm . '%';
        $parameters[':searchProduct'] = $searchValue;
        $parameters[':searchUPC'] = $searchValue;
        $parameters[':searchPLU'] = $searchValue;
    }

    if ($statusFilter === 'active') {
        $whereParts[] = 'p.Active = 1';
    } elseif ($statusFilter === 'inactive') {
        $whereParts[] = 'p.Active = 0';
    }

    if ($departmentFilter > 0) {
        $whereParts[] = 'p.DepartmentID = :departmentID';
        $parameters[':departmentID'] = $departmentFilter;
    }

    $inventoryStatement = $db->prepare(
        '
        SELECT
            p.ProductID,
            p.DepartmentID,
            d.DepartmentName,
            p.UPC,
            p.PLUCode,
            p.ProductName,
            p.UnitType,
            p.UnitCost,
            p.RetailPrice,
            p.Taxable,
            p.Active,
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
        WHERE ' . implode(' AND ', $whereParts) . '
        ORDER BY
            d.DepartmentName,
            p.ProductName
        '
    );

    $inventoryStatement->execute($parameters);
    $inventoryRecords = $inventoryStatement->fetchAll();

    $adjustmentStatement = $db->prepare(
        '
        SELECT
            AdjustmentID,
            ProductName,
            UnitType,
            Username,
            AdjustmentType,
            QuantityBefore,
            QuantityChange,
            QuantityAfter,
            Reason,
            AdjustedAt
        FROM vw_inventory_adjustments
        WHERE StoreID = :storeID
        ORDER BY AdjustedAt DESC, AdjustmentID DESC
        LIMIT 20
        '
    );

    $adjustmentStatement->execute([
        ':storeID' => $storeID
    ]);

    $adjustmentRecords = $adjustmentStatement->fetchAll();

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $errorMessage = 'Inventory management information could not be loaded.';
}

$pageTitle = 'Manage Inventory';
$currentSection = 'inventory';
$currentPage = 'manage';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel inventory-panel inventory-management-panel">

    <div class="page-intro inventory-compact-intro">
        <h1>Manage Inventory</h1>
        <p>
            Add products, edit product information, and adjust current stock for
            <?= escapeOutput(signedInStoreName()) ?>.
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="inventory-management-actions">
        <a href="<?= APPLICATION_URL ?>/inventory/inv_product.php" class="button button-primary">
            Add New Product
        </a>

        <a href="<?= APPLICATION_URL ?>/inventory/inv_stock.php" class="button button-secondary">
            View Stock Levels
        </a>
        <?php if (isAdministrator()): ?>
            <a href="<?= APPLICATION_URL ?>/inventory/inv_coupons.php" class="button button-secondary">
                Coupons
            </a>
        <?php endif; ?>
    </div>

    <form method="get" class="inventory-filter-form">

        <div class="inventory-filter-field inventory-filter-search">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="<?= escapeOutput($searchTerm) ?>"
                placeholder="Product name, UPC, or PLU">
        </div>

        <div class="inventory-filter-field">
            <label for="department">Department</label>
            <select id="department" name="department">
                <option value="0">All</option>
                <?php foreach ($departmentRecords as $departmentRecord): ?>
                    <option value="<?= (int) $departmentRecord['DepartmentID'] ?>" <?= $departmentFilter === (int) $departmentRecord['DepartmentID'] ? 'selected' : '' ?>>
                        <?= escapeOutput($departmentRecord['DepartmentName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="inventory-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="inventory-filter-actions">
            <button type="submit" class="button button-primary">
                Apply
            </button>
            <a href="<?= APPLICATION_URL ?>/inventory/inv_manage.php" class="button button-secondary">
                Clear
            </a>
        </div>

    </form>

    <div class="inventory-table-container inventory-management-table-container" tabindex="0">
        <table class="inventory-table inventory-management-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Department</th>
                    <th>Code</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Tax</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$inventoryRecords): ?>
                    <tr>
                        <td colspan="9" class="inventory-empty-row">No products match the selected filters.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($inventoryRecords as $inventoryRecord): ?>
                    <?php
                    $productCode = $inventoryRecord['UPC']
                        ?: ($inventoryRecord['PLUCode'] ?: 'None');
                    $stockQuantity = (float) $inventoryRecord['StockQuantity'];
                    $adjustAddress =
                        APPLICATION_URL
                        . '/inventory/inv_adjust.php?product='
                        . (int) $inventoryRecord['ProductID'];
                    $editAddress =
                        APPLICATION_URL
                        . '/inventory/inv_product.php?product='
                        . (int) $inventoryRecord['ProductID'];
                    ?>
                    <tr class="<?= $stockQuantity <= 0 ? 'inventory-out-of-stock' : '' ?>">
                        <td>
                            <strong><?= escapeOutput($inventoryRecord['ProductName']) ?></strong>
                        </td>
                        <td><?= escapeOutput($inventoryRecord['DepartmentName']) ?></td>
                        <td><?= escapeOutput($productCode) ?></td>
                        <td><?= escapeOutput($inventoryRecord['UnitType']) ?></td>
                        <td>$<?= escapeOutput(number_format((float) $inventoryRecord['RetailPrice'], 2)) ?></td>
                        <td><?= (int) $inventoryRecord['Taxable'] === 1 ? 'Taxable' : 'No Tax' ?></td>
                        <td>
                            <strong>
                                <?= escapeOutput(formatStock($stockQuantity, $inventoryRecord['UnitType'])) ?>
                            </strong>
                        </td>
                        <td><?= (int) $inventoryRecord['Active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <div class="inventory-row-actions">
                                <a href="<?= escapeOutput($adjustAddress) ?>"
                                    class="button button-primary inventory-row-button">
                                    Adjust
                                </a>
                                <a href="<?= escapeOutput($editAddress) ?>"
                                    class="button button-secondary inventory-row-button">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <section class="inventory-adjustment-history">
        <h2>Recent Manual Inventory Adjustments</h2>

        <?php if (!$adjustmentRecords): ?>
            <div class="message message-information">
                No manual inventory adjustments have been recorded yet.
            </div>
        <?php else: ?>
            <div class="inventory-table-container" tabindex="0">
                <table class="inventory-table inventory-adjustment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Before</th>
                            <th>Change</th>
                            <th>After</th>
                            <th>By</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustmentRecords as $adjustmentRecord): ?>
                            <tr>
                                <td>
                                    <?= escapeOutput(date('m/d/Y g:i A', strtotime($adjustmentRecord['AdjustedAt']))) ?>
                                </td>
                                <td><?= escapeOutput($adjustmentRecord['ProductName']) ?></td>
                                <td><?= escapeOutput($adjustmentRecord['AdjustmentType']) ?></td>
                                <td>
                                    <?= escapeOutput(number_format((float) $adjustmentRecord['QuantityBefore'], 3)) ?>
                                </td>
                                <td>
                                    <?= escapeOutput(number_format((float) $adjustmentRecord['QuantityChange'], 3)) ?>
                                </td>
                                <td>
                                    <?= escapeOutput(number_format((float) $adjustmentRecord['QuantityAfter'], 3)) ?>
                                </td>
                                <td><?= escapeOutput($adjustmentRecord['Username']) ?></td>
                                <td><?= escapeOutput($adjustmentRecord['Reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>