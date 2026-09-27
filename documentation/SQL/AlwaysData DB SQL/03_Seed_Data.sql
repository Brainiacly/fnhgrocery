-- FnH Groceries
-- Seed Data

USE `csc680-fnhgroceries_fnh_groceries`;

START TRANSACTION;

-- Insert data in foreign-key dependency order

-- store
INSERT INTO store (
	StoreID,
	StoreNumber,
	StoreName,
	AddressLine1,
	AddressLine2,
	City,
	StateCode,
	PostalCode,
	Phone,
	OpenDate,
	Active
)
VALUES
	(1,'001','FnH Groceries - Central','100 Fresh Way',NULL,'Los Angeles','CA','90001','555-0100','2020-01-01',1);

-- category
INSERT INTO category (
	CategoryID,
	CategoryName,
	Description
)
VALUES
	(1,'Food','Food and grocery products'),
	(2,'Household','Household and general merchandise');

-- customer
INSERT INTO customer (
	CustomerID,
	LoyaltyNumber,
	FirstName,
	LastName,
	Email,
	Phone,
	JoinDate,
	LoyaltyPoints,
	Active
)
VALUES
	(1,'FNH10001','Jamie','Customer','jamie@example.com','555-0111','2026-01-15',125,1);

-- department
INSERT INTO department (
	DepartmentID,
	CategoryID,
	DepartmentName,
	Description
)
VALUES
	(1,1,'Produce','Fresh fruits and vegetables'),
	(2,1,'Dairy','Milk, cheese, yogurt, and refrigerated dairy'),
	(3,1,'Bakery','Bread and baked goods'),
	(4,1,'Grocery','Packaged grocery products'),
	(5,2,'Household','Paper goods and household supplies');

-- product
INSERT INTO product (
	ProductID,
	DepartmentID,
	UPC,
	PLUCode,
	ProductName,
	Description,
	UnitType,
	UnitCost,
	RetailPrice,
	Taxable,
	Active
)
VALUES
	(1,1,NULL,'4011','Bananas','Fresh bananas sold by weight','Pound',0.39,0.69,0,1),
	(2,1,NULL,'4133','Gala Apples','Gala apples sold by weight','Pound',0.89,1.49,0,1),
	(3,2,'100000000003',NULL,'Whole Milk - 1 Gallon','One gallon whole milk','Each',3.10,4.29,0,1),
	(4,3,'100000000004',NULL,'Wheat Bread','Fresh wheat sandwich bread','Each',2.10,3.49,0,1),
	(5,4,'100000000005',NULL,'Brown Rice - 2 lb','Two-pound bag of brown rice','Each',3.25,4.99,0,1),
	(6,1,NULL,'4065','Green Bell Pepper','Fresh green bell pepper','Each',0.55,0.99,0,1),
	(7,2,'100000000007',NULL,'Large Eggs - Dozen','One dozen large eggs','Each',2.65,3.99,0,1),
	(8,2,'100000000008',NULL,'Cheddar Cheese','Eight ounce cheddar cheese','Each',2.45,3.79,0,1),
	(9,3,'100000000009',NULL,'French Bread','Fresh baked French bread','Each',1.75,2.99,0,1),
	(10,4,'100000000010',NULL,'Peanut Butter','Creamy peanut butter','Each',2.80,4.49,0,1),
	(11,4,'100000000011',NULL,'Cereal','Whole grain breakfast cereal','Each',3.20,5.29,0,1),
	(12,4,'100000000012',NULL,'Out of Stock Test Item','Product used to demonstrate out-of-stock protection','Each',1.00,1.99,0,1),
	(13,5,'100000000013',NULL,'Paper Towels','Two-roll paper towel package','Each',2.40,4.49,1,1);

-- operator
INSERT INTO operator (
	OperatorID,
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
	Active,
	CreatedAt
)
VALUES
	(1,1,1001,'Admin','$2y$10$F7mugA8rBswpCEcij3wF/OUi0SJZGeBfCLBHylJejv1w2aOCg/4nq','Admin','I','Strator','administrator@fnh.local',NULL,'Administrator','2026-01-01',1,'2026-09-14 13:58:29'),
	(2,1,1002,'CheesyChuck','$2y$10$0QDKUmKEZcdg5SlyBu7Eg.uyo7rQ6j.wRtSOuIXHS1X.tpXJ.e8hu','Chuck','E','Cheeesy','Chuck@email.com','5555555555','Administrator','2026-09-15',1,'2026-09-15 23:40:14'),
	(3,1,1003,'TestBob','$2y$10$3yCZLi/0e56UqSFjXaJlHOpr1rOMIgkJA5bx7cLMxw6aYt1Y8vcMu','Bob','D','Tester','Bob@email.com','5555555555','Operator','2026-09-20',1,'2026-09-20 20:50:13'),
	(4,1,1004,'TestAlice','$2y$10$6JMwioT7uTv3fzv6DJKBfOHkQWRL/PEW3b2N.opaZFcQoDpq691Vq','Alice','S','Restaurant','Alice@email.com','5555555555','Operator','2026-09-19',1,'2026-09-20 20:52:33');

-- register
INSERT INTO register (
	RegisterID,
	StoreID,
	RegisterNumber,
	RegisterName,
	Active
)
VALUES
	(1,1,1,'Front Register 1',1),
	(2,1,2,'Front Register 2',1),
	(3,1,3,'Express Register',1);

-- storeinventory
INSERT INTO storeinventory (
	StoreID,
	ProductID,
	StockQuantity,
	Aisle,
	SectionName,
	ShelfLocation,
	LastCountedAt
)
VALUES
	(1,1,80.000,NULL,'Produce','Banana Table','2026-09-14 06:00:00'),
	(1,2,55.000,NULL,'Produce','Apple Table','2026-09-14 06:00:00'),
	(1,3,24.000,NULL,'Dairy','Cooler 2-B','2026-09-14 06:00:00'),
	(1,4,30.000,NULL,'Bakery','Shelf 1','2026-09-14 06:00:00'),
	(1,5,20.000,'4','Rice and Grains','Shelf B3','2026-09-14 06:00:00'),
	(1,6,35.000,NULL,'Produce','Pepper Table','2026-09-21 04:06:24'),
	(1,7,18.000,NULL,'Dairy','Cooler 3-A','2026-09-21 04:06:24'),
	(1,8,15.000,NULL,'Dairy','Cooler 3-B','2026-09-21 04:06:24'),
	(1,9,20.000,NULL,'Bakery','Bread Rack 2','2026-09-21 04:06:24'),
	(1,10,25.000,'5','Spreads','Shelf A2','2026-09-21 04:06:24'),
	(1,11,22.000,'6','Breakfast','Shelf C1','2026-09-21 04:06:24'),
	(1,12,0.000,'6','Test Products','Shelf C4','2026-09-21 04:06:24'),
	(1,13,16.000,'7','Paper Goods','Shelf A1','2026-09-21 12:01:28');

-- salesreceipt
INSERT INTO salesreceipt (
	ReceiptID,
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
VALUES
	(1,'S001-20260914-000001',1,1,1,1,'2026-09-14 10:15:32','2026-09-14 10:15:32','Completed',1.00,9.51,0.00,0.00,8.51,'Cash',10.00,1.49),
	(2,'S001-20260921040636-0001',1,1,1,NULL,'2026-09-21 04:06:36','2026-09-21 09:41:59','Voided',0.00,6.48,0.00,0.00,0.00,'Cash',NULL,NULL),
	(3,'S001-20260921040918-0004',1,3,4,NULL,'2026-09-21 04:09:18','2026-09-21 04:10:33','Voided',0.00,17.76,0.00,0.00,0.00,'Cash',NULL,NULL),
	(4,'S001-20260921094205-0001',1,1,1,NULL,'2026-09-21 09:42:05','2026-09-21 09:43:26','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(5,'S001-20260921094659-0001',1,1,1,NULL,'2026-09-21 09:46:59','2026-09-21 10:03:47','Voided',0.00,27.24,0.00,0.00,0.00,'Cash',NULL,NULL),
	(6,'S001-20260921113344-0001',1,1,1,NULL,'2026-09-21 11:33:44','2026-09-21 14:08:52','Voided',0.00,19.75,0.00,0.00,0.00,'Cash',NULL,NULL),
	(7,'S001-20260921140852776581-0001',1,1,1,NULL,'2026-09-21 14:08:52','2026-09-21 14:08:57','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(8,'S001-20260921140857502640-0001',1,1,1,NULL,'2026-09-21 14:08:57','2026-09-21 14:09:05','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(9,'S001-20260921140912525214-0001',1,1,1,NULL,'2026-09-21 14:09:12','2026-09-21 14:10:21','Voided',0.00,3.79,0.00,0.00,0.00,'Cash',NULL,NULL),
	(10,'S001-20260921141021876972-0001',1,1,1,NULL,'2026-09-21 14:10:21','2026-09-21 14:10:28','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(11,'S001-20260921141032050072-0001',1,1,1,NULL,'2026-09-21 14:10:32','2026-09-26 12:32:29','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(12,'S001-20260921141213649186-0004',1,2,4,NULL,'2026-09-21 14:12:13','2026-09-21 17:41:52','Voided',0.00,18.95,0.00,0.00,0.00,'Cash',NULL,NULL),
	(13,'S001-20260921174152601657-0004',1,2,4,NULL,'2026-09-21 17:41:52','2026-09-21 18:09:58','Voided',0.00,2.99,0.00,0.00,0.00,'Cash',NULL,NULL),
	(14,'S001-20260926123127781052-0004',1,2,4,NULL,'2026-09-26 12:31:27','2026-09-26 12:31:48','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(15,'S001-20260926123148342569-0004',1,2,4,NULL,'2026-09-26 12:31:48','2026-09-26 23:02:51','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(16,'S001-20260926222253997853-0003',1,1,3,NULL,'2026-09-26 22:22:53','2026-09-26 22:22:59','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(17,'S001-20260926222305891530-0003',1,1,3,NULL,'2026-09-26 22:23:05','2026-09-26 22:25:24','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(18,'S001-20260926222524688002-0003',1,1,3,NULL,'2026-09-26 22:25:24','2026-09-26 22:25:26','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(19,'S001-20260926223311458757-0003',1,1,3,NULL,'2026-09-26 22:33:11','2026-09-26 22:33:19','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(20,'S001-20260926223319235871-0003',1,1,3,NULL,'2026-09-26 22:33:19','2026-09-26 22:33:23','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(21,'S001-20260926223330271937-0003',1,1,3,NULL,'2026-09-26 22:33:30','2026-09-26 22:33:39','Voided',0.00,7.28,0.00,0.00,0.00,'Cash',NULL,NULL),
	(22,'S001-20260926223339247213-0003',1,1,3,NULL,'2026-09-26 22:33:39','2026-09-26 22:34:20','Voided',0.00,7.28,0.00,0.00,0.00,'Cash',NULL,NULL),
	(23,'S001-20260926225905518779-0003',1,1,3,NULL,'2026-09-26 22:59:05','2026-09-26 23:02:58','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(24,'S001-20260927000651722068-0001',1,1,1,NULL,'2026-09-27 00:06:51','2026-09-27 00:09:35','Voided',0.00,8.08,0.00,0.00,0.00,'Cash',NULL,NULL),
	(25,'S001-20260927000935481704-0001',1,1,1,NULL,'2026-09-27 00:09:35','2026-09-27 00:18:29','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
	(26,'S001-20260927001833771986-0001',1,1,1,NULL,'2026-09-27 00:18:33','2026-09-27 00:59:59','Voided',0.00,25.43,0.00,0.00,0.00,'Cash',NULL,NULL);

-- salesreceiptline
INSERT INTO salesreceiptline (
	ReceiptLineID,
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
VALUES
	(1,1,1,1,'Bananas','Pound',0,2.500,0.69,0.00),
	(2,1,2,3,'Whole Milk - 1 Gallon','Each',0,1.000,4.29,0.00),
	(3,1,3,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(4,2,1,9,'French Bread','Each',0,1.000,2.99,0.00),
	(5,2,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(6,3,1,10,'Peanut Butter','Each',0,1.000,4.49,0.00),
	(7,3,2,7,'Large Eggs - Dozen','Each',0,2.000,3.99,0.00),
	(8,3,3,11,'Cereal','Each',0,1.000,5.29,0.00),
	(9,5,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
	(10,5,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(11,5,3,5,'Brown Rice - 2 lb','Each',0,4.000,4.99,0.00),
	(12,6,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
	(13,6,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(14,6,3,5,'Brown Rice - 2 lb','Each',0,1.000,4.99,0.00),
	(15,6,4,9,'French Bread','Each',0,1.000,2.99,0.00),
	(16,6,5,10,'Peanut Butter','Each',0,1.000,4.49,0.00),
	(17,9,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
	(19,12,1,4,'Wheat Bread','Each',0,2.000,3.49,0.00),
	(20,12,2,5,'Brown Rice - 2 lb','Each',0,1.000,4.99,0.00),
	(21,12,3,7,'Large Eggs - Dozen','Each',0,1.000,3.99,0.00),
	(22,12,4,9,'French Bread','Each',0,1.000,2.99,0.00),
	(23,13,1,9,'French Bread','Each',0,1.000,2.99,0.00),
	(24,21,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
	(25,21,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(26,22,1,9,'French Bread','Each',0,1.000,2.99,0.00),
	(27,22,2,3,'Whole Milk - 1 Gallon','Each',0,1.000,4.29,0.00),
	(29,24,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
	(30,24,2,3,'Whole Milk - 1 Gallon','Each',0,1.000,4.29,0.00),
	(32,26,1,9,'French Bread','Each',0,1.000,2.99,0.00),
	(33,26,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00),
	(34,26,3,8,'Cheddar Cheese','Each',0,5.000,3.79,0.00);

-- transactionjournal
INSERT INTO transactionjournal (
	JournalID,
	ReceiptID,
	TransactionNumber,
	StoreID,
	RegisterID,
	OpenedByOperatorID,
	ClosedByOperatorID,
	OpenedDateTime,
	ClosedDateTime,
	Status,
	LineCount,
	ItemQuantity,
	SubtotalAmount,
	DiscountAmount,
	TaxableSubtotalAmount,
	TaxAmount,
	TotalAmount,
	PaymentMethod,
	AmountTendered,
	ChangeDue
)
VALUES
	(1,1,'S001-20260914-000001',1,1,1,1,'2026-09-14 10:15:32','2026-09-14 10:15:32','Paid',3,4.500,9.51,1.00,0.00,0.00,8.51,'Cash',10.00,1.49),
	(2,2,'S001-20260921040636-0001',1,1,1,1,'2026-09-21 04:06:36','2026-09-21 09:41:59','Cancelled',2,2.000,6.48,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(3,3,'S001-20260921040918-0004',1,3,4,4,'2026-09-21 04:09:18','2026-09-21 04:10:33','Cancelled',3,4.000,17.76,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(4,4,'S001-20260921094205-0001',1,1,1,1,'2026-09-21 09:42:05','2026-09-21 09:43:26','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(5,5,'S001-20260921094659-0001',1,1,1,1,'2026-09-21 09:46:59','2026-09-21 10:03:47','Cancelled',3,6.000,27.24,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(6,6,'S001-20260921113344-0001',1,1,1,1,'2026-09-21 11:33:44','2026-09-21 14:08:52','Cancelled',5,5.000,19.75,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(8,7,'S001-20260921140852776581-0001',1,1,1,1,'2026-09-21 14:08:52','2026-09-21 14:08:57','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(9,8,'S001-20260921140857502640-0001',1,1,1,1,'2026-09-21 14:08:57','2026-09-21 14:09:05','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(10,9,'S001-20260921140912525214-0001',1,1,1,1,'2026-09-21 14:09:12','2026-09-21 14:10:21','Cancelled',1,1.000,3.79,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(11,10,'S001-20260921141021876972-0001',1,1,1,1,'2026-09-21 14:10:21','2026-09-21 14:10:28','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(12,11,'S001-20260921141032050072-0001',1,1,1,1,'2026-09-21 14:10:32','2026-09-26 12:32:29','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(13,12,'S001-20260921141213649186-0004',1,2,4,4,'2026-09-21 14:12:13','2026-09-21 17:41:52','Cancelled',4,5.000,18.95,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(14,13,'S001-20260921174152601657-0004',1,2,4,4,'2026-09-21 17:41:52','2026-09-21 18:09:58','Cancelled',1,1.000,2.99,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(15,14,'S001-20260926123127781052-0004',1,2,4,4,'2026-09-26 12:31:27','2026-09-26 12:31:48','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(16,15,'S001-20260926123148342569-0004',1,2,4,1,'2026-09-26 12:31:48','2026-09-26 23:02:51','Cleared',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(17,16,'S001-20260926222253997853-0003',1,1,3,3,'2026-09-26 22:22:53','2026-09-26 22:22:59','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(18,17,'S001-20260926222305891530-0003',1,1,3,3,'2026-09-26 22:23:05','2026-09-26 22:25:24','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(19,18,'S001-20260926222524688002-0003',1,1,3,3,'2026-09-26 22:25:24','2026-09-26 22:25:26','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(20,19,'S001-20260926223311458757-0003',1,1,3,3,'2026-09-26 22:33:11','2026-09-26 22:33:19','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(21,20,'S001-20260926223319235871-0003',1,1,3,3,'2026-09-26 22:33:19','2026-09-26 22:33:23','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(22,21,'S001-20260926223330271937-0003',1,1,3,3,'2026-09-26 22:33:30','2026-09-26 22:33:39','Cancelled',2,2.000,7.28,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(23,22,'S001-20260926223339247213-0003',1,1,3,3,'2026-09-26 22:33:39','2026-09-26 22:34:20','Cancelled',2,2.000,7.28,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(24,23,'S001-20260926225905518779-0003',1,1,3,1,'2026-09-26 22:59:05','2026-09-26 23:02:58','Cleared',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(25,24,'S001-20260927000651722068-0001',1,1,1,1,'2026-09-27 00:06:51','2026-09-27 00:09:35','Cancelled',2,2.000,8.08,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(26,25,'S001-20260927000935481704-0001',1,1,1,1,'2026-09-27 00:09:35','2026-09-27 00:18:29','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
	(27,26,'S001-20260927001833771986-0001',1,1,1,1,'2026-09-27 00:18:33','2026-09-27 00:59:59','Cancelled',3,7.000,25.43,0.00,0.00,0.00,0.00,NULL,NULL,NULL);

COMMIT;