<?php // includes/item_entry.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

// Raised when a requested amount cannot be sold, so the page can show the reason
class StockLimitException extends RuntimeException
{
}

// Cap a requested amount to the available stock (open sales already hold their stock)
function capRequestedStock(PDO $db, int $storeID, int $productID, float $requested): array
{
    $statement = $db->prepare(
        'SELECT ProductName, UnitType, StockQuantity
         FROM vw_store_stock
         WHERE StoreID = :store AND ProductID = :product
         LIMIT 1'
    );
    $statement->execute([':store' => $storeID, ':product' => $productID]);
    $product = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new StockLimitException('This product is not available at this store.');
    }
    $each = $product['UnitType'] === 'Each';
    $available = max(0.0, (float) $product['StockQuantity']);
    $available = $each ? floor($available) : floor($available * 1000 + 0.0000001) / 1000;
    if ($each && abs($requested - round($requested)) > 0.000001) {
        throw new StockLimitException('This item requires a whole-number quantity.');
    }
    $requested = $each ? round($requested) : round($requested, 3);
    $actual = min($requested, $available);
    $unit = $each ? 'each' : 'lb';
    if ($actual <= 0) {
        throw new StockLimitException($product['ProductName'] . ' is out of stock.');
    }
    $changed = $actual < $requested;
    $message = $changed ? sprintf(
        'Only %s %s of %s is available. The amount was adjusted to %s %s.',
        $each ? number_format($available, 0) : number_format($available, 3),
        $unit,
        $product['ProductName'],
        $each ? number_format($actual, 0) : number_format($actual, 3),
        $unit
    ) : '';
    return [$actual, $message];
}


// The quantity for the next item. It sits beside Add Item
function printQuantityPicker()
{
    ?>
    <div class="form-field sale-count-field" id="saleQuantityBox">
        <label for="sale_item_quantity">
            Quantity
        </label>
        <input type="number" id="sale_item_quantity" min="1" max="999" step="1" value="1" inputmode="numeric">
    </div>
    <?php
}


// The button that shows or hides the product codes in the Current Sale list
function printCodesButton()
{
    ?>

    <button type="button" class="button button-secondary sale-codes-button" id="saleCodesButton" aria-pressed="false"
        title="Show or hide the UPC and PLU codes" hidden> &plus; UPC </button>

    <?php
}


// The touch buttons for the products
// $hiddenFields names the order, $submitName is the form button name
function printProductTiles($products, $hiddenFields, $submitName)
{
    ?>

    <div class="sale-product-grid">

        <?php foreach ($products as $product): ?>

            <?php
            $isWeighed = $product['UnitType'] === 'Pound';

            $tileCode =
                trim((string) $product['UPC']) !== ''
                ? $product['UPC']
                : $product['PLUCode'];

            $tileClass = 'sale-product-button';

            if ($isWeighed) {
                $tileClass .= ' sale-weighted-product-button';
            }
            ?>

            <form method="post" class="sale-product-form">

                <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

                <?php foreach ($hiddenFields as $fieldName => $fieldValue): ?>

                    <input type="hidden" name="<?= escapeOutput($fieldName) ?>" value="<?= escapeOutput($fieldValue) ?>">

                <?php endforeach; ?>

                <input type="hidden" name="product_id" value="<?= (int) $product['ProductID'] ?>">

                <input type="hidden" name="quantity" value="1">

                <button <?php if ($isWeighed): ?> type="button" <?php else: ?> type="submit"
                        name="<?= escapeOutput($submitName) ?>" value="1" <?php endif; ?> class="<?= $tileClass ?>"
                    data-product-id="<?= (int) $product['ProductID'] ?>" data-product-code="<?= escapeOutput($tileCode) ?>"
                    data-upc="<?= escapeOutput((string) $product['UPC']) ?>"
                    data-plu="<?= escapeOutput((string) $product['PLUCode']) ?>"
                    data-unit-type="<?= escapeOutput($product['UnitType']) ?>"
                    data-stock="<?= escapeOutput((string) $product['StockQuantity']) ?>" <?= (float) $product['StockQuantity'] <= 0 ? 'disabled' : '' ?>>

                    <strong>
                        <?= escapeOutput($product['ProductName']) ?>
                    </strong>

                    <?php if (trim((string) $product['UPC']) !== ''): ?>

                        <span class="sale-product-code">
                            UPC# <?= escapeOutput($product['UPC']) ?>
                        </span>

                    <?php elseif (trim((string) $product['PLUCode']) !== ''): ?>

                        <span class="sale-product-code">
                            PLU# <?= escapeOutput($product['PLUCode']) ?>
                        </span>

                    <?php endif; ?>

                    <span>
                        <?= escapeOutput($product['DepartmentName']) ?>
                    </span>

                    <span>
                        $<?= escapeOutput(
                            number_format((float) $product['RetailPrice'], 2)
                        ) ?>
                    </span>

                    <?php if ((int) $product['Taxable'] === 1): ?>

                        <span>
                            Taxable
                        </span>

                    <?php endif; ?>

                    <span>
                        Stock:
                        <?= escapeOutput(
                            $isWeighed
                            ? number_format((float) $product['StockQuantity'], 3) . ' lb'
                            : number_format((float) $product['StockQuantity'], 0) . ' each'
                        ) ?>
                    </span>

                </button>

            </form>

        <?php endforeach; ?>

    </div>

    <?php
}


// The product code as text, such as UPC# 100000000013
function lineCodeText($upc, $pluCode)
{
    if (trim((string) $upc) !== '') {
        return 'UPC# ' . $upc;
    }

    if (trim((string) $pluCode) !== '') {
        return 'PLU# ' . $pluCode;
    }

    return '';
}


// Show the product code under the name in the Current Sale list
function printLineCode($upc, $pluCode)
{
    $code = lineCodeText($upc, $pluCode);

    if ($code !== '') {
        echo '<span class="sale-line-code">' . escapeOutput($code) . '</span>';
    }
}


// A small form with a symbol button and hidden fields for the order and line
function printLineButton($hiddenFields, $fields, $buttonName, $symbol, $label, $confirmText = '')
{
    ?>

    <form method="post">

        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

        <?php foreach (array_merge($hiddenFields, $fields) as $fieldName => $fieldValue): ?>

            <input type="hidden" name="<?= escapeOutput($fieldName) ?>" value="<?= escapeOutput($fieldValue) ?>">

        <?php endforeach; ?>

        <button type="submit" name="<?= escapeOutput($buttonName) ?>" value="1"
            class="button button-secondary symbol-button" title="<?= escapeOutput($label) ?>"
            aria-label="<?= escapeOutput($label) ?>" <?php if ($confirmText !== ''): ?>
                onclick="return window.confirm('<?= escapeOutput($confirmText) ?>');" <?php endif; ?>>
            <?= $symbol ?>
        </button>

    </form>

    <?php
}


// The Current Sale list: product, quantity with its - and + buttons, and the line total
// $names holds the form button names of the page: add, remove, and removeAll
function printSaleLines($lines, $hiddenFields, $canChange, $names)
{
    ?>

    <div class="sale-receipt-table-container">

        <table class="sale-receipt-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($lines as $line): ?>

                    <?php
                    $isWeighed = $line['UnitType'] === 'Pound';

                    $lineCode = lineCodeText($line['UPC'], $line['PLUCode']);

                    $priceText = '$' . number_format((float) $line['UnitPrice'], 2)
                        . ($isWeighed ? ' per lb' : ' each');

                    $quantityText = $isWeighed
                        ? number_format((float) $line['Quantity'], 3) . ' lb'
                        : number_format((float) $line['Quantity'], 0);

                    $lineField = ['receipt_line_id' => (int) $line['ReceiptLineID']];
                    ?>

                    <tr>

                        <td title="<?= escapeOutput($lineCode) ?>">

                            <?= escapeOutput($line['ProductName']) ?>
                            <?= (int) $line['Taxable'] === 1 ? ' *' : '' ?>

                            <span class="sale-line-price">
                                <?= escapeOutput($priceText) ?>
                            </span>

                            <?php printLineCode($line['UPC'], $line['PLUCode']); ?>

                        </td>

                        <td>

                            <div class="sale-line-controls">

                                <?php if ($canChange && !$isWeighed): ?>

                                    <?php
                                    printLineButton(
                                        $hiddenFields,
                                        $lineField,
                                        $names['remove'],
                                        '&minus;',
                                        'Remove one'
                                    );
                                    ?>

                                <?php endif; ?>

                                <span class="sale-line-count">
                                    <?= escapeOutput($quantityText) ?>
                                </span>

                                <?php if ($canChange && !$isWeighed): ?>

                                    <?php
                                    printLineButton(
                                        $hiddenFields,
                                        ['product_id' => (int) $line['ProductID'], 'quantity' => 1],
                                        $names['add'],
                                        '+',
                                        'Add one more'
                                    );
                                    ?>

                                <?php elseif ($canChange): ?>

                                    <?php
                                    printLineButton(
                                        $hiddenFields,
                                        $lineField,
                                        $names['removeAll'],
                                        '&times;',
                                        'Remove this item',
                                        'Remove this item and put it back in stock?'
                                    );
                                    ?>

                                <?php endif; ?>

                            </div>

                        </td>

                        <td>
                            $<?= escapeOutput(number_format((float) $line['LineTotal'], 2)) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php
}


// The script behind the quantity box, code box, weight box, and touch buttons
function printItemEntryScript()
{
    ?>

    <script>
        (function () {

            const quantityBox = document.getElementById('saleQuantityBox');
            const countInput = document.getElementById('sale_item_quantity');
            const codesButton = document.getElementById('saleCodesButton');
            const codesArea = document.querySelector('.sale-receipt-area');

            const scanForm = document.getElementById('saleScanForm');
            const codeInput = document.getElementById('product_code');
            const scanProductID = document.getElementById('scanner_product_id');
            const weightField = document.getElementById('saleWeightField');
            const weightLabel = document.getElementById('sale_quantity_label');
            const weightInput = document.getElementById('quantity');

            const tiles = document.querySelectorAll('.sale-product-button');
            const tileForms = document.querySelectorAll('.sale-product-form');

            let weighing = false;

            // The number of items to add: a whole number from 1 to 999
            function itemCount() {

                let count = parseInt(countInput.value, 10);

                if (isNaN(count) || count < 1) {
                    count = 1;
                }

                if (count > 999) {
                    count = 999;
                }

                return count;
            }

            // Weighed items use the weight box, counted items use the quantity above
            function setWeighing(isWeighed) {

                weighing = isWeighed;

                weightField.hidden = !isWeighed;
                weightInput.required = isWeighed;

                countInput.disabled = isWeighed;
                quantityBox.hidden = isWeighed;

                if (isWeighed) {

                    weightLabel.textContent = 'Weight (lb)';
                    weightInput.min = '0.001';
                    weightInput.step = '0.001';
                    weightInput.placeholder = '0.000';
                    weightInput.value = '';

                } else {

                    weightLabel.textContent = 'Quantity';
                    weightInput.min = '1';
                    weightInput.step = '1';
                    weightInput.placeholder = '';
                    weightInput.value = String(itemCount());
                }
            }

            // Find the touch button for a typed UPC or PLU code
            function findTile(code) {

                const typedCode = code.trim();

                if (typedCode === '') {
                    return null;
                }

                for (const tile of tiles) {

                    if (
                        typedCode === (tile.dataset.upc || '').trim()
                        || typedCode === (tile.dataset.plu || '').trim()
                    ) {
                        return tile;
                    }
                }

                return null;
            }

            // A typed code decides whether the weight box is needed
            function syncFromCode() {

                const tile = findTile(codeInput.value);

                scanProductID.value = tile ? (tile.dataset.productId || '0') : '0';

                const isWeighed = tile !== null && tile.dataset.unitType === 'Pound';

                if (isWeighed !== weighing) {
                    setWeighing(isWeighed);
                }
            }

            function changeCount(amount) {

                countInput.value = String(
                    Math.min(999, Math.max(1, itemCount() + amount))
                );

                if (!weighing) {
                    weightInput.value = String(itemCount());
                }
            }

            countInput.addEventListener('change', function () {
                changeCount(0);
            });

            countInput.addEventListener('focus', function () {
                countInput.select();
            });

            codeInput.addEventListener('input', syncFromCode);

            // A weighed item asks for its weight before it is added
            tiles.forEach(function (tile) {

                if (!tile.classList.contains('sale-weighted-product-button')) {
                    return;
                }

                tile.addEventListener('click', function () {

                    scanProductID.value = tile.dataset.productId || '0';
                    codeInput.value = tile.dataset.productCode || '';

                    setWeighing(true);
                    weightInput.focus();
                });
            });

            function capToStock(tile, input, weighed) {
                if (!tile) return true; // Backend validates codes not shown in tiles.
                const availableRaw = Math.max(0, Number(tile.dataset.stock || 0));
                const available = weighed ? Math.floor(availableRaw * 1000 + 1e-7) / 1000 : Math.floor(availableRaw);
                let requested = Number(input.value);
                if (!Number.isFinite(requested) || requested <= 0) return true;
                if (available <= 0) {
                    window.alert('This product is out of stock.');
                    return false;
                }
                if (requested > available) {
                    input.value = weighed ? available.toFixed(3) : String(available);
                    const productName = tile.querySelector('strong')?.textContent.trim() || 'this product';
                    window.alert('Only ' + (weighed ? available.toFixed(3) + ' lb' : available + ' each') +
                        ' of ' + productName + ' is available. The amount has been adjusted to the maximum available.');
                }
                return true;
            }

            // Touching an item adds the quantity that was set above
            tileForms.forEach(function (form) {

                form.addEventListener('submit', function (event) {
                    form.elements.quantity.value = String(itemCount());
                    const tile = form.querySelector('.sale-product-button');
                    if (!capToStock(tile, form.elements.quantity, false)) event.preventDefault();
                });
            });

            scanForm.addEventListener('submit', function (event) {
                if (!weighing) weightInput.value = String(itemCount());
                const tile = findTile(codeInput.value);
                if (!capToStock(tile, weightInput, weighing)) event.preventDefault();
            });

            // The Codes button shows or hides the product codes in the list
            if (codesButton && codesArea) {

                let showCodes = false;

                try {
                    showCodes = sessionStorage.getItem('fnhShowCodes') === '1';
                } catch (error) {
                    showCodes = false;
                }

                function showOrHideCodes() {

                    codesArea.classList.toggle('sale-show-codes', showCodes);
                    codesButton.setAttribute('aria-pressed', showCodes ? 'true' : 'false');
                    codesButton.classList.toggle('sale-codes-button-on', showCodes);
                    codesButton.innerHTML = showCodes ? '&minus; UPC' : '&plus; UPC';
                }

                codesButton.addEventListener('click', function () {

                    showCodes = !showCodes;

                    try {
                        sessionStorage.setItem('fnhShowCodes', showCodes ? '1' : '0');
                    } catch (error) {
                        // The choice is kept for this page only
                    }

                    showOrHideCodes();
                });

                codesButton.hidden = false;
                showOrHideCodes();
            }

            setWeighing(false);

        })();
    </script>

    <?php
}