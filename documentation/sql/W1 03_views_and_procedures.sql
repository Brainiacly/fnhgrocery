-- W1 03_views_and_procedures.sql
-- Brian Phillips
-- CSC 680
-- FnH Groceries
-- Week 1 views and procedures

-- Views

-- Lists active and inactive stores
-- Tables: store
-- PHP: account.php, operators/create.php, operators/update.php

-- Select the project database
USE fnh_groceries;

CREATE VIEW vw_storelist AS
SELECT store.StoreID AS StoreID,store.StoreNumber AS StoreNumber,store.StoreName AS StoreName,store.AddressLine1 AS AddressLine1,store.AddressLine2 AS AddressLine2,store.City AS City,store.StateCode AS StateCode,store.PostalCode AS PostalCode,store.Phone AS Phone,store.Active AS Active 
FROM store;

-- Returns active operator login and session data
-- Tables: operator, store
-- PHP: index.php, includes/access_control.php, account.php
CREATE VIEW vw_operatorlogin AS
SELECT o.OperatorID AS OperatorID,o.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,o.PasswordHash AS PasswordHash,o.FirstName AS FirstName,o.MiddleInitial AS MiddleInitial,o.LastName AS LastName,o.Email AS Email,o.Phone AS Phone,o.Role AS Role 
FROM (operator o 
JOIN store s on(s.StoreID = o.StoreID)) 
WHERE o.Active = 1 and s.Active = 1;

-- Returns operator details for administration
-- Tables: operator, store
-- PHP: operators/operator_list.php, operators/update.php, operators/delete.php, operators/reactivate.php
CREATE VIEW vw_operatorlist AS
SELECT o.OperatorID AS OperatorID,o.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,o.FirstName AS FirstName,o.MiddleInitial AS MiddleInitial,o.LastName AS LastName,concat(o.LastName,', ',o.FirstName,case when o.MiddleInitial is null or trim(o.MiddleInitial) = '' then '' else concat(', ',ucase(o.MiddleInitial),'.') end) AS FullName,o.Email AS Email,o.Phone AS Phone,o.Role AS Role,o.HireDate AS HireDate,o.Active AS Active,o.CreatedAt AS CreatedAt 
FROM (operator o 
JOIN store s on(s.StoreID = o.StoreID));

-- Summarizes product stock across stores
-- Tables: product, department, category, storeinventory
-- PHP: none in Assignment 1
CREATE VIEW vw_productinventory AS
SELECT p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,p.UnitType AS UnitType,p.UnitCost AS UnitCost,p.RetailPrice AS RetailPrice,coalesce(sum(si.StockQuantity),0) AS TotalStockQuantity,count(distinct si.StoreID) AS StoreCount,d.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,c.CategoryID AS CategoryID,c.CategoryName AS CategoryName,p.Active AS Active 
FROM (((product p 
JOIN department d on(d.DepartmentID = p.DepartmentID)) 
JOIN category c on(c.CategoryID = d.CategoryID)) 
LEFT JOIN storeinventory si on(si.ProductID = p.ProductID)) 
GROUP BY p.ProductID,p.ProductName,p.UPC,p.PLUCode,p.UnitType,p.UnitCost,p.RetailPrice,d.DepartmentID,d.DepartmentName,c.CategoryID,c.CategoryName,p.Active;

-- Shows detailed inventory by store
-- Tables: storeinventory, store, product, department, category
-- PHP: none in Assignment 1
CREATE VIEW vw_storeinventorydetail AS
SELECT s.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,p.UnitType AS UnitType,p.UnitCost AS UnitCost,p.RetailPrice AS RetailPrice,si.StockQuantity AS StockQuantity,si.Aisle AS Aisle,si.SectionName AS SectionName,si.ShelfLocation AS ShelfLocation,si.LastCountedAt AS LastCountedAt,d.DepartmentName AS DepartmentName,c.CategoryName AS CategoryName,p.Active AS Active 
FROM ((((storeinventory si 
JOIN store s on(s.StoreID = si.StoreID)) 
JOIN product p on(p.ProductID = si.ProductID)) 
JOIN department d on(d.DepartmentID = p.DepartmentID)) 
JOIN category c on(c.CategoryID = d.CategoryID));

-- Summarizes products and stock by department
-- Tables: department, category, product, storeinventory
-- PHP: none in Assignment 1
CREATE VIEW vw_departmentproducts AS
SELECT d.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,c.CategoryName AS CategoryName,count(p.ProductID) AS ProductCount,min(p.RetailPrice) AS LowestPrice,max(p.RetailPrice) AS HighestPrice,coalesce(sum(inv.TotalStockQuantity),0) AS TotalStock 
FROM (((department d 
JOIN category c on(c.CategoryID = d.CategoryID)) 
LEFT JOIN product p on(p.DepartmentID = d.DepartmentID and p.Active = 1)) 
LEFT JOIN (SELECT storeinventory.ProductID AS ProductID,sum(storeinventory.StockQuantity) AS TotalStockQuantity 
FROM storeinventory 
GROUP BY storeinventory.ProductID) inv on(inv.ProductID = p.ProductID)) 
GROUP BY d.DepartmentID,d.DepartmentName,c.CategoryName;

-- Shows receipt line item details
-- Tables: salesreceipt, store, register, operator, salesreceiptline, product
-- PHP: none in Assignment 1
CREATE VIEW vw_receiptdetail AS
SELECT sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.TransactionDateTime AS PurchaseDateTime,sr.Status AS Status,sr.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,sr.RegisterID AS RegisterID,r.RegisterNumber AS RegisterNumber,o.OperatorID AS CashierID,o.EmployeeNumber AS CashierEmployeeNumber,o.Username AS CashierUsername,concat(o.FirstName,' ',o.LastName) AS CashierName,sr.CustomerID AS CustomerID,srl.LineNumber AS LineNumber,p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,srl.Quantity AS Quantity,p.UnitType AS UnitType,srl.UnitPrice AS UnitPrice,srl.LineDiscountAmount AS LineDiscountAmount,round(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount,2) AS LineTotal 
FROM (((((salesreceipt sr 
JOIN store s on(s.StoreID = sr.StoreID)) 
JOIN register r on(r.StoreID = sr.StoreID and r.RegisterID = sr.RegisterID)) 
JOIN operator o on(o.OperatorID = sr.OperatorID)) 
JOIN salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) 
JOIN product p on(p.ProductID = srl.ProductID));

-- Calculates receipt totals and change
-- Tables: salesreceipt, store, register, operator, salesreceiptline
-- Dependent views: vw_customerpurchasehistory, vw_dailysalessummary, vw_operatoractivity
CREATE VIEW vw_receiptsummary AS
SELECT sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.TransactionDateTime AS PurchaseDateTime,sr.Status AS Status,sr.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,sr.RegisterID AS RegisterID,r.RegisterNumber AS RegisterNumber,o.OperatorID AS CashierID,o.EmployeeNumber AS CashierEmployeeNumber,o.Username AS CashierUsername,concat(o.FirstName,' ',o.LastName) AS CashierName,sr.CustomerID AS CustomerID,round(sum(srl.Quantity * srl.UnitPrice),2) AS GrossSubtotal,round(sum(srl.LineDiscountAmount),2) AS LineDiscountAmount,round(sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount),2) AS Subtotal,sr.ReceiptDiscountAmount AS ReceiptDiscountAmount,sr.TaxAmount AS TaxAmount,round(sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount) - sr.ReceiptDiscountAmount + sr.TaxAmount,2) AS TotalAmount,sr.PaymentMethod AS PaymentMethod,sr.AmountTendered AS AmountTendered,case when sr.AmountTendered is null then NULL else round(sr.AmountTendered - (sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount) - sr.ReceiptDiscountAmount + sr.TaxAmount),2) end AS ChangeDue 
FROM ((((salesreceipt sr 
JOIN store s on(s.StoreID = sr.StoreID)) 
JOIN register r on(r.StoreID = sr.StoreID and r.RegisterID = sr.RegisterID)) 
JOIN operator o on(o.OperatorID = sr.OperatorID)) 
JOIN salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) 
GROUP BY sr.ReceiptID,sr.TransactionNumber,sr.TransactionDateTime,sr.Status,sr.StoreID,s.StoreNumber,s.StoreName,sr.RegisterID,r.RegisterNumber,o.OperatorID,o.EmployeeNumber,o.Username,o.FirstName,o.LastName,sr.CustomerID,sr.ReceiptDiscountAmount,sr.TaxAmount,sr.PaymentMethod,sr.AmountTendered;

-- Shows completed purchase history by customer
-- Depends on: customer, vw_receiptsummary
-- PHP: none in Assignment 1
CREATE VIEW vw_customerpurchasehistory AS
SELECT cu.CustomerID AS CustomerID,cu.LoyaltyNumber AS LoyaltyNumber,cu.FirstName AS FirstName,cu.LastName AS LastName,cu.LoyaltyPoints AS LoyaltyPoints,rs.ReceiptID AS ReceiptID,rs.TransactionNumber AS TransactionNumber,rs.PurchaseDateTime AS PurchaseDateTime,rs.StoreNumber AS StoreNumber,rs.StoreName AS StoreName,rs.GrossSubtotal AS GrossSubtotal,rs.LineDiscountAmount AS LineDiscountAmount,rs.Subtotal AS Subtotal,rs.ReceiptDiscountAmount AS ReceiptDiscountAmount,round(rs.LineDiscountAmount + rs.ReceiptDiscountAmount,2) AS TotalDiscountAmount,rs.TaxAmount AS TaxAmount,rs.TotalAmount AS TotalAmount,rs.PaymentMethod AS PaymentMethod 
FROM (customer cu 
JOIN vw_receiptsummary rs on(rs.CustomerID = cu.CustomerID)) 
WHERE rs.Status = 'Completed';

-- Summarizes completed sales by day
-- Depends on: vw_receiptsummary
-- PHP: none in Assignment 1
CREATE VIEW vw_dailysalessummary AS
SELECT vw_receiptsummary.StoreID AS StoreID,vw_receiptsummary.StoreNumber AS StoreNumber,vw_receiptsummary.StoreName AS StoreName,cast(vw_receiptsummary.PurchaseDateTime as date) AS SaleDate,count(0) AS TransactionCount,round(sum(vw_receiptsummary.GrossSubtotal),2) AS GrossSales,round(sum(vw_receiptsummary.LineDiscountAmount + vw_receiptsummary.ReceiptDiscountAmount),2) AS TotalDiscounts,round(sum(vw_receiptsummary.Subtotal - vw_receiptsummary.ReceiptDiscountAmount),2) AS NetSales,round(sum(vw_receiptsummary.TaxAmount),2) AS TotalTax,round(sum(vw_receiptsummary.TotalAmount),2) AS TotalCollected 
FROM vw_receiptsummary 
WHERE vw_receiptsummary.Status = 'Completed' 
GROUP BY vw_receiptsummary.StoreID,vw_receiptsummary.StoreNumber,vw_receiptsummary.StoreName,cast(vw_receiptsummary.PurchaseDateTime as date);

-- Summarizes completed sales by operator
-- Depends on: operator, store, vw_receiptsummary
-- PHP: none in Assignment 1
CREATE VIEW vw_operatoractivity AS
SELECT o.OperatorID AS OperatorID,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,concat(o.FirstName,' ',o.LastName) AS OperatorName,o.Role AS Role,s.StoreNumber AS CurrentStoreNumber,s.StoreName AS CurrentStoreName,count(rs.ReceiptID) AS TransactionCount,coalesce(round(sum(rs.Subtotal - rs.ReceiptDiscountAmount),2),0.00) AS TotalSales,min(rs.PurchaseDateTime) AS FirstTransaction,max(rs.PurchaseDateTime) AS LastTransaction,o.Active AS Active 
FROM ((operator o 
JOIN store s on(s.StoreID = o.StoreID)) 
LEFT JOIN vw_receiptsummary rs on(rs.CashierID = o.OperatorID and rs.Status = 'Completed')) 
GROUP BY o.OperatorID,o.EmployeeNumber,o.Username,o.FirstName,o.LastName,o.Role,s.StoreNumber,s.StoreName,o.Active;

-- Procedures

-- Creates an operator and assigns the next employee number
-- Depends on tables: store, operator
-- PHP: operators/create.php
DELIMITER //
CREATE PROCEDURE sp_create_operator(
    IN pStoreID INT,
    IN pUsername VARCHAR(50),
    IN pPasswordHash VARCHAR(255),
    IN pFirstName VARCHAR(60),
    IN pMiddleInitial CHAR(1),
    IN pLastName VARCHAR(60),
    IN pEmail VARCHAR(120),
    IN pPhone VARCHAR(20),
    IN pRole VARCHAR(20),
    IN pHireDate DATE
)
BEGIN
    DECLARE newOperatorID INT;

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
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Username already exists';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM operator
        WHERE Email = pEmail
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Email address already exists';
    END IF;

    IF pRole NOT IN ('Administrator', 'Operator') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid operator role';
    END IF;

    INSERT INTO operator (
        StoreID,
        EmployeeNumber,
        Username,
        PasswordHash,
        FirstName,
        MiddleInitial,
        LastName,
        Email,
        Phone,
        Role,
        HireDate,
        Active
    )
    VALUES (
        pStoreID,
        NULL,
        pUsername,
        pPasswordHash,
        pFirstName,
        NULLIF(pMiddleInitial, ''),
        pLastName,
        pEmail,
        NULLIF(pPhone, ''),
        pRole,
        pHireDate,
        1
    );

    SET newOperatorID = LAST_INSERT_ID();

    UPDATE operator
    SET EmployeeNumber = newOperatorID + 1000
    WHERE OperatorID = newOperatorID;
END //
DELIMITER ;

-- Updates an operator while protecting the final administrator
-- Depends on tables: store, operator
-- PHP: operators/update.php
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
    IN pHireDate DATE
)
BEGIN
    DECLARE currentOperatorRole VARCHAR(20);

    IF NOT EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
    ) THEN
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

    SELECT Role
    INTO currentOperatorRole
    FROM operator
    WHERE OperatorID = pOperatorID;

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

    UPDATE operator
    SET StoreID = pStoreID,
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
END //
DELIMITER ;

-- Updates the currently logged-in operator account
-- Depends on tables: store, operator
-- PHP: account.php
DELIMITER //
CREATE PROCEDURE sp_update_own_account(
    IN pOperatorID INT,
    IN pUsername VARCHAR(50),
    IN pFirstName VARCHAR(60),
    IN pMiddleInitial CHAR(1),
    IN pLastName VARCHAR(60),
    IN pEmail VARCHAR(120),
    IN pPhone VARCHAR(20),
    IN pPasswordHash VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Active operator does not exist';
    END IF;


    IF pUsername IS NULL OR TRIM(pUsername) = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Username is required';
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

    UPDATE operator
    SET Username = pUsername,
        FirstName = pFirstName,
        MiddleInitial = NULLIF(pMiddleInitial, ''),
        LastName = pLastName,
        Email = pEmail,
        Phone = NULLIF(pPhone, ''),
        PasswordHash = CASE
            WHEN pPasswordHash IS NULL OR pPasswordHash = '' THEN PasswordHash
            ELSE pPasswordHash
        END
    WHERE OperatorID = pOperatorID;
END //
DELIMITER ;

-- Deactivates an operator while preserving historical records
-- Depends on table: operator
-- PHP: operators/delete.php
DELIMITER //
CREATE PROCEDURE sp_delete_operator(
    IN pOperatorID INT,
    IN pCurrentOperatorID INT
)
BEGIN
    DECLARE operatorRole VARCHAR(20);

    IF NOT EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Active operator does not exist';
    END IF;

    IF pOperatorID = pCurrentOperatorID THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'You cannot delete your own active account';
    END IF;

    SELECT Role
    INTO operatorRole
    FROM operator
    WHERE OperatorID = pOperatorID;

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

    UPDATE operator
    SET Active = 0
    WHERE OperatorID = pOperatorID;
END //
DELIMITER ;

-- Reactivates an inactive operator
-- Depends on table: operator
-- PHP: operators/reactivate.php
DELIMITER //
CREATE PROCEDURE sp_reactivate_operator(
    IN pOperatorID INT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Operator does not exist';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM operator
        WHERE OperatorID = pOperatorID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Operator is already active';
    END IF;

    UPDATE operator
    SET Active = 1
    WHERE OperatorID = pOperatorID;
END //
DELIMITER ;

-- Current PHP dependency summary
-- vw_operatorlogin: index.php, includes/access_control.php, account.php
-- vw_storelist: account.php, operators/create.php, operators/update.php
-- vw_operatorlist: operators/operator_list.php, operators/update.php, operators/delete.php, operators/reactivate.php
-- sp_create_operator: operators/create.php
-- sp_update_operator: operators/update.php
-- sp_update_own_account: account.php
-- sp_delete_operator: operators/delete.php
-- sp_reactivate_operator: operators/reactivate.php
-- Other views currently support database reporting and later POS features