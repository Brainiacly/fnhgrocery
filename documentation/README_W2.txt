FnH Groceries - CSC680 Assignment 2
Brian Phillips

WEEK 2 UPDATES

Assignment 2 extends the Assignment 1 project. This file lists only the Week 2 additions and changes.

NEW PROJECT FILES

inventory/store_stock_levels.php

sales/new.php
sales/checkout.php
sales/complete.php

assets/css/inventory.css
assets/css/sales.css

documentation/sql/W2 04 Create Tables.sql
documentation/sql/W2 05 Create Views and Procedures.sql
documentation/sql/W2 06 Seed Data.sql

DATABASE UPDATE

Assignment 1 must already be installed.

For a clean local rebuild of the complete project, run the SQL files in this order:

1. W1 01_create_tables.sql
2. W1 02_seed_data.sql
3. W1 03_views_and_procedures.sql
4. W2 04 Create Tables.sql
5. W2 05 Create Views and Procedures.sql
6. W2 06 Seed Data.sql

W1 01_create_tables.sql creates the local fnh_groceries database if it does not already exist. Each later SQL script selects fnh_groceries before making its changes, so the files can be run separately in the listed order.

The hosted Alwaysdata database is created by the hosting provider and uses its assigned hosted database name.

W2 04 extends the Week 1 schema for tax, checkout, transaction journaling, and sale-time product snapshots.

W2 05 creates the Week 2 views and procedures and updates affected Week 1 reporting and operator procedures.

W2 06 inserts the Week 2 demonstration data.

STORE STOCK LEVELS

Store Stock Levels shows the total quantity in the assigned store and detailed stock by department and product. Out-of-stock products remain visible.

Both Operators and Administrators with assigned access can view store stock levels.

NEW SALE AND REGISTER CONTROL

An operator selects an available register before starting a sale. The start time and transaction number are stored with the receipt.

Continue Sale resumes the current operator's open sale.

Cancel Sale voids the current operator's open sale, restores its inventory, and starts a fresh sale on the same register.

Close Register voids the current operator's open sale when necessary, restores inventory, and returns to register selection.

Clear Register is an Administrator-only action that cancels another operator's open sale, restores inventory, releases the register, and records the Administrator as the person who closed the transaction.

Ordinary Operators cannot add to, remove from, checkout, cancel, or clear another operator's transaction.

SALE ITEMS AND INVENTORY

Products can be added by product code or with the touch-oriented product controls.

Inventory is reduced when an item is added. The system refuses a quantity that is not available.

Removing an item restores the exact quantity removed to inventory.

Sale item removal uses ReceiptLineID so the exact receipt line is changed even if the same product appears on more than one line.

Each receipt line stores the product name, unit type, and tax status that applied when the item was added. Later product changes therefore do not rewrite the historical description or tax status of an existing receipt.

CHECKOUT

Checkout records the checkout time, calculates the sale, accepts cash tendered, calculates change, and marks the receipt Paid.

The sales tax rate is 7.75 percent and is rounded to the nearest cent.

Product.Taxable identifies products that are currently taxable. TaxableAtSale stores the tax status used for the receipt so later product changes do not change an existing sale.

ReceiptDiscountAmount remains available for future assignments. The current Week 2 interface does not enter discounts. Database checkout logic applies any stored receipt discount before tax.

TRANSACTION JOURNAL

transactionjournal keeps one summary row for every transaction.

It records who opened and closed the transaction, status, line count, item quantity, subtotal, discount, taxable subtotal, tax, total, payment method, amount tendered, and change.

Journal statuses are Open, Paid, Cancelled, and Cleared.

A Cleared transaction preserves the original operator as OpenedByOperatorID and records the Administrator who cleared it as ClosedByOperatorID.

ADMINISTRATOR CHANGES TO OPERATORS WITH OPEN SALES

Deleting or deactivating an operator with an open sale warns the Administrator before the change is made.

After confirmation, the open sale is cancelled, its inventory is restored, the register is released, the transaction journal records the Administrator who cleared it, and the operator is then made inactive.

Changing an operator's assigned store or changing access to No Access follows the same process when that operator has an open sale.

Changes that do not interfere with the open sale, such as name, email, phone, username, password, hire date, or changing between Operator and Administrator, do not cancel the sale and do not display the open-sale warning.

The cancellation and operator change are performed in one database transaction.

REPORTING COMPATIBILITY

Week 2 checkout uses Paid as the completed-sale status.

The affected reporting views accept both the original Week 1 Completed status and the Week 2 Paid status so Week 1 reports continue to include completed transactions.

PASSWORD RULE

Passwords require at least 8 characters with at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 symbol.

The validation rule and display text are centralized in includes/access_control.php.

JAVASCRIPT

Week 2 uses JavaScript for point-of-sale interface behavior, including leave-sale confirmation choices.

The Modify Operator page also uses JavaScript only to show the open-sale warning when a proposed store or access change would require the sale to be cancelled.

Security, permissions, validation, inventory changes, transaction ownership, and database updates are still enforced by PHP and stored procedures.