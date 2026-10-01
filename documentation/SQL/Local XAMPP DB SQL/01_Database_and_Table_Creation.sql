-- FnH Groceries
-- Database and Table Creation

CREATE DATABASE IF NOT EXISTS fnh_groceries;

USE fnh_groceries;

-- Drop existing tables in reverse foreign-key order

DROP TABLE IF EXISTS transactionjournal;
DROP TABLE IF EXISTS salesreceiptline;
DROP TABLE IF EXISTS salesreceipt;
DROP TABLE IF EXISTS storeinventory;
DROP TABLE IF EXISTS register;
DROP TABLE IF EXISTS operator;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS department;
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
	INDEX idx_store_location (City, StateCode)
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
	LoyaltyPoints int NOT NULL DEFAULT 0 CHECK (LoyaltyPoints >= 0),
	Active tinyint(1) NOT NULL DEFAULT 1,
	INDEX idx_customer_name (LastName, FirstName),
	INDEX idx_customer_email (Email)
);

CREATE TABLE department (
	DepartmentID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	CategoryID int NOT NULL,
	DepartmentName varchar(80) NOT NULL,
	Description varchar(255) DEFAULT NULL,
	UNIQUE (CategoryID, DepartmentName),
	FOREIGN KEY (CategoryID) REFERENCES category(CategoryID)
);

CREATE TABLE product (
	ProductID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	DepartmentID int NOT NULL,
	UPC varchar(20) DEFAULT NULL UNIQUE,
	PLUCode varchar(10) DEFAULT NULL UNIQUE,
	ProductName varchar(120) NOT NULL,
	Description varchar(500) DEFAULT NULL,
	UnitType enum('Each','Pound') NOT NULL DEFAULT 'Each',
	UnitCost decimal(10,2) DEFAULT NULL CHECK (UnitCost IS NULL OR UnitCost >= 0),
	RetailPrice decimal(10,2) NOT NULL CHECK (RetailPrice >= 0),
	Taxable tinyint(1) NOT NULL DEFAULT 0 CHECK (Taxable IN (0,1)),
	Active tinyint(1) NOT NULL DEFAULT 1,
	INDEX idx_product_department (DepartmentID),
	INDEX idx_product_name (ProductName),
	INDEX idx_product_active_name (Active, ProductName),
	FOREIGN KEY (DepartmentID) REFERENCES department(DepartmentID)
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
	Phone char(10) DEFAULT NULL CHECK (Phone IS NULL OR Phone REGEXP '^[0-9]{10}$'),
	Role enum('Pending','Administrator','Operator','Personal Shopper') NOT NULL DEFAULT 'Pending',
	HireDate date DEFAULT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_operator_name (LastName, FirstName),
	INDEX idx_operator_store (StoreID),
	FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);

CREATE TABLE register (
	RegisterID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	StoreID int NOT NULL,
	RegisterNumber int NOT NULL,
	RegisterName varchar(50) DEFAULT NULL,
	Active tinyint(1) NOT NULL DEFAULT 1,
	UNIQUE (StoreID, RegisterNumber),
	UNIQUE (StoreID, RegisterID),
	FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);

CREATE TABLE storeinventory (
	StoreID int NOT NULL,
	ProductID int NOT NULL,
	StockQuantity decimal(12,3) NOT NULL DEFAULT 0.000 CHECK (StockQuantity >= 0),
	Aisle varchar(20) DEFAULT NULL,
	SectionName varchar(50) DEFAULT NULL,
	ShelfLocation varchar(30) DEFAULT NULL,
	LastCountedAt datetime DEFAULT NULL,
	PRIMARY KEY (StoreID, ProductID),
	INDEX idx_store_inventory_product (ProductID, StoreID),
	INDEX idx_store_inventory_stock (StoreID, StockQuantity),
	FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);

CREATE TABLE salesreceipt (
	ReceiptID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	TransactionNumber varchar(40) NOT NULL UNIQUE,
	StoreID int NOT NULL,
	RegisterID int NOT NULL,
	OperatorID int NOT NULL,
	CustomerID int DEFAULT NULL,
	TransactionDateTime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CheckoutDateTime datetime DEFAULT NULL,
	Status enum('Open','Paid','Completed','Voided','Refunded') NOT NULL DEFAULT 'Open',
	ReceiptDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (ReceiptDiscountAmount >= 0),
	SubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (SubtotalAmount >= 0),
	TaxableSubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TaxableSubtotalAmount >= 0),
	TaxAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TaxAmount >= 0),
	TotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TotalAmount >= 0),
	PaymentMethod enum('Cash','Credit','Debit','Gift Card','Other') NOT NULL DEFAULT 'Cash',
	AmountTendered decimal(10,2) DEFAULT NULL CHECK (AmountTendered IS NULL OR AmountTendered >= 0),
	ChangeDue decimal(10,2) DEFAULT NULL CHECK (ChangeDue IS NULL OR ChangeDue >= 0),
	SaleType enum('Regular','Express') NOT NULL DEFAULT 'Regular',
	INDEX idx_sales_receipt_store_date (StoreID, TransactionDateTime),
	INDEX idx_sales_receipt_register_date (RegisterID, TransactionDateTime),
	INDEX idx_sales_receipt_operator_date (OperatorID, TransactionDateTime),
	INDEX idx_sales_receipt_customer_date (CustomerID, TransactionDateTime),
	INDEX idx_sales_receipt_open_sale (StoreID, RegisterID, OperatorID, Status),
	INDEX idx_sales_receipt_type_date (StoreID, SaleType, TransactionDateTime),
	FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
	FOREIGN KEY (OperatorID) REFERENCES operator(OperatorID),
	FOREIGN KEY (StoreID, RegisterID) REFERENCES register(StoreID, RegisterID),
	FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);

CREATE TABLE expressorder (
	ExpressOrderID bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ReceiptID bigint NOT NULL UNIQUE,
	CustomerID int DEFAULT NULL,
	PersonalShopperID int NOT NULL,
	OrderPlacedDateTime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FulfillmentMethod enum('Curbside','Delivery') NOT NULL DEFAULT 'Curbside',
	DeliveryFee decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (DeliveryFee IN (0.00, 10.00)),
	DeliveryAddressLine1 varchar(120) DEFAULT NULL,
	DeliveryAddressLine2 varchar(120) DEFAULT NULL,
	DeliveryCity varchar(80) DEFAULT NULL,
	DeliveryStateCode char(2) DEFAULT NULL,
	DeliveryPostalCode varchar(10) DEFAULT NULL,
	Status enum('Received','Picking','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Received',
	CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_express_order_date_status (OrderPlacedDateTime, Status),
	INDEX idx_express_order_shopper (PersonalShopperID, Status),
	INDEX idx_express_order_customer (CustomerID, OrderPlacedDateTime),
	FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID),
	FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID),
	FOREIGN KEY (PersonalShopperID) REFERENCES operator(OperatorID),
	CONSTRAINT chk_express_order_fulfillment CHECK (
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
	TaxableAtSale tinyint(1) NOT NULL CHECK (TaxableAtSale IN (0,1)),
	Quantity decimal(12,3) NOT NULL CHECK (Quantity > 0),
	UnitPrice decimal(10,2) NOT NULL CHECK (UnitPrice >= 0),
	LineDiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (LineDiscountAmount >= 0 AND LineDiscountAmount <= ROUND(Quantity * UnitPrice, 2)),
	UNIQUE (ReceiptID, LineNumber),
	INDEX idx_sales_receipt_line_product (ProductID, ReceiptID),
	FOREIGN KEY (ProductID) REFERENCES product(ProductID),
	FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID) ON DELETE CASCADE
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
	LineCount int unsigned NOT NULL DEFAULT 0 CHECK (LineCount >= 0),
	ItemQuantity decimal(12,3) NOT NULL DEFAULT 0.000 CHECK (ItemQuantity >= 0),
	SubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (SubtotalAmount >= 0),
	DiscountAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (DiscountAmount >= 0),
	TaxableSubtotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TaxableSubtotalAmount >= 0),
	TaxAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TaxAmount >= 0),
	TotalAmount decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (TotalAmount >= 0),
	PaymentMethod varchar(20) DEFAULT NULL,
	AmountTendered decimal(10,2) DEFAULT NULL,
	ChangeDue decimal(10,2) DEFAULT NULL,
	INDEX idx_transactionjournal_store_date (StoreID, OpenedDateTime),
	INDEX idx_transactionjournal_register (StoreID, RegisterID, Status),
	INDEX idx_transactionjournal_opened_by (OpenedByOperatorID),
	INDEX idx_transactionjournal_closed_by (ClosedByOperatorID),
	INDEX idx_transactionjournal_register_id (RegisterID),
	FOREIGN KEY (ClosedByOperatorID) REFERENCES operator(OperatorID),
	FOREIGN KEY (OpenedByOperatorID) REFERENCES operator(OperatorID),
	FOREIGN KEY (ReceiptID) REFERENCES salesreceipt(ReceiptID),
	FOREIGN KEY (RegisterID) REFERENCES register(RegisterID),
	FOREIGN KEY (StoreID) REFERENCES store(StoreID)
);