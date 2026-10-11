<?php // training/tc_data.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

// Each training topic has its own short MP4 video.
// Save the video in training/movies using the topic key as its filename.
// Example: login_logout => training/movies/login_logout.mp4.
// Video players and directions are shown on lesson pages only. No timestamps are needed.
return [
    'sections' => [
        'orientation' => [
            'title' => 'Signing In and Finding Your Way',
            'summary' => 'Signing in, navigation, account settings, and training help.'
        ],
        'regular' => [
            'title' => 'Regular Register and Customer Sale',
            'summary' => 'Starting sales, handling items, applying discounts, and checkout.'
        ],
        'express' => [
            'title' => 'FnH Express Orders',
            'summary' => 'Curbside pickup, delivery, order progress, and Express rules.'
        ],
        'inventory' => [
            'title' => 'Stock Levels and Inventory',
            'summary' => 'Stock checks, product changes, and inventory adjustments.'
        ],
        'employees' => [
            'title' => 'Managing Employees and Permissions',
            'summary' => 'Employee accounts, access levels, and employee status.'
        ],
        'registers' => [
            'title' => 'Managing Checkout Registers',
            'summary' => 'Register creation, configuration, and activation.'
        ],
        'additional' => [
            'title' => 'Transactions, Coupons, and Supervisor Tools',
            'summary' => 'Transaction history, supervisor tools, and coupon management.'
        ]
    ],
    'topics' => [
        'login_logout' => [
            'title' => 'Login and Logout',
            'assignment' => 'Assignment 1',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Sign in, recognize your account, and sign out safely.',
            'steps' => [
                'On the FnH Groceries welcome page, type your username and password into the login fields.',
                'Click Login to open your Home page. Check the signed-in username and privilege.',
                'Use a menu link, such as Stock Levels, to see an area your account can open.',
                'Click Logout in the upper-right corner to return to the sign-in page.',
            ],
            'section' => 'orientation',
        ],
        'top_level_menu' => [
            'title' => 'Use the Main Menu',
            'assignment' => 'Assignments 2 and 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Compare the Home cards and left menu for different staff roles.',
            'steps' => [
                'Sign in as an Operator and look at the available Home cards.',
                'Use the left menu to open an area such as Stock Levels, then return to Home.',
                'Sign out and sign in as a Personal Shopper.',
                'Compare the Home cards and menu. Express Orders is offered to Personal Shoppers, while regular checkout is not.',
            ],
            'section' => 'orientation',
        ],
        'account' => [
            'title' => 'My Account',
            'assignment' => 'Assignments 1 to 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Review your details and save changes to your own account.',
            'steps' => [
                'Sign in and click My Account on the left.',
                'Review the employee number, assigned store, and privilege. These fields cannot be changed here.',
                'Edit a permitted field, such as your middle initial or contact information.',
                'Click Save Changes. Confirm the update message appears.',
                'If changing your password, provide the current password and matching new password before saving.',
            ],
            'section' => 'orientation',
        ],
        'training_center' => [
            'title' => 'Use the Training Center',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Open a lesson, play its silent video, and read the steps below.',
            'steps' => [
                'Click Training Center in the left menu.',
                'On the current page, find the subject heading and click a lesson box.',
                'Press Play inside that lesson. Videos do not play on the Training Center home page.',
                'Read the numbered directions below the video.',
                'Use Previous, Next, or All Lessons. The first and last lessons connect in a loop.',
            ],
            'section' => 'orientation',
        ],
        'create_operator' => [
            'title' => 'Create an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Create an employee account using the Administrator employee form.',
            'steps' => [
                'Sign in as an Administrator and click Employees.',
                'Click Create Employee.',
                'Select the assigned store and enter a unique username.',
                'Fill in the employee name, email, role, hire date, and a password that meets the rules.',
                'Click Create Employee or Save on the form.',
                'Return to the employee list and verify that the account appears.',
            ],
            'section' => 'employees',
        ],
        'update_operator' => [
            'title' => 'Change an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Select an employee, modify their details, and verify the saved account.',
            'steps' => [
                'As an Administrator, open Employees.',
                'Select an employee using the circle beside their name.',
                'Click Modify Employee.',
                'Edit a permitted field in the employee form.',
                'Save the change, then return to the list and check the updated record.',
            ],
            'section' => 'employees',
        ],
        'delete_operator' => [
            'title' => 'Administrator: Deactivate an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Deactivate a test employee and restore the account afterward.',
            'steps' => [
                'Sign in as an Administrator and open Employees.',
                'Select the employee you want to deactivate.',
                'Click Delete Employee or Deactivate Employee and confirm.',
                'Use the Inactive filter to find that employee again.',
                'Select the inactive account, click Reactivate Employee, and confirm.',
                'Check that the account is listed as Active again.',
            ],
            'section' => 'employees',
        ],
        'supervisor_open' => [
            'title' => 'Open Another Employee\'s Transaction',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'Find another employee’s open sale, review it, and access the supervisor controls.',
            'steps' => [
                'As an Operator, start a Regular sale with test items and leave it open using Save.',
                'Sign out, then sign in as a Manager or Administrator.',
                'Open Transactions and find the sale that was left open by the Operator.',
                'Click Open to review the transaction details and the available supervisor actions.',
                'Use the supervisor controls to access the open register when assistance is needed.',
                'Return to Transaction Details to review the status and transaction history.',
            ],
            'section' => 'additional',
        ],
        'employee_access' => [
            'title' => 'Manager: Employee Access and History',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'Review staff transactions and change a non-Administrator account’s access.',
            'steps' => [
                'Sign in as a Manager and click Employees.',
                'Choose a non-Administrator employee using the selection circle.',
                'Click View Transactions and review the employee\'s sales in Transaction Viewer.',
                'Return to Employees to change the employee\'s access.',
                'Click Inactivate Employee or Reactivate Employee, as appropriate, and confirm.',
                'Check the updated status in the employee list. Managers cannot change Administrator accounts.',
            ],
            'section' => 'employees',
        ],
        'registers' => [
            'title' => 'Set Up Registers',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Edit an Express or Regular register, save it, and change its active status.',
            'steps' => [
                'Sign in as an Administrator and click Register Configuration.',
                'Choose an existing register or Add New Register from the dropdown.',
                'Enter or change the register number, name, type, and Active/Inactive status.',
                'Click Save Register or Add Register as appropriate.',
                'Check the saved message, then select the register again to verify the setting.',
                'Open New Sale to see which active Regular registers are available.',
            ],
            'section' => 'registers',
        ],
        'stock_levels' => [
            'title' => 'View Store Stock Levels',
            'assignment' => 'Assignment 2',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Find store products, quantities, locations, and out-of-stock items.',
            'steps' => [
                'Click Stock Levels on the left.',
                'Read Total Stock on Hand at the top of the page.',
                'Scroll through the table to locate a product and its department.',
                'Check the quantity, unit type, price, aisle, section, and shelf location.',
                'Look for the Out of Stock marking on products with no stock available.',
            ],
            'section' => 'inventory',
        ],
        'regular_sale' => [
            'title' => 'Ring Up a Sale',
            'assignment' => 'Assignment 2',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Select a Regular register and add groceries using product buttons or codes.',
            'steps' => [
                'Sign in as an Operator, click New Sale, and select an available Regular register.',
                'Click Open Register to start the sale.',
                'Click a product button, or enter its UPC or PLU in Barcode / Product Code and click Add Item.',
                'Set Quantity before adding several units of the same product.',
                'Use the + and - buttons in Current Sale to change an item quantity.',
                'For items sold by weight, enter the requested weight in pounds. Check the updated subtotal.',
            ],
            'section' => 'regular',
        ],
        'leave_sale' => [
            'title' => 'Leave a Sale You Have Not Finished',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators, Managers, and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Leave an unfinished sale and return without losing its items.',
            'steps' => [
                'Open a Regular register and add at least one item.',
                'Click another page in the left menu while the sale is still open.',
                'When Leave Current Transaction? appears, choose Stay to keep editing, or Save to leave it open.',
                'After Save, click Continue Sale to reopen the saved sale.',
                'Choose Close only when you intend to cancel the sale and return items to stock.',
            ],
            'section' => 'regular',
        ],
        'discounts' => [
            'title' => 'Apply Discounts to a Sale',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Apply a dollar discount before tax and check the updated sale total.',
            'steps' => [
                'Open a Regular sale and add a product.',
                'Click Discount below Current Sale.',
                'Choose Dollar amount off ($), select Before tax, and enter an amount such as 1.00.',
                'Choose a reason and click Apply Discount.',
                'Check that the discount appears under the items and lowers the estimated total.',
                'Use the x beside the discount if you need to remove it.',
            ],
            'section' => 'regular',
        ],
        'coupons' => [
            'title' => 'Use Coupons',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Apply a product coupon, check the reduction, and remove it.',
            'steps' => [
                'Start a Regular sale and add the product associated with the coupon, such as Cereal.',
                'Click Barcode / Product Code and scan or type the coupon barcode.',
                'Click Add Item if the barcode was typed instead of scanned.',
                'Check that a Coupon line appears with its dollar reduction.',
                'Click the x beside the coupon to remove it and check the updated total.',
            ],
            'section' => 'regular',
        ],
        'checkout' => [
            'title' => 'Check Out a Regular Sale',
            'assignment' => 'Assignment 2',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Take a cash payment, then check the receipt and change.',
            'steps' => [
                'In the Regular register, add the items and click Checkout.',
                'Review the subtotal, discounts, sales tax, and Total Due.',
                'Select Cash under Payment.',
                'Enter the Cash Tendered amount, making it at least the total due.',
                'Click Complete Sale and wait for the completed receipt.',
                'Read the amount tendered and change on the receipt. Open Transactions to review the completed sale.',
            ],
            'section' => 'regular',
        ],
        'express_orders' => [
            'title' => 'Take a Curbside Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Create a curbside order, add items, mark it Ready, and finish checkout.',
            'steps' => [
                'Click Express Orders, then Take New Order.',
                'Choose an available Express register and select an existing customer.',
                'Keep Curbside Pickup selected and click Create Order.',
                'Add groceries with the product buttons or Barcode / Product Code field.',
                'Use + or - to adjust quantities. The order moves from Received to Picking when items are added.',
                'Click Mark Ready once picking is complete, then Checkout.',
                'Confirm the completed Express order and return to the board.',
            ],
            'section' => 'express',
        ],
        'express_delivery' => [
            'title' => 'Take a Home Delivery Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Enter a delivery address, add items, and prepare the order for checkout.',
            'steps' => [
                'Click Express Orders, then Take New Order. Choose an available Express register.',
                'Choose an existing customer and click Load Customer.',
                'Select Home Delivery.',
                'Enter the street address, city, state, and ZIP Code into the delivery fields.',
                'Click Create Order after reviewing the address and delivery fee.',
                'Add groceries, then click Mark Ready when the order is complete.',
                'Open the order or Transactions to review its delivery status and details.',
            ],
            'section' => 'express',
        ],
        'express_today' => [
            'title' => 'See Today\'s Express Orders and Stock',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Check Express capacity, the order board, stock, and today’s orders.',
            'steps' => [
                'Click Express Orders to open the order board.',
                'Read the number of orders remaining from the 20-order daily limit.',
                'Look at the Received, Picking, and Ready columns.',
                'Scroll down to the stock list to check which products are available.',
                'Click View Today\'s Orders to see order status and fulfillment method.',
            ],
            'section' => 'express',
        ],
        'express_cancel' => [
            'title' => 'Cancel an Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Create a test Express order, cancel it, and check its final status.',
            'steps' => [
                'Click Express Orders and Take New Order. Choose an available Express register.',
                'Select or enter a test customer and choose the fulfillment method.',
                'Enter a delivery address if Home Delivery is chosen, then click Create Order.',
                'Add an item and open the Express order details.',
                'Click Cancel Order and confirm the cancellation.',
                'Return to the Express board or Transactions and verify that the order is Cancelled.',
                'Check inventory if you need to confirm that its items were returned to stock.',
            ],
            'section' => 'express',
        ],
        'express_rules' => [
            'title' => 'Express Rules at a Glance',
            'assignment' => 'Assignment 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'See available Express registers, order limits, and delivery-hour restrictions.',
            'steps' => [
                'Sign in as a Personal Shopper and open Express Orders.',
                'Read the daily capacity counter on the board.',
                'Click Take New Order and inspect the available Express registers.',
                'Review the Curbside Pickup and Home Delivery choices.',
                'Notice that Home Delivery cannot be submitted outside the configured delivery hours.',
            ],
            'section' => 'express',
        ],
        'inventory_management' => [
            'title' => 'Manage Products and Inventory',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'Create a test product and adjust its recorded inventory.',
            'steps' => [
                'Sign in as a Manager or Administrator and click Manage Inventory.',
                'Use the search and filters to find a product, or click Add New Product.',
                'Enter a department, product name, UPC or PLU, unit, price, tax setting, and starting inventory.',
                'If the form reports an invalid starting quantity, correct the quantity and save again.',
                'Click Edit to review product information, or Adjust to change stock.',
                'Choose Add, Remove, or Set; enter the quantity and reason; then save.',
                'Open Stock Levels to check the resulting quantity.',
            ],
            'section' => 'inventory',
        ],
        'coupons_manage' => [
            'title' => 'Create and Manage Coupons',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Create a product coupon, use its barcode, and manage the coupon list.',
            'steps' => [
                'Sign in as an Administrator, click Manage Inventory, then Coupons.',
                'Choose the product, required item quantity, discount type, and amount.',
                'Enter a description and optional start/end dates.',
                'Click Create Coupon and find the newly generated barcode in the list.',
                'Open a Regular sale and add the required quantity of that product.',
                'Scan or enter the new coupon barcode and check the discount.',
                'Return to Coupons to review the Used count or change its active status.',
            ],
            'section' => 'additional',
        ],
        'transaction_viewer' => [
            'title' => 'Look Up a Transaction',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Search the transaction list, filter results, and open the details.',
            'steps' => [
                'Click Transactions in the left menu.',
                'Type a transaction number, customer, or employee in Search if needed.',
                'Choose Type, Status, and optional From/To dates, then click Apply.',
                'Find the matching transaction and click Open.',
                'Read its item lines, discounts, tax, payment method, total, and any change.',
            ],
            'section' => 'additional',
        ]
    ]
];