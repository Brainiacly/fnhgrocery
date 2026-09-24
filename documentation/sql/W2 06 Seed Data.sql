-- W2 06 Seed Data.sql
-- Brian Phillips
-- CSC 680
-- FnH Groceries
-- Week 2 demonstration data

-- Existing Week 1 grocery products are food and are not taxable

-- Select the project database
USE fnh_groceries;

UPDATE product
SET Taxable = 0
WHERE ProductID IN (1,2,3,4,5);

-- Add a household category and department for a taxable test product
INSERT INTO category (
    CategoryID,
    CategoryName,
    Description
)
VALUES (
    2,
    'Household',
    'Household and general merchandise'
);

INSERT INTO department (
    DepartmentID,
    CategoryID,
    DepartmentName,
    Description
)
VALUES (
    5,
    2,
    'Household',
    'Paper goods and household supplies'
);

-- Add Week 2 POS products
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
    (
        6,
        1,
        NULL,
        '4065',
        'Green Bell Pepper',
        'Fresh green bell pepper',
        'Each',
        0.55,
        0.99,
        0,
        1
    ),
    (
        7,
        2,
        '100000000007',
        NULL,
        'Large Eggs - Dozen',
        'One dozen large eggs',
        'Each',
        2.65,
        3.99,
        0,
        1
    ),
    (
        8,
        2,
        '100000000008',
        NULL,
        'Cheddar Cheese',
        'Eight ounce cheddar cheese',
        'Each',
        2.45,
        3.79,
        0,
        1
    ),
    (
        9,
        3,
        '100000000009',
        NULL,
        'French Bread',
        'Fresh baked French bread',
        'Each',
        1.75,
        2.99,
        0,
        1
    ),
    (
        10,
        4,
        '100000000010',
        NULL,
        'Peanut Butter',
        'Creamy peanut butter',
        'Each',
        2.80,
        4.49,
        0,
        1
    ),
    (
        11,
        4,
        '100000000011',
        NULL,
        'Cereal',
        'Whole grain breakfast cereal',
        'Each',
        3.20,
        5.29,
        0,
        1
    ),
    (
        12,
        4,
        '100000000012',
        NULL,
        'Out of Stock Test Item',
        'Product used to demonstrate out-of-stock protection',
        'Each',
        1.00,
        1.99,
        0,
        1
    ),
    (
        13,
        5,
        '100000000013',
        NULL,
        'Paper Towels',
        'Two-roll paper towel package',
        'Each',
        2.40,
        4.49,
        1,
        1
    );

-- Add Week 2 store inventory
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
    (1,6,35.000,NULL,'Produce','Pepper Table',NOW()),
    (1,7,18.000,NULL,'Dairy','Cooler 3-A',NOW()),
    (1,8,15.000,NULL,'Dairy','Cooler 3-B',NOW()),
    (1,9,20.000,NULL,'Bakery','Bread Rack 2',NOW()),
    (1,10,25.000,'5','Spreads','Shelf A2',NOW()),
    (1,11,22.000,'6','Breakfast','Shelf C1',NOW()),
    (1,12,0.000,'6','Test Products','Shelf C4',NOW()),
    (1,13,16.000,'7','Paper Goods','Shelf A1',NOW());

-- Update the Week 1 completed sample receipt
-- with the Week 2 calculated receipt fields
UPDATE salesreceipt
SET
    CheckoutDateTime =
        TransactionDateTime,
    SubtotalAmount =
        8.51,
    TaxableSubtotalAmount =
        0.00,
    TotalAmount =
        8.51,
    ChangeDue =
        1.49
WHERE ReceiptID = 1;