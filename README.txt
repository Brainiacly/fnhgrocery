FnH Groceries - Final Project
CSC 680 | Brian Phillips

A web-based point-of-sale (POS) system for a grocery store.
The project includes regular checkout, FnH Express, and store management.
The backend uses PHP and MySQL.
The interface uses HTML, CSS, and JavaScript.

Hosted website
https://csc680-fnhgroceries.alwaysdata.net/CSC_680/index.php

Local website
http://localhost/CSC_680/index.php


1. LOCAL INSTALLATION

   1. Install XAMPP with Apache, PHP 8.1 or newer, and MySQL or MariaDB.
   2. Copy the project to C:\xampp\htdocs\CSC_680.
   3. Start Apache and MySQL in XAMPP.
   4. In phpMyAdmin or MySQL Workbench, run the following files in order.
      Location  documentation/SQL/Local XAMPP DB SQL
      - 01_Database_and_Table_Creation.sql
      - 02_Views_and_Procedures.sql
      - 03_Seed_Data.sql
   5. Open http://localhost/CSC_680/index.php.

   The first script replaces the fnh_groceries database.
   Back up any existing data first. Stop if a script fails.


2. DEMO ACCOUNTS

   These accounts come from 03_Seed_Data.sql.
   Usernames are not case-sensitive. Passwords are case-sensitive.

   Username      Password         Role                 Name
   Admin         Admin#123        Administrator        Admin Strator
   CheesyChuck   xLy2!*9E57kfuq  Administrator        Chuck Cheeesy
   ManAGer       #Testing123      Manager              Man A Ger
   TestBob       #Testing123      Operator             Bob Tester
   TestAlice     Testing123$      Operator             Alice Restaurant
   DellaVery     Shopper#123      Personal Shopper     Della Very

   Passwords require at least eight characters.
   Each must contain uppercase and lowercase letters.
   A number and a symbol are also required.
   These are demonstration accounts. Change the passwords before real use.


3. FEATURES AND ACCESS

   Regular sales
   - Administrators, Managers, and Operators can use regular checkout.
   - Scan or select products, adjust quantities, and apply discounts or coupons.
   - Payment is by cash or simulated charge. Cash checkout calculates change.

   FnH Express
   - Administrators, Managers, and Personal Shoppers can take Express orders.
   - Orders move from Received to Picking to Ready before checkout.
   - Curbside pickup and home delivery are supported.
   - Delivery costs $10 and is offered from 8 AM to 4 PM.
   - The daily limit is 20 orders. Express checkout uses simulated charge.

   Inventory and administration
   - Active staff can view stock levels.
   - Administrators and Managers can maintain products and stock quantities.
   - Administrators manage coupons, employee accounts, and registers.
   - Managers can inactivate or reactivate non-administrator staff in their store.
   - Managers cannot manage Administrators. One active Administrator must remain.

   Registers
   - Administrators can add, update, or inactivate registers.
   - Registers 1 to 3 start as Express. Registers 4 to 6 start as Regular.
   - Register 1 remains active for Express only.
   - A register with an open sale cannot be changed.

   Other features
   - Staff can view their transactions. Supervisors can assist with open sales.
   - The Training Center contains instructions and linked training videos.
   - Pending accounts have no operational access.


4. JAVASCRIPT USE AND WHY

   JavaScript updates page controls immediately without a full reload.
   PHP and the database still handle validation, access, and saved changes.

   - operators/op_list.php
     Updates employee action buttons when a person is selected.
     Applies list filters immediately. This reduces clicks on touch screens.

   - operators/op_update.php
     Warns about changes to an employee's store or access.
     This helps prevent accidental changes to open transactions.

   - sales/sale_new.php
     Updates register actions and discount fields.
     Handles the leave-sale choices so an open sale is not lost by accident.

   - sales/sale_checkout.php
     Shows the correct payment fields for Cash or Charge.
     Handles navigation away from an unfinished checkout.

   - includes/item_entry.php
     Updates product selection, quantity, and weighted-item fields.
     Shows product codes without opening another page.

   - express/ex_new.php
     Switches between new and existing customers.
     Shows delivery fields and checks saved-address changes.

   - registers/reg_setup.php and includes/footer.php
     Loads a selected register and closes messages without extra steps.

   - Sale and Express transaction pages
     Ask for confirmation before selected cancellation or takeover actions.
     This helps prevent accidental changes.

   JavaScript is used for faster form interaction and clear on-screen feedback.
   Bootstrap is not used.


5. DATABASE

   Database name  fnh_groceries
   Structure      19 tables, 10 views, 33 stored procedures

   - Views and parameterized queries retrieve data.
   - Stored procedures handle changes and enforce business rules.
   - Procedures also check the operator's role and store.

   Database rebuild
   1. A SQL dump was exported with MySQL Workbench.
   2. ChatGPT was used to sort the dump and help prepare the rebuild scripts.
   3. The three scripts were reviewed and run locally.

   The rebuild scripts are included with the project.
   Sample data was cleaned for installation and testing.


6. DEVELOPMENT TOOLS AND SOURCES

   Formatter
   - DEVSENSE PHP Tools was used for PHP and embedded HTML formatting.
   - https://docs.devsense.com/vscode/formatting/

   Register design
   - Online POS examples were reviewed for layout and sale flow.
   - The FnH screens and database were built for this project.

   Coupon barcode generator
   - PHP barcode examples on GitHub were reviewed during development.
   - Reference example  https://github.com/picqer/php-barcode-generator
   - UPC-A rules  https://www.gs1us.org/tools/check-digit-calculator
   - GS1 standards  https://ref.gs1.org/standards/genspecs/
   - The barcode functions are in includes/discounts.php.
   - The code calculates the UPC-A check digit and draws an SVG barcode.
   - No external barcode library is installed.
   - Physical barcode scanner testing was not performed.


7. PROJECT FILES

   The source code and database rebuild files are included.

   - sales/ and express/ contain checkout and ordering pages.
   - inventory/ contains products, stock, and coupons.
   - operators/ and registers/ contain administration pages.
   - transactions/ contains transaction history and details.
   - training/ contains training pages and movie references.
   - includes/ and config/ contain shared code and settings.
   - assets/css/ contains the stylesheets.
   - documentation/SQL/ contains the local database rebuild files.


8. SECURITY AND TESTING

   - Passwords are stored as hashes. Forms use security tokens.
   - Access checks are applied in PHP and stored procedures.
   - Simulated charge payments do not use a payment processor.
   - Sales and receipts are for demonstration, not real purchases.