-- W2 05 Create Views and Procedures.sql
-- Brian Phillips
-- CSC 680
-- FnH Groceries

USE fnh_groceries;

-- Assignment 1 reporting views must also include Assignment 2 paid sales
DROP VIEW IF EXISTS vw_customerpurchasehistory;

CREATE VIEW vw_customerpurchasehistory AS
SELECT
    cu.CustomerID,
    cu.LoyaltyNumber,
    cu.FirstName,
    cu.LastName,
    cu.LoyaltyPoints,
    rs.ReceiptID,
    rs.TransactionNumber,
    rs.PurchaseDateTime,
    rs.StoreNumber,
    rs.StoreName,
    rs.GrossSubtotal,
    rs.LineDiscountAmount,
    rs.Subtotal,
    rs.ReceiptDiscountAmount,
    ROUND(
        rs.LineDiscountAmount
        + rs.ReceiptDiscountAmount,
        2
    ) AS TotalDiscountAmount,
    rs.TaxAmount,
    rs.TotalAmount,
    rs.PaymentMethod
FROM customer cu
JOIN vw_receiptsummary rs
    ON rs.CustomerID = cu.CustomerID
WHERE rs.Status IN ('Completed', 'Paid');

DROP VIEW IF EXISTS vw_dailysalessummary;

CREATE VIEW vw_dailysalessummary AS
SELECT
    rs.StoreID,
    rs.StoreNumber,
    rs.StoreName,
    CAST(rs.PurchaseDateTime AS DATE) AS SaleDate,
    COUNT(*) AS TransactionCount,
    ROUND(SUM(rs.GrossSubtotal), 2) AS GrossSales,
    ROUND(
        SUM(
            rs.LineDiscountAmount
            + rs.ReceiptDiscountAmount
        ),
        2
    ) AS TotalDiscounts,
    ROUND(
        SUM(
            rs.Subtotal
            - rs.ReceiptDiscountAmount
        ),
        2
    ) AS NetSales,
    ROUND(SUM(rs.TaxAmount), 2) AS TotalTax,
    ROUND(SUM(rs.TotalAmount), 2) AS TotalCollected
FROM vw_receiptsummary rs
WHERE rs.Status IN ('Completed', 'Paid')
GROUP BY
    rs.StoreID,
    rs.StoreNumber,
    rs.StoreName,
    CAST(rs.PurchaseDateTime AS DATE);

DROP VIEW IF EXISTS vw_operatoractivity;

CREATE VIEW vw_operatoractivity AS
SELECT
    o.OperatorID,
    o.EmployeeNumber,
    o.Username,
    CONCAT(
        o.FirstName,
        ' ',
        o.LastName
    ) AS OperatorName,
    o.Role,
    s.StoreNumber AS CurrentStoreNumber,
    s.StoreName AS CurrentStoreName,
    COUNT(rs.ReceiptID) AS TransactionCount,
    COALESCE(
        ROUND(
            SUM(
                rs.Subtotal
                - rs.ReceiptDiscountAmount
            ),
            2
        ),
        0.00
    ) AS TotalSales,
    MIN(rs.PurchaseDateTime) AS FirstTransaction,
    MAX(rs.PurchaseDateTime) AS LastTransaction,
    o.Active
FROM operator o
JOIN store s
    ON s.StoreID = o.StoreID
LEFT JOIN vw_receiptsummary rs
    ON rs.CashierID = o.OperatorID
   AND rs.Status IN ('Completed', 'Paid')
GROUP BY
    o.OperatorID,
    o.EmployeeNumber,
    o.Username,
    o.FirstName,
    o.LastName,
    o.Role,
    s.StoreNumber,
    s.StoreName,
    o.Active;

-- Store stock detail
DROP VIEW IF EXISTS vw_store_stock;

CREATE VIEW vw_store_stock AS
SELECT
    s.StoreID,
    s.StoreNumber,
    s.StoreName,
    d.DepartmentID,
    d.DepartmentName,
    p.ProductID,
    p.UPC,
    p.PLUCode,
    p.ProductName,
    p.UnitType,
    p.RetailPrice,
    p.Taxable,
    si.StockQuantity,
    si.Aisle,
    si.SectionName,
    si.ShelfLocation
FROM storeinventory si
JOIN store s
    ON s.StoreID = si.StoreID
JOIN product p
    ON p.ProductID = si.ProductID
JOIN department d
    ON d.DepartmentID = p.DepartmentID
WHERE s.Active = 1
  AND p.Active = 1;

-- Store stock total
DROP VIEW IF EXISTS vw_store_stock_total;

CREATE VIEW vw_store_stock_total AS
SELECT
    s.StoreID,
    s.StoreNumber,
    s.StoreName,
    COALESCE(SUM(si.StockQuantity), 0) AS TotalStockQuantity
FROM store s
LEFT JOIN storeinventory si
    ON si.StoreID = s.StoreID
WHERE s.Active = 1
GROUP BY
    s.StoreID,
    s.StoreNumber,
    s.StoreName;

-- POS products
DROP VIEW IF EXISTS vw_pos_products;

CREATE VIEW vw_pos_products AS
SELECT
    si.StoreID,
    p.ProductID,
    p.DepartmentID,
    d.DepartmentName,
    p.UPC,
    p.PLUCode,
    p.ProductName,
    p.UnitType,
    p.RetailPrice,
    p.Taxable,
    si.StockQuantity
FROM product p
JOIN department d
    ON d.DepartmentID = p.DepartmentID
JOIN storeinventory si
    ON si.ProductID = p.ProductID
WHERE p.Active = 1;

-- Sale detail
DROP VIEW IF EXISTS vw_sale_detail;

CREATE VIEW vw_sale_detail AS
SELECT
    sr.ReceiptID,
    sr.TransactionNumber,
    sr.StoreID,
    sr.RegisterID,
    sr.OperatorID,
    sr.CustomerID,
    sr.TransactionDateTime,
    sr.CheckoutDateTime,
    sr.Status,
    srl.ReceiptLineID,
    srl.LineNumber,
    p.ProductID,
    p.UPC,
    p.PLUCode,
    srl.ProductNameAtSale AS ProductName,
    srl.UnitTypeAtSale AS UnitType,
    srl.TaxableAtSale AS Taxable,
    srl.Quantity,
    srl.UnitPrice,
    srl.LineDiscountAmount,
    ROUND(
        (srl.Quantity * srl.UnitPrice)
        - srl.LineDiscountAmount,
        2
    ) AS LineTotal
FROM salesreceipt sr
JOIN salesreceiptline srl
    ON srl.ReceiptID = sr.ReceiptID
JOIN product p
    ON p.ProductID = srl.ProductID;

-- Sale summary
DROP VIEW IF EXISTS vw_sale_summary;

CREATE VIEW vw_sale_summary AS
SELECT
    sr.ReceiptID,
    sr.TransactionNumber,
    sr.StoreID,
    sr.RegisterID,
    sr.OperatorID,
    sr.CustomerID,
    sr.TransactionDateTime,
    sr.CheckoutDateTime,
    sr.Status,
    sr.ReceiptDiscountAmount,
    sr.SubtotalAmount,
    sr.TaxableSubtotalAmount,
    sr.TaxAmount,
    sr.TotalAmount,
    sr.PaymentMethod,
    sr.AmountTendered,
    sr.ChangeDue,
    COUNT(srl.ReceiptLineID) AS LineCount,
    COALESCE(SUM(srl.Quantity), 0) AS ItemQuantity
FROM salesreceipt sr
LEFT JOIN salesreceiptline srl
    ON srl.ReceiptID = sr.ReceiptID
GROUP BY
    sr.ReceiptID,
    sr.TransactionNumber,
    sr.StoreID,
    sr.RegisterID,
    sr.OperatorID,
    sr.CustomerID,
    sr.TransactionDateTime,
    sr.CheckoutDateTime,
    sr.Status,
    sr.ReceiptDiscountAmount,
    sr.SubtotalAmount,
    sr.TaxableSubtotalAmount,
    sr.TaxAmount,
    sr.TotalAmount,
    sr.PaymentMethod,
    sr.AmountTendered,
    sr.ChangeDue;

-- Transaction journal
DROP VIEW IF EXISTS vw_transaction_journal;

CREATE VIEW vw_transaction_journal AS
SELECT
    tj.JournalID,
    tj.ReceiptID,
    tj.TransactionNumber,
    tj.StoreID,
    s.StoreNumber,
    s.StoreName,
    tj.RegisterID,
    r.RegisterNumber,
    tj.OpenedByOperatorID,
    CONCAT(
        opened.FirstName,
        ' ',
        opened.LastName
    ) AS OpenedByOperator,
    tj.ClosedByOperatorID,
    CASE
        WHEN closed.OperatorID IS NULL THEN NULL
        ELSE CONCAT(
            closed.FirstName,
            ' ',
            closed.LastName
        )
    END AS ClosedByOperator,
    tj.OpenedDateTime,
    tj.ClosedDateTime,
    tj.Status,
    tj.LineCount,
    tj.ItemQuantity,
    tj.SubtotalAmount,
    tj.DiscountAmount,
    tj.TaxableSubtotalAmount,
    tj.TaxAmount,
    tj.TotalAmount,
    tj.PaymentMethod,
    tj.AmountTendered,
    tj.ChangeDue
FROM transactionjournal tj
JOIN store s
    ON s.StoreID = tj.StoreID
JOIN register r
    ON r.RegisterID = tj.RegisterID
JOIN operator opened
    ON opened.OperatorID = tj.OpenedByOperatorID
LEFT JOIN operator closed
    ON closed.OperatorID = tj.ClosedByOperatorID;

-- Start a sale
DROP PROCEDURE IF EXISTS sp_start_sale;

DELIMITER //

CREATE PROCEDURE sp_start_sale(
    IN pStoreID INT,
    IN pRegisterID INT,
    IN pOperatorID INT
)
BEGIN
    DECLARE newReceiptID BIGINT;
    DECLARE newTransactionNumber VARCHAR(40);
    DECLARE lockedRegisterID INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF NOT EXISTS (
        SELECT 1
        FROM store
        WHERE StoreID = pStoreID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The selected store is not active';
    END IF;

    SELECT RegisterID
    INTO lockedRegisterID
    FROM register
    WHERE RegisterID = pRegisterID
      AND StoreID = pStoreID
      AND Active = 1
    FOR UPDATE;

    IF lockedRegisterID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The selected register is not active';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
          AND StoreID = pStoreID
          AND Active = 1
          AND Role IN ('Administrator', 'Operator')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The operator is not authorized for this store';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM salesreceipt
        WHERE StoreID = pStoreID
          AND RegisterID = pRegisterID
          AND Status = 'Open'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This register is currently in use';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM salesreceipt
        WHERE OperatorID = pOperatorID
          AND Status = 'Open'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This operator already has an open sale';
    END IF;

    SET newTransactionNumber =
        CONCAT(
            'S',
            LPAD(pStoreID, 3, '0'),
            '-',
            DATE_FORMAT(NOW(6), '%Y%m%d%H%i%s%f'),
            '-',
            LPAD(pOperatorID, 4, '0')
        );

    INSERT INTO salesreceipt (
        TransactionNumber,
        StoreID,
        RegisterID,
        OperatorID,
        CustomerID,
        TransactionDateTime,
        CheckoutDateTime,
        Status,
        ReceiptDiscountAmount,
        SubtotalAmount,
        TaxableSubtotalAmount,
        TaxAmount,
        TotalAmount,
        PaymentMethod,
        AmountTendered,
        ChangeDue
    )
    VALUES (
        newTransactionNumber,
        pStoreID,
        pRegisterID,
        pOperatorID,
        NULL,
        NOW(),
        NULL,
        'Open',
        0.00,
        0.00,
        0.00,
        0.00,
        0.00,
        'Cash',
        NULL,
        NULL
    );

    SET newReceiptID = LAST_INSERT_ID();

    INSERT INTO transactionjournal (
        ReceiptID,
        TransactionNumber,
        StoreID,
        RegisterID,
        OpenedByOperatorID,
        OpenedDateTime,
        Status
    )
    VALUES (
        newReceiptID,
        newTransactionNumber,
        pStoreID,
        pRegisterID,
        pOperatorID,
        NOW(),
        'Open'
    );

    COMMIT;

    SELECT
        newReceiptID AS ReceiptID,
        newTransactionNumber AS TransactionNumber;
END //

DELIMITER ;

-- Add one item
DROP PROCEDURE IF EXISTS sp_add_sale_item;

DELIMITER //

CREATE PROCEDURE sp_add_sale_item(
    IN pReceiptID BIGINT,
    IN pProductID INT,
    IN pQuantity DECIMAL(12,3),
    IN pActingOperatorID INT
)
BEGIN
    DECLARE saleStoreID INT DEFAULT NULL;
    DECLARE saleOperatorID INT DEFAULT NULL;
    DECLARE saleStatus VARCHAR(20);
    DECLARE availableStock DECIMAL(12,3);
    DECLARE currentPrice DECIMAL(10,2);
    DECLARE currentProductName VARCHAR(120);
    DECLARE currentUnitType VARCHAR(20);
    DECLARE currentTaxable TINYINT DEFAULT 0;
    DECLARE existingReceiptLineID BIGINT DEFAULT NULL;
    DECLARE nextLineNumber INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF pQuantity IS NULL OR pQuantity <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quantity must be greater than zero';
    END IF;

    START TRANSACTION;

    SELECT
        StoreID,
        OperatorID,
        Status
    INTO
        saleStoreID,
        saleOperatorID,
        saleStatus
    FROM salesreceipt
    WHERE ReceiptID = pReceiptID
    FOR UPDATE;

    IF saleStoreID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The sale does not exist';
    END IF;

    IF saleOperatorID <> pActingOperatorID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
    END IF;

    IF saleStatus <> 'Open' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Items can only be added to an open sale';
    END IF;

    SELECT
        si.StockQuantity,
        p.RetailPrice,
        p.ProductName,
        p.UnitType,
        p.Taxable
    INTO
        availableStock,
        currentPrice,
        currentProductName,
        currentUnitType,
        currentTaxable
    FROM storeinventory si
    JOIN product p
        ON p.ProductID = si.ProductID
    WHERE si.StoreID = saleStoreID
      AND si.ProductID = pProductID
      AND p.Active = 1
    FOR UPDATE;

    IF availableStock IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The product is not available at this store';
    END IF;

    IF currentUnitType = 'Each'
       AND pQuantity <> FLOOR(pQuantity) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'This product must use a whole-number quantity';
    END IF;

    IF availableStock < pQuantity THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'There is not enough stock for that quantity';
    END IF;

    SELECT ReceiptLineID
    INTO existingReceiptLineID
    FROM salesreceiptline
    WHERE ReceiptID = pReceiptID
      AND ProductID = pProductID
      AND UnitPrice = currentPrice
      AND ProductNameAtSale = currentProductName
      AND UnitTypeAtSale = currentUnitType
      AND TaxableAtSale = currentTaxable
    LIMIT 1;

    IF existingReceiptLineID IS NULL THEN
        SELECT COALESCE(MAX(LineNumber), 0) + 1
        INTO nextLineNumber
        FROM salesreceiptline
        WHERE ReceiptID = pReceiptID;

        INSERT INTO salesreceiptline (
            ReceiptID,
            LineNumber,
            ProductID,
            ProductNameAtSale,
            UnitTypeAtSale,
            TaxableAtSale,
            Quantity,
            UnitPrice,
            LineDiscountAmount
        )
        VALUES (
            pReceiptID,
            nextLineNumber,
            pProductID,
            currentProductName,
            currentUnitType,
            currentTaxable,
            pQuantity,
            currentPrice,
            0.00
        );
    ELSE
        UPDATE salesreceiptline
        SET Quantity = Quantity + pQuantity
        WHERE ReceiptLineID = existingReceiptLineID;
    END IF;

    UPDATE storeinventory
    SET StockQuantity = StockQuantity - pQuantity
    WHERE StoreID = saleStoreID
      AND ProductID = pProductID;

    UPDATE salesreceipt
    SET SubtotalAmount = (
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
        WHERE ReceiptID = pReceiptID
    ),
    TaxableSubtotalAmount = 0.00,
    TaxAmount = 0.00,
    TotalAmount = 0.00
    WHERE ReceiptID = pReceiptID;

    UPDATE transactionjournal tj
    JOIN salesreceipt sr
        ON sr.ReceiptID = tj.ReceiptID
    SET
        tj.LineCount = (
            SELECT COUNT(*)
            FROM salesreceiptline
            WHERE ReceiptID = pReceiptID
        ),
        tj.ItemQuantity = (
            SELECT COALESCE(SUM(Quantity), 0)
            FROM salesreceiptline
            WHERE ReceiptID = pReceiptID
        ),
        tj.SubtotalAmount = sr.SubtotalAmount,
        tj.DiscountAmount = sr.ReceiptDiscountAmount
    WHERE tj.ReceiptID = pReceiptID;

    COMMIT;
END //
DELIMITER ;

-- Remove one item
DROP PROCEDURE IF EXISTS sp_remove_sale_item;

DELIMITER //

CREATE PROCEDURE sp_remove_sale_item(
    IN pReceiptID BIGINT,
    IN pReceiptLineID BIGINT,
    IN pActingOperatorID INT
)
BEGIN
    DECLARE saleStoreID INT DEFAULT NULL;
    DECLARE saleOperatorID INT DEFAULT NULL;
    DECLARE saleStatus VARCHAR(20);
    DECLARE lineProductID INT DEFAULT NULL;
    DECLARE currentQuantity DECIMAL(12,3);
    DECLARE quantityToRemove DECIMAL(12,3);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT StoreID, OperatorID, Status
    INTO saleStoreID, saleOperatorID, saleStatus
    FROM salesreceipt
    WHERE ReceiptID = pReceiptID
    FOR UPDATE;

    IF saleStoreID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The sale does not exist';
    END IF;

    IF saleOperatorID <> pActingOperatorID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
    END IF;

    IF saleStatus <> 'Open' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Items can only be removed from an open sale';
    END IF;

    SELECT ProductID, Quantity
    INTO lineProductID, currentQuantity
    FROM salesreceiptline
    WHERE ReceiptID = pReceiptID
      AND ReceiptLineID = pReceiptLineID
    FOR UPDATE;

    IF lineProductID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The selected sale item does not exist';
    END IF;

    SET quantityToRemove = LEAST(currentQuantity, 1.000);

    IF currentQuantity > quantityToRemove THEN
        UPDATE salesreceiptline
        SET Quantity = Quantity - quantityToRemove
        WHERE ReceiptLineID = pReceiptLineID
          AND ReceiptID = pReceiptID;
    ELSE
        DELETE FROM salesreceiptline
        WHERE ReceiptLineID = pReceiptLineID
          AND ReceiptID = pReceiptID;
    END IF;

    UPDATE storeinventory
    SET StockQuantity = StockQuantity + quantityToRemove
    WHERE StoreID = saleStoreID
      AND ProductID = lineProductID;

    UPDATE salesreceipt
    SET SubtotalAmount = (
        SELECT COALESCE(
            ROUND(SUM((Quantity * UnitPrice) - LineDiscountAmount), 2),
            0.00
        )
        FROM salesreceiptline
        WHERE ReceiptID = pReceiptID
    ),
    TaxableSubtotalAmount = 0.00,
    TaxAmount = 0.00,
    TotalAmount = 0.00
    WHERE ReceiptID = pReceiptID;

    UPDATE transactionjournal tj
    JOIN salesreceipt sr ON sr.ReceiptID = tj.ReceiptID
    SET
        tj.LineCount = (
            SELECT COUNT(*)
            FROM salesreceiptline
            WHERE ReceiptID = pReceiptID
        ),
        tj.ItemQuantity = (
            SELECT COALESCE(SUM(Quantity), 0)
            FROM salesreceiptline
            WHERE ReceiptID = pReceiptID
        ),
        tj.SubtotalAmount = sr.SubtotalAmount,
        tj.DiscountAmount = sr.ReceiptDiscountAmount
    WHERE tj.ReceiptID = pReceiptID;

    COMMIT;
END //
DELIMITER ;

-- Complete a cash sale
DROP PROCEDURE IF EXISTS sp_checkout_sale;

DELIMITER //

CREATE PROCEDURE sp_checkout_sale(
    IN pReceiptID BIGINT,
    IN pAmountTendered DECIMAL(10,2),
    IN pActingOperatorID INT
)
BEGIN
    DECLARE saleOperatorID INT DEFAULT NULL;
    DECLARE saleStatus VARCHAR(20);
    DECLARE calculatedGrossSubtotal DECIMAL(10,2);
    DECLARE calculatedSubtotal DECIMAL(10,2);
    DECLARE calculatedGrossTaxable DECIMAL(10,2);
    DECLARE calculatedTaxableSubtotal DECIMAL(10,2);
    DECLARE calculatedDiscount DECIMAL(10,2);
    DECLARE taxableRatio DECIMAL(12,6);
    DECLARE taxableDiscount DECIMAL(10,2);
    DECLARE calculatedTax DECIMAL(10,2);
    DECLARE calculatedTotal DECIMAL(10,2);
    DECLARE calculatedChange DECIMAL(10,2);
    DECLARE receiptLineCount INT;
    DECLARE itemQuantity DECIMAL(12,3);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT
        OperatorID,
        Status,
        ReceiptDiscountAmount
    INTO
        saleOperatorID,
        saleStatus,
        calculatedDiscount
    FROM salesreceipt
    WHERE ReceiptID = pReceiptID
    FOR UPDATE;

    IF saleOperatorID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The sale does not exist';
    END IF;

    IF saleOperatorID <> pActingOperatorID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot complete another operator''s sale';
    END IF;

    IF saleStatus <> 'Open' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only an open sale can be checked out';
    END IF;

    SELECT
        COUNT(*),
        COALESCE(SUM(Quantity), 0),
        COALESCE(
            ROUND(
                SUM(
                    (Quantity * UnitPrice)
                    - LineDiscountAmount
                ),
                2
            ),
            0.00
        )
    INTO
        receiptLineCount,
        itemQuantity,
        calculatedGrossSubtotal
    FROM salesreceiptline
    WHERE ReceiptID = pReceiptID;

    IF receiptLineCount = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'At least one item is required before checkout';
    END IF;

    SET calculatedSubtotal =
        ROUND(
            calculatedGrossSubtotal
            - calculatedDiscount,
            2
        );

    IF calculatedSubtotal < 0 THEN
        SET calculatedSubtotal = 0.00;
    END IF;

    SELECT COALESCE(
        ROUND(
            SUM(
                CASE
                    WHEN srl.TaxableAtSale = 1 THEN
                        (srl.Quantity * srl.UnitPrice)
                        - srl.LineDiscountAmount
                    ELSE 0.00
                END
            ),
            2
        ),
        0.00
    )
    INTO calculatedGrossTaxable
    FROM salesreceiptline srl
    WHERE srl.ReceiptID = pReceiptID;

    IF calculatedGrossSubtotal > 0 THEN
        SET taxableRatio =
            calculatedGrossTaxable
            / calculatedGrossSubtotal;
    ELSE
        SET taxableRatio = 0;
    END IF;

    SET taxableDiscount =
        ROUND(
            calculatedDiscount
            * taxableRatio,
            2
        );

    SET calculatedTaxableSubtotal =
        ROUND(
            calculatedGrossTaxable
            - taxableDiscount,
            2
        );

    IF calculatedTaxableSubtotal < 0 THEN
        SET calculatedTaxableSubtotal = 0.00;
    END IF;

    IF calculatedTaxableSubtotal > calculatedSubtotal THEN
        SET calculatedTaxableSubtotal = calculatedSubtotal;
    END IF;

    SET calculatedTax =
        ROUND(
            calculatedTaxableSubtotal * 0.0775,
            2
        );

    SET calculatedTotal =
        ROUND(
            calculatedSubtotal
            + calculatedTax,
            2
        );

    IF pAmountTendered IS NULL
       OR pAmountTendered < calculatedTotal THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The cash tendered is less than the total due';
    END IF;

    SET calculatedChange =
        ROUND(
            pAmountTendered
            - calculatedTotal,
            2
        );

    UPDATE salesreceipt
    SET
        CheckoutDateTime = NOW(),
        Status = 'Paid',
        SubtotalAmount = calculatedSubtotal,
        TaxableSubtotalAmount = calculatedTaxableSubtotal,
        TaxAmount = calculatedTax,
        TotalAmount = calculatedTotal,
        PaymentMethod = 'Cash',
        AmountTendered = pAmountTendered,
        ChangeDue = calculatedChange
    WHERE ReceiptID = pReceiptID;

    UPDATE transactionjournal
    SET
        ClosedByOperatorID = pActingOperatorID,
        ClosedDateTime = NOW(),
        Status = 'Paid',
        LineCount = receiptLineCount,
        ItemQuantity = itemQuantity,
        SubtotalAmount = calculatedSubtotal,
        DiscountAmount = calculatedDiscount,
        TaxableSubtotalAmount = calculatedTaxableSubtotal,
        TaxAmount = calculatedTax,
        TotalAmount = calculatedTotal,
        PaymentMethod = 'Cash',
        AmountTendered = pAmountTendered,
        ChangeDue = calculatedChange
    WHERE ReceiptID = pReceiptID;

    COMMIT;

    SELECT
        ReceiptID,
        TransactionNumber,
        SubtotalAmount,
        TaxableSubtotalAmount,
        TaxAmount,
        TotalAmount,
        AmountTendered,
        ChangeDue,
        CheckoutDateTime,
        Status
    FROM salesreceipt
    WHERE ReceiptID = pReceiptID;
END //

DELIMITER ;

-- Cancel or clear an open sale
DROP PROCEDURE IF EXISTS sp_void_sale;

DELIMITER //

CREATE PROCEDURE sp_void_sale(
    IN pReceiptID BIGINT,
    IN pActingOperatorID INT
)
BEGIN
    DECLARE saleStoreID INT DEFAULT NULL;
    DECLARE saleOperatorID INT DEFAULT NULL;
    DECLARE saleStatus VARCHAR(20);
    DECLARE actingStoreID INT DEFAULT NULL;
    DECLARE actingRole VARCHAR(20);
    DECLARE actingActive TINYINT DEFAULT 0;
    DECLARE journalStatus VARCHAR(20);
    DECLARE currentLineCount INT DEFAULT 0;
    DECLARE currentItemQuantity DECIMAL(12,3) DEFAULT 0;
    DECLARE currentSubtotal DECIMAL(10,2) DEFAULT 0;
    DECLARE currentDiscount DECIMAL(10,2) DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT
        StoreID,
        OperatorID,
        Status,
        SubtotalAmount,
        ReceiptDiscountAmount
    INTO
        saleStoreID,
        saleOperatorID,
        saleStatus,
        currentSubtotal,
        currentDiscount
    FROM salesreceipt
    WHERE ReceiptID = pReceiptID
    FOR UPDATE;

    IF saleStoreID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The sale does not exist';
    END IF;

    SELECT
        StoreID,
        Role,
        Active
    INTO
        actingStoreID,
        actingRole,
        actingActive
    FROM operator
    WHERE OperatorID = pActingOperatorID;

    IF actingStoreID IS NULL
       OR actingActive <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The acting operator is not active';
    END IF;

    IF actingStoreID <> saleStoreID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The transaction belongs to another store';
    END IF;

    IF saleOperatorID <> pActingOperatorID
       AND actingRole <> 'Administrator' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot cancel another operator''s sale';
    END IF;

    IF saleStatus <> 'Open' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only an open sale can be cancelled';
    END IF;

    SELECT
        COUNT(*),
        COALESCE(SUM(Quantity), 0)
    INTO
        currentLineCount,
        currentItemQuantity
    FROM salesreceiptline
    WHERE ReceiptID = pReceiptID;

    UPDATE storeinventory si
    JOIN (
        SELECT
            ProductID,
            SUM(Quantity) AS QuantityToRestore
        FROM salesreceiptline
        WHERE ReceiptID = pReceiptID
        GROUP BY ProductID
    ) saleItems
        ON saleItems.ProductID = si.ProductID
    SET si.StockQuantity =
        si.StockQuantity
        + saleItems.QuantityToRestore
    WHERE si.StoreID = saleStoreID;

    IF saleOperatorID = pActingOperatorID THEN
        SET journalStatus = 'Cancelled';
    ELSE
        SET journalStatus = 'Cleared';
    END IF;

    UPDATE salesreceipt
    SET
        CheckoutDateTime = NOW(),
        Status = 'Voided',
        TaxableSubtotalAmount = 0.00,
        TaxAmount = 0.00,
        TotalAmount = 0.00,
        AmountTendered = NULL,
        ChangeDue = NULL
    WHERE ReceiptID = pReceiptID;

    UPDATE transactionjournal
    SET
        ClosedByOperatorID = pActingOperatorID,
        ClosedDateTime = NOW(),
        Status = journalStatus,
        LineCount = currentLineCount,
        ItemQuantity = currentItemQuantity,
        SubtotalAmount = currentSubtotal,
        DiscountAmount = currentDiscount,
        TaxableSubtotalAmount = 0.00,
        TaxAmount = 0.00,
        TotalAmount = 0.00,
        PaymentMethod = NULL,
        AmountTendered = NULL,
        ChangeDue = NULL
    WHERE ReceiptID = pReceiptID;

    COMMIT;
END //

DELIMITER ;

-- Administrator operator changes that may affect an open sale
-- These procedures replace the Week 1 versions after Week 2 sale support exists.

DROP PROCEDURE IF EXISTS sp_update_operator;

DELIMITER //

CREATE PROCEDURE sp_update_operator(
    IN pOperatorID INT,
    IN pStoreID INT,
    IN pUsername VARCHAR(50),
    IN pPasswordHash VARCHAR(255),
    IN pFirstName VARCHAR(60),
    IN pMiddleInitial CHAR(1),
    IN pLastName VARCHAR(60),
    IN pEmail VARCHAR(120),
    IN pPhone VARCHAR(20),
    IN pRole VARCHAR(20),
    IN pHireDate DATE,
    IN pCurrentOperatorID INT
)
BEGIN
    DECLARE currentOperatorRole VARCHAR(20);
    DECLARE currentStoreID INT;
    DECLARE actingRole VARCHAR(20);
    DECLARE actingActive TINYINT DEFAULT 0;
    DECLARE openReceiptID BIGINT DEFAULT NULL;
    DECLARE openSaleStoreID INT DEFAULT NULL;
    DECLARE currentLineCount INT DEFAULT 0;
    DECLARE currentItemQuantity DECIMAL(12,3) DEFAULT 0;
    DECLARE currentSubtotal DECIMAL(10,2) DEFAULT 0.00;
    DECLARE currentDiscount DECIMAL(10,2) DEFAULT 0.00;
    DECLARE cancelOpenSale TINYINT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT Role, Active
    INTO actingRole, actingActive
    FROM operator
    WHERE OperatorID = pCurrentOperatorID
    FOR UPDATE;

    IF actingRole IS NULL
       OR actingRole <> 'Administrator'
       OR actingActive <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrator access is required';
    END IF;

    SELECT Role, StoreID
    INTO currentOperatorRole, currentStoreID
    FROM operator
    WHERE OperatorID = pOperatorID
    FOR UPDATE;

    IF currentOperatorRole IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Operator does not exist';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM store
        WHERE StoreID = pStoreID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The selected store does not exist or is inactive';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM operator
        WHERE Username = pUsername
          AND OperatorID <> pOperatorID
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Username already exists';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM operator
        WHERE Email = pEmail
          AND OperatorID <> pOperatorID
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Email address already exists';
    END IF;

    IF pRole NOT IN ('Pending', 'Administrator', 'Operator') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid operator role';
    END IF;

    IF currentOperatorRole = 'Administrator'
       AND pRole <> 'Administrator'
       AND (
            SELECT COUNT(*)
            FROM operator
            WHERE Role = 'Administrator'
              AND Active = 1
       ) <= 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The final active administrator must remain an Administrator';
    END IF;

    IF currentStoreID <> pStoreID
       OR pRole = 'Pending' THEN
        SET cancelOpenSale = 1;
    END IF;

    IF cancelOpenSale = 1 THEN
        SELECT
            ReceiptID,
            StoreID,
            SubtotalAmount,
            ReceiptDiscountAmount
        INTO
            openReceiptID,
            openSaleStoreID,
            currentSubtotal,
            currentDiscount
        FROM salesreceipt
        WHERE OperatorID = pOperatorID
          AND Status = 'Open'
        LIMIT 1
        FOR UPDATE;

        IF openReceiptID IS NOT NULL THEN
            SELECT
                COUNT(*),
                COALESCE(SUM(Quantity), 0)
            INTO
                currentLineCount,
                currentItemQuantity
            FROM salesreceiptline
            WHERE ReceiptID = openReceiptID;

            UPDATE storeinventory si
            JOIN (
                SELECT
                    ProductID,
                    SUM(Quantity) AS QuantityToRestore
                FROM salesreceiptline
                WHERE ReceiptID = openReceiptID
                GROUP BY ProductID
            ) saleItems
                ON saleItems.ProductID = si.ProductID
            SET si.StockQuantity =
                si.StockQuantity
                + saleItems.QuantityToRestore
            WHERE si.StoreID = openSaleStoreID;

            UPDATE salesreceipt
            SET
                CheckoutDateTime = NOW(),
                Status = 'Voided',
                TaxableSubtotalAmount = 0.00,
                TaxAmount = 0.00,
                TotalAmount = 0.00,
                AmountTendered = NULL,
                ChangeDue = NULL
            WHERE ReceiptID = openReceiptID;

            UPDATE transactionjournal
            SET
                ClosedByOperatorID = pCurrentOperatorID,
                ClosedDateTime = NOW(),
                Status = 'Cleared',
                LineCount = currentLineCount,
                ItemQuantity = currentItemQuantity,
                SubtotalAmount = currentSubtotal,
                DiscountAmount = currentDiscount,
                TaxableSubtotalAmount = 0.00,
                TaxAmount = 0.00,
                TotalAmount = 0.00,
                PaymentMethod = NULL,
                AmountTendered = NULL,
                ChangeDue = NULL
            WHERE ReceiptID = openReceiptID;
        END IF;
    END IF;

    UPDATE operator
    SET
        StoreID = pStoreID,
        Username = pUsername,
        PasswordHash = CASE
            WHEN pPasswordHash IS NULL OR pPasswordHash = '' THEN PasswordHash
            ELSE pPasswordHash
        END,
        FirstName = pFirstName,
        MiddleInitial = NULLIF(pMiddleInitial, ''),
        LastName = pLastName,
        Email = pEmail,
        Phone = NULLIF(pPhone, ''),
        Role = pRole,
        HireDate = pHireDate
    WHERE OperatorID = pOperatorID;

    COMMIT;
END //

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_delete_operator;

DELIMITER //

CREATE PROCEDURE sp_delete_operator(
    IN pOperatorID INT,
    IN pCurrentOperatorID INT
)
BEGIN
    DECLARE operatorRole VARCHAR(20);
    DECLARE actingRole VARCHAR(20);
    DECLARE actingActive TINYINT DEFAULT 0;
    DECLARE openReceiptID BIGINT DEFAULT NULL;
    DECLARE openSaleStoreID INT DEFAULT NULL;
    DECLARE currentLineCount INT DEFAULT 0;
    DECLARE currentItemQuantity DECIMAL(12,3) DEFAULT 0;
    DECLARE currentSubtotal DECIMAL(10,2) DEFAULT 0.00;
    DECLARE currentDiscount DECIMAL(10,2) DEFAULT 0.00;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT Role, Active
    INTO actingRole, actingActive
    FROM operator
    WHERE OperatorID = pCurrentOperatorID
    FOR UPDATE;

    IF actingRole IS NULL
       OR actingRole <> 'Administrator'
       OR actingActive <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Administrator access is required';
    END IF;

    IF pOperatorID = pCurrentOperatorID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot delete your own active account';
    END IF;

    SELECT Role
    INTO operatorRole
    FROM operator
    WHERE OperatorID = pOperatorID
      AND Active = 1
    FOR UPDATE;

    IF operatorRole IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Active operator does not exist';
    END IF;

    IF operatorRole = 'Administrator'
       AND (
            SELECT COUNT(*)
            FROM operator
            WHERE Role = 'Administrator'
              AND Active = 1
       ) <= 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The final active administrator cannot be deleted';
    END IF;

    SELECT
        ReceiptID,
        StoreID,
        SubtotalAmount,
        ReceiptDiscountAmount
    INTO
        openReceiptID,
        openSaleStoreID,
        currentSubtotal,
        currentDiscount
    FROM salesreceipt
    WHERE OperatorID = pOperatorID
      AND Status = 'Open'
    LIMIT 1
    FOR UPDATE;

    IF openReceiptID IS NOT NULL THEN
        SELECT
            COUNT(*),
            COALESCE(SUM(Quantity), 0)
        INTO
            currentLineCount,
            currentItemQuantity
        FROM salesreceiptline
        WHERE ReceiptID = openReceiptID;

        UPDATE storeinventory si
        JOIN (
            SELECT
                ProductID,
                SUM(Quantity) AS QuantityToRestore
            FROM salesreceiptline
            WHERE ReceiptID = openReceiptID
            GROUP BY ProductID
        ) saleItems
            ON saleItems.ProductID = si.ProductID
        SET si.StockQuantity =
            si.StockQuantity
            + saleItems.QuantityToRestore
        WHERE si.StoreID = openSaleStoreID;

        UPDATE salesreceipt
        SET
            CheckoutDateTime = NOW(),
            Status = 'Voided',
            TaxableSubtotalAmount = 0.00,
            TaxAmount = 0.00,
            TotalAmount = 0.00,
            AmountTendered = NULL,
            ChangeDue = NULL
        WHERE ReceiptID = openReceiptID;

        UPDATE transactionjournal
        SET
            ClosedByOperatorID = pCurrentOperatorID,
            ClosedDateTime = NOW(),
            Status = 'Cleared',
            LineCount = currentLineCount,
            ItemQuantity = currentItemQuantity,
            SubtotalAmount = currentSubtotal,
            DiscountAmount = currentDiscount,
            TaxableSubtotalAmount = 0.00,
            TaxAmount = 0.00,
            TotalAmount = 0.00,
            PaymentMethod = NULL,
            AmountTendered = NULL,
            ChangeDue = NULL
        WHERE ReceiptID = openReceiptID;
    END IF;

    UPDATE operator
    SET Active = 0
    WHERE OperatorID = pOperatorID;

    COMMIT;
END //

DELIMITER ;