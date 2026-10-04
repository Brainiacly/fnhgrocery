FnH Groceries - CSC680 Assignment 1, 2, and 3
Brian Phillips


- Completed in Week 1: The original FnH Groceries application, operator management, account management, access control, navigation, database structure, and responsive interface were completed as part of Week 1.

- Week 2 update: The Week 2 Point of Sale, register, checkout, inventory, and account updates were added to the existing Week 1 project.

- Week 3 update: The Week 3 FnH Express curbside pickup and home delivery functionality was added to the existing Week 1 and Week 2 project.


WEBSITES

Live website on Alwaysdata:
https://csc680-fnhgroceries.alwaysdata.net/CSC_680/index.php

GitHub source:
https://github.com/Brainiacly/fnhgrocery


MAIN PROJECT FILES

index.php
account.php
logout.php

operators/create.php
operators/operator_list.php
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

assets/images/image1.png
assets/images/image2.png
assets/images/image3.png
assets/images/image4.png
assets/images/image5.png
assets/images/image6.png
assets/images/image7.png

- Week 2: These files were added for the Week 2 Point of Sale and inventory functionality, along with minor updates to the other PHP and CSS files:

sales/sale_new.php
sales/sale_checkout.php
sales/sale_complete.php

inventory/stock_levels.php

assets/css/sales.css
assets/css/inventory.css

- Week 3: These files were added for the FnH Express functionality, along with minor updates to the other PHP and CSS files:

express/ex_home.php
express/ex_new.php
express/order.php
express/orders.php
express/ex_checkout.php
express/ex_complete.php

assets/css/express.css


SCREENSHOTS

Screenshots demonstrating the project functionality are included in:
documentation/Screenshots

Week 1 screenshots are located in:
documentation/Screenshots/Week 1

Week 2 screenshots demonstrating the Week 2 Point of Sale, register, checkout, and inventory functionality are located in:
documentation/Screenshots/Week 2

The Week 2 screenshots were taken using the local XAMPP development database.

The local development database and the live Alwaysdata database began with the same data and numbering. Development and testing have since caused the two databases to become out of sync, so transaction numbers, record numbers, and other changing data shown in the screenshots may differ from the live website.

Both the local XAMPP database and the live Alwaysdata database are kept current throughout development. The same SQL files are run against both after each update, so either one can be used for testing.


DATABASE SETUP

The database and SQL were updated. To rebuild the database, run the SQL files in the order below. I rebuilt the database files from the database dump so the entire database can be recreated.

The SQL files are located in:
documentation/SQL

There are two sets of the SQL files, one for the local XAMPP database and one for the Alwaysdata database. The SQL is the same in both sets except for the database name and the database creation statement required by the local XAMPP version.

The local XAMPP SQL files are located in:
documentation/SQL/Local XAMPP DB SQL

The Alwaysdata SQL files are located in:
documentation/SQL/AlwaysData DB SQL

Each set contains:

    01_Database_and_Table_Creation.sql
    02_Views_and_Procedures.sql
    03_Seed_Data.sql

The local XAMPP database is named:
fnh_groceries

The Alwaysdata database is named:
csc680-fnhgroceries_fnh_groceries

The local XAMPP and Alwaysdata databases began with the same data. Both are updated and tested together as development continues, using the same SQL files.


JAVASCRIPT

JavaScript is used only for immediate interface responsiveness and to reduce unnecessary button clicks and page reloads. All security, permissions, validation, sales processing, inventory changes, and database updates are handled by PHP and the database procedures.

- Automatically refreshes the Operator List when the role filter or Show Inactive option changes.
- Enables the correct operator action buttons based on the selected operator, including Update, Delete, or Reactivate.
- Shows the Close Register button only when the selected register can be closed by the current user.
- Changes the quantity field to a weight field when a product sold by weight is selected.
- Warns when leaving a sale that contains items and gives the options to stay, save and leave, or close the register and cancel the transaction.
- Shows the Existing Customer or New Customer fields on the Express order form based on the option selected.
- Asks whether to update a customer's saved address on file when a loaded address is changed before an Express order is submitted.
- Warns on the Operator Update page when the store or role change would cancel the operator's open sale.
- Confirms before removing an item, cancelling a sale, or closing a register.


ADDITIONAL FUNCTIONALITY

- Completed in Week 1: Operators are made inactive instead of being deleted, inactive operators can be reactivated, and protections prevent removal of the current or final active administrator.

- Week 2 update: The project now includes register sessions, product selection, barcode and PLU entry, weighted products, inventory updates, transaction cancellation, checkout, and store stock viewing.

- Week 3 update: The project now includes FnH Express, a curbside pickup and home delivery workflow handled by a new Personal Shopper role. Register 1 is reserved exclusively for Express orders and is not available at the regular registers. Each store allows up to 20 Express orders per day, home delivery adds a $10.00 fee and is only available for orders placed between 8:00 AM and 4:00 PM, and each shopper may have only one open Express order at a time. Customers can save multiple named delivery addresses, such as Home or Work, for reuse on future orders, and an Administrator can open and complete another shopper's Express order if needed.


RESPONSIVE AND TOUCH-SCREEN DESIGN

- Completed in Week 1: The site uses responsive layouts, touch-friendly controls, keyboard focus, and scrollable tables for smaller screens.

- Week 2 update: The Point of Sale, product buttons, register controls, checkout, and inventory pages use the same responsive and touch-screen design.

- Week 3 update: The Express pages, including the capacity display, order forms, and checkout, use the same responsive and touch-screen design as the rest of the site.


SUBMISSION

The ZIP includes the original Week 1 project files with the Week 2 and Week 3 additions, one README file, both sets of the three SQL files needed to rebuild the local XAMPP or Alwaysdata database, and screenshots demonstrating the Week 1, Week 2, and Week 3 functionality.