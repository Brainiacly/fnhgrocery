FnH Groceries
CSC 680 | Brian Phillips

FnH Groceries is a point-of-sale system for a grocery store. It has a regular register, FnH Express for Curbside pickup and Home Delivery
orders, inventory management, discounts and coupons, a transaction viewer, employee and register management, and a Training Center with
a short lesson for each job. It runs on PHP, MySQL (MariaDB), HTML, CSS, and JavaScript.


GETTING IT RUNNING
1. Install XAMPP (PHP 8.1 or newer, Apache, and MariaDB or MySQL).
2. Copy this folder to C:\xampp\htdocs\CSC_680.
3. In phpMyAdmin or MySQL Workbench, run the three scripts in documentation/SQL/Local XAMPP DB SQL, in this order:
       01_Database_and_Table_Creation.sql, then 02_Views_and_Procedures.sql, then 03_Seed_Data.sql
   They wipe the fnh_groceries database and build it again from scratch, so back up anything you want to keep first.
   If a script reports an error, stop and fix it before running the next one.
4. Open http://localhost/CSC_680/index.php.

AlwaysData uses the same three scripts, from documentation/SQL/AlwaysData DB SQL (its database is csc680-fnhgroceries_fnh_groceries).
They rebuild the database too, so running them there also wipes what is in it.

Please keep database dumps, like development.sql, out of the project folder. Anything in a web folder can be downloaded by a visitor.


DEMO ACCOUNTS (USERNAMES AND PASSWORDS)
These are created by 03_Seed_Data.sql. The sign-in page does not list them.

    Username       Password         Role               Name
    Admin          Admin#123        Administrator      Admin Strator
    CheesyChuck    xLy2!*9E57kfuq   Administrator      Chuck Cheeesy
    ManAGer        #Testing123      Manager            Man A Ger
    TestBob        #Testing123      Operator           Bob Tester
    TestAlice      Testing123$      Operator           Alice Restaurant
    DellaVery      Shopper#123      Personal Shopper   Della Very

Usernames are not case sensitive, passwords are. Change these before the site goes anywhere public.


WHO CAN DO WHAT
                                                Administrator   Manager   Operator   Personal Shopper
    Regular register, discounts, coupons            yes           yes        yes          no
    Express orders                                  yes           yes        no           yes
    See every transaction in the store              yes           yes        own          own Express
    Open, assist, take over, or cancel another's    yes           yes        no           no
    Stock Levels                                    yes           yes        yes          yes
    Products and stock adjustments                  yes           yes        no           no
    Create coupons and turn them on or off          yes           no         no           no
    Employees: view, and see their transactions     yes           yes        no           no
    Employees: inactivate and reactivate            yes           yes (not Administrators)   no   no
    Employees: create, change, delete               yes           no         no           no
    Register Configuration                          yes           no         no           no
A Manager only works in their own store. A brand new account starts as Pending: it can sign in, but it has no access until an
Administrator gives it a role.


REGISTERS
Registers 1 to 3 are Express registers and 4 to 6 are Regular registers. Register 1 is always an active Express register.
The Administrator sets them up from Register Configuration, the last link in the menu. Pick a register from the dropdown (or Add New Register)
and a small form opens underneath for its number, name, type, and status. A register that has an open transaction can't be changed,
and at least one Regular register always stays active.


REGULAR SALES
The quantity box sits next to Add Item and also applies to the product buttons. Each line in the Current Sale list has a minus and a plus button.
The + UPC button shows the product codes (it turns into - UPC), and Close Register sits right beside it.
If someone asks for more than is in stock, the register adds what is there and says so. A sale is paid with Cash (with change) or Charge
(no cash, no change); a Charge is always for the exact total. Discounts and coupons are for the Administrator, Manager, and Operator,
on regular sales only.


COUPONS
Each coupon is for one product and has its own 12-digit UPC-A barcode that starts with 5. A coupon can need several of the product (buy 2, save $1.50).
A coupon can be used once per sale, an item only gets one coupon, and coupons come off before sale discounts and tax.
The Administrator creates coupons and sees how often each one was used, from Manage Inventory > Coupons. A coupon code can never be the same as
a product's UPC or PLU. The check digit and the barcode picture come from includes/discounts.php; see CREDITS AND SOURCES for where that code comes from.


FNH EXPRESS
The Express home page is an order board with four parts: Received, Picking, Ready, and Finished Today. Take New Order asks for the customer,
Curbside or Delivery, and a free Express register. An order moves to Picking as soon as its first item is added. Mark Ready needs at least
one item, and Checkout only shows up once the order is Ready. Adding or removing an item on a Ready order sends it back to Picking.
Express orders are paid in advance by Charge, so there is no cash box and no change. Home Delivery adds a fee and is only open during delivery hours.
An Express order holds its register until it is checked out or cancelled, and each shopper can have one open order at a time. With three
Express registers, that means at most three Express orders can be open together, even though the daily limit is 20.


SUPERVISORS (ADMINISTRATOR AND MANAGER)
On the details page of an open transaction that belongs to someone else, a supervisor picks how to open it:
    View Only   looks, and changes nothing
    Assist      adds items, discounts, or coupons. The transaction stays with its owner, who can still pick it back up
    Take Over   gives the transaction to the supervisor so they can check it out. They can Hand Back to the owner later
    Cancel      cancels the open transaction and puts its items back in stock
Every choice is saved and listed under Who Worked On This Transaction. Checkout and Mark Ready stay with the owner unless a supervisor takes over.
Inactivating an employee (Employees page) signs them out on their next click and blocks sign-in until they are reactivated. Their open
transactions stay open until someone takes them over or cancels them.


THE DATABASE
19 tables, 10 views, and 33 stored procedures. Pages read through views and queries. Every change to data goes through a stored procedure,
and the procedure checks the role, the store, and the business rules, so a rule can't be skipped by calling a page directly.
    Tables:      store, category, customer, customeraddress, department, product, operator, register, storeinventory, inventoryadjustment, discountreason, salesreceipt, expressorder, salesreceiptline, salesreceiptdiscount, coupon, salesreceiptcoupon, saleaccesslog, transactionjournal
    Views:       vw_storelist, vw_operatorlogin, vw_operatorlist, vw_pos_products, vw_store_stock, vw_sale_detail, vw_sale_discounts, vw_sale_coupons, vw_inventory_adjustments, vw_express_orders
    Procedures:  sp_create_operator, sp_update_operator, sp_update_own_account, sp_delete_operator, sp_reactivate_operator, sp_set_operator_active, sp_save_register, sp_start_sale, sp_add_sale_item, sp_remove_sale_item, sp_calculate_sale_totals, sp_get_sale_totals, sp_apply_discount, sp_remove_discount, sp_add_coupon_to_sale, sp_remove_coupon_from_sale, sp_checkout_sale, sp_void_sale, sp_take_over_sale, sp_hand_back_sale, sp_record_sale_access, sp_get_express_capacity, sp_create_express_customer, sp_remove_unused_express_customer, sp_create_customer_address, sp_update_customer_address, sp_create_express_order, sp_set_express_order_status, sp_create_product, sp_update_product, sp_adjust_inventory, sp_create_coupon, sp_set_coupon_active
Tables are lowercase, columns use CamelCase, procedures start with sp_, views with vw_, constraints with ck_ or fk_, and indexes with ix_.
No constraint or index name is longer than 20 characters.


HOW THE SQL SCRIPTS WERE MADE
The database lives in my local XAMPP database, fnh_groceries. It is not stored in the project folder, so the code files by themselves
don't show it. To get the scripts, I exported a dump of that database with MySQL's dump tool in MySQL Workbench, then split and edited it:
    01_Database_and_Table_Creation.sql   the table definitions, with their keys, checks, and indexes
    02_Views_and_Procedures.sql          the views and stored procedures, in one standard layout
    03_Seed_Data.sql                     the rows, with some records removed and some edited
I took some records out of the dump and edited others, so that a new database starts with clean sample data. Names were tidied into
one scheme, and views and procedures that no page uses were dropped.
The AlwaysData scripts are the same ones with the database name changed.

To see the database as it really looks, run 01, 02, and 03 in order on an empty database. I ran them in that order to make sure they build
everything the application needs. After that the data shows up in the application (the front end) and in phpMyAdmin.


CREDITS AND SOURCES
The base of the project is my own knowledge of PHP, MySQL, HTML, CSS, and JavaScript from CSC 680.

Register design. Other developers have already published design plans for point-of-sale registers. I read through them and looked under the hood
at how they were put together, so I'd understand how a register is laid out and how a sale flows: product buttons, a quantity, a running list of
items, discounts, and payment. The screens and the database in this project are made for FnH Groceries.

Coupon barcodes (the UPC converter). This is the code that works out a coupon's check digit and draws its barcode: upcCheckDigit, isCouponCode,
newCouponCode, and couponBarcodeSvg in includes/discounts.php. It is not a library, and it is not copied from another site. It was written for this
project from the public UPC-A rules published by GS1, the group that sets barcode standards:
    - A UPC-A code is 12 digits, and the last one is a check digit. To find it, add up the digits in positions 1, 3, 5, 7, 9, and 11,
      multiply that by 3, add the digits in positions 2, 4, 6, 8, and 10, and take whatever brings the total up to the next multiple of 10.
    - The picture is 95 narrow stripes: a start pattern (101), six digits on the left, a middle pattern (01010), six digits on the right, and
      an end pattern (101). Each digit has its own fixed 7-stripe pattern, and the right-hand digits use the same patterns flipped.
    - A first digit of 5 is the UPC number system used for coupons, so every coupon code here starts with 5.
    - The result is an SVG picture, so it stays sharp at any size and prints cleanly from the Coupons page.
To check it, the four sample coupons were drawn and then read back from the picture. Each one gave back its own 12 digits with a valid
check digit. It has not been tried on a physical barcode scanner.


FILES
    account.php                        My Account: change your own name, username, email, phone, and password
    index.php                          Sign-in page, and the Home page with a card for each area your account can use
    logout.php                         Signs you out and ends the session
    config/database.php                App name and URL, sign-in hours, business rules, and the database connection
    includes/access_control.php        Sessions, sign-in checks, role helpers, form tokens, and small shared helpers
    includes/access_denied.php         The Access Denied page, shown inside the normal layout
    includes/discounts.php             Discount and coupon helpers, including the coupon check digit and barcode picture
    includes/footer.php                Page footer, and the close button on every message box
    includes/header.php                Page header, the stylesheets for each area, and the left menu
    includes/item_entry.php            The quantity box, product buttons, sale lines, and script shared by the register and Express order pages
    includes/nav.php                   The left menu and the employee action buttons
    sales/sale_checkout.php            Payment page for a regular sale (Cash or Charge)
    sales/sale_complete.php            Receipt for a finished regular sale
    sales/sale_new.php                 The regular register: choose a register, add items, discounts, and coupons
    express/ex_checkout.php            Payment page for an Express order (always a Charge)
    express/ex_complete.php            Receipt for a finished Express order
    express/ex_home.php                Express home: the order board by status, and the Express stock list
    express/ex_new.php                 Take a new Express order: customer, Curbside or Delivery, and Express register
    express/ex_order.php               One Express order: add items, Mark Ready, Checkout, Cancel
    express/ex_orders.php              Express orders for today and earlier days
    inventory/inv_adjust.php           Add, remove, or set stock, with a reason
    inventory/inv_coupons.php          Administrator: create coupons, print their barcodes, turn them on or off, see how often they were used
    inventory/inv_manage.php           Manage Inventory: find products, add a product, adjust stock, open Coupons (Administrator)
    inventory/inv_product.php          Add or edit a product
    inventory/inv_stock.php            Stock Levels for everyone, with pounds and each items kept apart
    operators/op_access.php            Manager and Administrator: inactivate or reactivate an employee, after a confirmation
    operators/op_create.php            Administrator: create an employee
    operators/op_delete.php            Administrator: delete (deactivate) an employee
    operators/op_list.php              Employees: the list and its action buttons (Administrator and Manager)
    operators/op_reactivate.php        Administrator: reactivate an employee
    operators/op_update.php            Administrator: change an employee
    registers/reg_setup.php            Administrator: pick a register, or Add New Register, then change its settings
    transactions/tr_list.php           Transaction Viewer, with search, filters, and the per-employee view
    transactions/tr_view.php           Transaction details; supervisors choose View Only, Assist, Take Over, Hand Back, or Cancel here
    training/tc_data.php               The text of every training topic and movie
    training/tc_home.php               Training Center: the movies and the topics for your role
    training/tc_topic.php              One training topic, with its steps and a link into the movie
    assets/css/express.css             Express board, order, and payment pages
    assets/css/index.css               Sign-in and Home pages
    assets/css/inventory.css           Inventory and coupon pages
    assets/css/layout.css              Page header, left menu, and content area
    assets/css/operators.css           Employees pages
    assets/css/print.css               Printing receipts and transaction details
    assets/css/registers.css           Register Configuration page
    assets/css/sales.css               Register, payment pages, and the Leave box
    assets/css/styles.css              Shared colors, sizes, buttons, boxes, messages, and forms
    assets/css/training.css            Training Center
    assets/css/transactions.css        Transaction Viewer and details
    documentation/SQL                  The Local XAMPP and AlwaysData versions of the table, view and procedure, and seed scripts
    training/movies                    The training movies (MP4) named in training/tc_data.php

Every PHP file starts with a comment holding its own path, and every PHP and CSS file names its author and the course. File names follow one
scheme per area: op_ for employees, inv_ for inventory, ex_ for Express, sale_ for the register, tr_ for transactions, tc_ for training, reg_ for registers.


SECURITY NOTES
Passwords are stored as hashes. Every form that changes data carries a one-time token. Include files and the configuration file answer with a 404
if someone opens them directly. Each page checks your role before it does anything, and the database procedures check it again.
The sign-in expires after the number of hours set in config/database.php.

Charge is a pretend payment method. There is no card processor, and the sample receipts are demonstrations, not real purchases.