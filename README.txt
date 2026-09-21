FnH Groceries - CSC680 Assignment 1
Brian Phillips

WEBSITES

Live website on Alwaysdata:
https://csc680-fnhgroceries.alwaysdata.net/CSC_680/index.php

GitHub source:
https://github.com/Brainiacly/fnhgrocery

A copy of the complete project is also included in the submitted ZIP file.


HOSTED VERSION

The online version is hosted on alwaysdata and is connected to a MySQL/MariaDB database hosted 
there. The database login information is stored in the hosting environment and is not included 
in the project files. Demo login information is displayed on the website login page.

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

config/sql/01_create_tables.sql
config/sql/02_seed_data.sql
config/sql/03_views_and_procedures.sql

assets/css/styles.css
assets/css/layout.css
assets/css/index.css
assets/css/operators.css

assets/images/image1.png through image7.png

SCREENSHOTS

A small set of screenshots is included in:
documentation/screenshots/

01_database_schema.png
ER diagram reverse engineered in MySQL Workbench from the completed database, showing the tables and relationships.

02_view_operator_list.png
Query and results from vw_operatorlist showing operator information used by the administration pages.

03_view_inventory.png
Query and results from vw_storeinventorydetail showing store, product, department, category, and inventory information.

04_view_receipt_summary.png
Query and results from vw_receiptsummary showing summarized sales receipt information.

05_home_page.png
Public FnH Groceries home and login page.

06_operator_list.png
Administrator Operator List page showing the operator-management interface.

07_delete_operator.png
Delete Operator confirmation page.

08_delete_operator_success.png
Operator List showing that the selected operator was successfully made inactive.

DATABASE SETUP

Run the sql files in numeric order: 
Folder: config/sql 

    01_create_tables.sql
    02_seed_data.sql
    03_views_and_procedures.sql

The hosted Alwaysdata database is named:
csc680-fnhgroceries_fnh_groceries

The local XAMPP database is named:
fnh_groceries

JAVASCRIPT USE
JavaScript is used only on the Operator List page for small interface actions.
The All, Admins, and Operators dropdown submits the filter when the selection changes. The Inactive checkbox also submits the filter when it is changed. Without this small amount of JavaScript, an additional Apply or Show button would be needed.
JavaScript also watches the operator selection radio buttons. When an operator is selected, the appropriate buttons on the left side are enabled. If an inactive operator is selected, the Delete Operator button changes to Reactivate Operator and uses the reactivation page instead.
The JavaScript is only being used to make the Operator List easier and faster to use, especially on a touch screen. Login security, permissions, validation, and all database changes are still handled by PHP and the database procedures.

ADDITIONAL FUNCTIONALITY
Deleting an operator does not physically remove the employee from the database. The operator is marked inactive instead. This keeps older receipt and sales information connected to the correct employee.
An administrator can display inactive operators and reactivate an account when needed.
The system also prevents the currently logged-in administrator from deleting their own account and prevents the final active administrator from being deleted or changed to a lower access level.

RESPONSIVE AND TOUCH-SCREEN DESIGN
The site uses responsive CSS so the same pages adjust to smaller and larger screens. The main layout changes between compact, tablet, and desktop sizes. The header images and title resize, the navigation changes with the available width, and wide tables can scroll instead of forcing the entire page wider than the screen.
Buttons and navigation links have generous touch areas and also support keyboard focus. The website does not depend on hover-only actions. Checkboxes and radio buttons use clickable labels so the user can select them by touching the control or its label.

SUBMISSION

The submitted ZIP file contains the project source files and SQL files needed to rebuild the database.
