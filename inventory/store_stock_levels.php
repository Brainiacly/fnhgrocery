<?php // inventory/store_stock_levels.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAssignedAccess();

$storeID =
    (int)($_SESSION['store_id'] ?? 0);

$totalStockQuantity = 0;

$inventoryRecords = [];

$errorMessage = '';


try {

    $databaseConnection =
        connectDatabase();


    $totalStatement =
        $databaseConnection->prepare(
            '
            SELECT
                TotalStockQuantity
            FROM vw_store_stock_total
            WHERE StoreID = :storeID
            LIMIT 1
            '
        );


    $totalStatement->execute([
        ':storeID' =>
            $storeID
    ]);


    $totalRecord =
        $totalStatement->fetch();


    if ($totalRecord) {

        $totalStockQuantity =
            $totalRecord[
                'TotalStockQuantity'
            ];
    }


    $inventoryStatement =
        $databaseConnection->prepare(
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
            <?= escapeOutput($_SESSION['store_name'] ?? '') ?>
        </p>

    </div>


    <?php if ($errorMessage !== ''): ?>

        <div class="message message-error">
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php else: ?>

        <section class="inventory-total">

            <span class="inventory-total-label">
                Total Units Currently in Stock
            </span>

            <strong class="inventory-total-value">
                <?= escapeOutput(
                    number_format(
                        (float)$totalStockQuantity,
                        3
                    )
                ) ?>
            </strong>

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
                            Units Available
                        </th>

                        <th>
                            Location
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
                            (float)$inventoryRecord[
                                'StockQuantity'
                            ];

                        $locationParts = [];

                        if (
                            trim(
                                (string)$inventoryRecord['Aisle']
                            ) !== ''
                        ) {
                            $locationParts[] =
                                'Aisle '
                                .
                                $inventoryRecord['Aisle'];
                        }

                        if (
                            trim(
                                (string)$inventoryRecord['SectionName']
                            ) !== ''
                        ) {
                            $locationParts[] =
                                $inventoryRecord['SectionName'];
                        }

                        if (
                            trim(
                                (string)$inventoryRecord['ShelfLocation']
                            ) !== ''
                        ) {
                            $locationParts[] =
                                $inventoryRecord['ShelfLocation'];
                        }

                        ?>

                        <tr
                            class="<?=
                                $stockQuantity <= 0
                                    ? 'inventory-out-of-stock'
                                    : ''
                            ?>"
                        >

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
                                        (float)$inventoryRecord[
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
                                    ) ?>
                                </strong>

                                <?php if ($stockQuantity <= 0): ?>

                                    <span class="inventory-status">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= escapeOutput(
                                    implode(
                                        ' | ',
                                        $locationParts
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>