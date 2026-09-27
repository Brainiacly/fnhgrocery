FnH Groceries - CSC680 Assignment 1
Brian Phillips


- Completed in Week 1: The original FnH Groceries application, operator management, account management, access control, navigation, database structure, and responsive interface were completed as part of Week 1.

- Week 2 update: The Week 2 Point of Sale, register, checkout, inventory, and account updates were added to the existing Week 1 project.


MAIN PROJECT FILES

index.php
account.php
logout.php

operators/create.php
operators/list.php
operators/update.php
operators/delete.php
operators/reactivate.php

includes/access_control.php
includes/header.php
includes/nav.php
includes/footer.php

config/database.php

assets/css/styles.css
assets/css/layout.css
assets/css/index.css
assets/css/operators.css

assets/images/
    image1.png through image7.png

- Week 2: These files were added for the Week 2 Point of Sale and inventory functionality, along with minor updates to the other PHP and CSS files:

sales/new.php
sales/checkout.php
sales/complete.php

inventory/store_stock_levels.php

assets/css/sales.css
assets/css/inventory.css


VIDEO DEMONSTRATION

A short video demonstration is included.


DATABASE SETUP

The database and SQL were updated. To rebuild the database, run the SQL files in the order below. I rebuilt the database files from the database dump so the entire database can be recreated:

Folder: documentation/sql

    01_Database_and_Table_Creation.sql
    02_Views_and_Procedures.sql
    03_Seed_Data.sql

The local XAMPP database is named: fnh_groceries


JAVASCRIPT: JavaScript is used only for immediate interface responsiveness and to reduce unnecessary button clicks and page reloads. All security, permissions, validation, sales processing, inventory changes, and database updates are handled by PHP and the database procedures.

- Automatically refreshes the Operator List when the role filter or Show Inactive option changes.
- Enables the correct operator action buttons based on the selected operator, including Update, Delete, or Reactivate.
- Shows the Close Register button only when the selected register can be closed by the current user.
- Changes the quantity field to a weight field when a product sold by weight is selected.
- Warns when leaving a sale that contains items and gives the options to stay, save and leave, or close the register and cancel the transaction.


ADDITIONAL FUNCTIONALITY

- Completed in Week 1: Operators are made inactive instead of being deleted, inactive operators can be reactivated, and protections prevent removal of the current or final active administrator.

- Week 2 update: The project now includes register sessions, product selection, barcode and PLU entry, weighted products, inventory updates, transaction cancellation, checkout, and store stock viewing.


RESPONSIVE AND TOUCH-SCREEN DESIGN

- Completed in Week 1: The site uses responsive layouts, touch-friendly controls, keyboard focus, and scrollable tables for smaller screens.

- Week 2 update: The Point of Sale, product buttons, register controls, checkout, and inventory pages use the same responsive and touch-screen design.


SUBMISSION

The ZIP includes the original Week 1 project files with the Week 2 additions, one README file, the three SQL files needed to rebuild the database, and a short video demonstration.