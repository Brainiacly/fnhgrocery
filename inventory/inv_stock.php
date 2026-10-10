<?php // inventory/inv_stock.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAccess();

$storeID =
    signedInStoreID();

$totalStockWeight = 0.0;
$totalStockEach = 0;

$inventoryRecords = [];

$errorMessage = '';


try {

    $db =
        connectDatabase();


    $inventoryStatement =
        $db->prepare(
            '
            SELECT
                DepartmentID,
                DepartmentName,
                ProductID,
                UPC,
                PLUCode,
                ProductName,
                UnitType,
                RetailPrice,
                StockQuantity,
                Aisle,
                SectionName,
                ShelfLocation
            FROM vw_store_stock
            WHERE StoreID = :storeID
            ORDER BY
                DepartmentName,
                ProductName
            '
        );


    $inventoryStatement->execute([
        ':storeID' =>
            $storeID
    ]);


    $inventoryRecords =
        $inventoryStatement->fetchAll();

    // Pounds and individual units are different measures, so they are never added together
    foreach ($inventoryRecords as $record) {
        if ($record['UnitType'] === 'Each') {
            $totalStockEach += (int) round((float) $record['StockQuantity']);
        } else {
            $totalStockWeight += (float) $record['StockQuantity'];
        }
    }

} catch (PDOException $exception) {

    error_log(
        $exception->getMessage()
    );

    $errorMessage =
        'Inventory information could not be loaded.';
}


$pageTitle =
    'Store Stock Levels';

$currentSection =
    'inventory';

$currentPage =
    'list';


require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel inventory-panel">

    <div class="page-intro">

        <h1>
            Store Stock Levels
        </h1>

        <p>
            Current inventory for
            <?= escapeOutput(signedInStoreName()) ?>
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php else: ?>

        <section class="inventory-total" aria-label="Store stock totals">
            <span class="inventory-total-label">Total Stock on Hand</span>
            <strong class="inventory-total-value">
                <?= escapeOutput(number_format($totalStockWeight, 3)) ?> lb
            </strong>
            <span class="inventory-total-label">Weight (PLU items)</span>
            <strong class="inventory-total-value">
                <?= escapeOutput(number_format($totalStockEach, 0)) ?> each
            </strong>
            <span class="inventory-total-label">Quantity (each items)</span>
        </section>


        <div class="inventory-table-container">

            <table class="inventory-table">

                <thead>

                    <tr>
                        <th>
                            Department
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Product Code
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock on Hand
                        </th>

                        <th>
                            Aisle / Section
                        </th>

                        <th>
                            Shelf
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($inventoryRecords as $inventoryRecord): ?>

                        <?php

                        $productCode =
                            $inventoryRecord['UPC']
                            ??
                            $inventoryRecord['PLUCode']
                            ??
                            '';

                        $stockQuantity =
                            (float) $inventoryRecord[
                                'StockQuantity'
                            ];

                        $sectionParts = [];

                        if (trim((string) $inventoryRecord['Aisle']) !== '') {
                            $sectionParts[] = 'Aisle ' . $inventoryRecord['Aisle'];
                        }

                        if (trim((string) $inventoryRecord['SectionName']) !== '') {
                            $sectionParts[] = $inventoryRecord['SectionName'];
                        }

                        $sectionText = implode(', ', $sectionParts);
                        $shelfText = trim((string) $inventoryRecord['ShelfLocation']);

                        ?>

                        <tr class="<?=
                            $stockQuantity <= 0
                            ? 'inventory-out-of-stock'
                            : ''
                            ?>">

                            <td>
                                <?= escapeOutput(
                                    $inventoryRecord[
                                        'DepartmentName'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= escapeOutput(
                                    $inventoryRecord[
                                        'ProductName'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= escapeOutput(
                                    $productCode
                                ) ?>
                            </td>

                            <td>
                                $<?= escapeOutput(
                                    number_format(
                                        (float) $inventoryRecord[
                                            'RetailPrice'
                                        ],
                                        2
                                    )
                                ) ?>
                            </td>

                            <td>

                                <strong>
                                    <?= escapeOutput(
                                        $inventoryRecord['UnitType'] === 'Each'
                                        ? number_format(
                                            $stockQuantity,
                                            0
                                        )
                                        : number_format(
                                            $stockQuantity,
                                            3
                                        )
                                    ) ?>         <?= $inventoryRecord['UnitType'] === 'Each' ? 'each' : 'lb' ?>
                                </strong>

                                <?php if ($stockQuantity <= 0): ?>

                                    <span class="inventory-status">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= escapeOutput($sectionText) ?>
                            </td>

                            <td>
                                <?= escapeOutput($shelfText) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>