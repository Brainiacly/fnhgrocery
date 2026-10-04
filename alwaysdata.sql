/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: mysql-csc680-fnhgroceries.alwaysdata.net    Database: csc680-fnhgroceries_fnh_groceries
-- ------------------------------------------------------
-- Server version	11.4.13-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
SET net_read_timeout=600;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(60) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `CategoryName` (`CategoryName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES
(1,'Food','Food and grocery products'),
(2,'Household','Household and general merchandise');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer` (
  `CustomerID` int(11) NOT NULL AUTO_INCREMENT,
  `LoyaltyNumber` varchar(30) NOT NULL,
  `FirstName` varchar(60) NOT NULL,
  `LastName` varchar(60) NOT NULL,
  `Email` varchar(120) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `JoinDate` date NOT NULL,
  `LoyaltyPoints` int(11) NOT NULL DEFAULT 0 CHECK (`LoyaltyPoints` >= 0),
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `LoyaltyNumber` (`LoyaltyNumber`),
  KEY `idx_customer_name` (`LastName`,`FirstName`),
  KEY `idx_customer_email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
INSERT INTO `customer` VALUES
(1,'FNH10001','Jamie','Customer','jamie@example.com','555-0111','2026-01-15',125,1);
/*!40000 ALTER TABLE `customer` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customeraddress`
--

DROP TABLE IF EXISTS `customeraddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customeraddress` (
  `CustomerAddressID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) NOT NULL,
  `AddressLabel` varchar(40) NOT NULL,
  `AddressLine1` varchar(120) NOT NULL,
  `AddressLine2` varchar(120) DEFAULT NULL,
  `City` varchar(80) NOT NULL,
  `StateCode` char(2) NOT NULL,
  `PostalCode` varchar(10) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`CustomerAddressID`),
  UNIQUE KEY `uq_customer_address_label` (`CustomerID`,`AddressLabel`),
  KEY `idx_customer_address_customer` (`CustomerID`,`Active`),
  CONSTRAINT `fk_customer_address_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customeraddress`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customeraddress` WRITE;
/*!40000 ALTER TABLE `customeraddress` DISABLE KEYS */;
/*!40000 ALTER TABLE `customeraddress` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `department` (
  `DepartmentID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryID` int(11) NOT NULL,
  `DepartmentName` varchar(80) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`DepartmentID`),
  UNIQUE KEY `CategoryID` (`CategoryID`,`DepartmentName`),
  CONSTRAINT `department_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `department` WRITE;
/*!40000 ALTER TABLE `department` DISABLE KEYS */;
INSERT INTO `department` VALUES
(1,1,'Produce','Fresh fruits and vegetables'),
(2,1,'Dairy','Milk, cheese, yogurt, and refrigerated dairy'),
(3,1,'Bakery','Bread and baked goods'),
(4,1,'Grocery','Packaged grocery products'),
(5,2,'Household','Paper goods and household supplies');
/*!40000 ALTER TABLE `department` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `expressorder`
--

DROP TABLE IF EXISTS `expressorder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expressorder` (
  `ExpressOrderID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ReceiptID` bigint(20) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `PersonalShopperID` int(11) NOT NULL,
  `OrderPlacedDateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `FulfillmentMethod` enum('Curbside','Delivery') NOT NULL DEFAULT 'Curbside',
  `DeliveryFee` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`DeliveryFee` in (0.00,10.00)),
  `DeliveryAddressLine1` varchar(120) DEFAULT NULL,
  `DeliveryAddressLine2` varchar(120) DEFAULT NULL,
  `DeliveryCity` varchar(80) DEFAULT NULL,
  `DeliveryStateCode` char(2) DEFAULT NULL,
  `DeliveryPostalCode` varchar(10) DEFAULT NULL,
  `Status` enum('Received','Picking','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Received',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ExpressOrderID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`),
  KEY `idx_express_order_date_status` (`OrderPlacedDateTime`,`Status`),
  KEY `idx_express_order_shopper` (`PersonalShopperID`,`Status`),
  KEY `idx_express_order_customer` (`CustomerID`,`OrderPlacedDateTime`),
  CONSTRAINT `expressorder_ibfk_1` FOREIGN KEY (`ReceiptID`) REFERENCES `salesreceipt` (`ReceiptID`),
  CONSTRAINT `expressorder_ibfk_2` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  CONSTRAINT `expressorder_ibfk_3` FOREIGN KEY (`PersonalShopperID`) REFERENCES `operator` (`OperatorID`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`FulfillmentMethod` = 'Curbside' and `DeliveryFee` = 0.00 and `DeliveryAddressLine1` is null and `DeliveryAddressLine2` is null and `DeliveryCity` is null and `DeliveryStateCode` is null and `DeliveryPostalCode` is null or `FulfillmentMethod` = 'Delivery' and `DeliveryFee` = 10.00 and `DeliveryAddressLine1` is not null and `DeliveryCity` is not null and `DeliveryStateCode` is not null and `DeliveryPostalCode` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expressorder`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `expressorder` WRITE;
/*!40000 ALTER TABLE `expressorder` DISABLE KEYS */;
/*!40000 ALTER TABLE `expressorder` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `operator`
--

DROP TABLE IF EXISTS `operator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operator` (
  `OperatorID` int(11) NOT NULL AUTO_INCREMENT,
  `StoreID` int(11) NOT NULL,
  `EmployeeNumber` int(11) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FirstName` varchar(60) NOT NULL,
  `MiddleInitial` char(1) DEFAULT NULL,
  `LastName` varchar(60) NOT NULL,
  `Email` varchar(120) NOT NULL,
  `Phone` char(10) DEFAULT NULL CHECK (`Phone` is null or `Phone` regexp '^[0-9]{10}$'),
  `Role` enum('Pending','Administrator','Operator','Personal Shopper') NOT NULL DEFAULT 'Pending',
  `HireDate` date DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`OperatorID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `EmployeeNumber` (`EmployeeNumber`),
  KEY `idx_operator_name` (`LastName`,`FirstName`),
  KEY `idx_operator_store` (`StoreID`),
  CONSTRAINT `operator_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operator`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `operator` WRITE;
/*!40000 ALTER TABLE `operator` DISABLE KEYS */;
INSERT INTO `operator` VALUES
(1,1,1001,'Admin','$2y$10$F7mugA8rBswpCEcij3wF/OUi0SJZGeBfCLBHylJejv1w2aOCg/4nq','Admin','I','Strator','administrator@fnh.local',NULL,'Administrator','2026-01-01',1,'2026-09-14 11:58:29'),
(2,1,1002,'CheesyChuck','$2y$10$0QDKUmKEZcdg5SlyBu7Eg.uyo7rQ6j.wRtSOuIXHS1X.tpXJ.e8hu','Chuck','E','Cheeesy','Chuck@email.com','5555555555','Administrator','2026-09-15',0,'2026-09-15 21:40:14'),
(3,1,1003,'TestBob','$2y$10$3yCZLi/0e56UqSFjXaJlHOpr1rOMIgkJA5bx7cLMxw6aYt1Y8vcMu','Bob','D','Tester','Bob@email.com','5555555555','Operator','2026-09-20',1,'2026-09-20 18:50:13'),
(4,1,1004,'TestAlice','$2y$10$6JMwioT7uTv3fzv6DJKBfOHkQWRL/PEW3b2N.opaZFcQoDpq691Vq','Alice','S','Restaurant','Alice@email.com','5555555555','Operator','2026-09-19',1,'2026-09-20 18:52:33'),
(5,1,1005,'DellaVery','$2y$10$q6HX6B1.qWb5qO37Nm9XiO4unHBiRZthRiLg4D2UhqZjmqjmsQyuW','Della',NULL,'Very','della.very@fnh.local',NULL,'Personal Shopper','2026-10-01',1,'2026-10-01 06:00:00');
/*!40000 ALTER TABLE `operator` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL AUTO_INCREMENT,
  `DepartmentID` int(11) NOT NULL,
  `UPC` varchar(20) DEFAULT NULL,
  `PLUCode` varchar(10) DEFAULT NULL,
  `ProductName` varchar(120) NOT NULL,
  `Description` varchar(500) DEFAULT NULL,
  `UnitType` enum('Each','Pound') NOT NULL DEFAULT 'Each',
  `UnitCost` decimal(10,2) DEFAULT NULL CHECK (`UnitCost` is null or `UnitCost` >= 0),
  `RetailPrice` decimal(10,2) NOT NULL CHECK (`RetailPrice` >= 0),
  `Taxable` tinyint(1) NOT NULL DEFAULT 0 CHECK (`Taxable` in (0,1)),
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `UPC` (`UPC`),
  UNIQUE KEY `PLUCode` (`PLUCode`),
  KEY `idx_product_department` (`DepartmentID`),
  KEY `idx_product_name` (`ProductName`),
  KEY `idx_product_active_name` (`Active`,`ProductName`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES
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
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `register`
--

DROP TABLE IF EXISTS `register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `register` (
  `RegisterID` int(11) NOT NULL AUTO_INCREMENT,
  `StoreID` int(11) NOT NULL,
  `RegisterNumber` int(11) NOT NULL,
  `RegisterName` varchar(50) DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`RegisterID`),
  UNIQUE KEY `StoreID` (`StoreID`,`RegisterNumber`),
  UNIQUE KEY `StoreID_2` (`StoreID`,`RegisterID`),
  CONSTRAINT `register_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `register`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `register` WRITE;
/*!40000 ALTER TABLE `register` DISABLE KEYS */;
INSERT INTO `register` VALUES
(1,1,1,'Express Register',1),
(2,1,2,'Front Register 2',1),
(3,1,3,'Front Register 3',1);
/*!40000 ALTER TABLE `register` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `salesreceipt`
--

DROP TABLE IF EXISTS `salesreceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesreceipt` (
  `ReceiptID` bigint(20) NOT NULL AUTO_INCREMENT,
  `TransactionNumber` varchar(40) NOT NULL,
  `StoreID` int(11) NOT NULL,
  `RegisterID` int(11) NOT NULL,
  `OperatorID` int(11) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `SaleType` enum('Regular','Express') NOT NULL DEFAULT 'Regular',
  `TransactionDateTime` datetime NOT NULL DEFAULT current_timestamp(),
  `CheckoutDateTime` datetime DEFAULT NULL,
  `Status` enum('Open','Paid','Completed','Voided','Refunded') NOT NULL DEFAULT 'Open',
  `ReceiptDiscountAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`ReceiptDiscountAmount` >= 0),
  `SubtotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`SubtotalAmount` >= 0),
  `TaxableSubtotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TaxableSubtotalAmount` >= 0),
  `TaxAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TaxAmount` >= 0),
  `TotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TotalAmount` >= 0),
  `PaymentMethod` enum('Cash','Credit','Debit','Gift Card','Other') NOT NULL DEFAULT 'Cash',
  `AmountTendered` decimal(10,2) DEFAULT NULL CHECK (`AmountTendered` is null or `AmountTendered` >= 0),
  `ChangeDue` decimal(10,2) DEFAULT NULL CHECK (`ChangeDue` is null or `ChangeDue` >= 0),
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `TransactionNumber` (`TransactionNumber`),
  KEY `idx_sales_receipt_store_date` (`StoreID`,`TransactionDateTime`),
  KEY `idx_sales_receipt_register_date` (`RegisterID`,`TransactionDateTime`),
  KEY `idx_sales_receipt_operator_date` (`OperatorID`,`TransactionDateTime`),
  KEY `idx_sales_receipt_customer_date` (`CustomerID`,`TransactionDateTime`),
  KEY `idx_sales_receipt_open_sale` (`StoreID`,`RegisterID`,`OperatorID`,`Status`),
  KEY `idx_sales_receipt_type_date` (`StoreID`,`SaleType`,`TransactionDateTime`),
  CONSTRAINT `salesreceipt_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  CONSTRAINT `salesreceipt_ibfk_2` FOREIGN KEY (`OperatorID`) REFERENCES `operator` (`OperatorID`),
  CONSTRAINT `salesreceipt_ibfk_3` FOREIGN KEY (`StoreID`, `RegisterID`) REFERENCES `register` (`StoreID`, `RegisterID`),
  CONSTRAINT `salesreceipt_ibfk_4` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesreceipt`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `salesreceipt` WRITE;
/*!40000 ALTER TABLE `salesreceipt` DISABLE KEYS */;
INSERT INTO `salesreceipt` VALUES
(1,'S001-20260914-000001',1,2,1,1,'Regular','2026-09-14 10:15:32','2026-09-14 10:15:32','Completed',1.00,9.51,0.00,0.00,8.51,'Cash',10.00,1.49),
(2,'S001-20260921040636-0001',1,2,1,NULL,'Regular','2026-09-21 04:06:36','2026-09-21 09:41:59','Voided',0.00,6.48,0.00,0.00,0.00,'Cash',NULL,NULL),
(3,'S001-20260921040918-0004',1,3,4,NULL,'Regular','2026-09-21 04:09:18','2026-09-21 04:10:33','Voided',0.00,17.76,0.00,0.00,0.00,'Cash',NULL,NULL),
(4,'S001-20260921094205-0001',1,2,1,NULL,'Regular','2026-09-21 09:42:05','2026-09-21 09:43:26','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(5,'S001-20260921094659-0001',1,2,1,NULL,'Regular','2026-09-21 09:46:59','2026-09-21 10:03:47','Voided',0.00,27.24,0.00,0.00,0.00,'Cash',NULL,NULL),
(6,'S001-20260921113344-0001',1,2,1,NULL,'Regular','2026-09-21 11:33:44','2026-09-21 14:08:52','Voided',0.00,19.75,0.00,0.00,0.00,'Cash',NULL,NULL),
(7,'S001-20260921140852776581-0001',1,2,1,NULL,'Regular','2026-09-21 14:08:52','2026-09-21 14:08:57','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(8,'S001-20260921140857502640-0001',1,2,1,NULL,'Regular','2026-09-21 14:08:57','2026-09-21 14:09:05','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(9,'S001-20260921140912525214-0001',1,2,1,NULL,'Regular','2026-09-21 14:09:12','2026-09-21 14:10:21','Voided',0.00,3.79,0.00,0.00,0.00,'Cash',NULL,NULL),
(10,'S001-20260921141021876972-0001',1,2,1,NULL,'Regular','2026-09-21 14:10:21','2026-09-21 14:10:28','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(11,'S001-20260921141032050072-0001',1,2,1,NULL,'Regular','2026-09-21 14:10:32','2026-09-26 12:32:29','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(12,'S001-20260921141213649186-0004',1,2,4,NULL,'Regular','2026-09-21 14:12:13','2026-09-21 17:41:52','Voided',0.00,18.95,0.00,0.00,0.00,'Cash',NULL,NULL),
(13,'S001-20260921174152601657-0004',1,2,4,NULL,'Regular','2026-09-21 17:41:52','2026-09-21 18:09:58','Voided',0.00,2.99,0.00,0.00,0.00,'Cash',NULL,NULL),
(14,'S001-20260926123127781052-0004',1,2,4,NULL,'Regular','2026-09-26 12:31:27','2026-09-26 12:31:48','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(15,'S001-20260926123148342569-0004',1,2,4,NULL,'Regular','2026-09-26 12:31:48','2026-09-26 23:02:51','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(16,'S001-20260926222253997853-0003',1,2,3,NULL,'Regular','2026-09-26 22:22:53','2026-09-26 22:22:59','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(17,'S001-20260926222305891530-0003',1,2,3,NULL,'Regular','2026-09-26 22:23:05','2026-09-26 22:25:24','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(18,'S001-20260926222524688002-0003',1,2,3,NULL,'Regular','2026-09-26 22:25:24','2026-09-26 22:25:26','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(19,'S001-20260926223311458757-0003',1,2,3,NULL,'Regular','2026-09-26 22:33:11','2026-09-26 22:33:19','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(20,'S001-20260926223319235871-0003',1,2,3,NULL,'Regular','2026-09-26 22:33:19','2026-09-26 22:33:23','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(21,'S001-20260926223330271937-0003',1,2,3,NULL,'Regular','2026-09-26 22:33:30','2026-09-26 22:33:39','Voided',0.00,7.28,0.00,0.00,0.00,'Cash',NULL,NULL),
(22,'S001-20260926223339247213-0003',1,2,3,NULL,'Regular','2026-09-26 22:33:39','2026-09-26 22:34:20','Voided',0.00,7.28,0.00,0.00,0.00,'Cash',NULL,NULL),
(23,'S001-20260926225905518779-0003',1,2,3,NULL,'Regular','2026-09-26 22:59:05','2026-09-26 23:02:58','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(24,'S001-20260927000651722068-0001',1,2,1,NULL,'Regular','2026-09-27 00:06:51','2026-09-27 00:09:35','Voided',0.00,8.08,0.00,0.00,0.00,'Cash',NULL,NULL),
(25,'S001-20260927000935481704-0001',1,2,1,NULL,'Regular','2026-09-27 00:09:35','2026-09-27 00:18:29','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(26,'S001-20260927001833771986-0001',1,2,1,NULL,'Regular','2026-09-27 00:18:33','2026-09-27 00:59:59','Voided',0.00,25.43,0.00,0.00,0.00,'Cash',NULL,NULL),
(27,'S001-20260927224458743002-0004',1,2,4,NULL,'Regular','2026-09-27 22:44:58','2026-09-28 00:54:36','Voided',0.00,2.99,0.00,0.00,0.00,'Cash',NULL,NULL),
(28,'S001-20260928003029958537-0001',1,2,1,NULL,'Regular','2026-09-28 00:30:29','2026-09-28 00:30:35','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(29,'S001-20260928005436249382-0004',1,2,4,NULL,'Regular','2026-09-28 00:54:36','2026-09-28 00:54:43','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL),
(30,'S001-20261004021258000907-0001',1,2,1,NULL,'Regular','2026-10-04 02:12:58','2026-10-04 02:15:01','Voided',0.00,7.28,0.00,0.00,0.00,'Cash',NULL,NULL),
(31,'S001-20261004021501653293-0001',1,2,1,NULL,'Regular','2026-10-04 02:15:01','2026-10-04 02:15:05','Voided',0.00,0.00,0.00,0.00,0.00,'Cash',NULL,NULL);
/*!40000 ALTER TABLE `salesreceipt` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `salesreceiptline`
--

DROP TABLE IF EXISTS `salesreceiptline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `salesreceiptline` (
  `ReceiptLineID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ReceiptID` bigint(20) NOT NULL,
  `LineNumber` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `ProductNameAtSale` varchar(120) NOT NULL,
  `UnitTypeAtSale` enum('Each','Pound') NOT NULL,
  `TaxableAtSale` tinyint(1) NOT NULL CHECK (`TaxableAtSale` in (0,1)),
  `Quantity` decimal(12,3) NOT NULL CHECK (`Quantity` > 0),
  `UnitPrice` decimal(10,2) NOT NULL CHECK (`UnitPrice` >= 0),
  `LineDiscountAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`LineDiscountAmount` >= 0 and `LineDiscountAmount` <= round(`Quantity` * `UnitPrice`,2)),
  PRIMARY KEY (`ReceiptLineID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`,`LineNumber`),
  KEY `idx_sales_receipt_line_product` (`ProductID`,`ReceiptID`),
  CONSTRAINT `salesreceiptline_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `salesreceiptline_ibfk_2` FOREIGN KEY (`ReceiptID`) REFERENCES `salesreceipt` (`ReceiptID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salesreceiptline`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `salesreceiptline` WRITE;
/*!40000 ALTER TABLE `salesreceiptline` DISABLE KEYS */;
INSERT INTO `salesreceiptline` VALUES
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
(34,26,3,8,'Cheddar Cheese','Each',0,5.000,3.79,0.00),
(35,27,1,9,'French Bread','Each',0,1.000,2.99,0.00),
(36,30,1,8,'Cheddar Cheese','Each',0,1.000,3.79,0.00),
(37,30,2,4,'Wheat Bread','Each',0,1.000,3.49,0.00);
/*!40000 ALTER TABLE `salesreceiptline` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `store`
--

DROP TABLE IF EXISTS `store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store` (
  `StoreID` int(11) NOT NULL AUTO_INCREMENT,
  `StoreNumber` varchar(10) NOT NULL,
  `StoreName` varchar(100) NOT NULL,
  `AddressLine1` varchar(120) NOT NULL,
  `AddressLine2` varchar(120) DEFAULT NULL,
  `City` varchar(80) NOT NULL,
  `StateCode` char(2) NOT NULL,
  `PostalCode` varchar(10) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `OpenDate` date DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`StoreID`),
  UNIQUE KEY `StoreNumber` (`StoreNumber`),
  KEY `idx_store_location` (`City`,`StateCode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `store` WRITE;
/*!40000 ALTER TABLE `store` DISABLE KEYS */;
INSERT INTO `store` VALUES
(1,'001','FnH Groceries - Central','100 Fresh Way',NULL,'Los Angeles','CA','90001','555-0100','2020-01-01',1);
/*!40000 ALTER TABLE `store` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `storeinventory`
--

DROP TABLE IF EXISTS `storeinventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `storeinventory` (
  `StoreID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `StockQuantity` decimal(12,3) NOT NULL DEFAULT 0.000 CHECK (`StockQuantity` >= 0),
  `Aisle` varchar(20) DEFAULT NULL,
  `SectionName` varchar(50) DEFAULT NULL,
  `ShelfLocation` varchar(30) DEFAULT NULL,
  `LastCountedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`StoreID`,`ProductID`),
  KEY `idx_store_inventory_product` (`ProductID`,`StoreID`),
  KEY `idx_store_inventory_stock` (`StoreID`,`StockQuantity`),
  CONSTRAINT `storeinventory_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `storeinventory_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `storeinventory`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `storeinventory` WRITE;
/*!40000 ALTER TABLE `storeinventory` DISABLE KEYS */;
INSERT INTO `storeinventory` VALUES
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
/*!40000 ALTER TABLE `storeinventory` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transactionjournal`
--

DROP TABLE IF EXISTS `transactionjournal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactionjournal` (
  `JournalID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ReceiptID` bigint(20) NOT NULL,
  `TransactionNumber` varchar(40) NOT NULL,
  `StoreID` int(11) NOT NULL,
  `RegisterID` int(11) NOT NULL,
  `OpenedByOperatorID` int(11) NOT NULL,
  `ClosedByOperatorID` int(11) DEFAULT NULL,
  `OpenedDateTime` datetime NOT NULL,
  `ClosedDateTime` datetime DEFAULT NULL,
  `Status` enum('Open','Paid','Cancelled','Cleared') NOT NULL DEFAULT 'Open',
  `LineCount` int(10) unsigned NOT NULL DEFAULT 0 CHECK (`LineCount` >= 0),
  `ItemQuantity` decimal(12,3) NOT NULL DEFAULT 0.000 CHECK (`ItemQuantity` >= 0),
  `SubtotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`SubtotalAmount` >= 0),
  `DiscountAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`DiscountAmount` >= 0),
  `TaxableSubtotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TaxableSubtotalAmount` >= 0),
  `TaxAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TaxAmount` >= 0),
  `TotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`TotalAmount` >= 0),
  `PaymentMethod` varchar(20) DEFAULT NULL,
  `AmountTendered` decimal(10,2) DEFAULT NULL,
  `ChangeDue` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`JournalID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`),
  UNIQUE KEY `TransactionNumber` (`TransactionNumber`),
  KEY `idx_transactionjournal_store_date` (`StoreID`,`OpenedDateTime`),
  KEY `idx_transactionjournal_register` (`StoreID`,`RegisterID`,`Status`),
  KEY `idx_transactionjournal_opened_by` (`OpenedByOperatorID`),
  KEY `idx_transactionjournal_closed_by` (`ClosedByOperatorID`),
  KEY `idx_transactionjournal_register_id` (`RegisterID`),
  CONSTRAINT `transactionjournal_ibfk_1` FOREIGN KEY (`ClosedByOperatorID`) REFERENCES `operator` (`OperatorID`),
  CONSTRAINT `transactionjournal_ibfk_2` FOREIGN KEY (`OpenedByOperatorID`) REFERENCES `operator` (`OperatorID`),
  CONSTRAINT `transactionjournal_ibfk_3` FOREIGN KEY (`ReceiptID`) REFERENCES `salesreceipt` (`ReceiptID`),
  CONSTRAINT `transactionjournal_ibfk_4` FOREIGN KEY (`RegisterID`) REFERENCES `register` (`RegisterID`),
  CONSTRAINT `transactionjournal_ibfk_5` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactionjournal`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transactionjournal` WRITE;
/*!40000 ALTER TABLE `transactionjournal` DISABLE KEYS */;
INSERT INTO `transactionjournal` VALUES
(1,1,'S001-20260914-000001',1,2,1,1,'2026-09-14 10:15:32','2026-09-14 10:15:32','Paid',3,4.500,9.51,1.00,0.00,0.00,8.51,'Cash',10.00,1.49),
(2,2,'S001-20260921040636-0001',1,2,1,1,'2026-09-21 04:06:36','2026-09-21 09:41:59','Cancelled',2,2.000,6.48,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(3,3,'S001-20260921040918-0004',1,3,4,4,'2026-09-21 04:09:18','2026-09-21 04:10:33','Cancelled',3,4.000,17.76,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(4,4,'S001-20260921094205-0001',1,2,1,1,'2026-09-21 09:42:05','2026-09-21 09:43:26','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(5,5,'S001-20260921094659-0001',1,2,1,1,'2026-09-21 09:46:59','2026-09-21 10:03:47','Cancelled',3,6.000,27.24,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(6,6,'S001-20260921113344-0001',1,2,1,1,'2026-09-21 11:33:44','2026-09-21 14:08:52','Cancelled',5,5.000,19.75,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(8,7,'S001-20260921140852776581-0001',1,2,1,1,'2026-09-21 14:08:52','2026-09-21 14:08:57','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(9,8,'S001-20260921140857502640-0001',1,2,1,1,'2026-09-21 14:08:57','2026-09-21 14:09:05','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(10,9,'S001-20260921140912525214-0001',1,2,1,1,'2026-09-21 14:09:12','2026-09-21 14:10:21','Cancelled',1,1.000,3.79,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(11,10,'S001-20260921141021876972-0001',1,2,1,1,'2026-09-21 14:10:21','2026-09-21 14:10:28','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(12,11,'S001-20260921141032050072-0001',1,2,1,1,'2026-09-21 14:10:32','2026-09-26 12:32:29','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(13,12,'S001-20260921141213649186-0004',1,2,4,4,'2026-09-21 14:12:13','2026-09-21 17:41:52','Cancelled',4,5.000,18.95,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(14,13,'S001-20260921174152601657-0004',1,2,4,4,'2026-09-21 17:41:52','2026-09-21 18:09:58','Cancelled',1,1.000,2.99,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(15,14,'S001-20260926123127781052-0004',1,2,4,4,'2026-09-26 12:31:27','2026-09-26 12:31:48','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(16,15,'S001-20260926123148342569-0004',1,2,4,1,'2026-09-26 12:31:48','2026-09-26 23:02:51','Cleared',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(17,16,'S001-20260926222253997853-0003',1,2,3,3,'2026-09-26 22:22:53','2026-09-26 22:22:59','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(18,17,'S001-20260926222305891530-0003',1,2,3,3,'2026-09-26 22:23:05','2026-09-26 22:25:24','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(19,18,'S001-20260926222524688002-0003',1,2,3,3,'2026-09-26 22:25:24','2026-09-26 22:25:26','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(20,19,'S001-20260926223311458757-0003',1,2,3,3,'2026-09-26 22:33:11','2026-09-26 22:33:19','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(21,20,'S001-20260926223319235871-0003',1,2,3,3,'2026-09-26 22:33:19','2026-09-26 22:33:23','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(22,21,'S001-20260926223330271937-0003',1,2,3,3,'2026-09-26 22:33:30','2026-09-26 22:33:39','Cancelled',2,2.000,7.28,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(23,22,'S001-20260926223339247213-0003',1,2,3,3,'2026-09-26 22:33:39','2026-09-26 22:34:20','Cancelled',2,2.000,7.28,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(24,23,'S001-20260926225905518779-0003',1,2,3,1,'2026-09-26 22:59:05','2026-09-26 23:02:58','Cleared',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(25,24,'S001-20260927000651722068-0001',1,2,1,1,'2026-09-27 00:06:51','2026-09-27 00:09:35','Cancelled',2,2.000,8.08,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(26,25,'S001-20260927000935481704-0001',1,2,1,1,'2026-09-27 00:09:35','2026-09-27 00:18:29','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(27,26,'S001-20260927001833771986-0001',1,2,1,1,'2026-09-27 00:18:33','2026-09-27 00:59:59','Cancelled',3,7.000,25.43,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(28,27,'S001-20260927224458743002-0004',1,2,4,4,'2026-09-27 22:44:58','2026-09-28 00:54:36','Cancelled',1,1.000,2.99,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(29,28,'S001-20260928003029958537-0001',1,2,1,1,'2026-09-28 00:30:29','2026-09-28 00:30:35','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(30,29,'S001-20260928005436249382-0004',1,2,4,4,'2026-09-28 00:54:36','2026-09-28 00:54:43','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(31,30,'S001-20261004021258000907-0001',1,2,1,1,'2026-10-04 02:12:58','2026-10-04 02:15:01','Cancelled',2,2.000,7.28,0.00,0.00,0.00,0.00,NULL,NULL,NULL),
(32,31,'S001-20261004021501653293-0001',1,2,1,1,'2026-10-04 02:15:01','2026-10-04 02:15:05','Cancelled',0,0.000,0.00,0.00,0.00,0.00,0.00,NULL,NULL,NULL);
/*!40000 ALTER TABLE `transactionjournal` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `vw_customerpurchasehistory`
--

DROP TABLE IF EXISTS `vw_customerpurchasehistory`;
/*!50001 DROP VIEW IF EXISTS `vw_customerpurchasehistory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_customerpurchasehistory` AS SELECT
 NULL AS `CustomerID`,
 NULL AS `LoyaltyNumber`,
 NULL AS `FirstName`,
 NULL AS `LastName`,
 NULL AS `LoyaltyPoints`,
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `PurchaseDateTime`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `GrossSubtotal`,
 NULL AS `LineDiscountAmount`,
 NULL AS `Subtotal`,
 NULL AS `ReceiptDiscountAmount`,
 NULL AS `TotalDiscountAmount`,
 NULL AS `TaxAmount`,
 NULL AS `TotalAmount`,
 NULL AS `PaymentMethod` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_dailysalessummary`
--

DROP TABLE IF EXISTS `vw_dailysalessummary`;
/*!50001 DROP VIEW IF EXISTS `vw_dailysalessummary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_dailysalessummary` AS SELECT
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `SaleDate`,
 NULL AS `TransactionCount`,
 NULL AS `GrossSales`,
 NULL AS `TotalDiscounts`,
 NULL AS `NetSales`,
 NULL AS `TotalTax`,
 NULL AS `TotalCollected` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_departmentproducts`
--

DROP TABLE IF EXISTS `vw_departmentproducts`;
/*!50001 DROP VIEW IF EXISTS `vw_departmentproducts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_departmentproducts` AS SELECT
 NULL AS `DepartmentID`,
 NULL AS `DepartmentName`,
 NULL AS `CategoryName`,
 NULL AS `ProductCount`,
 NULL AS `LowestPrice`,
 NULL AS `HighestPrice`,
 NULL AS `TotalStock` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_express_orders`
--

DROP TABLE IF EXISTS `vw_express_orders`;
/*!50001 DROP VIEW IF EXISTS `vw_express_orders`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_express_orders` AS SELECT
 NULL AS `ExpressOrderID`,
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `RegisterID`,
 NULL AS `RegisterNumber`,
 NULL AS `RegisterName`,
 NULL AS `CustomerID`,
 NULL AS `LoyaltyNumber`,
 NULL AS `CustomerFirstName`,
 NULL AS `CustomerLastName`,
 NULL AS `CustomerPhone`,
 NULL AS `PersonalShopperID`,
 NULL AS `PersonalShopperUsername`,
 NULL AS `PersonalShopperFirstName`,
 NULL AS `PersonalShopperLastName`,
 NULL AS `OrderPlacedDateTime`,
 NULL AS `FulfillmentMethod`,
 NULL AS `DeliveryFee`,
 NULL AS `DeliveryAddressLine1`,
 NULL AS `DeliveryAddressLine2`,
 NULL AS `DeliveryCity`,
 NULL AS `DeliveryStateCode`,
 NULL AS `DeliveryPostalCode`,
 NULL AS `ExpressStatus`,
 NULL AS `ReceiptStatus`,
 NULL AS `ReceiptDiscountAmount`,
 NULL AS `SubtotalAmount`,
 NULL AS `TaxableSubtotalAmount`,
 NULL AS `TaxAmount`,
 NULL AS `TotalAmount`,
 NULL AS `PaymentMethod`,
 NULL AS `AmountTendered`,
 NULL AS `ChangeDue`,
 NULL AS `CheckoutDateTime` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_operatoractivity`
--

DROP TABLE IF EXISTS `vw_operatoractivity`;
/*!50001 DROP VIEW IF EXISTS `vw_operatoractivity`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_operatoractivity` AS SELECT
 NULL AS `OperatorID`,
 NULL AS `EmployeeNumber`,
 NULL AS `Username`,
 NULL AS `OperatorName`,
 NULL AS `Role`,
 NULL AS `CurrentStoreNumber`,
 NULL AS `CurrentStoreName`,
 NULL AS `TransactionCount`,
 NULL AS `TotalSales`,
 NULL AS `FirstTransaction`,
 NULL AS `LastTransaction`,
 NULL AS `Active` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_operatorlist`
--

DROP TABLE IF EXISTS `vw_operatorlist`;
/*!50001 DROP VIEW IF EXISTS `vw_operatorlist`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_operatorlist` AS SELECT
 NULL AS `OperatorID`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `EmployeeNumber`,
 NULL AS `Username`,
 NULL AS `FirstName`,
 NULL AS `MiddleInitial`,
 NULL AS `LastName`,
 NULL AS `FullName`,
 NULL AS `Email`,
 NULL AS `Phone`,
 NULL AS `Role`,
 NULL AS `HireDate`,
 NULL AS `Active`,
 NULL AS `CreatedAt` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_operatorlogin`
--

DROP TABLE IF EXISTS `vw_operatorlogin`;
/*!50001 DROP VIEW IF EXISTS `vw_operatorlogin`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_operatorlogin` AS SELECT
 NULL AS `OperatorID`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `EmployeeNumber`,
 NULL AS `Username`,
 NULL AS `PasswordHash`,
 NULL AS `FirstName`,
 NULL AS `MiddleInitial`,
 NULL AS `LastName`,
 NULL AS `Email`,
 NULL AS `Phone`,
 NULL AS `Role` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_pos_products`
--

DROP TABLE IF EXISTS `vw_pos_products`;
/*!50001 DROP VIEW IF EXISTS `vw_pos_products`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_pos_products` AS SELECT
 NULL AS `StoreID`,
 NULL AS `ProductID`,
 NULL AS `DepartmentID`,
 NULL AS `DepartmentName`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `ProductName`,
 NULL AS `UnitType`,
 NULL AS `RetailPrice`,
 NULL AS `Taxable`,
 NULL AS `StockQuantity` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_productinventory`
--

DROP TABLE IF EXISTS `vw_productinventory`;
/*!50001 DROP VIEW IF EXISTS `vw_productinventory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_productinventory` AS SELECT
 NULL AS `ProductID`,
 NULL AS `ProductName`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `UnitType`,
 NULL AS `UnitCost`,
 NULL AS `RetailPrice`,
 NULL AS `TotalStockQuantity`,
 NULL AS `StoreCount`,
 NULL AS `DepartmentID`,
 NULL AS `DepartmentName`,
 NULL AS `CategoryID`,
 NULL AS `CategoryName`,
 NULL AS `Active` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_receiptdetail`
--

DROP TABLE IF EXISTS `vw_receiptdetail`;
/*!50001 DROP VIEW IF EXISTS `vw_receiptdetail`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_receiptdetail` AS SELECT
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `PurchaseDateTime`,
 NULL AS `Status`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `RegisterID`,
 NULL AS `RegisterNumber`,
 NULL AS `CashierID`,
 NULL AS `CashierEmployeeNumber`,
 NULL AS `CashierUsername`,
 NULL AS `CashierName`,
 NULL AS `CustomerID`,
 NULL AS `LineNumber`,
 NULL AS `ProductID`,
 NULL AS `ProductName`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `Quantity`,
 NULL AS `UnitType`,
 NULL AS `UnitPrice`,
 NULL AS `LineDiscountAmount`,
 NULL AS `LineTotal` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_receiptsummary`
--

DROP TABLE IF EXISTS `vw_receiptsummary`;
/*!50001 DROP VIEW IF EXISTS `vw_receiptsummary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_receiptsummary` AS SELECT
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `PurchaseDateTime`,
 NULL AS `Status`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `RegisterID`,
 NULL AS `RegisterNumber`,
 NULL AS `CashierID`,
 NULL AS `CashierEmployeeNumber`,
 NULL AS `CashierUsername`,
 NULL AS `CashierName`,
 NULL AS `CustomerID`,
 NULL AS `GrossSubtotal`,
 NULL AS `LineDiscountAmount`,
 NULL AS `Subtotal`,
 NULL AS `ReceiptDiscountAmount`,
 NULL AS `TaxAmount`,
 NULL AS `TotalAmount`,
 NULL AS `PaymentMethod`,
 NULL AS `AmountTendered`,
 NULL AS `ChangeDue` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_sale_detail`
--

DROP TABLE IF EXISTS `vw_sale_detail`;
/*!50001 DROP VIEW IF EXISTS `vw_sale_detail`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_sale_detail` AS SELECT
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `StoreID`,
 NULL AS `RegisterID`,
 NULL AS `OperatorID`,
 NULL AS `CustomerID`,
 NULL AS `TransactionDateTime`,
 NULL AS `CheckoutDateTime`,
 NULL AS `Status`,
 NULL AS `ReceiptLineID`,
 NULL AS `LineNumber`,
 NULL AS `ProductID`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `ProductName`,
 NULL AS `UnitType`,
 NULL AS `Taxable`,
 NULL AS `Quantity`,
 NULL AS `UnitPrice`,
 NULL AS `LineDiscountAmount`,
 NULL AS `LineTotal` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_sale_summary`
--

DROP TABLE IF EXISTS `vw_sale_summary`;
/*!50001 DROP VIEW IF EXISTS `vw_sale_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_sale_summary` AS SELECT
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `StoreID`,
 NULL AS `RegisterID`,
 NULL AS `OperatorID`,
 NULL AS `CustomerID`,
 NULL AS `TransactionDateTime`,
 NULL AS `CheckoutDateTime`,
 NULL AS `Status`,
 NULL AS `ReceiptDiscountAmount`,
 NULL AS `SubtotalAmount`,
 NULL AS `TaxableSubtotalAmount`,
 NULL AS `TaxAmount`,
 NULL AS `TotalAmount`,
 NULL AS `PaymentMethod`,
 NULL AS `AmountTendered`,
 NULL AS `ChangeDue`,
 NULL AS `LineCount`,
 NULL AS `ItemQuantity` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_store_stock`
--

DROP TABLE IF EXISTS `vw_store_stock`;
/*!50001 DROP VIEW IF EXISTS `vw_store_stock`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_store_stock` AS SELECT
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `DepartmentID`,
 NULL AS `DepartmentName`,
 NULL AS `ProductID`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `ProductName`,
 NULL AS `UnitType`,
 NULL AS `RetailPrice`,
 NULL AS `Taxable`,
 NULL AS `StockQuantity`,
 NULL AS `Aisle`,
 NULL AS `SectionName`,
 NULL AS `ShelfLocation` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_store_stock_total`
--

DROP TABLE IF EXISTS `vw_store_stock_total`;
/*!50001 DROP VIEW IF EXISTS `vw_store_stock_total`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_store_stock_total` AS SELECT
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `TotalStockQuantity` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_storeinventorydetail`
--

DROP TABLE IF EXISTS `vw_storeinventorydetail`;
/*!50001 DROP VIEW IF EXISTS `vw_storeinventorydetail`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_storeinventorydetail` AS SELECT
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `ProductID`,
 NULL AS `ProductName`,
 NULL AS `UPC`,
 NULL AS `PLUCode`,
 NULL AS `UnitType`,
 NULL AS `UnitCost`,
 NULL AS `RetailPrice`,
 NULL AS `StockQuantity`,
 NULL AS `Aisle`,
 NULL AS `SectionName`,
 NULL AS `ShelfLocation`,
 NULL AS `LastCountedAt`,
 NULL AS `DepartmentName`,
 NULL AS `CategoryName`,
 NULL AS `Active` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_storelist`
--

DROP TABLE IF EXISTS `vw_storelist`;
/*!50001 DROP VIEW IF EXISTS `vw_storelist`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_storelist` AS SELECT
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `AddressLine1`,
 NULL AS `AddressLine2`,
 NULL AS `City`,
 NULL AS `StateCode`,
 NULL AS `PostalCode`,
 NULL AS `Phone`,
 NULL AS `Active` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_transaction_journal`
--

DROP TABLE IF EXISTS `vw_transaction_journal`;
/*!50001 DROP VIEW IF EXISTS `vw_transaction_journal`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_transaction_journal` AS SELECT
 NULL AS `JournalID`,
 NULL AS `ReceiptID`,
 NULL AS `TransactionNumber`,
 NULL AS `StoreID`,
 NULL AS `StoreNumber`,
 NULL AS `StoreName`,
 NULL AS `RegisterID`,
 NULL AS `RegisterNumber`,
 NULL AS `OpenedByOperatorID`,
 NULL AS `OpenedByOperator`,
 NULL AS `ClosedByOperatorID`,
 NULL AS `ClosedByOperator`,
 NULL AS `OpenedDateTime`,
 NULL AS `ClosedDateTime`,
 NULL AS `Status`,
 NULL AS `LineCount`,
 NULL AS `ItemQuantity`,
 NULL AS `SubtotalAmount`,
 NULL AS `DiscountAmount`,
 NULL AS `TaxableSubtotalAmount`,
 NULL AS `TaxAmount`,
 NULL AS `TotalAmount`,
 NULL AS `PaymentMethod`,
 NULL AS `AmountTendered`,
 NULL AS `ChangeDue` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'csc680-fnhgroceries_fnh_groceries'
--

--
-- Dumping routines for database 'csc680-fnhgroceries_fnh_groceries'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_add_sale_item` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_add_sale_item`(
	IN pReceiptID BIGINT,
	IN pProductID INT,
	IN pQuantity DECIMAL(12,3),
	IN pActingOperatorID INT
)
BEGIN
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOperatorID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
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
	SELECT Role
	INTO actingOperatorRole
	FROM operator
	WHERE OperatorID = pActingOperatorID;

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
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole <> 'Administrator' THEN
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_checkout_sale` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_checkout_sale`(
	IN pReceiptID BIGINT,
	IN pAmountTendered DECIMAL(10,2),
	IN pActingOperatorID INT
)
BEGIN
	DECLARE saleOperatorID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
	DECLARE saleStatus VARCHAR(20);
	DECLARE currentSaleType VARCHAR(20);
	DECLARE expressDeliveryFee DECIMAL(10,2) DEFAULT 0.00;
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
	SELECT Role
	INTO actingOperatorRole
	FROM operator
	WHERE OperatorID = pActingOperatorID;

	SELECT
		OperatorID,
		Status,
		SaleType,
		ReceiptDiscountAmount
	INTO
		saleOperatorID,
		saleStatus,
		currentSaleType,
		calculatedDiscount
	FROM salesreceipt
	WHERE ReceiptID = pReceiptID
	FOR UPDATE;
	IF saleOperatorID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The sale does not exist';
	END IF;
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole <> 'Administrator' THEN
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
	IF currentSaleType = 'Express' THEN
		SELECT DeliveryFee
		INTO expressDeliveryFee
		FROM expressorder
		WHERE ReceiptID = pReceiptID
		FOR UPDATE;
	END IF;
	SET calculatedTotal =
		ROUND(
			calculatedSubtotal
			+ calculatedTax
			+ expressDeliveryFee,
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_create_customer_address` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_create_customer_address`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_create_express_customer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_create_express_customer`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_create_express_order` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_create_express_order`(
	IN pStoreID INT,
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
	  AND RegisterNumber = 1
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
			SET MESSAGE_TEXT = 'FnH Express Register 1 is not active';
	END IF;

	IF NOT EXISTS (
		SELECT 1
		FROM operator
		WHERE OperatorID = pPersonalShopperID
		  AND StoreID = pStoreID
		  AND Active = 1
		  AND Role IN ('Administrator','Personal Shopper')
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'The user is not authorized to create an Express order';
	END IF;

	IF EXISTS (
		SELECT 1
		FROM expressorder eo
		WHERE eo.PersonalShopperID = pPersonalShopperID
		  AND eo.Status IN ('Received','Picking','Ready')
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Finish or cancel your current Express order before starting another';
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_create_operator` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_create_operator`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_delete_operator` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_delete_operator`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_get_express_capacity` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_get_express_capacity`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_reactivate_operator` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_reactivate_operator`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_remove_sale_item` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_remove_sale_item`(
	IN pReceiptID BIGINT,
	IN pReceiptLineID BIGINT,
	IN pActingOperatorID INT,
	IN pQuantityToRemove DECIMAL(12,3)
)
BEGIN
	DECLARE saleStoreID INT DEFAULT NULL;
	DECLARE saleOperatorID INT DEFAULT NULL;
	DECLARE actingOperatorRole VARCHAR(20) DEFAULT NULL;
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
	SELECT Role
	INTO actingOperatorRole
	FROM operator
	WHERE OperatorID = pActingOperatorID;

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
	IF saleOperatorID <> pActingOperatorID
	   AND actingOperatorRole <> 'Administrator' THEN
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
	COMMIT;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_set_express_order_status` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_set_express_order_status`(
	IN pExpressOrderID BIGINT,
	IN pPersonalShopperID INT,
	IN pStatus VARCHAR(20)
)
BEGIN
	DECLARE orderShopperID INT DEFAULT NULL;
	DECLARE receiptID BIGINT DEFAULT NULL;
	DECLARE receiptStatus VARCHAR(20) DEFAULT NULL;

	IF pStatus NOT IN ('Received','Picking','Ready') THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Invalid Express order status';
	END IF;

	SELECT
		eo.PersonalShopperID,
		eo.ReceiptID,
		sr.Status
	INTO
		orderShopperID,
		receiptID,
		receiptStatus
	FROM expressorder eo
	JOIN salesreceipt sr
		ON sr.ReceiptID = eo.ReceiptID
	WHERE eo.ExpressOrderID = pExpressOrderID;

	IF receiptID IS NULL THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Express order does not exist';
	END IF;

	IF orderShopperID <> pPersonalShopperID
	   AND NOT EXISTS (
			SELECT 1
			FROM operator
			WHERE OperatorID = pPersonalShopperID
			  AND Active = 1
			  AND Role = 'Administrator'
	   ) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'You cannot update another personal shopper''s Express order';
	END IF;

	IF receiptStatus <> 'Open' THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Only an open Express order can be updated';
	END IF;

	UPDATE expressorder
	SET Status = pStatus
	WHERE ExpressOrderID = pExpressOrderID;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_start_sale` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_start_sale`(
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
	IF EXISTS (
		SELECT 1
		FROM register
		WHERE RegisterID = pRegisterID
		  AND StoreID = pStoreID
		  AND RegisterNumber = 1
	) THEN
		SIGNAL SQLSTATE '45000'
			SET MESSAGE_TEXT = 'Register 1 is reserved for FnH Express orders';
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_update_customer_address` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_update_customer_address`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_update_operator` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_update_operator`(
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
	IF pRole NOT IN ('Pending', 'Administrator', 'Operator', 'Personal Shopper') THEN
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_update_own_account` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_update_own_account`(
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
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_void_sale` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`csc680-fnhgroceries`@`%` PROCEDURE `sp_void_sale`(
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
	IF currentSaleType = 'Express' THEN
		UPDATE expressorder
		SET Status = 'Cancelled'
		WHERE ReceiptID = pReceiptID;
	END IF;
	COMMIT;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `vw_customerpurchasehistory`
--

/*!50001 DROP VIEW IF EXISTS `vw_customerpurchasehistory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_customerpurchasehistory` AS select `cu`.`CustomerID` AS `CustomerID`,`cu`.`LoyaltyNumber` AS `LoyaltyNumber`,`cu`.`FirstName` AS `FirstName`,`cu`.`LastName` AS `LastName`,`cu`.`LoyaltyPoints` AS `LoyaltyPoints`,`rs`.`ReceiptID` AS `ReceiptID`,`rs`.`TransactionNumber` AS `TransactionNumber`,`rs`.`PurchaseDateTime` AS `PurchaseDateTime`,`rs`.`StoreNumber` AS `StoreNumber`,`rs`.`StoreName` AS `StoreName`,`rs`.`GrossSubtotal` AS `GrossSubtotal`,`rs`.`LineDiscountAmount` AS `LineDiscountAmount`,`rs`.`Subtotal` AS `Subtotal`,`rs`.`ReceiptDiscountAmount` AS `ReceiptDiscountAmount`,round(`rs`.`LineDiscountAmount` + `rs`.`ReceiptDiscountAmount`,2) AS `TotalDiscountAmount`,`rs`.`TaxAmount` AS `TaxAmount`,`rs`.`TotalAmount` AS `TotalAmount`,`rs`.`PaymentMethod` AS `PaymentMethod` from (`customer` `cu` join `vw_receiptsummary` `rs` on(`rs`.`CustomerID` = `cu`.`CustomerID`)) where `rs`.`Status` in ('Completed','Paid') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_dailysalessummary`
--

/*!50001 DROP VIEW IF EXISTS `vw_dailysalessummary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_dailysalessummary` AS select `rs`.`StoreID` AS `StoreID`,`rs`.`StoreNumber` AS `StoreNumber`,`rs`.`StoreName` AS `StoreName`,cast(`rs`.`PurchaseDateTime` as date) AS `SaleDate`,count(0) AS `TransactionCount`,round(sum(`rs`.`GrossSubtotal`),2) AS `GrossSales`,round(sum(`rs`.`LineDiscountAmount` + `rs`.`ReceiptDiscountAmount`),2) AS `TotalDiscounts`,round(sum(`rs`.`Subtotal` - `rs`.`ReceiptDiscountAmount`),2) AS `NetSales`,round(sum(`rs`.`TaxAmount`),2) AS `TotalTax`,round(sum(`rs`.`TotalAmount`),2) AS `TotalCollected` from `vw_receiptsummary` `rs` where `rs`.`Status` in ('Completed','Paid') group by `rs`.`StoreID`,`rs`.`StoreNumber`,`rs`.`StoreName`,cast(`rs`.`PurchaseDateTime` as date) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_departmentproducts`
--

/*!50001 DROP VIEW IF EXISTS `vw_departmentproducts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_departmentproducts` AS select `d`.`DepartmentID` AS `DepartmentID`,`d`.`DepartmentName` AS `DepartmentName`,`c`.`CategoryName` AS `CategoryName`,count(`p`.`ProductID`) AS `ProductCount`,min(`p`.`RetailPrice`) AS `LowestPrice`,max(`p`.`RetailPrice`) AS `HighestPrice`,coalesce(sum(`inv`.`TotalStockQuantity`),0) AS `TotalStock` from (((`department` `d` join `category` `c` on(`c`.`CategoryID` = `d`.`CategoryID`)) left join `product` `p` on(`p`.`DepartmentID` = `d`.`DepartmentID` and `p`.`Active` = 1)) left join (select `storeinventory`.`ProductID` AS `ProductID`,sum(`storeinventory`.`StockQuantity`) AS `TotalStockQuantity` from `storeinventory` group by `storeinventory`.`ProductID`) `inv` on(`inv`.`ProductID` = `p`.`ProductID`)) group by `d`.`DepartmentID`,`d`.`DepartmentName`,`c`.`CategoryName` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_express_orders`
--

/*!50001 DROP VIEW IF EXISTS `vw_express_orders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_express_orders` AS select `eo`.`ExpressOrderID` AS `ExpressOrderID`,`sr`.`ReceiptID` AS `ReceiptID`,`sr`.`TransactionNumber` AS `TransactionNumber`,`sr`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`sr`.`RegisterID` AS `RegisterID`,`r`.`RegisterNumber` AS `RegisterNumber`,`r`.`RegisterName` AS `RegisterName`,`sr`.`CustomerID` AS `CustomerID`,`c`.`LoyaltyNumber` AS `LoyaltyNumber`,`c`.`FirstName` AS `CustomerFirstName`,`c`.`LastName` AS `CustomerLastName`,`c`.`Phone` AS `CustomerPhone`,`eo`.`PersonalShopperID` AS `PersonalShopperID`,`o`.`Username` AS `PersonalShopperUsername`,`o`.`FirstName` AS `PersonalShopperFirstName`,`o`.`LastName` AS `PersonalShopperLastName`,`eo`.`OrderPlacedDateTime` AS `OrderPlacedDateTime`,`eo`.`FulfillmentMethod` AS `FulfillmentMethod`,`eo`.`DeliveryFee` AS `DeliveryFee`,`eo`.`DeliveryAddressLine1` AS `DeliveryAddressLine1`,`eo`.`DeliveryAddressLine2` AS `DeliveryAddressLine2`,`eo`.`DeliveryCity` AS `DeliveryCity`,`eo`.`DeliveryStateCode` AS `DeliveryStateCode`,`eo`.`DeliveryPostalCode` AS `DeliveryPostalCode`,`eo`.`Status` AS `ExpressStatus`,`sr`.`Status` AS `ReceiptStatus`,`sr`.`ReceiptDiscountAmount` AS `ReceiptDiscountAmount`,`sr`.`SubtotalAmount` AS `SubtotalAmount`,`sr`.`TaxableSubtotalAmount` AS `TaxableSubtotalAmount`,`sr`.`TaxAmount` AS `TaxAmount`,`sr`.`TotalAmount` AS `TotalAmount`,`sr`.`PaymentMethod` AS `PaymentMethod`,`sr`.`AmountTendered` AS `AmountTendered`,`sr`.`ChangeDue` AS `ChangeDue`,`sr`.`CheckoutDateTime` AS `CheckoutDateTime` from (((((`expressorder` `eo` join `salesreceipt` `sr` on(`sr`.`ReceiptID` = `eo`.`ReceiptID`)) join `store` `s` on(`s`.`StoreID` = `sr`.`StoreID`)) join `register` `r` on(`r`.`StoreID` = `sr`.`StoreID` and `r`.`RegisterID` = `sr`.`RegisterID`)) join `operator` `o` on(`o`.`OperatorID` = `eo`.`PersonalShopperID`)) left join `customer` `c` on(`c`.`CustomerID` = `eo`.`CustomerID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_operatoractivity`
--

/*!50001 DROP VIEW IF EXISTS `vw_operatoractivity`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_operatoractivity` AS select `o`.`OperatorID` AS `OperatorID`,`o`.`EmployeeNumber` AS `EmployeeNumber`,`o`.`Username` AS `Username`,concat(`o`.`FirstName`,' ',`o`.`LastName`) AS `OperatorName`,`o`.`Role` AS `Role`,`s`.`StoreNumber` AS `CurrentStoreNumber`,`s`.`StoreName` AS `CurrentStoreName`,count(`rs`.`ReceiptID`) AS `TransactionCount`,coalesce(round(sum(`rs`.`Subtotal` - `rs`.`ReceiptDiscountAmount`),2),0.00) AS `TotalSales`,min(`rs`.`PurchaseDateTime`) AS `FirstTransaction`,max(`rs`.`PurchaseDateTime`) AS `LastTransaction`,`o`.`Active` AS `Active` from ((`operator` `o` join `store` `s` on(`s`.`StoreID` = `o`.`StoreID`)) left join `vw_receiptsummary` `rs` on(`rs`.`CashierID` = `o`.`OperatorID` and `rs`.`Status` in ('Completed','Paid'))) group by `o`.`OperatorID`,`o`.`EmployeeNumber`,`o`.`Username`,`o`.`FirstName`,`o`.`LastName`,`o`.`Role`,`s`.`StoreNumber`,`s`.`StoreName`,`o`.`Active` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_operatorlist`
--

/*!50001 DROP VIEW IF EXISTS `vw_operatorlist`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_operatorlist` AS select `o`.`OperatorID` AS `OperatorID`,`o`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`o`.`EmployeeNumber` AS `EmployeeNumber`,`o`.`Username` AS `Username`,`o`.`FirstName` AS `FirstName`,`o`.`MiddleInitial` AS `MiddleInitial`,`o`.`LastName` AS `LastName`,concat(`o`.`LastName`,', ',`o`.`FirstName`,case when `o`.`MiddleInitial` is null or trim(`o`.`MiddleInitial`) = '' then '' else concat(', ',ucase(`o`.`MiddleInitial`),'.') end) AS `FullName`,`o`.`Email` AS `Email`,`o`.`Phone` AS `Phone`,`o`.`Role` AS `Role`,`o`.`HireDate` AS `HireDate`,`o`.`Active` AS `Active`,`o`.`CreatedAt` AS `CreatedAt` from (`operator` `o` join `store` `s` on(`s`.`StoreID` = `o`.`StoreID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_operatorlogin`
--

/*!50001 DROP VIEW IF EXISTS `vw_operatorlogin`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_operatorlogin` AS select `o`.`OperatorID` AS `OperatorID`,`o`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`o`.`EmployeeNumber` AS `EmployeeNumber`,`o`.`Username` AS `Username`,`o`.`PasswordHash` AS `PasswordHash`,`o`.`FirstName` AS `FirstName`,`o`.`MiddleInitial` AS `MiddleInitial`,`o`.`LastName` AS `LastName`,`o`.`Email` AS `Email`,`o`.`Phone` AS `Phone`,`o`.`Role` AS `Role` from (`operator` `o` join `store` `s` on(`s`.`StoreID` = `o`.`StoreID`)) where `o`.`Active` = 1 and `s`.`Active` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_pos_products`
--

/*!50001 DROP VIEW IF EXISTS `vw_pos_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_pos_products` AS select `si`.`StoreID` AS `StoreID`,`p`.`ProductID` AS `ProductID`,`p`.`DepartmentID` AS `DepartmentID`,`d`.`DepartmentName` AS `DepartmentName`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`p`.`ProductName` AS `ProductName`,`p`.`UnitType` AS `UnitType`,`p`.`RetailPrice` AS `RetailPrice`,`p`.`Taxable` AS `Taxable`,`si`.`StockQuantity` AS `StockQuantity` from ((`product` `p` join `department` `d` on(`d`.`DepartmentID` = `p`.`DepartmentID`)) join `storeinventory` `si` on(`si`.`ProductID` = `p`.`ProductID`)) where `p`.`Active` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_productinventory`
--

/*!50001 DROP VIEW IF EXISTS `vw_productinventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_productinventory` AS select `p`.`ProductID` AS `ProductID`,`p`.`ProductName` AS `ProductName`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`p`.`UnitType` AS `UnitType`,`p`.`UnitCost` AS `UnitCost`,`p`.`RetailPrice` AS `RetailPrice`,coalesce(sum(`si`.`StockQuantity`),0) AS `TotalStockQuantity`,count(distinct `si`.`StoreID`) AS `StoreCount`,`d`.`DepartmentID` AS `DepartmentID`,`d`.`DepartmentName` AS `DepartmentName`,`c`.`CategoryID` AS `CategoryID`,`c`.`CategoryName` AS `CategoryName`,`p`.`Active` AS `Active` from (((`product` `p` join `department` `d` on(`d`.`DepartmentID` = `p`.`DepartmentID`)) join `category` `c` on(`c`.`CategoryID` = `d`.`CategoryID`)) left join `storeinventory` `si` on(`si`.`ProductID` = `p`.`ProductID`)) group by `p`.`ProductID`,`p`.`ProductName`,`p`.`UPC`,`p`.`PLUCode`,`p`.`UnitType`,`p`.`UnitCost`,`p`.`RetailPrice`,`d`.`DepartmentID`,`d`.`DepartmentName`,`c`.`CategoryID`,`c`.`CategoryName`,`p`.`Active` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_receiptdetail`
--

/*!50001 DROP VIEW IF EXISTS `vw_receiptdetail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_receiptdetail` AS select `sr`.`ReceiptID` AS `ReceiptID`,`sr`.`TransactionNumber` AS `TransactionNumber`,`sr`.`TransactionDateTime` AS `PurchaseDateTime`,`sr`.`Status` AS `Status`,`sr`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`sr`.`RegisterID` AS `RegisterID`,`r`.`RegisterNumber` AS `RegisterNumber`,`o`.`OperatorID` AS `CashierID`,`o`.`EmployeeNumber` AS `CashierEmployeeNumber`,`o`.`Username` AS `CashierUsername`,concat(`o`.`FirstName`,' ',`o`.`LastName`) AS `CashierName`,`sr`.`CustomerID` AS `CustomerID`,`srl`.`LineNumber` AS `LineNumber`,`p`.`ProductID` AS `ProductID`,`p`.`ProductName` AS `ProductName`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`srl`.`Quantity` AS `Quantity`,`p`.`UnitType` AS `UnitType`,`srl`.`UnitPrice` AS `UnitPrice`,`srl`.`LineDiscountAmount` AS `LineDiscountAmount`,round(`srl`.`Quantity` * `srl`.`UnitPrice` - `srl`.`LineDiscountAmount`,2) AS `LineTotal` from (((((`salesreceipt` `sr` join `store` `s` on(`s`.`StoreID` = `sr`.`StoreID`)) join `register` `r` on(`r`.`StoreID` = `sr`.`StoreID` and `r`.`RegisterID` = `sr`.`RegisterID`)) join `operator` `o` on(`o`.`OperatorID` = `sr`.`OperatorID`)) join `salesreceiptline` `srl` on(`srl`.`ReceiptID` = `sr`.`ReceiptID`)) join `product` `p` on(`p`.`ProductID` = `srl`.`ProductID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_receiptsummary`
--

/*!50001 DROP VIEW IF EXISTS `vw_receiptsummary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_receiptsummary` AS select `sr`.`ReceiptID` AS `ReceiptID`,`sr`.`TransactionNumber` AS `TransactionNumber`,`sr`.`TransactionDateTime` AS `PurchaseDateTime`,`sr`.`Status` AS `Status`,`sr`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`sr`.`RegisterID` AS `RegisterID`,`r`.`RegisterNumber` AS `RegisterNumber`,`o`.`OperatorID` AS `CashierID`,`o`.`EmployeeNumber` AS `CashierEmployeeNumber`,`o`.`Username` AS `CashierUsername`,concat(`o`.`FirstName`,' ',`o`.`LastName`) AS `CashierName`,`sr`.`CustomerID` AS `CustomerID`,round(sum(`srl`.`Quantity` * `srl`.`UnitPrice`),2) AS `GrossSubtotal`,round(sum(`srl`.`LineDiscountAmount`),2) AS `LineDiscountAmount`,round(sum(`srl`.`Quantity` * `srl`.`UnitPrice` - `srl`.`LineDiscountAmount`),2) AS `Subtotal`,`sr`.`ReceiptDiscountAmount` AS `ReceiptDiscountAmount`,`sr`.`TaxAmount` AS `TaxAmount`,`sr`.`TotalAmount` AS `TotalAmount`,`sr`.`PaymentMethod` AS `PaymentMethod`,`sr`.`AmountTendered` AS `AmountTendered`,`sr`.`ChangeDue` AS `ChangeDue` from ((((`salesreceipt` `sr` join `store` `s` on(`s`.`StoreID` = `sr`.`StoreID`)) join `register` `r` on(`r`.`StoreID` = `sr`.`StoreID` and `r`.`RegisterID` = `sr`.`RegisterID`)) join `operator` `o` on(`o`.`OperatorID` = `sr`.`OperatorID`)) join `salesreceiptline` `srl` on(`srl`.`ReceiptID` = `sr`.`ReceiptID`)) group by `sr`.`ReceiptID`,`sr`.`TransactionNumber`,`sr`.`TransactionDateTime`,`sr`.`Status`,`sr`.`StoreID`,`s`.`StoreNumber`,`s`.`StoreName`,`sr`.`RegisterID`,`r`.`RegisterNumber`,`o`.`OperatorID`,`o`.`EmployeeNumber`,`o`.`Username`,`o`.`FirstName`,`o`.`LastName`,`sr`.`CustomerID`,`sr`.`ReceiptDiscountAmount`,`sr`.`TaxAmount`,`sr`.`TotalAmount`,`sr`.`PaymentMethod`,`sr`.`AmountTendered`,`sr`.`ChangeDue` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_sale_detail`
--

/*!50001 DROP VIEW IF EXISTS `vw_sale_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_sale_detail` AS select `sr`.`ReceiptID` AS `ReceiptID`,`sr`.`TransactionNumber` AS `TransactionNumber`,`sr`.`StoreID` AS `StoreID`,`sr`.`RegisterID` AS `RegisterID`,`sr`.`OperatorID` AS `OperatorID`,`sr`.`CustomerID` AS `CustomerID`,`sr`.`TransactionDateTime` AS `TransactionDateTime`,`sr`.`CheckoutDateTime` AS `CheckoutDateTime`,`sr`.`Status` AS `Status`,`srl`.`ReceiptLineID` AS `ReceiptLineID`,`srl`.`LineNumber` AS `LineNumber`,`p`.`ProductID` AS `ProductID`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`srl`.`ProductNameAtSale` AS `ProductName`,`srl`.`UnitTypeAtSale` AS `UnitType`,`srl`.`TaxableAtSale` AS `Taxable`,`srl`.`Quantity` AS `Quantity`,`srl`.`UnitPrice` AS `UnitPrice`,`srl`.`LineDiscountAmount` AS `LineDiscountAmount`,round(`srl`.`Quantity` * `srl`.`UnitPrice` - `srl`.`LineDiscountAmount`,2) AS `LineTotal` from ((`salesreceipt` `sr` join `salesreceiptline` `srl` on(`srl`.`ReceiptID` = `sr`.`ReceiptID`)) join `product` `p` on(`p`.`ProductID` = `srl`.`ProductID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_sale_summary`
--

/*!50001 DROP VIEW IF EXISTS `vw_sale_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_sale_summary` AS select `sr`.`ReceiptID` AS `ReceiptID`,`sr`.`TransactionNumber` AS `TransactionNumber`,`sr`.`StoreID` AS `StoreID`,`sr`.`RegisterID` AS `RegisterID`,`sr`.`OperatorID` AS `OperatorID`,`sr`.`CustomerID` AS `CustomerID`,`sr`.`TransactionDateTime` AS `TransactionDateTime`,`sr`.`CheckoutDateTime` AS `CheckoutDateTime`,`sr`.`Status` AS `Status`,`sr`.`ReceiptDiscountAmount` AS `ReceiptDiscountAmount`,`sr`.`SubtotalAmount` AS `SubtotalAmount`,`sr`.`TaxableSubtotalAmount` AS `TaxableSubtotalAmount`,`sr`.`TaxAmount` AS `TaxAmount`,`sr`.`TotalAmount` AS `TotalAmount`,`sr`.`PaymentMethod` AS `PaymentMethod`,`sr`.`AmountTendered` AS `AmountTendered`,`sr`.`ChangeDue` AS `ChangeDue`,count(`srl`.`ReceiptLineID`) AS `LineCount`,coalesce(sum(`srl`.`Quantity`),0) AS `ItemQuantity` from (`salesreceipt` `sr` left join `salesreceiptline` `srl` on(`srl`.`ReceiptID` = `sr`.`ReceiptID`)) group by `sr`.`ReceiptID`,`sr`.`TransactionNumber`,`sr`.`StoreID`,`sr`.`RegisterID`,`sr`.`OperatorID`,`sr`.`CustomerID`,`sr`.`TransactionDateTime`,`sr`.`CheckoutDateTime`,`sr`.`Status`,`sr`.`ReceiptDiscountAmount`,`sr`.`SubtotalAmount`,`sr`.`TaxableSubtotalAmount`,`sr`.`TaxAmount`,`sr`.`TotalAmount`,`sr`.`PaymentMethod`,`sr`.`AmountTendered`,`sr`.`ChangeDue` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_store_stock`
--

/*!50001 DROP VIEW IF EXISTS `vw_store_stock`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_store_stock` AS select `s`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`d`.`DepartmentID` AS `DepartmentID`,`d`.`DepartmentName` AS `DepartmentName`,`p`.`ProductID` AS `ProductID`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`p`.`ProductName` AS `ProductName`,`p`.`UnitType` AS `UnitType`,`p`.`RetailPrice` AS `RetailPrice`,`p`.`Taxable` AS `Taxable`,`si`.`StockQuantity` AS `StockQuantity`,`si`.`Aisle` AS `Aisle`,`si`.`SectionName` AS `SectionName`,`si`.`ShelfLocation` AS `ShelfLocation` from (((`storeinventory` `si` join `store` `s` on(`s`.`StoreID` = `si`.`StoreID`)) join `product` `p` on(`p`.`ProductID` = `si`.`ProductID`)) join `department` `d` on(`d`.`DepartmentID` = `p`.`DepartmentID`)) where `s`.`Active` = 1 and `p`.`Active` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_store_stock_total`
--

/*!50001 DROP VIEW IF EXISTS `vw_store_stock_total`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_store_stock_total` AS select `s`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,coalesce(sum(`si`.`StockQuantity`),0) AS `TotalStockQuantity` from (`store` `s` left join `storeinventory` `si` on(`si`.`StoreID` = `s`.`StoreID`)) where `s`.`Active` = 1 group by `s`.`StoreID`,`s`.`StoreNumber`,`s`.`StoreName` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_storeinventorydetail`
--

/*!50001 DROP VIEW IF EXISTS `vw_storeinventorydetail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_storeinventorydetail` AS select `s`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`p`.`ProductID` AS `ProductID`,`p`.`ProductName` AS `ProductName`,`p`.`UPC` AS `UPC`,`p`.`PLUCode` AS `PLUCode`,`p`.`UnitType` AS `UnitType`,`p`.`UnitCost` AS `UnitCost`,`p`.`RetailPrice` AS `RetailPrice`,`si`.`StockQuantity` AS `StockQuantity`,`si`.`Aisle` AS `Aisle`,`si`.`SectionName` AS `SectionName`,`si`.`ShelfLocation` AS `ShelfLocation`,`si`.`LastCountedAt` AS `LastCountedAt`,`d`.`DepartmentName` AS `DepartmentName`,`c`.`CategoryName` AS `CategoryName`,`p`.`Active` AS `Active` from ((((`storeinventory` `si` join `store` `s` on(`s`.`StoreID` = `si`.`StoreID`)) join `product` `p` on(`p`.`ProductID` = `si`.`ProductID`)) join `department` `d` on(`d`.`DepartmentID` = `p`.`DepartmentID`)) join `category` `c` on(`c`.`CategoryID` = `d`.`CategoryID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_storelist`
--

/*!50001 DROP VIEW IF EXISTS `vw_storelist`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_storelist` AS select `store`.`StoreID` AS `StoreID`,`store`.`StoreNumber` AS `StoreNumber`,`store`.`StoreName` AS `StoreName`,`store`.`AddressLine1` AS `AddressLine1`,`store`.`AddressLine2` AS `AddressLine2`,`store`.`City` AS `City`,`store`.`StateCode` AS `StateCode`,`store`.`PostalCode` AS `PostalCode`,`store`.`Phone` AS `Phone`,`store`.`Active` AS `Active` from `store` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_transaction_journal`
--

/*!50001 DROP VIEW IF EXISTS `vw_transaction_journal`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`csc680-fnhgroceries`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_transaction_journal` AS select `tj`.`JournalID` AS `JournalID`,`tj`.`ReceiptID` AS `ReceiptID`,`tj`.`TransactionNumber` AS `TransactionNumber`,`tj`.`StoreID` AS `StoreID`,`s`.`StoreNumber` AS `StoreNumber`,`s`.`StoreName` AS `StoreName`,`tj`.`RegisterID` AS `RegisterID`,`r`.`RegisterNumber` AS `RegisterNumber`,`tj`.`OpenedByOperatorID` AS `OpenedByOperatorID`,concat(`opened`.`FirstName`,' ',`opened`.`LastName`) AS `OpenedByOperator`,`tj`.`ClosedByOperatorID` AS `ClosedByOperatorID`,case when `closed`.`OperatorID` is null then NULL else concat(`closed`.`FirstName`,' ',`closed`.`LastName`) end AS `ClosedByOperator`,`tj`.`OpenedDateTime` AS `OpenedDateTime`,`tj`.`ClosedDateTime` AS `ClosedDateTime`,`tj`.`Status` AS `Status`,`tj`.`LineCount` AS `LineCount`,`tj`.`ItemQuantity` AS `ItemQuantity`,`tj`.`SubtotalAmount` AS `SubtotalAmount`,`tj`.`DiscountAmount` AS `DiscountAmount`,`tj`.`TaxableSubtotalAmount` AS `TaxableSubtotalAmount`,`tj`.`TaxAmount` AS `TaxAmount`,`tj`.`TotalAmount` AS `TotalAmount`,`tj`.`PaymentMethod` AS `PaymentMethod`,`tj`.`AmountTendered` AS `AmountTendered`,`tj`.`ChangeDue` AS `ChangeDue` from ((((`transactionjournal` `tj` join `store` `s` on(`s`.`StoreID` = `tj`.`StoreID`)) join `register` `r` on(`r`.`RegisterID` = `tj`.`RegisterID`)) join `operator` `opened` on(`opened`.`OperatorID` = `tj`.`OpenedByOperatorID`)) left join `operator` `closed` on(`closed`.`OperatorID` = `tj`.`ClosedByOperatorID`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-04 16:04:02
