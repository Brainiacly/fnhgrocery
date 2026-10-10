<?php // includes/discounts.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

// Describe a discount in plain words, such as 10% off before tax
function describeDiscount(array $discount): string
{
    $discountValue = (float) $discount['DiscountValue'];

    if ($discount['DiscountKind'] === 'Percent') {
        $amountText =
            rtrim(rtrim(number_format($discountValue, 2), '0'), '.')
            . '%';
    } else {
        $amountText = '$' . number_format($discountValue, 2);
    }

    return $amountText . ' off ' . strtolower($discount['TaxTiming']);
}

// A dollar discount can be smaller than requested when little was left to discount
function discountWasLimited(array $discount): bool
{
    return $discount['DiscountKind'] === 'Dollar'
        && (float) $discount['AppliedAmount']
        < (float) $discount['DiscountValue'] - 0.004;
}

// Load the totals for a sale from the database calculation
function fetchSaleTotals(PDO $db, int $receiptID): array
{
    $statement = $db->prepare(
        'CALL sp_get_sale_totals(:receiptID)'
    );

    $statement->execute([
        ':receiptID' => $receiptID
    ]);

    $totals = $statement->fetch();
    $statement->closeCursor();

    if (!$totals) {
        return [
            'GrossSubtotal' => 0,
            'PreTaxDiscount' => 0,
            'SubtotalAfterDiscount' => 0,
            'TaxableSubtotal' => 0,
            'TaxAmount' => 0,
            'PostTaxDiscount' => 0,
            'TotalDue' => 0
        ];
    }

    return $totals;
}

// Load the discounts on a sale in the order they were added
function fetchSaleDiscounts(PDO $db, int $receiptID): array
{
    $statement = $db->prepare(
        '
        SELECT
            DiscountID,
            DiscountNumber,
            DiscountKind,
            TaxTiming,
            DiscountValue,
            Reason,
            AppliedAmount,
            AppliedByUsername
        FROM vw_sale_discounts
        WHERE ReceiptID = :receiptID
        ORDER BY DiscountNumber
        '
    );

    $statement->execute([
        ':receiptID' => $receiptID
    ]);

    return $statement->fetchAll();
}

// Load the saved reasons, the most used first
function fetchDiscountReasons(PDO $db): array
{
    $statement = $db->query(
        '
        SELECT
            ReasonID,
            ReasonText
        FROM discountreason
        WHERE Active = 1
        ORDER BY
            TimesUsed DESC,
            ReasonText
        LIMIT 50
        '
    );

    return $statement->fetchAll();
}

// Start values for the discount form
function defaultDiscountForm(): array
{
    return [
        'kind' => 'Percent',
        'timing' => 'Before Tax',
        'value' => '',
        'reason_choice' => '',
        'reason_text' => '',
        'save' => true
    ];
}

// Read the discount form from a submitted request
function readDiscountForm(array $source): array
{
    return [
        'kind' => (string) ($source['discount_kind'] ?? ''),
        'timing' => (string) ($source['tax_timing'] ?? ''),
        'value' => trim((string) ($source['discount_value'] ?? '')),
        'reason_choice' => (string) ($source['reason_choice'] ?? ''),
        'reason_text' => trim((string) ($source['reason_text'] ?? '')),
        'save' => isset($source['save_reason'])
    ];
}

// Check the discount form and work out which reason text to record
function checkDiscountForm(PDO $db, array $form): array
{
    $result = [
        'error' => '',
        'reason' => '',
        'save' => 0
    ];

    if (!in_array($form['kind'], ['Percent', 'Dollar'], true)) {
        $result['error'] = 'Choose a percent or dollar discount.';
        return $result;
    }

    if (!in_array($form['timing'], ['Before Tax', 'After Tax'], true)) {
        $result['error'] = 'Choose whether the discount applies before or after tax.';
        return $result;
    }

    if (
        !preg_match('/^\d{1,7}(\.\d{1,2})?$/', $form['value'])
        || (float) $form['value'] <= 0
    ) {
        $result['error'] =
            'Enter a discount amount greater than zero '
            . 'with no more than two decimal places.';
        return $result;
    }

    if ($form['kind'] === 'Percent' && (float) $form['value'] > 100) {
        $result['error'] = 'A percent discount cannot be more than 100.';
        return $result;
    }

    if ($form['reason_choice'] === 'new') {
        if ($form['reason_text'] === '') {
            $result['error'] = 'Enter a reason for the discount.';
        } elseif (strlen($form['reason_text']) > 255) {
            $result['error'] =
                'The discount reason cannot contain more than 255 characters.';
        } else {
            $result['reason'] = $form['reason_text'];
            $result['save'] = $form['save'] ? 1 : 0;
        }

        return $result;
    }

    if (ctype_digit($form['reason_choice']) && (int) $form['reason_choice'] > 0) {
        $reasonStatement = $db->prepare(
            '
            SELECT ReasonText
            FROM discountreason
            WHERE ReasonID = :reasonID
              AND Active = 1
            LIMIT 1
            '
        );

        $reasonStatement->execute([
            ':reasonID' => (int) $form['reason_choice']
        ]);

        $reasonText = $reasonStatement->fetchColumn();

        if ($reasonText !== false) {
            $result['reason'] = (string) $reasonText;
            return $result;
        }
    }

    $result['error'] = 'Choose a reason for the discount.';

    return $result;
}


// ---- Coupons ----

// The last digit of a UPC-A barcode, worked out from the first 11 digits
function upcCheckDigit($firstEleven)
{
    $oddTotal = 0;
    $evenTotal = 0;

    for ($position = 0; $position < 11; $position++) {
        if ($position % 2 === 0) {
            $oddTotal += (int) $firstEleven[$position];
        } else {
            $evenTotal += (int) $firstEleven[$position];
        }
    }

    return (string) ((10 - (($oddTotal * 3 + $evenTotal) % 10)) % 10);
}


// A coupon code is 12 digits that start with 5 and end with the right check digit
function isCouponCode($code)
{
    return preg_match('/^5[0-9]{11}$/', (string) $code) === 1
        && upcCheckDigit(substr($code, 0, 11)) === $code[11];
}


// The next unused coupon code
function newCouponCode(PDO $db)
{
    $next = (int) $db->query(
        'SELECT COALESCE(MAX(CouponID), 0) + 1 FROM coupon'
    )->fetchColumn();

    do {
        $firstEleven = '5' . str_pad((string) $next, 10, '0', STR_PAD_LEFT);
        $code = $firstEleven . upcCheckDigit($firstEleven);

        $statement = $db->prepare(
            'SELECT COUNT(*) FROM coupon WHERE CouponCode = :code'
        );

        $statement->execute([':code' => $code]);

        $next++;
    } while ((int) $statement->fetchColumn() > 0);

    return $code;
}


// The coupons on a sale, in the order they were added
function fetchSaleCoupons(PDO $db, int $receiptID): array
{
    $statement = $db->prepare(
        '
        SELECT
            SaleCouponID,
            CouponNumber,
            CouponCode,
            Description,
            ProductName,
            RequiredQuantity,
            UnitsCovered,
            AppliedAmount
        FROM vw_sale_coupons
        WHERE ReceiptID = :receiptID
        ORDER BY CouponNumber
        '
    );

    $statement->execute([':receiptID' => $receiptID]);

    return $statement->fetchAll();
}


// What a coupon is worth, in words
function describeCouponValue(array $coupon): string
{
    $value = (float) $coupon['DiscountValue'];

    if ($coupon['DiscountKind'] === 'Percent') {
        return rtrim(rtrim(number_format($value, 2), '0'), '.') . '% off';
    }

    return '$' . number_format($value, 2) . ' off';
}


// The coupon's barcode as a picture a scanner can read (UPC-A)
function couponBarcodeSvg($code)
{
    $leftPatterns = [
        '0001101',
        '0011001',
        '0010011',
        '0111101',
        '0100011',
        '0110001',
        '0101111',
        '0111011',
        '0110111',
        '0001011'
    ];

    $bits = '101';

    for ($position = 0; $position < 6; $position++) {
        $bits .= $leftPatterns[(int) $code[$position]];
    }

    $bits .= '01010';

    for ($position = 6; $position < 12; $position++) {
        $bits .= strtr($leftPatterns[(int) $code[$position]], '01', '10');
    }

    $bits .= '101';

    $bars = '';
    $start = null;

    for ($module = 0; $module <= strlen($bits); $module++) {
        $isBar = $module < strlen($bits) && $bits[$module] === '1';

        if ($isBar && $start === null) {
            $start = $module;
        }

        if (!$isBar && $start !== null) {
            $bars .= '<rect x="' . (9 + $start) . '" y="0" width="'
                . ($module - $start) . '" height="50"/>';

            $start = null;
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 113 64"'
        . ' role="img" aria-label="Barcode ' . $code . '">'
        . '<rect x="0" y="0" width="113" height="64" fill="#fff"/>'
        . '<g fill="#000">' . $bars . '</g>'
        . '<text x="56.5" y="61" font-size="9" text-anchor="middle"'
        . ' font-family="monospace">' . $code . '</text></svg>';
}