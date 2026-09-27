-- FnH Groceries
-- Views and Procedures

USE `csc680-fnhgroceries_fnh_groceries`;

-- Drop views in reverse dependency order
DROP VIEW IF EXISTS vw_operatoractivity;
DROP VIEW IF EXISTS vw_dailysalessummary;
DROP VIEW IF EXISTS vw_customerpurchasehistory;
DROP VIEW IF EXISTS vw_transaction_journal;
DROP VIEW IF EXISTS vw_receiptsummary;
DROP VIEW IF EXISTS vw_receiptdetail;
DROP VIEW IF EXISTS vw_sale_summary;
DROP VIEW IF EXISTS vw_sale_detail;
DROP VIEW IF EXISTS vw_departmentproducts;
DROP VIEW IF EXISTS vw_productinventory;
DROP VIEW IF EXISTS vw_storeinventorydetail;
DROP VIEW IF EXISTS vw_store_stock_total;
DROP VIEW IF EXISTS vw_store_stock;
DROP VIEW IF EXISTS vw_pos_products;
DROP VIEW IF EXISTS vw_operatorlist;
DROP VIEW IF EXISTS vw_operatorlogin;
DROP VIEW IF EXISTS vw_storelist;

-- Create views in dependency order

-- vw_storelist
CREATE VIEW vw_storelist AS
select store.StoreID AS StoreID,store.StoreNumber AS StoreNumber,store.StoreName AS StoreName,store.AddressLine1 AS AddressLine1,store.AddressLine2 AS AddressLine2,store.City AS City,store.StateCode AS StateCode,store.PostalCode AS PostalCode,store.Phone AS Phone,store.Active AS Active from store;

-- vw_operatorlogin
CREATE VIEW vw_operatorlogin AS
select o.OperatorID AS OperatorID,o.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,o.PasswordHash AS PasswordHash,o.FirstName AS FirstName,o.MiddleInitial AS MiddleInitial,o.LastName AS LastName,o.Email AS Email,o.Phone AS Phone,o.Role AS Role from (operator o join store s on(s.StoreID = o.StoreID)) where o.Active = 1 and s.Active = 1;

-- vw_operatorlist
CREATE VIEW vw_operatorlist AS
select o.OperatorID AS OperatorID,o.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,o.FirstName AS FirstName,o.MiddleInitial AS MiddleInitial,o.LastName AS LastName,concat(o.LastName,', ',o.FirstName,case when o.MiddleInitial is null or trim(o.MiddleInitial) = '' then '' else concat(', ',ucase(o.MiddleInitial),'.') end) AS FullName,o.Email AS Email,o.Phone AS Phone,o.Role AS Role,o.HireDate AS HireDate,o.Active AS Active,o.CreatedAt AS CreatedAt from (operator o join store s on(s.StoreID = o.StoreID));

-- vw_pos_products
CREATE VIEW vw_pos_products AS
select si.StoreID AS StoreID,p.ProductID AS ProductID,p.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,p.UPC AS UPC,p.PLUCode AS PLUCode,p.ProductName AS ProductName,p.UnitType AS UnitType,p.RetailPrice AS RetailPrice,p.Taxable AS Taxable,si.StockQuantity AS StockQuantity from ((product p join department d on(d.DepartmentID = p.DepartmentID)) join storeinventory si on(si.ProductID = p.ProductID)) where p.Active = 1;

-- vw_store_stock
CREATE VIEW vw_store_stock AS
select s.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,d.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,p.ProductID AS ProductID,p.UPC AS UPC,p.PLUCode AS PLUCode,p.ProductName AS ProductName,p.UnitType AS UnitType,p.RetailPrice AS RetailPrice,p.Taxable AS Taxable,si.StockQuantity AS StockQuantity,si.Aisle AS Aisle,si.SectionName AS SectionName,si.ShelfLocation AS ShelfLocation from (((storeinventory si join store s on(s.StoreID = si.StoreID)) join product p on(p.ProductID = si.ProductID)) join department d on(d.DepartmentID = p.DepartmentID)) where s.Active = 1 and p.Active = 1;

-- vw_store_stock_total
CREATE VIEW vw_store_stock_total AS
select s.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,coalesce(sum(si.StockQuantity),0) AS TotalStockQuantity from (store s left join storeinventory si on(si.StoreID = s.StoreID)) where s.Active = 1 group by s.StoreID,s.StoreNumber,s.StoreName;

-- vw_storeinventorydetail
CREATE VIEW vw_storeinventorydetail AS
select s.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,p.UnitType AS UnitType,p.UnitCost AS UnitCost,p.RetailPrice AS RetailPrice,si.StockQuantity AS StockQuantity,si.Aisle AS Aisle,si.SectionName AS SectionName,si.ShelfLocation AS ShelfLocation,si.LastCountedAt AS LastCountedAt,d.DepartmentName AS DepartmentName,c.CategoryName AS CategoryName,p.Active AS Active from ((((storeinventory si join store s on(s.StoreID = si.StoreID)) join product p on(p.ProductID = si.ProductID)) join department d on(d.DepartmentID = p.DepartmentID)) join category c on(c.CategoryID = d.CategoryID));

-- vw_productinventory
CREATE VIEW vw_productinventory AS
select p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,p.UnitType AS UnitType,p.UnitCost AS UnitCost,p.RetailPrice AS RetailPrice,coalesce(sum(si.StockQuantity),0) AS TotalStockQuantity,count(distinct si.StoreID) AS StoreCount,d.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,c.CategoryID AS CategoryID,c.CategoryName AS CategoryName,p.Active AS Active from (((product p join department d on(d.DepartmentID = p.DepartmentID)) join category c on(c.CategoryID = d.CategoryID)) left join storeinventory si on(si.ProductID = p.ProductID)) group by p.ProductID,p.ProductName,p.UPC,p.PLUCode,p.UnitType,p.UnitCost,p.RetailPrice,d.DepartmentID,d.DepartmentName,c.CategoryID,c.CategoryName,p.Active;

-- vw_departmentproducts
CREATE VIEW vw_departmentproducts AS
select d.DepartmentID AS DepartmentID,d.DepartmentName AS DepartmentName,c.CategoryName AS CategoryName,count(p.ProductID) AS ProductCount,min(p.RetailPrice) AS LowestPrice,max(p.RetailPrice) AS HighestPrice,coalesce(sum(inv.TotalStockQuantity),0) AS TotalStock from (((department d join category c on(c.CategoryID = d.CategoryID)) left join product p on(p.DepartmentID = d.DepartmentID and p.Active = 1)) left join (select storeinventory.ProductID AS ProductID,sum(storeinventory.StockQuantity) AS TotalStockQuantity from storeinventory group by storeinventory.ProductID) inv on(inv.ProductID = p.ProductID)) group by d.DepartmentID,d.DepartmentName,c.CategoryName;

-- vw_sale_detail
CREATE VIEW vw_sale_detail AS
select sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.StoreID AS StoreID,sr.RegisterID AS RegisterID,sr.OperatorID AS OperatorID,sr.CustomerID AS CustomerID,sr.TransactionDateTime AS TransactionDateTime,sr.CheckoutDateTime AS CheckoutDateTime,sr.Status AS Status,srl.ReceiptLineID AS ReceiptLineID,srl.LineNumber AS LineNumber,p.ProductID AS ProductID,p.UPC AS UPC,p.PLUCode AS PLUCode,srl.ProductNameAtSale AS ProductName,srl.UnitTypeAtSale AS UnitType,srl.TaxableAtSale AS Taxable,srl.Quantity AS Quantity,srl.UnitPrice AS UnitPrice,srl.LineDiscountAmount AS LineDiscountAmount,round(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount,2) AS LineTotal from ((salesreceipt sr join salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) join product p on(p.ProductID = srl.ProductID));

-- vw_sale_summary
CREATE VIEW vw_sale_summary AS
select sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.StoreID AS StoreID,sr.RegisterID AS RegisterID,sr.OperatorID AS OperatorID,sr.CustomerID AS CustomerID,sr.TransactionDateTime AS TransactionDateTime,sr.CheckoutDateTime AS CheckoutDateTime,sr.Status AS Status,sr.ReceiptDiscountAmount AS ReceiptDiscountAmount,sr.SubtotalAmount AS SubtotalAmount,sr.TaxableSubtotalAmount AS TaxableSubtotalAmount,sr.TaxAmount AS TaxAmount,sr.TotalAmount AS TotalAmount,sr.PaymentMethod AS PaymentMethod,sr.AmountTendered AS AmountTendered,sr.ChangeDue AS ChangeDue,count(srl.ReceiptLineID) AS LineCount,coalesce(sum(srl.Quantity),0) AS ItemQuantity from (salesreceipt sr left join salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) group by sr.ReceiptID,sr.TransactionNumber,sr.StoreID,sr.RegisterID,sr.OperatorID,sr.CustomerID,sr.TransactionDateTime,sr.CheckoutDateTime,sr.Status,sr.ReceiptDiscountAmount,sr.SubtotalAmount,sr.TaxableSubtotalAmount,sr.TaxAmount,sr.TotalAmount,sr.PaymentMethod,sr.AmountTendered,sr.ChangeDue;

-- vw_receiptdetail
CREATE VIEW vw_receiptdetail AS
select sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.TransactionDateTime AS PurchaseDateTime,sr.Status AS Status,sr.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,sr.RegisterID AS RegisterID,r.RegisterNumber AS RegisterNumber,o.OperatorID AS CashierID,o.EmployeeNumber AS CashierEmployeeNumber,o.Username AS CashierUsername,concat(o.FirstName,' ',o.LastName) AS CashierName,sr.CustomerID AS CustomerID,srl.LineNumber AS LineNumber,p.ProductID AS ProductID,p.ProductName AS ProductName,p.UPC AS UPC,p.PLUCode AS PLUCode,srl.Quantity AS Quantity,p.UnitType AS UnitType,srl.UnitPrice AS UnitPrice,srl.LineDiscountAmount AS LineDiscountAmount,round(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount,2) AS LineTotal from (((((salesreceipt sr join store s on(s.StoreID = sr.StoreID)) join register r on(r.StoreID = sr.StoreID and r.RegisterID = sr.RegisterID)) join operator o on(o.OperatorID = sr.OperatorID)) join salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) join product p on(p.ProductID = srl.ProductID));

-- vw_receiptsummary
CREATE VIEW vw_receiptsummary AS
select sr.ReceiptID AS ReceiptID,sr.TransactionNumber AS TransactionNumber,sr.TransactionDateTime AS PurchaseDateTime,sr.Status AS Status,sr.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,sr.RegisterID AS RegisterID,r.RegisterNumber AS RegisterNumber,o.OperatorID AS CashierID,o.EmployeeNumber AS CashierEmployeeNumber,o.Username AS CashierUsername,concat(o.FirstName,' ',o.LastName) AS CashierName,sr.CustomerID AS CustomerID,round(sum(srl.Quantity * srl.UnitPrice),2) AS GrossSubtotal,round(sum(srl.LineDiscountAmount),2) AS LineDiscountAmount,round(sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount),2) AS Subtotal,sr.ReceiptDiscountAmount AS ReceiptDiscountAmount,sr.TaxAmount AS TaxAmount,round(sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount) - sr.ReceiptDiscountAmount + sr.TaxAmount,2) AS TotalAmount,sr.PaymentMethod AS PaymentMethod,sr.AmountTendered AS AmountTendered,case when sr.AmountTendered is null then NULL else round(sr.AmountTendered - (sum(srl.Quantity * srl.UnitPrice - srl.LineDiscountAmount) - sr.ReceiptDiscountAmount + sr.TaxAmount),2) end AS ChangeDue from ((((salesreceipt sr join store s on(s.StoreID = sr.StoreID)) join register r on(r.StoreID = sr.StoreID and r.RegisterID = sr.RegisterID)) join operator o on(o.OperatorID = sr.OperatorID)) join salesreceiptline srl on(srl.ReceiptID = sr.ReceiptID)) group by sr.ReceiptID,sr.TransactionNumber,sr.TransactionDateTime,sr.Status,sr.StoreID,s.StoreNumber,s.StoreName,sr.RegisterID,r.RegisterNumber,o.OperatorID,o.EmployeeNumber,o.Username,o.FirstName,o.LastName,sr.CustomerID,sr.ReceiptDiscountAmount,sr.TaxAmount,sr.PaymentMethod,sr.AmountTendered;

-- vw_transaction_journal
CREATE VIEW vw_transaction_journal AS
select tj.JournalID AS JournalID,tj.ReceiptID AS ReceiptID,tj.TransactionNumber AS TransactionNumber,tj.StoreID AS StoreID,s.StoreNumber AS StoreNumber,s.StoreName AS StoreName,tj.RegisterID AS RegisterID,r.RegisterNumber AS RegisterNumber,tj.OpenedByOperatorID AS OpenedByOperatorID,concat(opened.FirstName,' ',opened.LastName) AS OpenedByOperator,tj.ClosedByOperatorID AS ClosedByOperatorID,case when closed.OperatorID is null then NULL else concat(closed.FirstName,' ',closed.LastName) end AS ClosedByOperator,tj.OpenedDateTime AS OpenedDateTime,tj.ClosedDateTime AS ClosedDateTime,tj.Status AS Status,tj.LineCount AS LineCount,tj.ItemQuantity AS ItemQuantity,tj.SubtotalAmount AS SubtotalAmount,tj.DiscountAmount AS DiscountAmount,tj.TaxableSubtotalAmount AS TaxableSubtotalAmount,tj.TaxAmount AS TaxAmount,tj.TotalAmount AS TotalAmount,tj.PaymentMethod AS PaymentMethod,tj.AmountTendered AS AmountTendered,tj.ChangeDue AS ChangeDue from ((((transactionjournal tj join store s on(s.StoreID = tj.StoreID)) join register r on(r.RegisterID = tj.RegisterID)) join operator opened on(opened.OperatorID = tj.OpenedByOperatorID)) left join operator closed on(closed.OperatorID = tj.ClosedByOperatorID));

-- vw_customerpurchasehistory
CREATE VIEW vw_customerpurchasehistory AS
select cu.CustomerID AS CustomerID,cu.LoyaltyNumber AS LoyaltyNumber,cu.FirstName AS FirstName,cu.LastName AS LastName,cu.LoyaltyPoints AS LoyaltyPoints,rs.ReceiptID AS ReceiptID,rs.TransactionNumber AS TransactionNumber,rs.PurchaseDateTime AS PurchaseDateTime,rs.StoreNumber AS StoreNumber,rs.StoreName AS StoreName,rs.GrossSubtotal AS GrossSubtotal,rs.LineDiscountAmount AS LineDiscountAmount,rs.Subtotal AS Subtotal,rs.ReceiptDiscountAmount AS ReceiptDiscountAmount,round(rs.LineDiscountAmount + rs.ReceiptDiscountAmount,2) AS TotalDiscountAmount,rs.TaxAmount AS TaxAmount,rs.TotalAmount AS TotalAmount,rs.PaymentMethod AS PaymentMethod from (customer cu join vw_receiptsummary rs on(rs.CustomerID = cu.CustomerID)) where rs.Status in ('Completed','Paid');

-- vw_dailysalessummary
CREATE VIEW vw_dailysalessummary AS
select rs.StoreID AS StoreID,rs.StoreNumber AS StoreNumber,rs.StoreName AS StoreName,cast(rs.PurchaseDateTime as date) AS SaleDate,count(0) AS TransactionCount,round(sum(rs.GrossSubtotal),2) AS GrossSales,round(sum(rs.LineDiscountAmount + rs.ReceiptDiscountAmount),2) AS TotalDiscounts,round(sum(rs.Subtotal - rs.ReceiptDiscountAmount),2) AS NetSales,round(sum(rs.TaxAmount),2) AS TotalTax,round(sum(rs.TotalAmount),2) AS TotalCollected from vw_receiptsummary rs where rs.Status in ('Completed','Paid') group by rs.StoreID,rs.StoreNumber,rs.StoreName,cast(rs.PurchaseDateTime as date);

-- vw_operatoractivity
CREATE VIEW vw_operatoractivity AS
select o.OperatorID AS OperatorID,o.EmployeeNumber AS EmployeeNumber,o.Username AS Username,concat(o.FirstName,' ',o.LastName) AS OperatorName,o.Role AS Role,s.StoreNumber AS CurrentStoreNumber,s.StoreName AS CurrentStoreName,count(rs.ReceiptID) AS TransactionCount,coalesce(round(sum(rs.Subtotal - rs.ReceiptDiscountAmount),2),0.00) AS TotalSales,min(rs.PurchaseDateTime) AS FirstTransaction,max(rs.PurchaseDateTime) AS LastTransaction,o.Active AS Active from ((operator o join store s on(s.StoreID = o.StoreID)) left join vw_receiptsummary rs on(rs.CashierID = o.OperatorID and rs.Status in ('Completed','Paid'))) group by o.OperatorID,o.EmployeeNumber,o.Username,o.FirstName,o.LastName,o.Role,s.StoreNumber,s.StoreName,o.Active;

-- Drop existing procedures
DROP PROCEDURE IF EXISTS sp_create_operator;
DROP PROCEDURE IF EXISTS sp_update_operator;
DROP PROCEDURE IF EXISTS sp_update_own_account;
DROP PROCEDURE IF EXISTS sp_delete_operator;
DROP PROCEDURE IF EXISTS sp_reactivate_operator;
DROP PROCEDURE IF EXISTS sp_start_sale;
DROP PROCEDURE IF EXISTS sp_add_sale_item;
DROP PROCEDURE IF EXISTS sp_remove_sale_item;
DROP PROCEDURE IF EXISTS sp_checkout_sale;
DROP PROCEDURE IF EXISTS sp_void_sale;

-- Create procedures
DELIMITER $$

-- sp_create_operator
CREATE PROCEDURE sp_create_operator(
	IN pStoreID INT,
	IN pUsername VARCHAR(50),
	IN pPasswordHash VARCHAR(255),
	IN pFirstName VARCHAR(60),
	IN pMiddleInitial CHAR(1),
	IN pLastName VARCHAR(60),
	IN pEmail VARCHAR(120),
	IN pPhone VARCHAR(10),
	IN pRole VARCHAR(20),
	IN pHireDate DATE
)
BEGIN

	DECLARE newOperatorID INT;

	/* Make sure the selected store exists and is active. */
	IF NOT EXISTS (

		SELECT 1

		FROM store

		WHERE StoreID = pStoreID
		AND Active = 1

	) THEN

		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT =
			'The selected store does not exist or is inactive';

	END IF;

	/* Make sure the username is unique. */
	IF EXISTS (

		SELECT 1

		FROM operator

		WHERE Username = pUsername

	) THEN

		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT =
			'Username already exists';

	END IF;

	/* Make sure the email address is unique. */
	IF EXISTS (

		SELECT 1

		FROM operator

		WHERE Email = pEmail

	) THEN

		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT =
			'Email address already exists';

	END IF;

	/* Make sure a valid operator role was supplied. */
	IF pRole NOT IN (
		'Administrator',
		'Operator'
	) THEN

		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT =
			'Invalid operator role';

	END IF;

	/*
	   OperatorID is assigned automatically.

	   EmployeeNumber is filled immediately afterward.
	*/
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

	/* This gets the new AUTO_INCREMENT OperatorID. */
	SET newOperatorID =
		LAST_INSERT_ID();

	/* This automatically creates the employee number. */
	UPDATE operator
	SET EmployeeNumber =
		newOperatorID + 1000
	WHERE OperatorID =
		newOperatorID;

END$$

-- sp_update_operator
CREATE PROCEDURE sp_update_operator(
	IN pOperatorID INT,
	IN pStoreID INT,
	IN pUsername VARCHAR(50),
	IN pPasswordHash VARCHAR(255),
	IN pFirstName VARCHAR(60),
	IN pMiddleInitial CHAR(1),
	IN pLastName VARCHAR(60),
	IN pEmail VARCHAR(120),
	IN pPhone VARCHAR(10),
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
END$$

-- sp_update_own_account
CREATE PROCEDURE sp_update_own_account(
	IN pOperatorID INT,
	IN pUsername VARCHAR(50),
	IN pFirstName VARCHAR(60),
	IN pMiddleInitial CHAR(1),
	IN pLastName VARCHAR(60),
	IN pEmail VARCHAR(120),
	IN pPhone VARCHAR(10),
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
END$$

-- sp_delete_operator
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
END$$

-- sp_reactivate_operator
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
END$$

-- sp_start_sale
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
END$$

-- sp_add_sale_item
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
END$$

-- sp_remove_sale_item
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
			SET MESSAGE_TEXT = 'Items can only be removed from an open sale';
	END IF;

	SELECT
		ProductID,
		Quantity
	INTO
		lineProductID,
		currentQuantity
	FROM salesreceiptline
	WHERE ReceiptID = pReceiptID
	  AND ReceiptLineID = pReceiptLineID
	FOR UPDATE;

	IF lineProductID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected sale item does not exist';
	END IF;

	SET quantityToRemove =
		LEAST(
			currentQuantity,
			1.000
		);

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
END$$

-- sp_checkout_sale
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
		SubtotalAmount = calculatedGrossSubtotal,
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
		SubtotalAmount = calculatedGrossSubtotal,
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
END$$

-- sp_void_sale
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
END$$

DELIMITER ;