-- FnH Groceries
-- Database and Table Creation

USE `csc680-fnhgroceries_fnh_groceries`;

-- Drop existing tables in reverse foreign-key order

DROP TABLE IF EXISTS saleaccesslog;
DROP TABLE IF EXISTS salesreceiptcoupon;
DROP TABLE IF EXISTS coupon;
DROP TABLE IF EXISTS salesreceiptdiscount;
DROP TABLE IF EXISTS discountreason;
DROP TABLE IF EXISTS inventoryadjustment;
DROP TABLE IF EXISTS transactionjournal;
DROP TABLE IF EXISTS salesreceiptline;
DROP TABLE IF EXISTS expressorder;
DROP TABLE IF EXISTS salesreceipt;
DROP TABLE IF EXISTS storeinventory;
DROP TABLE IF EXISTS register;
DROP TABLE IF EXISTS operator;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS department;
DROP TABLE IF EXISTS customeraddress;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS category;
DROP TABLE IF EXISTS store;

-- Create tables in foreign-key dependency order

CREATE TABLE store (
	StoreID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	StoreNumber varchar(10) NOT NULL UNIQUE,
	StoreName varchar(100) NOT NULL,
	AddressLine1 varchar(120) NOT NULL,
	AddressLine2 varchar(120) DEFAULT NULL,
	City varchar(80) NOT NULL,
	StateCode char(2) NOT NULL,
	PostalCode varchar(10) NOT NULL,
	Phone varchar(20) DEFAULT NULL,
	OpenDate date DEFAULT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	INDEX ix_store_city (City, StateCode)
);

CREATE TABLE category (
	CategoryID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	CategoryName varchar(60) NOT NULL UNIQUE,
	Description varchar(255) DEFAULT NULL
);

CREATE TABLE customer (
	CustomerID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	LoyaltyNumber varchar(30) NOT NULL UNIQUE,
	FirstName varchar(60) NOT NULL,
	LastName varchar(60) NOT NULL,
	Email varchar(120) DEFAULT NULL,
	Phone varchar(20) DEFAULT NULL,
	JoinDate date NOT NULL,
	LoyaltyPoints int NOT NULL DEFAULT 0,
	Active tinyint(1) NOT NULL DEFAULT 1,
	INDEX ix_customer_name (LastName, FirstName),
	INDEX ix_customer_email (Email),
	CONSTRAINT ck_customer_points CHECK (LoyaltyPoints >= 0)
);

CREATE TABLE customeraddress (
	CustomerAddressID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	CustomerID int NOT NULL,
	AddressLabel varchar(40) NOT NULL,
	AddressLine1 varchar(120) NOT NULL,
	AddressLine2 varchar(120) DEFAULT NULL,
	City varchar(80) NOT NULL,
	StateCode char(2) NOT NULL,
	PostalCode varchar(10) NOT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (CustomerID, AddressLabel),
	INDEX ix_address_customer (CustomerID, Active),
	CONSTRAINT fk_address_customer FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID)
);

CREATE TABLE department (
	DepartmentID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	CategoryID int NOT NULL,
	DepartmentName varchar(80) NOT NULL,
	Description varchar(255) DEFAULT NULL,
	UNIQUE (CategoryID, DepartmentName),
	CONSTRAINT fk_dept_category FOREIGN KEY (CategoryID) REFERENCES category(CategoryID)
);

CREATE TABLE product (
	ProductID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	DepartmentID int NOT NULL,
	UPC varchar(20) DEFAULT NULL UNIQUE,
	PLUCode varchar(10) DEFAULT NULL UNIQUE,
	ProductName varchar(120) NOT NULL,
	Description varchar(500) DEFAULT NULL,
	UnitType enum('Each','Pound') NOT NULL DEFAULT 'Each',
	UnitCost decimal(10,2) DEFAULT NULL,
	RetailPrice decimal(10,2) NOT NULL,
	Taxable tinyint(1) NOT NULL DEFAULT 0,
	Active tinyint(1) NOT NULL DEFAULT 1,
	INDEX ix_product_dept (DepartmentID),
	INDEX ix_product_name (ProductName),
	INDEX ix_product_active (Active, ProductName),
	CONSTRAINT fk_product_dept FOREIGN KEY (DepartmentID) REFERENCES department(DepartmentID),
	CONSTRAINT ck_product_cost CHECK (UnitCost IS NULL OR UnitCost >= 0),
	CONSTRAINT ck_product_price CHECK (RetailPrice >= 0),
	CONSTRAINT ck_product_taxable CHECK (Taxable IN (0,1))
);

CREATE TABLE operator (
	OperatorID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	StoreID int NOT NULL,
	EmployeeNumber int DEFAULT NULL UNIQUE,
	Username varchar(50) NOT NULL UNIQUE,
	PasswordHash varchar(255) NOT NULL,
	FirstName varchar(60) NOT NULL,
	MiddleInitial char(1) DEFAULT NULL,
	LastName varchar(60) NOT NULL,
	Email varchar(120) NOT NULL UNIQUE,
	Phone char(10) DEFAULT NULL,
	Role enum('Pending','Administrator','Manager','Operator','Personal Shopper') NOT NULL DEFAULT 'Pending',
	HireDate date DEFAULT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_operator_name (LastName, FirstName),
	INDEX ix_operator_store (StoreID),
	CONSTRAINT fk_operator_store FOREIGN KEY (StoreID) REFERENCES store(StoreID),
	CONSTRAINT ck_operator_phone CHECK (Phone IS NULL OR Phone REGEXP '^[0-9]{10}$')
);

CREATE TABLE register (
	RegisterID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	StoreID int NOT NULL,
	RegisterNumber int NOT NULL,
	RegisterName varchar(50) DEFAULT NULL,
	RegisterType enum('Express','Regular') NOT NULL DEFAULT 'Regular',
	Active tinyint(1) NOT NULL DEFAULT 1,
	UNIQUE (StoreID, RegisterNumber),
	UNIQUE (StoreID, RegisterID),
	CONSTRAINT ck_register_express1 CHECK (
		RegisterNumber <> 1 OR (RegisterType = 'Express' AND Active = 1)
	),
	CONSTRAINT fk_register_store FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);

CREATE TABLE storeinventory (
	StoreID int NOT NULL,
	ProductID int NOT NULL,
	StockQuantity decimal(12,3) NOT NULL DEFAULT 0.000,
	Aisle varchar(20) DEFAULT NULL,
	SectionName varchar(50) DEFAULT NULL,
	ShelfLocation varchar(30) DEFAULT NULL,
	LastCountedAt datetime DEFAULT NULL,
	PRIMARY KEY (StoreID, ProductID),
	INDEX ix_stock_product (ProductID, StoreID),
	INDEX ix_stock_qty (StoreID, StockQuantity),
	CONSTRAINT fk_stock_product FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	CONSTRAINT fk_stock_store FOREIGN KEY (StoreID) REFERENCES store(StoreID),
	CONSTRAINT ck_stock_quantity CHECK (StockQuantity >= 0)
);

CREATE TABLE inventoryadjustment (
	AdjustmentID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	StoreID int NOT NULL,
	ProductID int NOT NULL,
	OperatorID int NOT NULL,
	AdjustmentType enum('Add','Remove','Set','New Product') NOT NULL,
	QuantityBefore decimal(12,3) NOT NULL,
	QuantityChange decimal(12,3) NOT NULL,
	QuantityAfter decimal(12,3) NOT NULL,
	Reason varchar(255) NOT NULL,
	AdjustedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_adjust_store (StoreID, AdjustedAt),
	INDEX ix_adjust_product (ProductID, AdjustedAt),
	INDEX ix_adjust_operator (OperatorID, AdjustedAt),
	CONSTRAINT fk_adjust_store FOREIGN KEY (StoreID) REFERENCES store(StoreID),
	CONSTRAINT fk_adjust_product FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	CONSTRAINT fk_adjust_operator FOREIGN KEY (OperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT ck_adjust_before CHECK (QuantityBefore >= 0),
	CONSTRAINT ck_adjust_after CHECK (QuantityAfter >= 0)
);

CREATE TABLE discountreason (
	ReasonID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReasonText varchar(255) NOT NULL UNIQUE,
	Active tinyint(1) NOT NULL DEFAULT 1,
	TimesUsed int NOT NULL DEFAULT 0,
	LastUsedAt datetime DEFAULT NULL,
	CreatedByOperatorID int DEFAULT NULL,
	CreatedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_reason_usage (Active, TimesUsed),
	CONSTRAINT fk_reason_operator FOREIGN KEY (CreatedByOperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT ck_reason_active CHECK (Active IN (0,1)),
	CONSTRAINT ck_reason_used CHECK (TimesUsed >= 0)
);

CREATE TABLE salesreceipt (
	ReceiptID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	TransactionNumber varchar(40) NOT NULL UNIQUE,
	StoreID int NOT NULL,
	RegisterID int NOT NULL,
	OperatorID int NOT NULL,
	CustomerID int DEFAULT NULL,
	SaleType enum('Regular','Express') NOT NULL DEFAULT 'Regular',
	TransactionDateTime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CheckoutDateTime datetime DEFAULT NULL,
	Status enum('Open','Paid','Completed','Voided','Refunded') NOT NULL DEFAULT 'Open',
	ReceiptDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	SubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TaxableSubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TaxAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	PostTaxDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	PaymentMethod enum('Cash','Charge','Credit','Debit','Gift Card','Other') NOT NULL DEFAULT 'Cash',
	AmountTendered decimal(10,2) DEFAULT NULL,
	ChangeDue decimal(10,2) DEFAULT NULL,
	INDEX ix_receipt_store (StoreID, TransactionDateTime),
	INDEX ix_receipt_register (RegisterID, TransactionDateTime),
	INDEX ix_receipt_operator (OperatorID, TransactionDateTime),
	INDEX ix_receipt_customer (CustomerID, TransactionDateTime),
	INDEX ix_receipt_open (StoreID, RegisterID, OperatorID, Status),
	INDEX ix_receipt_type (StoreID, SaleType, TransactionDateTime),
	CONSTRAINT fk_receipt_customer FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
	CONSTRAINT fk_receipt_operator FOREIGN KEY (OperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT fk_receipt_register FOREIGN KEY (StoreID, RegisterID) REFERENCES register(StoreID, RegisterID),
	CONSTRAINT fk_receipt_store FOREIGN KEY (StoreID) REFERENCES store(StoreID),
	CONSTRAINT ck_receipt_tax CHECK (TaxAmount >= 0),
	CONSTRAINT ck_receipt_tendered CHECK (AmountTendered IS NULL OR AmountTendered >= 0),
	CONSTRAINT ck_receipt_discount CHECK (ReceiptDiscountAmount >= 0),
	CONSTRAINT ck_receipt_subtotal CHECK (SubtotalAmount >= 0),
	CONSTRAINT ck_receipt_total CHECK (TotalAmount >= 0),
	CONSTRAINT ck_receipt_change CHECK (ChangeDue IS NULL OR ChangeDue >= 0),
	CONSTRAINT ck_receipt_taxable CHECK (TaxableSubtotalAmount >= 0),
	CONSTRAINT ck_receipt_posttax CHECK (PostTaxDiscountAmount >= 0)
);

-- Express fulfillment and delivery-fee rules are enforced by CHECK constraints,
-- sp_create_express_order, and sp_checkout_sale in 02_Views_and_Procedures.sql.

CREATE TABLE expressorder (
	ExpressOrderID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL UNIQUE,
	CustomerID int DEFAULT NULL,
	PersonalShopperID int NOT NULL,
	OrderPlacedDateTime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FulfillmentMethod enum('Curbside','Delivery') NOT NULL DEFAULT 'Curbside',
	DeliveryFee decimal(10,2) NOT NULL DEFAULT 0.00,
	DeliveryAddressLine1 varchar(120) DEFAULT NULL,
	DeliveryAddressLine2 varchar(120) DEFAULT NULL,
	DeliveryCity varchar(80) DEFAULT NULL,
	DeliveryStateCode char(2) DEFAULT NULL,
	DeliveryPostalCode varchar(10) DEFAULT NULL,
	Status enum('Received','Picking','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Received',
	CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_express_date (OrderPlacedDateTime, Status),
	INDEX ix_express_shopper (PersonalShopperID, Status),
	INDEX ix_express_customer (CustomerID, OrderPlacedDateTime),
	CONSTRAINT fk_express_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID),
	CONSTRAINT fk_express_customer FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
	CONSTRAINT fk_express_shopper FOREIGN KEY (PersonalShopperID) REFERENCES operator(OperatorID),
	CONSTRAINT ck_express_fee CHECK (DeliveryFee IN (0.00, 10.00)),
	CONSTRAINT ck_express_method CHECK (
		(
			FulfillmentMethod = 'Curbside'
			AND DeliveryFee = 0.00
			AND DeliveryAddressLine1 IS NULL
			AND DeliveryAddressLine2 IS NULL
			AND DeliveryCity IS NULL
			AND DeliveryStateCode IS NULL
			AND DeliveryPostalCode IS NULL
		)
		OR
		(
			FulfillmentMethod = 'Delivery'
			AND DeliveryFee = 10.00
			AND DeliveryAddressLine1 IS NOT NULL
			AND DeliveryCity IS NOT NULL
			AND DeliveryStateCode IS NOT NULL
			AND DeliveryPostalCode IS NOT NULL
		)
	)
);

CREATE TABLE salesreceiptline (
	ReceiptLineID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL,
	LineNumber int NOT NULL,
	ProductID int NOT NULL,
	ProductNameAtSale varchar(120) NOT NULL,
	UnitTypeAtSale enum('Each','Pound') NOT NULL,
	TaxableAtSale tinyint(1) NOT NULL,
	Quantity decimal(12,3) NOT NULL,
	UnitPrice decimal(10,2) NOT NULL,
	LineDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	UNIQUE (ReceiptID, LineNumber),
	INDEX ix_line_product (ProductID, ReceiptID),
	CONSTRAINT fk_line_product FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	CONSTRAINT fk_line_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID) ON DELETE CASCADE,
	CONSTRAINT ck_line_quantity CHECK (Quantity > 0),
	CONSTRAINT ck_line_price CHECK (UnitPrice >= 0),
	CONSTRAINT ck_line_discount CHECK (
		LineDiscountAmount >= 0
		AND LineDiscountAmount <= ROUND(Quantity * UnitPrice, 2)
	),
	CONSTRAINT ck_line_taxable CHECK (TaxableAtSale IN (0,1))
);

CREATE TABLE salesreceiptdiscount (
	DiscountID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL,
	DiscountNumber int NOT NULL,
	DiscountKind enum('Percent','Dollar') NOT NULL,
	TaxTiming enum('Before Tax','After Tax') NOT NULL,
	DiscountValue decimal(10,2) NOT NULL,
	Reason varchar(255) NOT NULL,
	AppliedAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	AppliedByOperatorID int NOT NULL,
	AppliedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (ReceiptID, DiscountNumber),
	INDEX ix_disc_operator (AppliedByOperatorID),
	CONSTRAINT fk_disc_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID) ON DELETE CASCADE,
	CONSTRAINT fk_disc_operator FOREIGN KEY (AppliedByOperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT ck_disc_value CHECK (DiscountValue > 0),
	CONSTRAINT ck_disc_percent CHECK (DiscountKind <> 'Percent' OR DiscountValue <= 100),
	CONSTRAINT ck_disc_applied CHECK (AppliedAmount >= 0)
);

CREATE TABLE coupon (
	CouponID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	CouponCode char(12) NOT NULL UNIQUE,
	Description varchar(120) NOT NULL,
	ProductID int NOT NULL,
	RequiredQuantity int NOT NULL DEFAULT 1,
	DiscountKind enum('Percent','Dollar') NOT NULL,
	DiscountValue decimal(10,2) NOT NULL,
	StartDate date DEFAULT NULL,
	EndDate date DEFAULT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	CreatedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_coupon_product (ProductID),
	CONSTRAINT fk_coupon_product FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	CONSTRAINT ck_coupon_code CHECK (CouponCode REGEXP '^5[0-9]{11}$'),
	CONSTRAINT ck_coupon_qty CHECK (RequiredQuantity BETWEEN 1 AND 99),
	CONSTRAINT ck_coupon_value CHECK (DiscountValue > 0),
	CONSTRAINT ck_coupon_percent CHECK (DiscountKind <> 'Percent' OR DiscountValue <= 100),
	CONSTRAINT ck_coupon_dates CHECK (StartDate IS NULL OR EndDate IS NULL OR EndDate >= StartDate),
	CONSTRAINT ck_coupon_active CHECK (Active IN (0,1))
);

CREATE TABLE salesreceiptcoupon (
	SaleCouponID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL,
	CouponID int NOT NULL,
	CouponNumber int NOT NULL,
	UnitsCovered int NOT NULL DEFAULT 0,
	AppliedAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	AppliedByOperatorID int NOT NULL,
	AppliedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (ReceiptID, CouponID),
	UNIQUE (ReceiptID, CouponNumber),
	INDEX ix_cpn_coupon (CouponID),
	INDEX ix_cpn_operator (AppliedByOperatorID),
	CONSTRAINT fk_cpn_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID) ON DELETE CASCADE,
	CONSTRAINT fk_cpn_coupon FOREIGN KEY (CouponID) REFERENCES coupon(CouponID),
	CONSTRAINT fk_cpn_operator FOREIGN KEY (AppliedByOperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT ck_cpn_units CHECK (UnitsCovered >= 0),
	CONSTRAINT ck_cpn_amount CHECK (AppliedAmount >= 0)
);

CREATE TABLE saleaccesslog (
	AccessID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL,
	ActingOperatorID int NOT NULL,
	AccessMode enum('View','Assist','Take Over','Hand Back') NOT NULL,
	PreviousOperatorID int DEFAULT NULL,
	NewOperatorID int DEFAULT NULL,
	AccessedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX ix_access_receipt (ReceiptID, AccessedAt),
	INDEX ix_access_operator (ActingOperatorID),
	CONSTRAINT fk_access_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID) ON DELETE CASCADE,
	CONSTRAINT fk_access_operator FOREIGN KEY (ActingOperatorID) REFERENCES operator(OperatorID)
);

CREATE TABLE transactionjournal (
	JournalID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL UNIQUE,
	TransactionNumber varchar(40) NOT NULL UNIQUE,
	StoreID int NOT NULL,
	RegisterID int NOT NULL,
	OpenedByOperatorID int NOT NULL,
	ClosedByOperatorID int DEFAULT NULL,
	OpenedDateTime datetime NOT NULL,
	ClosedDateTime datetime DEFAULT NULL,
	Status enum('Open','Paid','Cancelled','Cleared') NOT NULL DEFAULT 'Open',
	LineCount int unsigned NOT NULL DEFAULT 0,
	ItemQuantity decimal(12,3) NOT NULL DEFAULT 0.000,
	SubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	DiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TaxableSubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TaxAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	PostTaxDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	TotalAmount decimal(10,2) NOT NULL DEFAULT 0.00,
	PaymentMethod varchar(20) DEFAULT NULL,
	AmountTendered decimal(10,2) DEFAULT NULL,
	ChangeDue decimal(10,2) DEFAULT NULL,
	INDEX ix_journal_store (StoreID, OpenedDateTime),
	INDEX ix_journal_reg_status (StoreID, RegisterID, Status),
	INDEX ix_journal_opened (OpenedByOperatorID),
	INDEX ix_journal_closed (ClosedByOperatorID),
	INDEX ix_journal_register (RegisterID),
	CONSTRAINT fk_journal_closed_by FOREIGN KEY (ClosedByOperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT fk_journal_opened_by FOREIGN KEY (OpenedByOperatorID) REFERENCES operator(OperatorID),
	CONSTRAINT fk_journal_receipt FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID),
	CONSTRAINT fk_journal_register FOREIGN KEY (RegisterID) REFERENCES register(RegisterID),
	CONSTRAINT fk_journal_store FOREIGN KEY (StoreID) REFERENCES store(StoreID),
	CONSTRAINT ck_journal_lines CHECK (LineCount >= 0),
	CONSTRAINT ck_journal_quantity CHECK (ItemQuantity >= 0),
	CONSTRAINT ck_journal_subtotal CHECK (SubtotalAmount >= 0),
	CONSTRAINT ck_journal_discount CHECK (DiscountAmount >= 0),
	CONSTRAINT ck_journal_taxable CHECK (TaxableSubtotalAmount >= 0),
	CONSTRAINT ck_journal_tax CHECK (TaxAmount >= 0),
	CONSTRAINT ck_journal_posttax CHECK (PostTaxDiscountAmount >= 0),
	CONSTRAINT ck_journal_total CHECK (TotalAmount >= 0)
);