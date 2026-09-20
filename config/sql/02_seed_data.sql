-- FnH Groceries seed data
-- Run after 01_create_tables.sql
-- Parent data is inserted before dependent data

-- Seed store locations
INSERT INTO `store` (`StoreID`, `StoreNumber`, `StoreName`, `AddressLine1`, `AddressLine2`, `City`, `StateCode`, `PostalCode`, `Phone`, `OpenDate`, `Active`)
VALUES
    (1,'001','FnH Groceries - Central','100 Fresh Way',NULL,'Los Angeles','CA','90001','555-0100','2020-01-01',1);

-- Seed product categories
INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`)
VALUES
    (1,'Food','Food and grocery products');

-- Seed loyalty customers
INSERT INTO `customer` (`CustomerID`, `LoyaltyNumber`, `FirstName`, `LastName`, `Email`, `Phone`, `JoinDate`, `LoyaltyPoints`, `Active`)
VALUES
    (1,'FNH10001','Jamie','Customer','jamie@example.com','555-0111','2026-01-15',125,1);

-- Seed checkout registers
INSERT INTO `register` (`RegisterID`, `StoreID`, `RegisterNumber`, `RegisterName`, `Active`)
VALUES
    (1,1,1,'Front Register 1',1),
    (2,1,2,'Front Register 2',1),
    (3,1,3,'Express Register',1);

-- Seed grocery departments
INSERT INTO `department` (`DepartmentID`, `CategoryID`, `DepartmentName`, `Description`)
VALUES
    (1,1,'Produce','Fresh fruits and vegetables'),
    (2,1,'Dairy','Milk, cheese, yogurt, and refrigerated dairy'),
    (3,1,'Bakery','Bread and baked goods'),
    (4,1,'Grocery','Packaged grocery products');

-- Seed operator accounts
INSERT INTO `operator` (`OperatorID`, `StoreID`, `EmployeeNumber`, `Username`, `PasswordHash`, `FirstName`, `MiddleInitial`, `LastName`, `Email`, `Phone`, `Role`, `HireDate`, `Active`, `CreatedAt`)
VALUES
    (1,1,1001,'Admin','$2y$10$F7mugA8rBswpCEcij3wF/OUi0SJZGeBfCLBHylJejv1w2aOCg/4nq','Admin','I','Strator','administrator@fnh.local',NULL,'Administrator','2026-01-01',1,'2026-09-14 13:58:29'),
    (2,1,1002,'CheesyChuck','$2y$10$0QDKUmKEZcdg5SlyBu7Eg.uyo7rQ6j.wRtSOuIXHS1X.tpXJ.e8hu','Chuck','E','Cheeesy','Chuck@email.com','555-555-5555','Administrator','2026-09-15',0,'2026-09-15 23:40:14'),
    (3,1,1003,'TestBob','$2y$10$3yCZLi/0e56UqSFjXaJlHOpr1rOMIgkJA5bx7cLMxw6aYt1Y8vcMu','Bob','D','Tester','Bob@email.com','5555555555','Operator','2026-09-20',1,'2026-09-20 20:50:13'),
    (4,1,1004,'TestAlice','$2y$10$6JMwioT7uTv3fzv6DJKBfOHkQWRL/PEW3b2N.opaZFcQoDpq691Vq','Alice','S','Restaurant','Alice@email.com','5555555555','Operator','2026-09-19',1,'2026-09-20 20:52:33');

-- Seed products
INSERT INTO `product` (`ProductID`, `DepartmentID`, `UPC`, `PLUCode`, `ProductName`, `Description`, `UnitType`, `UnitCost`, `RetailPrice`, `Active`)
VALUES
    (1,1,NULL,'4011','Bananas','Fresh bananas sold by weight','Pound',0.39,0.69,1),
    (2,1,NULL,'4133','Gala Apples','Gala apples sold by weight','Pound',0.89,1.49,1),
    (3,2,'100000000003',NULL,'Whole Milk - 1 Gallon','One gallon whole milk','Each',3.10,4.29,1),
    (4,3,'100000000004',NULL,'Wheat Bread','Fresh wheat sandwich bread','Each',2.10,3.49,1),
    (5,4,'100000000005',NULL,'Brown Rice - 2 lb','Two-pound bag of brown rice','Each',3.25,4.99,1);

-- Seed sample sales receipt
INSERT INTO `salesreceipt` (`ReceiptID`, `TransactionNumber`, `StoreID`, `RegisterID`, `OperatorID`, `CustomerID`, `TransactionDateTime`, `Status`, `ReceiptDiscountAmount`, `TaxAmount`, `PaymentMethod`, `AmountTendered`)
VALUES
    (1,'S001-20260914-000001',1,1,1,1,'2026-09-14 10:15:32','Completed',1.00,0.00,'Cash',10.00);

-- Seed sample receipt lines
INSERT INTO `salesreceiptline` (`ReceiptLineID`, `ReceiptID`, `LineNumber`, `ProductID`, `Quantity`, `UnitPrice`, `LineDiscountAmount`)
VALUES
    (1,1,1,1,2.500,0.69,0.00),
    (2,1,2,3,1.000,4.29,0.00),
    (3,1,3,4,1.000,3.49,0.00);

-- Seed store inventory
INSERT INTO `storeinventory` (`StoreID`, `ProductID`, `StockQuantity`, `Aisle`, `SectionName`, `ShelfLocation`, `LastCountedAt`)
VALUES
    (1,1,80.000,NULL,'Produce','Banana Table','2026-09-14 06:00:00'),
    (1,2,55.000,NULL,'Produce','Apple Table','2026-09-14 06:00:00'),
    (1,3,24.000,NULL,'Dairy','Cooler 2-B','2026-09-14 06:00:00'),
    (1,4,30.000,NULL,'Bakery','Shelf 1','2026-09-14 06:00:00'),
    (1,5,20.000,'4','Rice and Grains','Shelf B3','2026-09-14 06:00:00');
