-- W2 04 Create Tables.sql
-- Brian Phillips
-- CSC 680
-- FnH Groceries

USE fnh_groceries;

-- Extend products for tax status
ALTER TABLE product
    ADD COLUMN Taxable
        TINYINT(1)
        NOT NULL
        DEFAULT 0
        AFTER RetailPrice,
    ADD CONSTRAINT chk_product_taxable
        CHECK (Taxable IN (0,1));

-- Preserve product information as it appeared when each item was sold
ALTER TABLE salesreceiptline
    ADD COLUMN ProductNameAtSale VARCHAR(120) NULL AFTER ProductID,
    ADD COLUMN UnitTypeAtSale ENUM('Each','Pound') NULL AFTER ProductNameAtSale,
    ADD COLUMN TaxableAtSale TINYINT(1) NULL AFTER UnitTypeAtSale;

UPDATE salesreceiptline srl
JOIN product p ON p.ProductID = srl.ProductID
SET
    srl.ProductNameAtSale = p.ProductName,
    srl.UnitTypeAtSale = p.UnitType,
    srl.TaxableAtSale = p.Taxable;

ALTER TABLE salesreceiptline
    MODIFY COLUMN ProductNameAtSale VARCHAR(120) NOT NULL,
    MODIFY COLUMN UnitTypeAtSale ENUM('Each','Pound') NOT NULL,
    MODIFY COLUMN TaxableAtSale TINYINT(1) NOT NULL,
    ADD CONSTRAINT chk_salesreceiptline_taxable
        CHECK (TaxableAtSale IN (0,1));

-- Extend receipts for checkout
ALTER TABLE salesreceipt
    MODIFY COLUMN Status
        ENUM(
            'Open',
            'Paid',
            'Completed',
            'Voided',
            'Refunded'
        )
        NOT NULL
        DEFAULT 'Open',
    ADD COLUMN CheckoutDateTime
        DATETIME
        NULL
        AFTER TransactionDateTime,
    ADD COLUMN SubtotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00
        AFTER ReceiptDiscountAmount,
    ADD COLUMN TaxableSubtotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00
        AFTER SubtotalAmount,
    ADD COLUMN TotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00
        AFTER TaxAmount,
    ADD COLUMN ChangeDue
        DECIMAL(10,2)
        NULL
        AFTER AmountTendered,
    ADD CONSTRAINT chk_salesreceipt_subtotal
        CHECK (SubtotalAmount >= 0),
    ADD CONSTRAINT chk_salesreceipt_taxable_subtotal
        CHECK (TaxableSubtotalAmount >= 0),
    ADD CONSTRAINT chk_salesreceipt_total
        CHECK (TotalAmount >= 0),
    ADD CONSTRAINT chk_salesreceipt_change
        CHECK (ChangeDue IS NULL OR ChangeDue >= 0);

-- Keep one journal row for every transaction
CREATE TABLE transactionjournal (
    JournalID
        BIGINT
        NOT NULL
        AUTO_INCREMENT,
    ReceiptID
        BIGINT
        NOT NULL,
    TransactionNumber
        VARCHAR(40)
        NOT NULL,
    StoreID
        INT
        NOT NULL,
    RegisterID
        INT
        NOT NULL,
    OpenedByOperatorID
        INT
        NOT NULL,
    ClosedByOperatorID
        INT
        NULL,
    OpenedDateTime
        DATETIME
        NOT NULL,
    ClosedDateTime
        DATETIME
        NULL,
    Status
        ENUM(
            'Open',
            'Paid',
            'Cancelled',
            'Cleared'
        )
        NOT NULL
        DEFAULT 'Open',
    LineCount
        INT
        NOT NULL
        DEFAULT 0,
    ItemQuantity
        DECIMAL(12,3)
        NOT NULL
        DEFAULT 0.000,
    SubtotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,
    DiscountAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,
    TaxableSubtotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,
    TaxAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,
    TotalAmount
        DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,
    PaymentMethod
        VARCHAR(20)
        NULL,
    AmountTendered
        DECIMAL(10,2)
        NULL,
    ChangeDue
        DECIMAL(10,2)
        NULL,
    PRIMARY KEY (JournalID),
    UNIQUE KEY uq_transactionjournal_receipt
        (ReceiptID),
    UNIQUE KEY uq_transactionjournal_number
        (TransactionNumber),
    KEY idx_transactionjournal_store_date
        (StoreID, OpenedDateTime),
    KEY idx_transactionjournal_register
        (StoreID, RegisterID, Status),
    KEY idx_transactionjournal_opened_by
        (OpenedByOperatorID),
    KEY idx_transactionjournal_closed_by
        (ClosedByOperatorID),
    CONSTRAINT fk_transactionjournal_receipt
        FOREIGN KEY (ReceiptID)
        REFERENCES salesreceipt (ReceiptID),
    CONSTRAINT fk_transactionjournal_store
        FOREIGN KEY (StoreID)
        REFERENCES store (StoreID),
    CONSTRAINT fk_transactionjournal_register
        FOREIGN KEY (RegisterID)
        REFERENCES register (RegisterID),
    CONSTRAINT fk_transactionjournal_opened_by
        FOREIGN KEY (OpenedByOperatorID)
        REFERENCES operator (OperatorID),
    CONSTRAINT fk_transactionjournal_closed_by
        FOREIGN KEY (ClosedByOperatorID)
        REFERENCES operator (OperatorID),
    CONSTRAINT chk_transactionjournal_line_count
        CHECK (LineCount >= 0),
    CONSTRAINT chk_transactionjournal_quantity
        CHECK (ItemQuantity >= 0),
    CONSTRAINT chk_transactionjournal_subtotal
        CHECK (SubtotalAmount >= 0),
    CONSTRAINT chk_transactionjournal_discount
        CHECK (DiscountAmount >= 0),
    CONSTRAINT chk_transactionjournal_taxable
        CHECK (TaxableSubtotalAmount >= 0),
    CONSTRAINT chk_transactionjournal_tax
        CHECK (TaxAmount >= 0),
    CONSTRAINT chk_transactionjournal_total
        CHECK (TotalAmount >= 0)
) ENGINE=InnoDB;

-- Add Week 2 indexes
CREATE INDEX idx_salesreceipt_open_sale
    ON salesreceipt
        (StoreID, RegisterID, OperatorID, Status);

CREATE INDEX idx_product_active_name
    ON product
        (Active, ProductName);

CREATE INDEX idx_storeinventory_stock
    ON storeinventory
        (StoreID, StockQuantity);