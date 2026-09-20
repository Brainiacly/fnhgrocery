-- FnH Groceries table creation
-- Run first in the empty alwaysdata database
-- Order protects all foreign key dependencies

-- Stores FnH store locations
CREATE TABLE `store` (
  `StoreID` INT NOT NULL AUTO_INCREMENT,
  `StoreNumber` VARCHAR(10) NOT NULL,
  `StoreName` VARCHAR(100) NOT NULL,
  `AddressLine1` VARCHAR(120) NOT NULL,
  `AddressLine2` VARCHAR(120) DEFAULT NULL,
  `City` VARCHAR(80) NOT NULL,
  `StateCode` CHAR(2) NOT NULL,
  `PostalCode` VARCHAR(10) NOT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `OpenDate` DATE DEFAULT NULL,
  `Active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`StoreID`),
  UNIQUE KEY `uq_store_number` (`StoreNumber`),
  INDEX `idx_store_location` (`City`,`StateCode`)
);

-- Groups related departments
CREATE TABLE `category` (
  `CategoryID` INT NOT NULL AUTO_INCREMENT,
  `CategoryName` VARCHAR(60) NOT NULL,
  `Description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `uq_category_name` (`CategoryName`)
);

-- Stores loyalty customers
CREATE TABLE `customer` (
  `CustomerID` INT NOT NULL AUTO_INCREMENT,
  `LoyaltyNumber` VARCHAR(30) NOT NULL,
  `FirstName` VARCHAR(60) NOT NULL,
  `LastName` VARCHAR(60) NOT NULL,
  `Email` VARCHAR(120) DEFAULT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `JoinDate` DATE NOT NULL,
  `LoyaltyPoints` INT NOT NULL DEFAULT 0,
  `Active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `uq_customer_loyalty_number` (`LoyaltyNumber`),
  INDEX `idx_customer_name` (`LastName`,`FirstName`),
  INDEX `idx_customer_email` (`Email`),
  CONSTRAINT `chk_customer_loyalty_points` CHECK (`LoyaltyPoints` >= 0)
);

-- Stores checkout registers for each store
CREATE TABLE `register` (
  `RegisterID` INT NOT NULL AUTO_INCREMENT,
  `StoreID` INT NOT NULL,
  `RegisterNumber` INT NOT NULL,
  `RegisterName` VARCHAR(50) DEFAULT NULL,
  `Active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`RegisterID`),
  UNIQUE KEY `uq_register_store_number` (`StoreID`,`RegisterNumber`),
  UNIQUE KEY `uq_register_store_id` (`StoreID`,`RegisterID`),
  CONSTRAINT `fk_register_store` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
);

-- Stores grocery departments
CREATE TABLE `department` (
  `DepartmentID` INT NOT NULL AUTO_INCREMENT,
  `CategoryID` INT NOT NULL,
  `DepartmentName` VARCHAR(80) NOT NULL,
  `Description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `uq_department_category_name` (`CategoryID`,`DepartmentName`),
  CONSTRAINT `fk_department_category` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`)
);

-- Stores employee login and access information
CREATE TABLE `operator` (
  `OperatorID` INT NOT NULL AUTO_INCREMENT,
  `StoreID` INT NOT NULL,
  `EmployeeNumber` INT DEFAULT NULL,
  `Username` VARCHAR(50) NOT NULL,
  `PasswordHash` VARCHAR(255) NOT NULL,
  `FirstName` VARCHAR(60) NOT NULL,
  `MiddleInitial` CHAR(1) DEFAULT NULL,
  `LastName` VARCHAR(60) NOT NULL,
  `Email` VARCHAR(120) NOT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `Role` ENUM('Pending','Administrator','Operator') NOT NULL DEFAULT 'Pending',
  `HireDate` DATE DEFAULT NULL,
  `Active` TINYINT NOT NULL DEFAULT 1,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`OperatorID`),
  UNIQUE KEY `uq_operator_username` (`Username`),
  UNIQUE KEY `uq_operator_email` (`Email`),
  UNIQUE KEY `uq_operator_employee_number` (`EmployeeNumber`),
  INDEX `idx_operator_name` (`LastName`,`FirstName`),
  INDEX `idx_operator_store` (`StoreID`),
  CONSTRAINT `fk_operator_store` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
);

-- Stores products and pricing
CREATE TABLE `product` (
  `ProductID` INT NOT NULL AUTO_INCREMENT,
  `DepartmentID` INT NOT NULL,
  `UPC` VARCHAR(20) DEFAULT NULL,
  `PLUCode` VARCHAR(10) DEFAULT NULL,
  `ProductName` VARCHAR(120) NOT NULL,
  `Description` VARCHAR(500) DEFAULT NULL,
  `UnitType` ENUM('Each','Pound') NOT NULL DEFAULT 'Each',
  `UnitCost` DECIMAL(10,2) DEFAULT NULL,
  `RetailPrice` DECIMAL(10,2) NOT NULL,
  `Active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `uq_product_upc` (`UPC`),
  UNIQUE KEY `uq_product_plu` (`PLUCode`),
  INDEX `idx_product_department` (`DepartmentID`),
  INDEX `idx_product_name` (`ProductName`),
  CONSTRAINT `fk_product_department` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`),
  CONSTRAINT `chk_product_cost` CHECK (`UnitCost` IS NULL or `UnitCost` >= 0),
  CONSTRAINT `chk_product_retail_price` CHECK (`RetailPrice` >= 0)
);

-- Stores sale headers
CREATE TABLE `salesreceipt` (
  `ReceiptID` BIGINT NOT NULL AUTO_INCREMENT,
  `TransactionNumber` VARCHAR(40) NOT NULL,
  `StoreID` INT NOT NULL,
  `RegisterID` INT NOT NULL,
  `OperatorID` INT NOT NULL,
  `CustomerID` INT DEFAULT NULL,
  `TransactionDateTime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` ENUM('Completed','Voided','Refunded') NOT NULL DEFAULT 'Completed',
  `ReceiptDiscountAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `PaymentMethod` ENUM('Cash','Credit','Debit','Gift Card','Other') NOT NULL DEFAULT 'Cash',
  `AmountTendered` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `uq_sales_receipt_transaction_number` (`TransactionNumber`),
  INDEX `fk_sales_receipt_register_store` (`StoreID`,`RegisterID`),
  INDEX `idx_sales_receipt_store_date` (`StoreID`,`TransactionDateTime`),
  INDEX `idx_sales_receipt_register_date` (`RegisterID`,`TransactionDateTime`),
  INDEX `idx_sales_receipt_operator_date` (`OperatorID`,`TransactionDateTime`),
  INDEX `idx_sales_receipt_customer_date` (`CustomerID`,`TransactionDateTime`),
  CONSTRAINT `fk_sales_receipt_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  CONSTRAINT `fk_sales_receipt_operator` FOREIGN KEY (`OperatorID`) REFERENCES `operator` (`OperatorID`),
  CONSTRAINT `fk_sales_receipt_register_store` FOREIGN KEY (`StoreID`, `RegisterID`) REFERENCES `register` (`StoreID`, `RegisterID`),
  CONSTRAINT `fk_sales_receipt_store` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`),
  CONSTRAINT `chk_sales_receipt_tax` CHECK (`TaxAmount` >= 0),
  CONSTRAINT `chk_sales_receipt_tendered` CHECK (`AmountTendered` IS NULL or `AmountTendered` >= 0),
  CONSTRAINT `chk_sales_receipt_discount` CHECK (`ReceiptDiscountAmount` >= 0)
);

-- Stores receipt line items
CREATE TABLE `salesreceiptline` (
  `ReceiptLineID` BIGINT NOT NULL AUTO_INCREMENT,
  `ReceiptID` BIGINT NOT NULL,
  `LineNumber` INT NOT NULL,
  `ProductID` INT NOT NULL,
  `Quantity` DECIMAL(12,3) NOT NULL,
  `UnitPrice` DECIMAL(10,2) NOT NULL,
  `LineDiscountAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`ReceiptLineID`),
  UNIQUE KEY `uq_sales_receipt_line_number` (`ReceiptID`,`LineNumber`),
  INDEX `idx_sales_receipt_line_product` (`ProductID`,`ReceiptID`),
  CONSTRAINT `fk_sales_receipt_line_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `fk_sales_receipt_line_receipt` FOREIGN KEY (`ReceiptID`) REFERENCES `salesreceipt` (`ReceiptID`) ON DELETE CASCADE,
  CONSTRAINT `chk_sales_receipt_line_quantity` CHECK (`Quantity` > 0),
  CONSTRAINT `chk_sales_receipt_line_price` CHECK (`UnitPrice` >= 0),
  CONSTRAINT `chk_sales_receipt_line_discount` CHECK (`LineDiscountAmount` >= 0 AND `LineDiscountAmount` <= round(`Quantity` * `UnitPrice`,2))
);

-- Stores product quantities by store
CREATE TABLE `storeinventory` (
  `StoreID` INT NOT NULL,
  `ProductID` INT NOT NULL,
  `StockQuantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `Aisle` VARCHAR(20) DEFAULT NULL,
  `SectionName` VARCHAR(50) DEFAULT NULL,
  `ShelfLocation` VARCHAR(30) DEFAULT NULL,
  `LastCountedAt` DATETIME DEFAULT NULL,
  PRIMARY KEY (`StoreID`,`ProductID`),
  INDEX `idx_store_inventory_product` (`ProductID`,`StoreID`),
  CONSTRAINT `fk_store_inventory_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `fk_store_inventory_store` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`),
  CONSTRAINT `chk_store_inventory_quantity` CHECK (`StockQuantity` >= 0)
);
