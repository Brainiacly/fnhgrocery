-- FnH Groceries
-- Views and Procedures
-- Run after 01_Database_and_Table_Creation.sql and before 03_Seed_Data.sql
USE fnh_groceries;
-- Drop views in reverse dependency order
DROP VIEW IF EXISTS vw_express_orders;
DROP VIEW IF EXISTS vw_inventory_adjustments;
DROP VIEW IF EXISTS vw_sale_coupons;
DROP VIEW IF EXISTS vw_sale_discounts;
DROP VIEW IF EXISTS vw_sale_detail;
DROP VIEW IF EXISTS vw_store_stock;
DROP VIEW IF EXISTS vw_pos_products;
DROP VIEW IF EXISTS vw_operatorlist;
DROP VIEW IF EXISTS vw_operatorlogin;
DROP VIEW IF EXISTS vw_storelist;

-- Create views in dependency order

-- vw_storelist
CREATE VIEW vw_storelist AS
SELECT
	store.StoreID AS StoreID,
	store.StoreNumber AS StoreNumber,
	store.StoreName AS StoreName,
	store.AddressLine1 AS AddressLine1,
	store.AddressLine2 AS AddressLine2,
	store.City AS City,
	store.StateCode AS StateCode,
	store.PostalCode AS PostalCode,
	store.Phone AS Phone,
	store.Active AS Active
FROM store;

-- vw_operatorlogin
CREATE VIEW vw_operatorlogin AS
SELECT
	o.OperatorID AS OperatorID,
	o.StoreID AS StoreID,
	s.StoreNumber AS StoreNumber,
	s.StoreName AS StoreName,
	o.EmployeeNumber AS EmployeeNumber,
	o.Username AS Username,
	o.PasswordHash AS PasswordHash,
	o.FirstName AS FirstName,
	o.MiddleInitial AS MiddleInitial,
	o.LastName AS LastName,
	o.Email AS Email,
	o.Phone AS Phone,
	o.Role AS Role
FROM operator o
JOIN store s
	ON s.StoreID = o.StoreID
WHERE o.Active = 1
AND s.Active = 1;

-- vw_operatorlist
CREATE VIEW vw_operatorlist AS
SELECT
	o.OperatorID AS OperatorID,
	o.StoreID AS StoreID,
	s.StoreNumber AS StoreNumber,
	s.StoreName AS StoreName,
	o.EmployeeNumber AS EmployeeNumber,
	o.Username AS Username,
	o.FirstName AS FirstName,
	o.MiddleInitial AS MiddleInitial,
	o.LastName AS LastName,
	CONCAT(
		o.LastName,
		', ',
		o.FirstName,
		CASE
			WHEN o.MiddleInitial IS NULL
				OR TRIM(o.MiddleInitial) = ''
			THEN ''
			ELSE CONCAT(
				', ',
				UCASE(o.MiddleInitial),
				'.'
			)
		END
	) AS FullName,
	o.Email AS Email,
	o.Phone AS Phone,
	o.Role AS Role,
	o.HireDate AS HireDate,
	o.Active AS Active,
	o.CreatedAt AS CreatedAt
FROM operator o
JOIN store s
	ON s.StoreID = o.StoreID;

-- vw_pos_products
CREATE VIEW vw_pos_products AS
SELECT
	si.StoreID AS StoreID,
	p.ProductID AS ProductID,
	p.DepartmentID AS DepartmentID,
	d.DepartmentName AS DepartmentName,
	p.UPC AS UPC,
	p.PLUCode AS PLUCode,
	p.ProductName AS ProductName,
	p.UnitType AS UnitType,
	p.RetailPrice AS RetailPrice,
	p.Taxable AS Taxable,
	si.StockQuantity AS StockQuantity
FROM product p
JOIN department d
	ON d.DepartmentID = p.DepartmentID
JOIN storeinventory si
	ON si.ProductID = p.ProductID
WHERE p.Active = 1;

-- vw_store_stock
CREATE VIEW vw_store_stock AS
SELECT
	s.StoreID AS StoreID,
	s.StoreNumber AS StoreNumber,
	s.StoreName AS StoreName,
	d.DepartmentID AS DepartmentID,
	d.DepartmentName AS DepartmentName,
	p.ProductID AS ProductID,
	p.UPC AS UPC,
	p.PLUCode AS PLUCode,
	p.ProductName AS ProductName,
	p.UnitType AS UnitType,
	p.RetailPrice AS RetailPrice,
	p.Taxable AS Taxable,
	si.StockQuantity AS StockQuantity,
	si.Aisle AS Aisle,
	si.SectionName AS SectionName,
	si.ShelfLocation AS ShelfLocation
FROM storeinventory si
JOIN store s
	ON s.StoreID = si.StoreID
JOIN product p
	ON p.ProductID = si.ProductID
JOIN department d
	ON d.DepartmentID = p.DepartmentID
WHERE s.Active = 1
AND p.Active = 1;

-- vw_sale_detail
CREATE VIEW vw_sale_detail AS
SELECT
	sr.ReceiptID AS ReceiptID,
	sr.TransactionNumber AS TransactionNumber,
	sr.StoreID AS StoreID,
	sr.RegisterID AS RegisterID,
	sr.OperatorID AS OperatorID,
	sr.CustomerID AS CustomerID,
	sr.TransactionDateTime AS TransactionDateTime,
	sr.CheckoutDateTime AS CheckoutDateTime,
	sr.Status AS Status,
	srl.ReceiptLineID AS ReceiptLineID,
	srl.LineNumber AS LineNumber,
	p.ProductID AS ProductID,
	p.UPC AS UPC,
	p.PLUCode AS PLUCode,
	srl.ProductNameAtSale AS ProductName,
	srl.UnitTypeAtSale AS UnitType,
	srl.TaxableAtSale AS Taxable,
	srl.Quantity AS Quantity,
	srl.UnitPrice AS UnitPrice,
	srl.LineDiscountAmount AS LineDiscountAmount,
	ROUND(
		srl.Quantity * srl.UnitPrice
		- srl.LineDiscountAmount,
		2
	) AS LineTotal
FROM salesreceipt sr
JOIN salesreceiptline srl
	ON srl.ReceiptID = sr.ReceiptID
JOIN product p
	ON p.ProductID = srl.ProductID;

-- vw_sale_discounts
CREATE VIEW vw_sale_discounts AS
SELECT
	sd.DiscountID AS DiscountID,
	sd.ReceiptID AS ReceiptID,
	sd.DiscountNumber AS DiscountNumber,
	sd.DiscountKind AS DiscountKind,
	sd.TaxTiming AS TaxTiming,
	sd.DiscountValue AS DiscountValue,
	sd.Reason AS Reason,
	sd.AppliedAmount AS AppliedAmount,
	sd.AppliedByOperatorID AS AppliedByOperatorID,
	o.Username AS AppliedByUsername,
	sd.AppliedAt AS AppliedAt
FROM salesreceiptdiscount sd
JOIN operator o
	ON o.OperatorID = sd.AppliedByOperatorID;

-- vw_sale_coupons
CREATE VIEW vw_sale_coupons AS
SELECT
	sc.SaleCouponID AS SaleCouponID,
	sc.ReceiptID AS ReceiptID,
	sc.CouponNumber AS CouponNumber,
	sc.CouponID AS CouponID,
	c.CouponCode AS CouponCode,
	c.Description AS Description,
	c.ProductID AS ProductID,
	p.ProductName AS ProductName,
	c.RequiredQuantity AS RequiredQuantity,
	c.DiscountKind AS DiscountKind,
	c.DiscountValue AS DiscountValue,
	sc.UnitsCovered AS UnitsCovered,
	sc.AppliedAmount AS AppliedAmount,
	sc.AppliedAt AS AppliedAt
FROM salesreceiptcoupon sc
JOIN coupon c
	ON c.CouponID = sc.CouponID
JOIN product p
	ON p.ProductID = c.ProductID;

-- vw_inventory_adjustments
CREATE VIEW vw_inventory_adjustments AS
SELECT
	ia.AdjustmentID AS AdjustmentID,
	ia.StoreID AS StoreID,
	s.StoreNumber AS StoreNumber,
	s.StoreName AS StoreName,
	ia.ProductID AS ProductID,
	p.ProductName AS ProductName,
	p.UnitType AS UnitType,
	ia.OperatorID AS OperatorID,
	o.Username AS Username,
	CONCAT(o.FirstName, ' ', o.LastName) AS OperatorName,
	ia.AdjustmentType AS AdjustmentType,
	ia.QuantityBefore AS QuantityBefore,
	ia.QuantityChange AS QuantityChange,
	ia.QuantityAfter AS QuantityAfter,
	ia.Reason AS Reason,
	ia.AdjustedAt AS AdjustedAt
FROM inventoryadjustment ia
JOIN store s
	ON s.StoreID = ia.StoreID
JOIN product p
	ON p.ProductID = ia.ProductID
JOIN operator o
	ON o.OperatorID = ia.OperatorID;

-- vw_express_orders
CREATE VIEW vw_express_orders AS
SELECT
	eo.ExpressOrderID,
	sr.ReceiptID,
	sr.TransactionNumber,
	sr.StoreID,
	s.StoreNumber,
	s.StoreName,
	sr.RegisterID,
	r.RegisterNumber,
	r.RegisterName,
	sr.CustomerID,
	c.LoyaltyNumber,
	c.FirstName AS CustomerFirstName,
	c.LastName AS CustomerLastName,
	c.Phone AS CustomerPhone,
	eo.PersonalShopperID,
	o.Username AS PersonalShopperUsername,
	o.FirstName AS PersonalShopperFirstName,
	o.LastName AS PersonalShopperLastName,
	eo.OrderPlacedDateTime,
	eo.FulfillmentMethod,
	eo.DeliveryFee,
	eo.DeliveryAddressLine1,
	eo.DeliveryAddressLine2,
	eo.DeliveryCity,
	eo.DeliveryStateCode,
	eo.DeliveryPostalCode,
	eo.Status AS ExpressStatus,
	sr.Status AS ReceiptStatus,
	sr.ReceiptDiscountAmount,
	sr.SubtotalAmount,
	sr.TaxableSubtotalAmount,
	sr.TaxAmount,
	sr.TotalAmount,
	sr.PaymentMethod,
	sr.AmountTendered,
	sr.ChangeDue,
	sr.CheckoutDateTime
FROM expressorder eo
JOIN salesreceipt sr
	ON sr.ReceiptID = eo.ReceiptID
JOIN store s
	ON s.StoreID = sr.StoreID
JOIN register r
	ON r.StoreID = sr.StoreID
	AND r.RegisterID = sr.RegisterID
JOIN operator o
	ON o.OperatorID = eo.PersonalShopperID
LEFT JOIN customer c
	ON c.CustomerID = eo.CustomerID;

-- Drop procedures
DROP PROCEDURE IF EXISTS sp_create_operator;
DROP PROCEDURE IF EXISTS sp_update_operator;
DROP PROCEDURE IF EXISTS sp_update_own_account;
DROP PROCEDURE IF EXISTS sp_delete_operator;
DROP PROCEDURE IF EXISTS sp_reactivate_operator;
DROP PROCEDURE IF EXISTS sp_set_operator_active;
DROP PROCEDURE IF EXISTS sp_save_register;
DROP PROCEDURE IF EXISTS sp_start_sale;
DROP PROCEDURE IF EXISTS sp_add_sale_item;
DROP PROCEDURE IF EXISTS sp_remove_sale_item;
DROP PROCEDURE IF EXISTS sp_calculate_sale_totals;
DROP PROCEDURE IF EXISTS sp_get_sale_totals;
DROP PROCEDURE IF EXISTS sp_apply_discount;
DROP PROCEDURE IF EXISTS sp_remove_discount;
DROP PROCEDURE IF EXISTS sp_add_coupon_to_sale;
DROP PROCEDURE IF EXISTS sp_remove_coupon_from_sale;
DROP PROCEDURE IF EXISTS sp_checkout_sale;
DROP PROCEDURE IF EXISTS sp_void_sale;
DROP PROCEDURE IF EXISTS sp_take_over_sale;
DROP PROCEDURE IF EXISTS sp_hand_back_sale;
DROP PROCEDURE IF EXISTS sp_record_sale_access;
DROP PROCEDURE IF EXISTS sp_get_express_capacity;
DROP PROCEDURE IF EXISTS sp_create_express_customer;
DROP PROCEDURE IF EXISTS sp_remove_unused_express_customer;
DROP PROCEDURE IF EXISTS sp_create_customer_address;
DROP PROCEDURE IF EXISTS sp_update_customer_address;
DROP PROCEDURE IF EXISTS sp_create_express_order;
DROP PROCEDURE IF EXISTS sp_set_express_order_status;
DROP PROCEDURE IF EXISTS sp_create_product;
DROP PROCEDURE IF EXISTS sp_update_product;
DROP PROCEDURE IF EXISTS sp_adjust_inventory;
DROP PROCEDURE IF EXISTS sp_create_coupon;
DROP PROCEDURE IF EXISTS sp_set_coupon_active;

DELIMITER $$

-- ==== Employees ====

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
		'Manager',
		'Operator',
		'Personal Shopper'
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
	IF pRole NOT IN ('Pending', 'Administrator', 'Manager', 'Operator', 'Personal Shopper') THEN
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
	IF (
		currentStoreID <> pStoreID
		OR currentOperatorRole <> pRole
	)
	AND EXISTS (
		SELECT 1
		FROM expressorder eo
		JOIN salesreceipt sr
			ON sr.ReceiptID = eo.ReceiptID
		WHERE eo.PersonalShopperID = pOperatorID
		  AND sr.Status = 'Open'
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Complete or cancel this employee''s open Express orders before changing store or role';
	END IF;
	IF currentStoreID <> pStoreID
	   OR pRole = 'Pending'
	   OR currentOperatorRole <> pRole THEN
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
		  AND SaleType = 'Regular'
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
	IF EXISTS (
		SELECT 1
		FROM expressorder eo
		JOIN salesreceipt sr
			ON sr.ReceiptID = eo.ReceiptID
		WHERE eo.PersonalShopperID = pOperatorID
		  AND sr.Status = 'Open'
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Complete or cancel this employee''s open Express orders before deleting the account';
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

-- sp_set_operator_active
CREATE PROCEDURE sp_set_operator_active(
	IN pActingOperatorID INT,
	IN pOperatorID INT,
	IN pActive TINYINT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE targetStoreID INT DEFAULT NULL;
	DECLARE targetRole VARCHAR(20) DEFAULT NULL;
	DECLARE targetActive TINYINT DEFAULT 0;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	IF actingStoreID IS NULL
	   OR actingActive <> 1
	   OR actingRole NOT IN ('Administrator', 'Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an active Administrator or Manager can change an employee''s access';
	END IF;

	IF pActive IS NULL OR pActive NOT IN (0, 1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose active or inactive';
	END IF;

	SELECT StoreID, Role, Active
	INTO targetStoreID, targetRole, targetActive
	FROM operator
	WHERE OperatorID = pOperatorID;

	IF targetStoreID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Employee does not exist';
	END IF;

	IF targetStoreID <> actingStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That employee belongs to another store';
	END IF;

	IF pOperatorID = pActingOperatorID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot change your own access';
	END IF;

	IF actingRole = 'Manager' AND targetRole = 'Administrator' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'A Manager cannot change an Administrator';
	END IF;

	IF pActive = 0
	   AND targetRole = 'Administrator'
	   AND (
			SELECT COUNT(*)
			FROM operator
			WHERE Role = 'Administrator'
			  AND Active = 1
	   ) <= 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The final active administrator cannot be inactivated';
	END IF;

	IF targetActive = pActive THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The employee already has that access';
	END IF;

	UPDATE operator
	SET Active = pActive
	WHERE OperatorID = pOperatorID;
END$$

-- ==== Registers ====

-- sp_save_register
CREATE PROCEDURE sp_save_register(
	IN pActingOperatorID INT,
	IN pRegisterID INT,
	IN pRegisterNumber INT,
	IN pRegisterName VARCHAR(50),
	IN pRegisterType VARCHAR(10),
	IN pActive TINYINT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE currentNumber INT DEFAULT NULL;
	DECLARE savedRegisterID INT DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	IF actingStoreID IS NULL
	   OR actingActive <> 1
	   OR actingRole <> 'Administrator' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an active Administrator can change registers';
	END IF;

	IF pRegisterNumber IS NULL OR pRegisterNumber < 1 OR pRegisterNumber > 999 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The register number must be from 1 to 999';
	END IF;

	IF NULLIF(TRIM(pRegisterName), '') IS NULL OR CHAR_LENGTH(TRIM(pRegisterName)) > 50 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a register name of 50 characters or fewer';
	END IF;

	IF pRegisterType IS NULL OR pRegisterType NOT IN ('Express', 'Regular') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose Express or Regular';
	END IF;

	IF pActive IS NULL OR pActive NOT IN (0, 1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose active or inactive';
	END IF;

	IF pRegisterNumber = 1 AND (pRegisterType <> 'Express' OR pActive <> 1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Register 1 must stay active and Express-only';
	END IF;

	IF EXISTS (
		SELECT 1
		FROM register r
		WHERE r.StoreID = actingStoreID
		  AND r.RegisterNumber = pRegisterNumber
		  AND r.RegisterID <> COALESCE(pRegisterID, 0)
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That register number is already used';
	END IF;

	IF pRegisterID IS NOT NULL AND pRegisterID > 0 THEN

		SELECT r.RegisterNumber
		INTO currentNumber
		FROM register r
		WHERE r.RegisterID = pRegisterID
		  AND r.StoreID = actingStoreID
		FOR UPDATE;

		IF currentNumber IS NULL THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Register not found in this store';
		END IF;

		IF currentNumber = 1 AND pRegisterNumber <> 1 THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Register 1 cannot be renumbered';
		END IF;

		IF EXISTS (
			SELECT 1
			FROM salesreceipt sr
			WHERE sr.RegisterID = pRegisterID
			  AND sr.StoreID = actingStoreID
			  AND sr.Status = 'Open'
		) THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Finish or cancel the open transaction on this register before changing it';
		END IF;

		IF NOT (pRegisterType = 'Regular' AND pActive = 1)
		   AND NOT EXISTS (
				SELECT 1
				FROM register r
				WHERE r.StoreID = actingStoreID
				  AND r.RegisterType = 'Regular'
				  AND r.Active = 1
				  AND r.RegisterID <> pRegisterID
		   ) THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'At least one Regular register must stay active';
		END IF;

		UPDATE register
		SET RegisterNumber = pRegisterNumber,
			RegisterName = TRIM(pRegisterName),
			RegisterType = pRegisterType,
			Active = pActive
		WHERE RegisterID = pRegisterID
		  AND StoreID = actingStoreID;

		SET savedRegisterID = pRegisterID;

	ELSE

		INSERT INTO register (
			StoreID, RegisterNumber, RegisterName, RegisterType, Active
		)
		VALUES (
			actingStoreID, pRegisterNumber, TRIM(pRegisterName), pRegisterType, pActive
		);

		SET savedRegisterID = LAST_INSERT_ID();

	END IF;

	COMMIT;

	SELECT savedRegisterID AS RegisterID;
END$$

-- ==== Regular sales ====

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
		SELECT 1 FROM register
		WHERE RegisterID = pRegisterID
		  AND StoreID = pStoreID
		  AND RegisterType = 'Regular'
		  AND RegisterNumber <> 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This register is reserved for Express orders';
	END IF;
	IF NOT EXISTS (
		SELECT 1
		FROM operator
		WHERE OperatorID = pOperatorID
		  AND StoreID = pStoreID
		  AND Active = 1
		  AND Role IN ('Administrator', 'Manager', 'Operator')
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
		  AND SaleType = 'Regular'
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
	DECLARE currentSaleType VARCHAR(20);
	DECLARE actingOperatorStoreID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingOperatorActive TINYINT DEFAULT 0;
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
		Role,
		Active
	INTO
		actingOperatorStoreID,
		actingOperatorRole,
		actingOperatorActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT
		StoreID,
		OperatorID,
		SaleType,
		Status
	INTO
		saleStoreID,
		saleOperatorID,
		currentSaleType,
		saleStatus
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;
	IF saleStoreID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;
	IF actingOperatorStoreID IS NULL
	   OR actingOperatorActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;
	IF actingOperatorStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;
	IF currentSaleType = 'Regular'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to change a regular sale';
	ELSEIF currentSaleType = 'Express'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Personal Shopper') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to change an Express order';
	END IF;
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole NOT IN ('Administrator','Manager') THEN
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
	UPDATE expressorder
	SET Status = 'Picking'
	WHERE ReceiptID = pReceiptID
	  AND Status IN ('Received', 'Ready');

	COMMIT;
END$$

-- sp_remove_sale_item
CREATE PROCEDURE sp_remove_sale_item(
	IN pReceiptID BIGINT,
	IN pReceiptLineID BIGINT,
	IN pActingOperatorID INT,
	IN pQuantityToRemove DECIMAL(12,3)
)
BEGIN
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOperatorID INT DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20);
	DECLARE actingOperatorStoreID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingOperatorActive TINYINT DEFAULT 0;
	DECLARE saleStatus VARCHAR(20);
	DECLARE lineProductID INT DEFAULT NULL;
	DECLARE lineUnitType VARCHAR(20);
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
		Role,
		Active
	INTO
		actingOperatorStoreID,
		actingOperatorRole,
		actingOperatorActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT
		StoreID,
		OperatorID,
		SaleType,
		Status
	INTO
		saleStoreID,
		saleOperatorID,
		currentSaleType,
		saleStatus
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;
	IF saleStoreID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;
	IF actingOperatorStoreID IS NULL
	   OR actingOperatorActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;
	IF actingOperatorStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;
	IF currentSaleType = 'Regular'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to change a regular sale';
	ELSEIF currentSaleType = 'Express'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Personal Shopper') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to change an Express order';
	END IF;
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
	END IF;
	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Items can only be removed from an open sale';
	END IF;
	SELECT
		ProductID,
		UnitTypeAtSale,
		Quantity
	INTO
		lineProductID,
		lineUnitType,
		currentQuantity
	FROM salesreceiptline
	WHERE ReceiptID = pReceiptID
	  AND ReceiptLineID = pReceiptLineID
	FOR UPDATE;
	IF lineProductID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected sale item does not exist';
	END IF;
	IF lineUnitType = 'Each'
	   AND pQuantityToRemove IS NOT NULL
	   AND pQuantityToRemove > 0
	   AND pQuantityToRemove <> FLOOR(pQuantityToRemove) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This product must use a whole-number quantity';
	END IF;
	SET quantityToRemove =
		LEAST(
			currentQuantity,
			CASE
				WHEN pQuantityToRemove IS NULL OR pQuantityToRemove <= 0 THEN 1.000
				ELSE pQuantityToRemove
			END
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
	UPDATE expressorder
	SET Status = 'Picking'
	WHERE ReceiptID = pReceiptID
	  AND Status IN ('Received', 'Ready');

	COMMIT;
END$$

-- sp_calculate_sale_totals
CREATE PROCEDURE sp_calculate_sale_totals(
	IN pReceiptID BIGINT,
	OUT oGrossSubtotal DECIMAL(10,2),
	OUT oPreTaxDiscount DECIMAL(10,2),
	OUT oSubtotalAfterDiscount DECIMAL(10,2),
	OUT oTaxableSubtotal DECIMAL(10,2),
	OUT oTaxAmount DECIMAL(10,2),
	OUT oPostTaxDiscount DECIMAL(10,2),
	OUT oTotalBeforeFee DECIMAL(10,2)
)
BEGIN
	DECLARE grossTaxableAmount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE taxableRatio DECIMAL(12,6) DEFAULT 0;
	DECLARE taxableDiscountPortion DECIMAL(10,2) DEFAULT 0.00;
	DECLARE runningSubtotal DECIMAL(10,2) DEFAULT 0.00;
	DECLARE runningTotal DECIMAL(10,2) DEFAULT 0.00;
	DECLARE requestedDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE grantedDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE cursorDiscountID BIGINT DEFAULT NULL;
	DECLARE cursorKind VARCHAR(10) DEFAULT NULL;
	DECLARE cursorValue DECIMAL(10,2) DEFAULT 0.00;
	DECLARE cursorFinished TINYINT DEFAULT 0;
	DECLARE couponTotal DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponTaxableTotal DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponSaleID BIGINT DEFAULT NULL;
	DECLARE couponProductID INT DEFAULT NULL;
	DECLARE couponRequired INT DEFAULT 1;
	DECLARE couponKind VARCHAR(10) DEFAULT NULL;
	DECLARE couponValue DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponLineQuantity DECIMAL(12,3) DEFAULT 0;
	DECLARE couponUnitPrice DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponLineTaxable TINYINT DEFAULT 0;
	DECLARE couponUnitsUsed INT DEFAULT 0;
	DECLARE couponBase DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponAmount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE couponCursor CURSOR FOR
		SELECT sc.SaleCouponID, c.ProductID, c.RequiredQuantity, c.DiscountKind, c.DiscountValue,
			COALESCE(l.Quantity, 0), COALESCE(l.UnitPrice, 0), COALESCE(l.TaxableAtSale, 0)
		FROM salesreceiptcoupon sc
		JOIN coupon c
			ON c.CouponID = sc.CouponID
		LEFT JOIN salesreceiptline l
			ON l.ReceiptID = sc.ReceiptID
		   AND l.ProductID = c.ProductID
		WHERE sc.ReceiptID = pReceiptID
		ORDER BY sc.CouponNumber;
	DECLARE preTaxCursor CURSOR FOR
		SELECT DiscountID, DiscountKind, DiscountValue
		FROM salesreceiptdiscount
		WHERE ReceiptID = pReceiptID
		  AND TaxTiming = 'Before Tax'
		ORDER BY DiscountNumber;
	DECLARE postTaxCursor CURSOR FOR
		SELECT DiscountID, DiscountKind, DiscountValue
		FROM salesreceiptdiscount
		WHERE ReceiptID = pReceiptID
		  AND TaxTiming = 'After Tax'
		ORDER BY DiscountNumber;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursorFinished = 1;

	SELECT
		COALESCE(ROUND(SUM(Quantity * UnitPrice - LineDiscountAmount), 2), 0.00),
		COALESCE(
			ROUND(
				SUM(
					CASE
						WHEN TaxableAtSale = 1 THEN Quantity * UnitPrice - LineDiscountAmount
						ELSE 0.00
					END
				),
				2
			),
			0.00
		)
	INTO oGrossSubtotal, grossTaxableAmount
	FROM salesreceiptline
	WHERE ReceiptID = pReceiptID;

	-- Coupons take money off the item they are for, before any other discount
	IF EXISTS (SELECT 1 FROM salesreceiptcoupon WHERE ReceiptID = pReceiptID) THEN
		CREATE TEMPORARY TABLE IF NOT EXISTS tmp_coupon_used (
			ProductID INT NOT NULL PRIMARY KEY,
			UsedUnits INT NOT NULL
		) ENGINE = MEMORY;

		DELETE FROM tmp_coupon_used;

		OPEN couponCursor;

		couponLoop: LOOP
			FETCH couponCursor INTO
				couponSaleID, couponProductID, couponRequired, couponKind, couponValue,
				couponLineQuantity, couponUnitPrice, couponLineTaxable;

			IF cursorFinished = 1 THEN
				LEAVE couponLoop;
			END IF;

			SET couponUnitsUsed = COALESCE(
				(SELECT UsedUnits FROM tmp_coupon_used WHERE ProductID = couponProductID),
				0
			);

			IF couponLineQuantity - couponUnitsUsed >= couponRequired THEN
				SET couponBase = ROUND(couponRequired * couponUnitPrice, 2);

				IF couponKind = 'Percent' THEN
					SET couponAmount = ROUND(couponBase * couponValue / 100, 2);
				ELSE
					SET couponAmount = LEAST(couponValue, couponBase);
				END IF;

				REPLACE INTO tmp_coupon_used (ProductID, UsedUnits)
				VALUES (couponProductID, couponUnitsUsed + couponRequired);

				UPDATE salesreceiptcoupon
				SET UnitsCovered = couponRequired,
					AppliedAmount = couponAmount
				WHERE SaleCouponID = couponSaleID;

				SET couponTotal = couponTotal + couponAmount;

				IF couponLineTaxable = 1 THEN
					SET couponTaxableTotal = couponTaxableTotal + couponAmount;
				END IF;
			ELSE
				UPDATE salesreceiptcoupon
				SET UnitsCovered = 0,
					AppliedAmount = 0.00
				WHERE SaleCouponID = couponSaleID;
			END IF;
		END LOOP;

		CLOSE couponCursor;
		SET cursorFinished = 0;

		SET oGrossSubtotal = GREATEST(ROUND(oGrossSubtotal - couponTotal, 2), 0.00);
		SET grossTaxableAmount = GREATEST(ROUND(grossTaxableAmount - couponTaxableTotal, 2), 0.00);
	END IF;

	SET runningSubtotal = oGrossSubtotal;
	SET oPreTaxDiscount = 0.00;
	SET oPostTaxDiscount = 0.00;

	OPEN preTaxCursor;

	preTaxLoop: LOOP
		FETCH preTaxCursor INTO cursorDiscountID, cursorKind, cursorValue;

		IF cursorFinished = 1 THEN
			LEAVE preTaxLoop;
		END IF;

		IF cursorKind = 'Percent' THEN
			SET requestedDiscount = ROUND(runningSubtotal * cursorValue / 100, 2);
		ELSE
			SET requestedDiscount = cursorValue;
		END IF;

		SET grantedDiscount = LEAST(requestedDiscount, runningSubtotal);

		UPDATE salesreceiptdiscount
		SET AppliedAmount = grantedDiscount
		WHERE DiscountID = cursorDiscountID;

		SET runningSubtotal = runningSubtotal - grantedDiscount;
		SET oPreTaxDiscount = oPreTaxDiscount + grantedDiscount;
	END LOOP;

	CLOSE preTaxCursor;
	SET cursorFinished = 0;

	SET oSubtotalAfterDiscount = ROUND(oGrossSubtotal - oPreTaxDiscount, 2);

	IF oGrossSubtotal > 0 THEN
		SET taxableRatio = grossTaxableAmount / oGrossSubtotal;
	ELSE
		SET taxableRatio = 0;
	END IF;

	SET taxableDiscountPortion = ROUND(oPreTaxDiscount * taxableRatio, 2);
	SET oTaxableSubtotal = ROUND(grossTaxableAmount - taxableDiscountPortion, 2);

	IF oTaxableSubtotal < 0 THEN
		SET oTaxableSubtotal = 0.00;
	END IF;

	IF oTaxableSubtotal > oSubtotalAfterDiscount THEN
		SET oTaxableSubtotal = oSubtotalAfterDiscount;
	END IF;

	SET oTaxAmount = ROUND(oTaxableSubtotal * 0.0775, 2);
	SET runningTotal = ROUND(oSubtotalAfterDiscount + oTaxAmount, 2);

	OPEN postTaxCursor;

	postTaxLoop: LOOP
		FETCH postTaxCursor INTO cursorDiscountID, cursorKind, cursorValue;

		IF cursorFinished = 1 THEN
			LEAVE postTaxLoop;
		END IF;

		IF cursorKind = 'Percent' THEN
			SET requestedDiscount = ROUND(runningTotal * cursorValue / 100, 2);
		ELSE
			SET requestedDiscount = cursorValue;
		END IF;

		SET grantedDiscount = LEAST(requestedDiscount, runningTotal);

		UPDATE salesreceiptdiscount
		SET AppliedAmount = grantedDiscount
		WHERE DiscountID = cursorDiscountID;

		SET runningTotal = runningTotal - grantedDiscount;
		SET oPostTaxDiscount = oPostTaxDiscount + grantedDiscount;
	END LOOP;

	CLOSE postTaxCursor;

	SET oTotalBeforeFee = ROUND(runningTotal, 2);
END$$

-- sp_get_sale_totals
CREATE PROCEDURE sp_get_sale_totals(
	IN pReceiptID BIGINT
)
BEGIN
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE calcGross DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPreTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcAfterDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTaxable DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPostTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTotal DECIMAL(10,2) DEFAULT 0.00;

	SELECT Status
	INTO saleStatus
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID;

	IF saleStatus IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;

	IF saleStatus = 'Open' THEN
		CALL sp_calculate_sale_totals(
			pReceiptID,
			calcGross,
			calcPreTax,
			calcAfterDiscount,
			calcTaxable,
			calcTax,
			calcPostTax,
			calcTotal
		);

		SELECT
			calcGross AS GrossSubtotal,
			calcPreTax AS PreTaxDiscount,
			calcAfterDiscount AS SubtotalAfterDiscount,
			calcTaxable AS TaxableSubtotal,
			calcTax AS TaxAmount,
			calcPostTax AS PostTaxDiscount,
			calcTotal AS TotalDue;
	ELSE
		SELECT
			SubtotalAmount AS GrossSubtotal,
			ReceiptDiscountAmount AS PreTaxDiscount,
			ROUND(SubtotalAmount - ReceiptDiscountAmount, 2) AS SubtotalAfterDiscount,
			TaxableSubtotalAmount AS TaxableSubtotal,
			TaxAmount AS TaxAmount,
			PostTaxDiscountAmount AS PostTaxDiscount,
			TotalAmount AS TotalDue
		FROM salesreceipt
		WHERE ReceiptID = pReceiptID;
	END IF;
END$$

-- sp_apply_discount
CREATE PROCEDURE sp_apply_discount(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT,
	IN pDiscountKind VARCHAR(10),
	IN pTaxTiming VARCHAR(10),
	IN pDiscountValue DECIMAL(10,2),
	IN pReason VARCHAR(255),
	IN pSaveReason TINYINT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;
	DECLARE normalizedReason VARCHAR(255) DEFAULT NULL;
	DECLARE existingCount INT DEFAULT 0;
	DECLARE nextNumber INT DEFAULT 1;
	DECLARE newDiscountID BIGINT DEFAULT NULL;
	DECLARE newAppliedAmount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE requestedDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE applicableBase DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcGross DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPreTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcAfterDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTaxable DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPostTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTotal DECIMAL(10,2) DEFAULT 0.00;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;

	IF actingStoreID IS NULL OR actingActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;

	IF actingRole NOT IN ('Administrator', 'Manager', 'Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to discount a sale';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF currentSaleType <> 'Regular' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only a regular sale can be discounted';
	END IF;

	IF saleOwnerID <> pActingOperatorID AND actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot discount another operator''s sale';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open sale can be discounted';
	END IF;

	IF pDiscountKind IS NULL OR pDiscountKind NOT IN ('Percent', 'Dollar') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose a percent or dollar discount';
	END IF;

	IF pTaxTiming IS NULL OR pTaxTiming NOT IN ('Before Tax', 'After Tax') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose whether the discount applies before or after tax';
	END IF;

	IF pDiscountValue IS NULL OR pDiscountValue <= 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a discount amount greater than zero';
	END IF;

	IF pDiscountKind = 'Percent' AND pDiscountValue > 100 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'A percent discount cannot be more than 100';
	END IF;

	SET normalizedReason = NULLIF(TRIM(pReason), '');

	IF normalizedReason IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a reason for the discount';
	END IF;

	SELECT COUNT(*), COALESCE(MAX(DiscountNumber), 0) + 1
	INTO existingCount, nextNumber
	FROM salesreceiptdiscount
	WHERE ReceiptID = pReceiptID;

	IF existingCount >= 5 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'A sale can have no more than 5 discounts';
	END IF;

	CALL sp_calculate_sale_totals(
		pReceiptID,
		calcGross,
		calcPreTax,
		calcAfterDiscount,
		calcTaxable,
		calcTax,
		calcPostTax,
		calcTotal
	);

	IF calcGross <= 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Add an item with a price before applying a discount';
	END IF;

	IF pTaxTiming = 'Before Tax' THEN
		SET applicableBase = calcAfterDiscount;
	ELSE
		SET applicableBase = calcTotal;
	END IF;

	IF pDiscountKind = 'Percent' THEN
		SET requestedDiscount = ROUND(applicableBase * pDiscountValue / 100, 2);
	ELSE
		SET requestedDiscount = pDiscountValue;
	END IF;

	IF requestedDiscount > applicableBase THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The discount is larger than the amount it applies to';
	END IF;

	IF requestedDiscount <= 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The discount is too small to change the total';
	END IF;

	INSERT INTO salesreceiptdiscount (
		ReceiptID, DiscountNumber, DiscountKind, TaxTiming,
		DiscountValue, Reason, AppliedByOperatorID
	)
	VALUES (
		pReceiptID, nextNumber, pDiscountKind, pTaxTiming,
		pDiscountValue, normalizedReason, pActingOperatorID
	);

	SET newDiscountID = LAST_INSERT_ID();

	CALL sp_calculate_sale_totals(
		pReceiptID,
		calcGross,
		calcPreTax,
		calcAfterDiscount,
		calcTaxable,
		calcTax,
		calcPostTax,
		calcTotal
	);

	SELECT AppliedAmount
	INTO newAppliedAmount
	FROM salesreceiptdiscount
	WHERE DiscountID = newDiscountID;

	IF newAppliedAmount <= 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The discount is too small to change the total';
	END IF;

	IF pSaveReason = 1 OR EXISTS (
		SELECT 1 FROM discountreason WHERE ReasonText = normalizedReason
	) THEN
		INSERT INTO discountreason (
			ReasonText, TimesUsed, LastUsedAt, CreatedByOperatorID
		)
		VALUES (
			normalizedReason, 1, NOW(), pActingOperatorID
		)
		ON DUPLICATE KEY UPDATE
			TimesUsed = TimesUsed + 1,
			LastUsedAt = NOW(),
			Active = 1;
	END IF;

	COMMIT;

	SELECT newDiscountID AS DiscountID;
END$$

-- sp_remove_discount
CREATE PROCEDURE sp_remove_discount(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT,
	IN pDiscountID BIGINT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;
	DECLARE removedRows INT DEFAULT 0;
	DECLARE calcGross DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPreTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcAfterDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTaxable DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPostTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTotal DECIMAL(10,2) DEFAULT 0.00;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;

	IF actingStoreID IS NULL OR actingActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;

	IF actingRole NOT IN ('Administrator', 'Manager', 'Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to change a discount';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF currentSaleType <> 'Regular' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only a regular sale can be discounted';
	END IF;

	IF saleOwnerID <> pActingOperatorID AND actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open sale can be changed';
	END IF;

	DELETE FROM salesreceiptdiscount
	WHERE DiscountID = pDiscountID
	  AND ReceiptID = pReceiptID;

	SET removedRows = ROW_COUNT();

	IF removedRows = 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That discount was not found on this sale';
	END IF;

	CALL sp_calculate_sale_totals(
		pReceiptID,
		calcGross,
		calcPreTax,
		calcAfterDiscount,
		calcTaxable,
		calcTax,
		calcPostTax,
		calcTotal
	);

	COMMIT;
END$$

-- sp_add_coupon_to_sale
CREATE PROCEDURE sp_add_coupon_to_sale(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT,
	IN pCouponCode VARCHAR(20)
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;
	DECLARE foundCouponID INT DEFAULT NULL;
	DECLARE couponIsActive TINYINT DEFAULT 0;
	DECLARE couponStart DATE DEFAULT NULL;
	DECLARE couponEnd DATE DEFAULT NULL;
	DECLARE couponProductID INT DEFAULT NULL;
	DECLARE couponNeeded INT DEFAULT 1;
	DECLARE couponProductName VARCHAR(120) DEFAULT NULL;
	DECLARE couponCount INT DEFAULT 0;
	DECLARE nextNumber INT DEFAULT 1;
	DECLARE newSaleCouponID BIGINT DEFAULT NULL;
	DECLARE newUnitsCovered INT DEFAULT 0;
	DECLARE couponMessage VARCHAR(200) DEFAULT NULL;
	DECLARE calcGross DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPreTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcAfterDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTaxable DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPostTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTotal DECIMAL(10,2) DEFAULT 0.00;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;

	IF actingStoreID IS NULL OR actingActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;

	IF actingRole NOT IN ('Administrator', 'Manager', 'Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to use coupons';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF currentSaleType <> 'Regular' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Coupons can be used on a regular sale only';
	END IF;

	IF saleOwnerID <> pActingOperatorID AND actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open sale can use coupons';
	END IF;

	SELECT CouponID, Active, StartDate, EndDate, ProductID, RequiredQuantity
	INTO foundCouponID, couponIsActive, couponStart, couponEnd, couponProductID, couponNeeded
	FROM coupon
	WHERE CouponCode = TRIM(pCouponCode);

	IF foundCouponID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon was not found';
	END IF;

	IF couponIsActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon is not active';
	END IF;

	IF couponStart IS NOT NULL AND CURDATE() < couponStart THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon is not valid yet';
	END IF;

	IF couponEnd IS NOT NULL AND CURDATE() > couponEnd THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon has expired';
	END IF;

	IF EXISTS (
		SELECT 1 FROM salesreceiptcoupon
		WHERE ReceiptID = pReceiptID AND CouponID = foundCouponID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon is already on this sale';
	END IF;

	SELECT COUNT(*), COALESCE(MAX(CouponNumber), 0) + 1
	INTO couponCount, nextNumber
	FROM salesreceiptcoupon
	WHERE ReceiptID = pReceiptID;

	IF couponCount >= 10 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'A sale can have no more than 10 coupons';
	END IF;

	INSERT INTO salesreceiptcoupon (ReceiptID, CouponID, CouponNumber, AppliedByOperatorID)
	VALUES (pReceiptID, foundCouponID, nextNumber, pActingOperatorID);

	SET newSaleCouponID = LAST_INSERT_ID();

	CALL sp_calculate_sale_totals(
		pReceiptID,
		calcGross,
		calcPreTax,
		calcAfterDiscount,
		calcTaxable,
		calcTax,
		calcPostTax,
		calcTotal
	);

	SELECT UnitsCovered
	INTO newUnitsCovered
	FROM salesreceiptcoupon
	WHERE SaleCouponID = newSaleCouponID;

	IF newUnitsCovered = 0 THEN
		SELECT ProductName
		INTO couponProductName
		FROM product
		WHERE ProductID = couponProductID;

		SET couponMessage = CONCAT(
			'Add ', couponNeeded, ' ', couponProductName,
			' to the sale first. Items covered by another coupon do not count'
		);

		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = couponMessage;
	END IF;

	COMMIT;

	SELECT newSaleCouponID AS SaleCouponID;
END$$

-- sp_remove_coupon_from_sale
CREATE PROCEDURE sp_remove_coupon_from_sale(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT,
	IN pSaleCouponID BIGINT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;
	DECLARE removedRows INT DEFAULT 0;
	DECLARE calcGross DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPreTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcAfterDiscount DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTaxable DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcPostTax DECIMAL(10,2) DEFAULT 0.00;
	DECLARE calcTotal DECIMAL(10,2) DEFAULT 0.00;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;

	IF actingStoreID IS NULL OR actingActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;

	IF actingRole NOT IN ('Administrator', 'Manager', 'Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to use coupons';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF currentSaleType <> 'Regular' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Coupons can be used on a regular sale only';
	END IF;

	IF saleOwnerID <> pActingOperatorID AND actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot change another operator''s sale';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open sale can use coupons';
	END IF;

	DELETE FROM salesreceiptcoupon
	WHERE SaleCouponID = pSaleCouponID
	  AND ReceiptID = pReceiptID;

	SET removedRows = ROW_COUNT();

	IF removedRows = 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon was not found on this sale';
	END IF;

	CALL sp_calculate_sale_totals(
		pReceiptID,
		calcGross,
		calcPreTax,
		calcAfterDiscount,
		calcTaxable,
		calcTax,
		calcPostTax,
		calcTotal
	);

	COMMIT;
END$$

-- sp_checkout_sale
CREATE PROCEDURE sp_checkout_sale(
	IN pReceiptID BIGINT,
	IN pAmountTendered DECIMAL(10,2),
	IN pActingOperatorID INT,
	IN pPaymentMethod VARCHAR(10)
)
BEGIN
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOperatorID INT DEFAULT NULL;
	DECLARE actingOperatorStoreID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingOperatorActive TINYINT DEFAULT 0;
	DECLARE saleStatus VARCHAR(20);
	DECLARE currentSaleType VARCHAR(20);
	DECLARE expressOrderStatus VARCHAR(20) DEFAULT NULL;
	DECLARE paymentMethodUsed VARCHAR(20) DEFAULT 'Cash';
	DECLARE expressFulfillmentMethod VARCHAR(20) DEFAULT NULL;
	DECLARE expressDeliveryFee DECIMAL(10,2) DEFAULT 0.00;
	DECLARE expressDeliveryAddressLine1 VARCHAR(120) DEFAULT NULL;
	DECLARE expressDeliveryAddressLine2 VARCHAR(120) DEFAULT NULL;
	DECLARE expressDeliveryCity VARCHAR(80) DEFAULT NULL;
	DECLARE expressDeliveryStateCode CHAR(2) DEFAULT NULL;
	DECLARE expressDeliveryPostalCode VARCHAR(10) DEFAULT NULL;
	DECLARE calculatedGrossSubtotal DECIMAL(10,2);
	DECLARE calculatedDiscount DECIMAL(10,2);
	DECLARE calculatedSubtotal DECIMAL(10,2);
	DECLARE calculatedTaxableSubtotal DECIMAL(10,2);
	DECLARE calculatedTax DECIMAL(10,2);
	DECLARE calculatedPostTaxDiscount DECIMAL(10,2);
	DECLARE calculatedBeforeFee DECIMAL(10,2);
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
		StoreID,
		Role,
		Active
	INTO
		actingOperatorStoreID,
		actingOperatorRole,
		actingOperatorActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT
		StoreID,
		OperatorID,
		Status,
		SaleType
	INTO
		saleStoreID,
		saleOperatorID,
		saleStatus,
		currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;
	IF saleOperatorID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;
	IF actingOperatorStoreID IS NULL
	   OR actingOperatorActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;
	IF actingOperatorStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;
	IF currentSaleType = 'Regular'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to complete a regular sale';
	ELSEIF currentSaleType = 'Express'
	   AND actingOperatorRole NOT IN ('Administrator','Manager','Personal Shopper') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to complete an Express order';
	END IF;
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot complete another operator''s sale';
	END IF;
	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open sale can be checked out';
	END IF;
	SELECT
		COUNT(*),
		COALESCE(SUM(Quantity), 0)
	INTO
		receiptLineCount,
		itemQuantity
	FROM salesreceiptline
	WHERE ReceiptID = pReceiptID;
	IF receiptLineCount = 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'At least one item is required before checkout';
	END IF;
	CALL sp_calculate_sale_totals(
		pReceiptID,
		calculatedGrossSubtotal,
		calculatedDiscount,
		calculatedSubtotal,
		calculatedTaxableSubtotal,
		calculatedTax,
		calculatedPostTaxDiscount,
		calculatedBeforeFee
	);
	IF currentSaleType = 'Express' THEN
		SELECT Status, FulfillmentMethod, DeliveryFee,
			DeliveryAddressLine1,
			DeliveryAddressLine2,
			DeliveryCity,
			DeliveryStateCode,
			DeliveryPostalCode
		INTO expressOrderStatus, expressFulfillmentMethod, expressDeliveryFee,
			expressDeliveryAddressLine1,
			expressDeliveryAddressLine2,
			expressDeliveryCity,
			expressDeliveryStateCode,
			expressDeliveryPostalCode
		FROM expressorder
		WHERE ReceiptID = pReceiptID
		FOR UPDATE;

		IF expressFulfillmentMethod IS NULL THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Express order details are missing';
		END IF;

		IF expressFulfillmentMethod = 'Curbside' THEN
			IF expressDeliveryFee <> 0.00
			   OR expressDeliveryAddressLine1 IS NOT NULL
			   OR expressDeliveryAddressLine2 IS NOT NULL
			   OR expressDeliveryCity IS NOT NULL
			   OR expressDeliveryStateCode IS NOT NULL
			   OR expressDeliveryPostalCode IS NOT NULL THEN
				SIGNAL SQLSTATE '45000'
					SET MESSAGE_TEXT = 'Curbside Express order delivery data is invalid';
			END IF;
		ELSEIF expressFulfillmentMethod = 'Delivery' THEN
			IF expressDeliveryFee <> 10.00
			   OR NULLIF(TRIM(expressDeliveryAddressLine1), '') IS NULL
			   OR NULLIF(TRIM(expressDeliveryCity), '') IS NULL
			   OR NULLIF(TRIM(expressDeliveryStateCode), '') IS NULL
			   OR NULLIF(TRIM(expressDeliveryPostalCode), '') IS NULL THEN
				SIGNAL SQLSTATE '45000'
					SET MESSAGE_TEXT = 'Delivery Express order delivery data is invalid';
			END IF;
		ELSE
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Express order fulfillment method is invalid';
		END IF;
	END IF;
	SET calculatedTotal =
		ROUND(
			calculatedBeforeFee
			+ expressDeliveryFee,
			2
		);
	IF pPaymentMethod IS NULL
	   OR pPaymentMethod NOT IN ('Cash', 'Charge') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose Cash or Charge as the payment method';
	END IF;

	IF currentSaleType = 'Express' THEN
		IF expressOrderStatus <> 'Ready' THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Mark the Express order Ready before checkout';
		END IF;

		IF pPaymentMethod <> 'Charge' THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Express orders are paid by Charge';
		END IF;
	END IF;

	IF pPaymentMethod = 'Charge' THEN
		-- A charge is for exactly the total, so there is no cash and no change
		SET pAmountTendered = calculatedTotal;
		SET paymentMethodUsed = 'Charge';
	END IF;

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
	UPDATE salesreceiptline l
	JOIN (
		SELECT c.ProductID AS ProductID, SUM(sc.AppliedAmount) AS CouponAmount
		FROM salesreceiptcoupon sc
		JOIN coupon c
			ON c.CouponID = sc.CouponID
		WHERE sc.ReceiptID = pReceiptID
		GROUP BY c.ProductID
	) AS coupon_totals
		ON coupon_totals.ProductID = l.ProductID
	SET l.LineDiscountAmount = l.LineDiscountAmount + coupon_totals.CouponAmount
	WHERE l.ReceiptID = pReceiptID;

	UPDATE salesreceipt
	SET
		CheckoutDateTime = NOW(),
		Status = 'Paid',
		SubtotalAmount = calculatedGrossSubtotal,
		ReceiptDiscountAmount = calculatedDiscount,
		TaxableSubtotalAmount = calculatedTaxableSubtotal,
		TaxAmount = calculatedTax,
		PostTaxDiscountAmount = calculatedPostTaxDiscount,
		TotalAmount = calculatedTotal,
		PaymentMethod = paymentMethodUsed,
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
		PostTaxDiscountAmount = calculatedPostTaxDiscount,
		TotalAmount = calculatedTotal,
		PaymentMethod = paymentMethodUsed,
		AmountTendered = pAmountTendered,
		ChangeDue = calculatedChange
	WHERE ReceiptID = pReceiptID;
	IF currentSaleType = 'Express' THEN
		UPDATE expressorder
		SET Status = 'Completed'
		WHERE ReceiptID = pReceiptID;
	END IF;
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
	DECLARE currentSaleType VARCHAR(20);
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
		SaleType,
		SubtotalAmount,
		ReceiptDiscountAmount
	INTO
		saleStoreID,
		saleOperatorID,
		saleStatus,
		currentSaleType,
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
	IF currentSaleType = 'Regular'
	   AND actingRole NOT IN ('Administrator','Manager','Operator') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to cancel a regular sale';
	ELSEIF currentSaleType = 'Express'
	   AND actingRole NOT IN ('Administrator','Manager','Personal Shopper') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to cancel an Express order';
	END IF;
	IF saleOperatorID <> pActingOperatorID
	   AND actingRole NOT IN ('Administrator','Manager') THEN
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
	IF currentSaleType = 'Express' THEN
		UPDATE expressorder
		SET Status = 'Cancelled'
		WHERE ReceiptID = pReceiptID;
	END IF;
	COMMIT;
END$$

-- ==== Supervisors: take over, hand back, and access log ====

-- sp_take_over_sale
CREATE PROCEDURE sp_take_over_sale(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction does not exist';
	END IF;

	IF actingStoreID IS NULL
	   OR actingActive <> 1
	   OR actingRole NOT IN ('Administrator', 'Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an Administrator or Manager can take over a transaction';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open transaction can be taken over';
	END IF;

	IF saleOwnerID = pActingOperatorID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This transaction is already yours';
	END IF;

	IF EXISTS (
		SELECT 1
		FROM salesreceipt sr
		WHERE sr.OperatorID = pActingOperatorID
		  AND sr.Status = 'Open'
		  AND sr.SaleType = currentSaleType
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Finish or cancel your own open transaction of this kind before taking over another';
	END IF;

	UPDATE salesreceipt
	SET OperatorID = pActingOperatorID
	WHERE ReceiptID = pReceiptID;

	IF currentSaleType = 'Express' THEN
		UPDATE expressorder
		SET PersonalShopperID = pActingOperatorID
		WHERE ReceiptID = pReceiptID;
	END IF;

	INSERT INTO saleaccesslog (
		ReceiptID, ActingOperatorID, AccessMode, PreviousOperatorID, NewOperatorID
	)
	VALUES (
		pReceiptID, pActingOperatorID, 'Take Over', saleOwnerID, pActingOperatorID
	);

	COMMIT;
END$$

-- sp_hand_back_sale
CREATE PROCEDURE sp_hand_back_sale(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20) DEFAULT NULL;
	DECLARE currentSaleType VARCHAR(20) DEFAULT NULL;
	DECLARE lastTakeOverID BIGINT DEFAULT NULL;
	DECLARE previousOwnerID INT DEFAULT NULL;
	DECLARE takenByID INT DEFAULT NULL;
	DECLARE previousActive TINYINT DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID, Status, SaleType
	INTO saleStoreID, saleOwnerID, saleStatus, currentSaleType
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction does not exist';
	END IF;

	IF actingStoreID IS NULL
	   OR actingActive <> 1
	   OR actingRole NOT IN ('Administrator', 'Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an Administrator or Manager can hand a transaction back';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF saleStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open transaction can be handed back';
	END IF;

	SELECT sl.AccessID, sl.PreviousOperatorID, sl.NewOperatorID
	INTO lastTakeOverID, previousOwnerID, takenByID
	FROM saleaccesslog sl
	WHERE sl.ReceiptID = pReceiptID
	  AND sl.AccessMode = 'Take Over'
	ORDER BY sl.AccessID DESC
	LIMIT 1;

	IF lastTakeOverID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This transaction was not taken over from anyone';
	END IF;

	IF saleOwnerID <> takenByID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This transaction has changed hands since it was taken over';
	END IF;

	SELECT Active
	INTO previousActive
	FROM operator
	WHERE OperatorID = previousOwnerID;

	IF previousActive IS NULL OR previousActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The original employee is not active';
	END IF;

	IF EXISTS (
		SELECT 1
		FROM salesreceipt sr
		WHERE sr.OperatorID = previousOwnerID
		  AND sr.Status = 'Open'
		  AND sr.SaleType = currentSaleType
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The original employee has started another transaction of this kind';
	END IF;

	UPDATE salesreceipt
	SET OperatorID = previousOwnerID
	WHERE ReceiptID = pReceiptID;

	IF currentSaleType = 'Express' THEN
		UPDATE expressorder
		SET PersonalShopperID = previousOwnerID
		WHERE ReceiptID = pReceiptID;
	END IF;

	INSERT INTO saleaccesslog (
		ReceiptID, ActingOperatorID, AccessMode, PreviousOperatorID, NewOperatorID
	)
	VALUES (
		pReceiptID, pActingOperatorID, 'Hand Back', saleOwnerID, previousOwnerID
	);

	COMMIT;
END$$

-- sp_record_sale_access
CREATE PROCEDURE sp_record_sale_access(
	IN pReceiptID BIGINT,
	IN pActingOperatorID INT,
	IN pMode VARCHAR(10)
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOwnerID INT DEFAULT NULL;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT StoreID, OperatorID
	INTO saleStoreID, saleOwnerID
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID;

	IF saleOwnerID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction does not exist';
	END IF;

	IF actingStoreID IS NULL
	   OR actingActive <> 1
	   OR actingRole NOT IN ('Administrator', 'Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an Administrator or Manager can open another employee''s transaction';
	END IF;

	IF actingStoreID <> saleStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The transaction belongs to another store';
	END IF;

	IF pMode IS NULL OR pMode NOT IN ('View', 'Assist') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose View or Assist';
	END IF;

	INSERT INTO saleaccesslog (
		ReceiptID, ActingOperatorID, AccessMode, PreviousOperatorID
	)
	VALUES (
		pReceiptID, pActingOperatorID, pMode, saleOwnerID
	);
END$$

-- ==== FnH Express ====

-- sp_get_express_capacity
CREATE PROCEDURE sp_get_express_capacity(
	IN pStoreID INT
)
BEGIN
	DECLARE usedOrders INT DEFAULT 0;

	SELECT COUNT(*)
	INTO usedOrders
	FROM expressorder eo
	JOIN salesreceipt sr
		ON sr.ReceiptID = eo.ReceiptID
	WHERE sr.StoreID = pStoreID
	  AND DATE(eo.OrderPlacedDateTime) = CURDATE()
	  AND eo.Status <> 'Cancelled';

	SELECT
		20 AS DailyCapacity,
		usedOrders AS OrdersUsed,
		GREATEST(20 - usedOrders, 0) AS OrdersRemaining;
END$$

-- sp_create_express_customer
CREATE PROCEDURE sp_create_express_customer(
	IN pFirstName VARCHAR(60),
	IN pLastName VARCHAR(60),
	IN pPhone VARCHAR(20),
	IN pEmail VARCHAR(120)
)
BEGIN
	DECLARE newCustomerID INT;

	IF NULLIF(TRIM(pFirstName), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'First name is required';
	END IF;

	IF NULLIF(TRIM(pLastName), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Last name is required';
	END IF;

	IF NULLIF(TRIM(pPhone), '') IS NOT NULL
	   AND TRIM(pPhone) NOT REGEXP '^[0-9]{10}$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Phone number must contain exactly 10 digits or be left blank';
	END IF;

	/* A temporary loyalty number is saved first, then replaced using the new CustomerID. */
	INSERT INTO customer (
		LoyaltyNumber,
		FirstName,
		LastName,
		Email,
		Phone,
		JoinDate,
		LoyaltyPoints,
		Active
	)
	VALUES (
		CONCAT('TEMP', UUID_SHORT()),
		pFirstName,
		pLastName,
		NULLIF(pEmail, ''),
		NULLIF(pPhone, ''),
		CURDATE(),
		0,
		1
	);

	SET newCustomerID = LAST_INSERT_ID();

	UPDATE customer
	SET LoyaltyNumber =
		CONCAT('EXP', LPAD(newCustomerID, 6, '0'))
	WHERE CustomerID = newCustomerID;

	SELECT newCustomerID AS CustomerID;
END$$

-- sp_remove_unused_express_customer
CREATE PROCEDURE sp_remove_unused_express_customer(
	IN pCustomerID INT
)
BEGIN
	DELETE FROM customer
	WHERE CustomerID = pCustomerID
	  AND LoyaltyNumber LIKE 'EXP%'
	  AND NOT EXISTS (
			SELECT 1
			FROM salesreceipt sr
			WHERE sr.CustomerID = pCustomerID
	  )
	  AND NOT EXISTS (
			SELECT 1
			FROM customeraddress ca
			WHERE ca.CustomerID = pCustomerID
	  );
END$$

-- sp_create_customer_address
CREATE PROCEDURE sp_create_customer_address(
	IN pCustomerID INT,
	IN pAddressLabel VARCHAR(40),
	IN pAddressLine1 VARCHAR(120),
	IN pAddressLine2 VARCHAR(120),
	IN pCity VARCHAR(80),
	IN pStateCode CHAR(2),
	IN pPostalCode VARCHAR(10)
)
BEGIN
	DECLARE newCustomerAddressID INT;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	IF NOT EXISTS (
		SELECT 1
		FROM customer
		WHERE CustomerID = pCustomerID
		  AND Active = 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected customer is not active';
	END IF;

	IF NULLIF(TRIM(pAddressLabel), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a name for this address, such as Home or Work';
	END IF;

	IF NULLIF(TRIM(pAddressLine1), '') IS NULL
	   OR NULLIF(TRIM(pCity), '') IS NULL
	   OR NULLIF(TRIM(pStateCode), '') IS NULL
	   OR NULLIF(TRIM(pPostalCode), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Street address, city, state, and ZIP code are all required';
	END IF;

	IF EXISTS (
		SELECT 1
		FROM customeraddress
		WHERE CustomerID = pCustomerID
		  AND AddressLabel = pAddressLabel
		  AND Active = 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This customer already has an address saved under that name';
	END IF;

	INSERT INTO customeraddress (
		CustomerID,
		AddressLabel,
		AddressLine1,
		AddressLine2,
		City,
		StateCode,
		PostalCode
	)
	VALUES (
		pCustomerID,
		TRIM(pAddressLabel),
		TRIM(pAddressLine1),
		NULLIF(TRIM(pAddressLine2), ''),
		TRIM(pCity),
		UPPER(TRIM(pStateCode)),
		TRIM(pPostalCode)
	);

	SET newCustomerAddressID = LAST_INSERT_ID();

	COMMIT;

	SELECT newCustomerAddressID AS CustomerAddressID;
END$$

-- sp_update_customer_address
CREATE PROCEDURE sp_update_customer_address(
	IN pCustomerID INT,
	IN pCustomerAddressID INT,
	IN pAddressLine1 VARCHAR(120),
	IN pAddressLine2 VARCHAR(120),
	IN pCity VARCHAR(80),
	IN pStateCode CHAR(2),
	IN pPostalCode VARCHAR(10)
)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	IF NOT EXISTS (
		SELECT 1
		FROM customeraddress
		WHERE CustomerAddressID = pCustomerAddressID
		  AND CustomerID = pCustomerID
		  AND Active = 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected saved address was not found';
	END IF;

	IF NULLIF(TRIM(pAddressLine1), '') IS NULL
	   OR NULLIF(TRIM(pCity), '') IS NULL
	   OR NULLIF(TRIM(pStateCode), '') IS NULL
	   OR NULLIF(TRIM(pPostalCode), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Street address, city, state, and ZIP code are all required';
	END IF;

	UPDATE customeraddress
	SET
		AddressLine1 = TRIM(pAddressLine1),
		AddressLine2 = NULLIF(TRIM(pAddressLine2), ''),
		City = TRIM(pCity),
		StateCode = UPPER(TRIM(pStateCode)),
		PostalCode = TRIM(pPostalCode)
	WHERE CustomerAddressID = pCustomerAddressID
	  AND CustomerID = pCustomerID;

	COMMIT;
END$$

-- sp_create_express_order
CREATE PROCEDURE sp_create_express_order(
	IN pStoreID INT,
	IN pRegisterID INT,
	IN pPersonalShopperID INT,
	IN pCustomerID INT,
	IN pFulfillmentMethod VARCHAR(20),
	IN pDeliveryAddressLine1 VARCHAR(120),
	IN pDeliveryAddressLine2 VARCHAR(120),
	IN pDeliveryCity VARCHAR(80),
	IN pDeliveryStateCode CHAR(2),
	IN pDeliveryPostalCode VARCHAR(10)
)
BEGIN
	DECLARE expressRegisterID INT DEFAULT NULL;
	DECLARE usedOrders INT DEFAULT 0;
	DECLARE newReceiptID BIGINT;
	DECLARE newExpressOrderID BIGINT;
	DECLARE newTransactionNumber VARCHAR(40);
	DECLARE deliveryFee DECIMAL(10,2) DEFAULT 0.00;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT RegisterID
	INTO expressRegisterID
	FROM register
	WHERE StoreID = pStoreID
	  AND RegisterID = pRegisterID
	  AND RegisterType = 'Express'
	  AND Active = 1
	FOR UPDATE;

	IF NOT EXISTS (
		SELECT 1
		FROM store
		WHERE StoreID = pStoreID
		  AND Active = 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected store is not active';
	END IF;

	IF expressRegisterID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected Express register is unavailable';
	END IF;

	IF NOT EXISTS (
		SELECT 1
		FROM operator
		WHERE OperatorID = pPersonalShopperID
		  AND StoreID = pStoreID
		  AND Active = 1
		  AND Role IN ('Administrator','Manager','Personal Shopper')
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The user is not authorized to create an Express order';
	END IF;

	IF EXISTS (
		SELECT 1 FROM salesreceipt
		WHERE StoreID = pStoreID
		  AND RegisterID = expressRegisterID
		  AND Status = 'Open'
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'This register is already in use';
	END IF;

	IF EXISTS (
		SELECT 1 FROM salesreceipt
		WHERE StoreID = pStoreID
		  AND OperatorID = pPersonalShopperID
		  AND Status = 'Open'
		  AND SaleType = 'Express'
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Resume your open Express order before starting another';
	END IF;

	IF pCustomerID IS NOT NULL
	   AND NOT EXISTS (
			SELECT 1
			FROM customer
			WHERE CustomerID = pCustomerID
			  AND Active = 1
	   ) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected customer is not active';
	END IF;

	SELECT COUNT(*)
	INTO usedOrders
	FROM expressorder eo
	JOIN salesreceipt sr
		ON sr.ReceiptID = eo.ReceiptID
	WHERE sr.StoreID = pStoreID
	  AND DATE(eo.OrderPlacedDateTime) = CURDATE()
	  AND eo.Status <> 'Cancelled';

	IF usedOrders >= 20 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The store has reached its daily capacity of 20 Express orders';
	END IF;

	IF pFulfillmentMethod NOT IN ('Curbside','Delivery') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Fulfillment method must be Curbside or Delivery';
	END IF;

	IF pFulfillmentMethod = 'Delivery' THEN
		IF CURTIME() < '08:00:00'
		   OR CURTIME() > '16:00:00' THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'Home delivery is available only for orders placed from 8:00 AM through 4:00 PM';
		END IF;

		IF NULLIF(TRIM(pDeliveryAddressLine1), '') IS NULL
		   OR NULLIF(TRIM(pDeliveryCity), '') IS NULL
		   OR NULLIF(TRIM(pDeliveryStateCode), '') IS NULL
		   OR NULLIF(TRIM(pDeliveryPostalCode), '') IS NULL THEN
			SIGNAL SQLSTATE '45000'
				SET MESSAGE_TEXT = 'A delivery address is required for home delivery';
		END IF;

		SET deliveryFee = 10.00;
	ELSE
		SET deliveryFee = 0.00;
		SET pDeliveryAddressLine1 = NULL;
		SET pDeliveryAddressLine2 = NULL;
		SET pDeliveryCity = NULL;
		SET pDeliveryStateCode = NULL;
		SET pDeliveryPostalCode = NULL;
	END IF;

	SET newTransactionNumber =
		CONCAT(
			'E',
			LPAD(pStoreID, 3, '0'),
			'-',
			DATE_FORMAT(NOW(6), '%Y%m%d%H%i%s%f'),
			'-',
			LPAD(pPersonalShopperID, 4, '0')
		);

	INSERT INTO salesreceipt (
		TransactionNumber,
		StoreID,
		RegisterID,
		OperatorID,
		CustomerID,
		SaleType,
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
		expressRegisterID,
		pPersonalShopperID,
		pCustomerID,
		'Express',
		NOW(),
		NULL,
		'Open',
		0.00,
		0.00,
		0.00,
		0.00,
		deliveryFee,
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
		expressRegisterID,
		pPersonalShopperID,
		NOW(),
		'Open'
	);

	INSERT INTO expressorder (
		ReceiptID,
		CustomerID,
		PersonalShopperID,
		OrderPlacedDateTime,
		FulfillmentMethod,
		DeliveryFee,
		DeliveryAddressLine1,
		DeliveryAddressLine2,
		DeliveryCity,
		DeliveryStateCode,
		DeliveryPostalCode,
		Status
	)
	VALUES (
		newReceiptID,
		pCustomerID,
		pPersonalShopperID,
		NOW(),
		pFulfillmentMethod,
		deliveryFee,
		pDeliveryAddressLine1,
		pDeliveryAddressLine2,
		pDeliveryCity,
		CASE
			WHEN pDeliveryStateCode IS NULL THEN NULL
			ELSE UPPER(pDeliveryStateCode)
		END,
		pDeliveryPostalCode,
		'Received'
	);

	SET newExpressOrderID = LAST_INSERT_ID();

	COMMIT;

	SELECT
		newExpressOrderID AS ExpressOrderID,
		newReceiptID AS ReceiptID,
		newTransactionNumber AS TransactionNumber,
		GREATEST(20 - (usedOrders + 1), 0) AS OrdersRemaining;
END$$

-- sp_set_express_order_status
CREATE PROCEDURE sp_set_express_order_status(
	IN pExpressOrderID BIGINT,
	IN pPersonalShopperID INT,
	IN pStatus VARCHAR(20)
)
BEGIN
	DECLARE orderShopperID INT DEFAULT NULL;
	DECLARE receiptID BIGINT DEFAULT NULL;
	DECLARE orderStoreID INT DEFAULT NULL;
	DECLARE receiptStatus VARCHAR(20) DEFAULT NULL;
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;

	IF pStatus NOT IN ('Received','Picking','Ready') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Invalid Express order status';
	END IF;

	SELECT
		eo.PersonalShopperID,
		eo.ReceiptID,
		sr.StoreID,
		sr.Status
	INTO
		orderShopperID,
		receiptID,
		orderStoreID,
		receiptStatus
	FROM expressorder eo
	JOIN salesreceipt sr
		ON sr.ReceiptID = eo.ReceiptID
	WHERE eo.ExpressOrderID = pExpressOrderID;

	IF receiptID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Express order does not exist';
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
	WHERE OperatorID = pPersonalShopperID;

	IF actingStoreID IS NULL
	   OR actingActive <> 1 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The acting operator is not active';
	END IF;
	IF actingStoreID <> orderStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The Express order belongs to another store';
	END IF;
	IF actingRole NOT IN ('Administrator','Manager','Personal Shopper') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The operator is not authorized to update an Express order';
	END IF;
	IF orderShopperID <> pPersonalShopperID
	   AND actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot update another Personal Shopper''s Express order';
	END IF;

	IF receiptStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open Express order can be updated';
	END IF;

	IF pStatus = 'Ready'
	   AND NOT EXISTS (
			SELECT 1
			FROM salesreceiptline sl
			WHERE sl.ReceiptID = receiptID
	   ) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Add at least one item before marking the order Ready';
	END IF;

	UPDATE expressorder
	SET Status = pStatus
	WHERE ExpressOrderID = pExpressOrderID;
END$$

-- ==== Products and stock ====

-- sp_create_product
CREATE PROCEDURE sp_create_product(
	IN pStoreID INT,
	IN pOperatorID INT,
	IN pDepartmentID INT,
	IN pUPC VARCHAR(20),
	IN pPLUCode VARCHAR(10),
	IN pProductName VARCHAR(120),
	IN pDescription VARCHAR(500),
	IN pUnitType VARCHAR(10),
	IN pUnitCost DECIMAL(10,2),
	IN pRetailPrice DECIMAL(10,2),
	IN pTaxable TINYINT,
	IN pActive TINYINT,
	IN pStartingQuantity DECIMAL(12,3),
	IN pAisle VARCHAR(20),
	IN pSectionName VARCHAR(50),
	IN pShelfLocation VARCHAR(30)
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE newProductID INT DEFAULT NULL;
	DECLARE normalizedUPC VARCHAR(20) DEFAULT NULL;
	DECLARE normalizedPLU VARCHAR(10) DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pOperatorID;

	IF actingStoreID IS NULL OR actingActive <> 1 OR actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Administrator access is required to create products';
	END IF;

	IF actingStoreID <> pStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected store does not match the administrator account';
	END IF;

	IF NOT EXISTS (
		SELECT 1 FROM department WHERE DepartmentID = pDepartmentID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Select a valid department';
	END IF;

	SET normalizedUPC = NULLIF(TRIM(pUPC), '');
	SET normalizedPLU = NULLIF(TRIM(pPLUCode), '');

	IF NULLIF(TRIM(pProductName), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Product name is required';
	END IF;

	IF normalizedUPC IS NULL AND normalizedPLU IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a UPC or PLU code';
	END IF;

	IF normalizedUPC IS NOT NULL AND normalizedUPC NOT REGEXP '^[0-9]+$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The UPC can contain digits only';
	END IF;

	IF normalizedPLU IS NOT NULL AND normalizedPLU NOT REGEXP '^[0-9]+$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The PLU code can contain digits only';
	END IF;

	IF normalizedUPC IS NOT NULL AND normalizedUPC = normalizedPLU THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The UPC and PLU code must be different';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM coupon WHERE CouponCode = normalizedUPC
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC is used by a coupon';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE PLUCode = normalizedUPC
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC matches the PLU code of another product';
	END IF;

	IF normalizedPLU IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE UPC = normalizedPLU
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That PLU code matches the UPC of another product';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE UPC = normalizedUPC
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC is already assigned to another product';
	END IF;

	IF normalizedPLU IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE PLUCode = normalizedPLU
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That PLU code is already assigned to another product';
	END IF;

	IF pUnitType IS NULL OR pUnitType NOT IN ('Each','Pound') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Unit type must be Each or Pound';
	END IF;

	IF pUnitCost IS NOT NULL AND pUnitCost < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Unit cost cannot be negative';
	END IF;

	IF pRetailPrice IS NULL OR pRetailPrice < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Retail price cannot be negative';
	END IF;

	IF pTaxable IS NULL OR pActive IS NULL OR pTaxable NOT IN (0,1) OR pActive NOT IN (0,1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Invalid product status option';
	END IF;

	IF pStartingQuantity IS NULL OR pStartingQuantity < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Starting inventory cannot be negative';
	END IF;

	IF pUnitType = 'Each' AND pStartingQuantity <> FLOOR(pStartingQuantity) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Each-count products require a whole-number inventory quantity';
	END IF;

	INSERT INTO product (
		DepartmentID, UPC, PLUCode, ProductName, Description,
		UnitType, UnitCost, RetailPrice, Taxable, Active
	)
	VALUES (
		pDepartmentID,
		normalizedUPC,
		normalizedPLU,
		TRIM(pProductName),
		NULLIF(TRIM(pDescription), ''),
		pUnitType,
		pUnitCost,
		pRetailPrice,
		pTaxable,
		pActive
	);

	SET newProductID = LAST_INSERT_ID();

	INSERT INTO storeinventory (
		StoreID, ProductID, StockQuantity, Aisle, SectionName, ShelfLocation, LastCountedAt
	)
	VALUES (
		pStoreID,
		newProductID,
		pStartingQuantity,
		NULLIF(TRIM(pAisle), ''),
		NULLIF(TRIM(pSectionName), ''),
		NULLIF(TRIM(pShelfLocation), ''),
		NOW()
	);

	INSERT INTO inventoryadjustment (
		StoreID, ProductID, OperatorID, AdjustmentType,
		QuantityBefore, QuantityChange, QuantityAfter, Reason
	)
	VALUES (
		pStoreID, newProductID, pOperatorID, 'New Product',
		0.000, pStartingQuantity, pStartingQuantity, 'New product created'
	);

	COMMIT;

	SELECT newProductID AS ProductID;
END$$

-- sp_update_product
CREATE PROCEDURE sp_update_product(
	IN pStoreID INT,
	IN pOperatorID INT,
	IN pProductID INT,
	IN pDepartmentID INT,
	IN pUPC VARCHAR(20),
	IN pPLUCode VARCHAR(10),
	IN pProductName VARCHAR(120),
	IN pDescription VARCHAR(500),
	IN pUnitType VARCHAR(10),
	IN pUnitCost DECIMAL(10,2),
	IN pRetailPrice DECIMAL(10,2),
	IN pTaxable TINYINT,
	IN pActive TINYINT,
	IN pAisle VARCHAR(20),
	IN pSectionName VARCHAR(50),
	IN pShelfLocation VARCHAR(30)
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE currentUnitType VARCHAR(10) DEFAULT NULL;
	DECLARE currentStock DECIMAL(12,3) DEFAULT 0.000;
	DECLARE normalizedUPC VARCHAR(20) DEFAULT NULL;
	DECLARE normalizedPLU VARCHAR(10) DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pOperatorID;

	IF actingStoreID IS NULL OR actingActive <> 1 OR actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Administrator access is required to modify products';
	END IF;

	IF actingStoreID <> pStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected store does not match the administrator account';
	END IF;

	SELECT p.UnitType, si.StockQuantity
	INTO currentUnitType, currentStock
	FROM product p
	JOIN storeinventory si ON si.ProductID = p.ProductID AND si.StoreID = pStoreID
	WHERE p.ProductID = pProductID
	FOR UPDATE;

	IF currentUnitType IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected product was not found at this store';
	END IF;

	IF NOT EXISTS (
		SELECT 1 FROM department WHERE DepartmentID = pDepartmentID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Select a valid department';
	END IF;

	SET normalizedUPC = NULLIF(TRIM(pUPC), '');
	SET normalizedPLU = NULLIF(TRIM(pPLUCode), '');

	IF NULLIF(TRIM(pProductName), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Product name is required';
	END IF;

	IF normalizedUPC IS NULL AND normalizedPLU IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a UPC or PLU code';
	END IF;

	IF normalizedUPC IS NOT NULL AND normalizedUPC NOT REGEXP '^[0-9]+$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The UPC can contain digits only';
	END IF;

	IF normalizedPLU IS NOT NULL AND normalizedPLU NOT REGEXP '^[0-9]+$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The PLU code can contain digits only';
	END IF;

	IF normalizedUPC IS NOT NULL AND normalizedUPC = normalizedPLU THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The UPC and PLU code must be different';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM coupon WHERE CouponCode = normalizedUPC
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC is used by a coupon';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE PLUCode = normalizedUPC AND ProductID <> pProductID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC matches the PLU code of another product';
	END IF;

	IF normalizedPLU IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE UPC = normalizedPLU AND ProductID <> pProductID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That PLU code matches the UPC of another product';
	END IF;

	IF normalizedUPC IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE UPC = normalizedUPC AND ProductID <> pProductID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That UPC is already assigned to another product';
	END IF;

	IF normalizedPLU IS NOT NULL AND EXISTS (
		SELECT 1 FROM product WHERE PLUCode = normalizedPLU AND ProductID <> pProductID
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That PLU code is already assigned to another product';
	END IF;

	IF pUnitType IS NULL OR pUnitType NOT IN ('Each','Pound') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Unit type must be Each or Pound';
	END IF;

	IF pUnitType = 'Each' AND currentStock <> FLOOR(currentStock) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Stock has a fractional quantity. Adjust stock before changing this product to Each';
	END IF;

	IF pUnitCost IS NOT NULL AND pUnitCost < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Unit cost cannot be negative';
	END IF;

	IF pRetailPrice IS NULL OR pRetailPrice < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Retail price cannot be negative';
	END IF;

	IF pTaxable IS NULL OR pActive IS NULL OR pTaxable NOT IN (0,1) OR pActive NOT IN (0,1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Invalid product status option';
	END IF;

	UPDATE product
	SET DepartmentID = pDepartmentID,
		UPC = normalizedUPC,
		PLUCode = normalizedPLU,
		ProductName = TRIM(pProductName),
		Description = NULLIF(TRIM(pDescription), ''),
		UnitType = pUnitType,
		UnitCost = pUnitCost,
		RetailPrice = pRetailPrice,
		Taxable = pTaxable,
		Active = pActive
	WHERE ProductID = pProductID;

	UPDATE storeinventory
	SET Aisle = NULLIF(TRIM(pAisle), ''),
		SectionName = NULLIF(TRIM(pSectionName), ''),
		ShelfLocation = NULLIF(TRIM(pShelfLocation), '')
	WHERE StoreID = pStoreID
	  AND ProductID = pProductID;

	COMMIT;
END$$

-- sp_adjust_inventory
CREATE PROCEDURE sp_adjust_inventory(
	IN pStoreID INT,
	IN pOperatorID INT,
	IN pProductID INT,
	IN pAdjustmentType VARCHAR(10),
	IN pQuantity DECIMAL(12,3),
	IN pReason VARCHAR(255),
	IN pAisle VARCHAR(20),
	IN pSectionName VARCHAR(50),
	IN pShelfLocation VARCHAR(30)
)
BEGIN
	DECLARE actingStoreID INT DEFAULT NULL;
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE unitType VARCHAR(10) DEFAULT NULL;
	DECLARE quantityBefore DECIMAL(12,3) DEFAULT NULL;
	DECLARE quantityAfter DECIMAL(12,3) DEFAULT NULL;
	DECLARE quantityChange DECIMAL(12,3) DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT StoreID, Role, Active
	INTO actingStoreID, actingRole, actingActive
	FROM operator
	WHERE OperatorID = pOperatorID;

	IF actingStoreID IS NULL OR actingActive <> 1 OR actingRole NOT IN ('Administrator','Manager') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Administrator access is required to adjust inventory';
	END IF;

	IF actingStoreID <> pStoreID THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected store does not match the administrator account';
	END IF;

	SELECT p.UnitType, si.StockQuantity
	INTO unitType, quantityBefore
	FROM product p
	JOIN storeinventory si ON si.ProductID = p.ProductID AND si.StoreID = pStoreID
	WHERE p.ProductID = pProductID
	FOR UPDATE;

	IF unitType IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The selected product was not found at this store';
	END IF;

	IF pAdjustmentType IS NULL OR pAdjustmentType NOT IN ('Add','Remove','Set') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose Add, Remove, or Set inventory';
	END IF;

	IF pQuantity IS NULL OR pQuantity < 0 OR (pAdjustmentType IN ('Add','Remove') AND pQuantity = 0) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a quantity greater than zero for Add or Remove';
	END IF;

	IF unitType = 'Each' AND pQuantity <> FLOOR(pQuantity) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Each-count products require whole-number quantities';
	END IF;

	IF NULLIF(TRIM(pReason), '') IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a reason for the inventory adjustment';
	END IF;

	IF pAdjustmentType = 'Add' THEN
		SET quantityAfter = quantityBefore + pQuantity;
	ELSEIF pAdjustmentType = 'Remove' THEN
		SET quantityAfter = quantityBefore - pQuantity;
	ELSE
		SET quantityAfter = pQuantity;
	END IF;

	IF quantityAfter < 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Inventory cannot be adjusted below zero';
	END IF;

	SET quantityChange = quantityAfter - quantityBefore;

	UPDATE storeinventory
	SET StockQuantity = quantityAfter,
		Aisle = NULLIF(TRIM(pAisle), ''),
		SectionName = NULLIF(TRIM(pSectionName), ''),
		ShelfLocation = NULLIF(TRIM(pShelfLocation), ''),
		LastCountedAt = NOW()
	WHERE StoreID = pStoreID
	  AND ProductID = pProductID;

	INSERT INTO inventoryadjustment (
		StoreID, ProductID, OperatorID, AdjustmentType,
		QuantityBefore, QuantityChange, QuantityAfter, Reason
	)
	VALUES (
		pStoreID,
		pProductID,
		pOperatorID,
		pAdjustmentType,
		quantityBefore,
		quantityChange,
		quantityAfter,
		TRIM(pReason)
	);

	COMMIT;
END$$

-- ==== Coupons ====

-- sp_create_coupon
CREATE PROCEDURE sp_create_coupon(
	IN pActingOperatorID INT,
	IN pCouponCode CHAR(12),
	IN pProductID INT,
	IN pRequiredQuantity INT,
	IN pDiscountKind VARCHAR(10),
	IN pDiscountValue DECIMAL(10,2),
	IN pDescription VARCHAR(120),
	IN pStartDate DATE,
	IN pEndDate DATE
)
BEGIN
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;
	DECLARE productPrice DECIMAL(10,2) DEFAULT NULL;
	DECLARE productUnit VARCHAR(10) DEFAULT NULL;
	DECLARE productIsActive TINYINT DEFAULT 0;
	DECLARE normalizedDescription VARCHAR(120) DEFAULT NULL;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		RESIGNAL;
	END;

	START TRANSACTION;

	SELECT Role, Active
	INTO actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	IF actingRole IS NULL OR actingActive <> 1 OR actingRole <> 'Administrator' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an active Administrator can create coupons';
	END IF;

	SELECT RetailPrice, UnitType, Active
	INTO productPrice, productUnit, productIsActive
	FROM product
	WHERE ProductID = pProductID;

	IF productPrice IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose a product for the coupon';
	END IF;

	IF productUnit <> 'Each' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Coupons are for items sold by the each, not by weight';
	END IF;

	IF pCouponCode IS NULL OR pCouponCode NOT REGEXP '^5[0-9]{11}$' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The coupon code must be 12 digits starting with 5';
	END IF;

	IF EXISTS (SELECT 1 FROM product WHERE UPC = pCouponCode OR PLUCode = pCouponCode) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That code belongs to a product';
	END IF;

	IF EXISTS (SELECT 1 FROM coupon WHERE CouponCode = pCouponCode) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'That coupon code is already used';
	END IF;

	IF pRequiredQuantity IS NULL OR pRequiredQuantity < 1 OR pRequiredQuantity > 99 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The required quantity must be from 1 to 99';
	END IF;

	IF pDiscountKind IS NULL OR pDiscountKind NOT IN ('Percent', 'Dollar') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose a percent or dollar coupon';
	END IF;

	IF pDiscountValue IS NULL OR pDiscountValue <= 0 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a coupon amount greater than zero';
	END IF;

	IF pDiscountKind = 'Percent' AND pDiscountValue > 100 THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'A percent coupon cannot be more than 100';
	END IF;

	IF pDiscountKind = 'Dollar' AND pDiscountValue > productPrice * pRequiredQuantity THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The coupon is worth more than the items it covers';
	END IF;

	IF pStartDate IS NOT NULL AND pEndDate IS NOT NULL AND pEndDate < pStartDate THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The end date cannot be before the start date';
	END IF;

	SET normalizedDescription = NULLIF(TRIM(pDescription), '');

	IF normalizedDescription IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Enter a description for the coupon';
	END IF;

	INSERT INTO coupon (
		CouponCode, Description, ProductID, RequiredQuantity,
		DiscountKind, DiscountValue, StartDate, EndDate
	)
	VALUES (
		pCouponCode, normalizedDescription, pProductID, pRequiredQuantity,
		pDiscountKind, pDiscountValue, pStartDate, pEndDate
	);

	COMMIT;

	SELECT LAST_INSERT_ID() AS CouponID;
END$$

-- sp_set_coupon_active
CREATE PROCEDURE sp_set_coupon_active(
	IN pActingOperatorID INT,
	IN pCouponID INT,
	IN pActive TINYINT
)
BEGIN
	DECLARE actingRole VARCHAR(20) DEFAULT NULL;
	DECLARE actingActive TINYINT DEFAULT 0;

	SELECT Role, Active
	INTO actingRole, actingActive
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	IF actingRole IS NULL OR actingActive <> 1 OR actingRole <> 'Administrator' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an active Administrator can change coupons';
	END IF;

	IF pActive NOT IN (0, 1) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Choose active or inactive';
	END IF;

	UPDATE coupon
	SET Active = pActive
	WHERE CouponID = pCouponID;

	IF ROW_COUNT() = 0 AND NOT EXISTS (SELECT 1 FROM coupon WHERE CouponID = pCouponID) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The coupon was not found';
	END IF;
END$$

DELIMITER ;