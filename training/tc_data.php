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

// Each use case names its full movie and the second at which its segment starts.
// Leave start => null while filming. AFTER editing, enter a whole number of seconds
// (e.g. 02:35 = 155). Topic pages will then seek to the right part of the movie.
// Keep each segment ending with a spoken closing line and 2-3 seconds of silence.
return [
    'movies' => [
        'orientation' => [
            'title' => '1. Signing In and Finding Your Way',
            'file' => '01_sign_in_and_navigation.mp4',
            'summary' => 'Compare roles and navigation, use My Account and the Training Center, and sign out safely.'
        ],
        'regular' => [
            'title' => '2. Regular Register and Customer Sale',
            'file' => '02_regular_checkout.mp4',
            'summary' => 'Open a regular register, scan or tap products, apply a coupon and discount, and take payment.'
        ],
        'express' => [
            'title' => '3. FnH Express Orders',
            'file' => '03_express_orders.mp4',
            'summary' => 'Take, pick, prepare, and check out curbside and delivery orders, including the Express rules.'
        ],
        'inventory' => [
            'title' => '4. Stock Levels and Inventory',
            'file' => '04_inventory.mp4',
            'summary' => 'Check stock, add or edit products, and add, remove, or set quantities with recorded reasons.'
        ],
        'employees' => [
            'title' => '5. Managing Employees and Permissions',
            'file' => '05_employee_management.mp4',
            'summary' => 'Create, change, inactivate, and reactivate accounts; compare Administrator and Manager authority.'
        ],
        'registers' => [
            'title' => '6. Managing Checkout Registers',
            'file' => '06_register_management.mp4',
            'summary' => 'Add and update registers, choose Regular or Express, and deactivate safely.'
        ],
        'additional' => [
            'title' => '7. Transactions, Coupons, and Supervisor Tools',
            'file' => '07_transactions_and_coupons.mp4',
            'summary' => 'Find receipts, inspect and assist with open transactions, and administer coupon definitions.'
        ]
    ],
    'topics' => [
        'login_logout' => [
            'title' => 'Login and Logout',
            'assignment' => 'Assignment 1',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Sign in with your own account, check your name on the page, and log out when you step away.',
            'steps' => [
                'Open the FnH Groceries home page and enter your username and password.',
                'Press Login and check that your username appears at the top of the page.',
                'Use the left menu to move around. It only offers what your role allows.',
                'Press Logout before you leave the workstation.'
            ],
            'movie' => 'orientation',
            'start' => null
        ],
        'top_level_menu' => [
            'title' => 'Use the Main Menu',
            'assignment' => 'Assignments 2 and 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'See how the home page and left menu change for each role.',
            'steps' => [
                'Sign in and read the username and privilege shown on the home page.',
                'Look at the menu cards. Each card opens an area your role can use.',
                'Use the left menu to switch between areas.',
                'Notice that areas outside your role are not offered.'
            ],
            'movie' => 'orientation',
            'start' => null
        ],
        'account' => [
            'title' => 'My Account',
            'assignment' => 'Assignments 1 to 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Review your own details and change the contact information and password you may change.',
            'steps' => [
                'Choose My Account from the left menu.',
                'Review your store, employee number, and privilege. An Administrator controls those.',
                'Update your contact details and save.',
                'To change your password, enter the current one and a valid new one, then save.'
            ],
            'movie' => 'orientation',
            'start' => null
        ],
        'training_center' => [
            'title' => 'Use the Training Center',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Find the training movies and the lesson for each job you do.',
            'steps' => [
                'Choose Training Center from the left menu.',
                'Training Movies lists the seven full videos relevant to your role. Press Watch Full Video to start a complete video.',
                'Training Topics lists a lesson for each job you do. Press a topic to read its steps.',
                'Open an individual topic to play its related movie. Once timestamps are entered, playback starts at the correct segment.',
                'Use the Training Center button below the lesson to go back.'
            ],
            'movie' => 'orientation',
            'start' => null
        ],
        'create_operator' => [
            'title' => 'Create an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Add a new employee account with a store, a role, and a password that meets the rules.',
            'steps' => [
                'Sign in as an Administrator and open Employees.',
                'Press Create Employee.',
                'Enter the name, username, email, role, hire date, and password. Contact details are optional.',
                'Save, then check that the new account appears in the employee list.'
            ],
            'movie' => 'employees',
            'start' => null
        ],
        'update_operator' => [
            'title' => 'Change an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Update an existing employee\'s account, store, role, or contact information.',
            'steps' => [
                'Open Employees and select an employee.',
                'Press Modify Employee.',
                'Change what needs to change. The page warns you if the change would cancel an open sale.',
                'Save, then reopen the employee to confirm the new values.'
            ],
            'movie' => 'employees',
            'start' => null
        ],
        'delete_operator' => [
            'title' => 'Deactivate and Reactivate an Employee',
            'assignment' => 'Assignment 1',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Remove an employee\'s access without erasing their history, and restore it when needed.',
            'steps' => [
                'Select an active employee in Employees.',
                'Press Delete Employee and confirm. The account becomes inactive. Nothing is erased.',
                'Check Inactive in the filter bar to find the account again.',
                'Select the inactive employee and press Reactivate Employee to restore access.'
            ],
            'movie' => 'employees',
            'start' => null
        ],
        'supervisor_open' => [
            'title' => 'Open Another Employee\'s Transaction',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'View, assist with, take over, or cancel a transaction that belongs to someone else.',
            'steps' => [
                'Open Transactions and press Open beside the open transaction.',
                'Press View Only to look without changing anything.',
                'Press Assist to add items, discounts, or coupons. It stays with its owner, who can still resume it.',
                'Press Take Over to assign it to yourself and check it out. Press Hand Back to return it.',
                'Press Cancel Transaction to cancel it and return its items to stock.',
                'Read Who Worked On This Transaction to see every view, assist, take over, and hand back.'
            ],
            'movie' => 'additional',
            'start' => null
        ],
        'employee_access' => [
            'title' => 'Inactivate or Reactivate an Employee',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'Stop an employee from using the system, or let them back in, without deleting their record.',
            'steps' => [
                'Open Employees and choose the employee in the list.',
                'Press View Transactions to see everything they opened, worked on, or completed.',
                'Press Inactivate Employee and confirm. They are signed out on their next click and cannot sign in.',
                'Their open transactions stay open until a Manager takes them over or cancels them.',
                'To restore access, show inactive employees, choose the employee, and press Reactivate.',
                'A Manager cannot change an Administrator, and nobody can change their own access.'
            ],
            'movie' => 'employees',
            'start' => null
        ],
        'registers' => [
            'title' => 'Set Up Registers',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Add a register or change its number, name, type, or status.',
            'steps' => [
                'Open Register Configuration at the bottom of the menu.',
                'Choose a register from the dropdown, or choose Add New Register.',
                'Change the number, name, type (Express or Regular), or status in the form that opens below. Select Inactive to take a register out of service; there is no Delete Register button.',
                'Press Add Register when making a new register, or Save Register when editing one. A register with an open transaction cannot be changed.',
                'Register 1 always stays an active Express register, and at least one Regular register stays active.'
            ],
            'movie' => 'registers',
            'start' => null
        ],
        'stock_levels' => [
            'title' => 'View Store Stock Levels',
            'assignment' => 'Assignment 2',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Check how many of each product are on the shelf, where they are, and what is out of stock.',
            'steps' => [
                'Choose Stock Levels from the left menu.',
                'Read Total Stock on Hand: the weight of items sold by the pound and the number of each items.',
                'Find a product by department and check its quantity, price, aisle, section, and shelf.',
                'Spot the products marked Out of Stock.'
            ],
            'movie' => 'inventory',
            'start' => null
        ],
        'regular_sale' => [
            'title' => 'Ring Up a Sale',
            'assignment' => 'Assignment 2',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Open a register, add products by touch or by code, and handle weighed items.',
            'steps' => [
                'Choose New Sale and select an available Regular register (4-6 by default).',
                'To add several of one item, type the quantity beside Add Item first, then touch the item.',
                'Or type a UPC or PLU code. An item sold by weight asks for its weight in pounds.',
                'Press + or - beside a quantity to add or remove one. Press x to remove an item sold by weight.',
                'Press + UPC to show each product code in the Current Sale list. Press - UPC to hide them.',
                'Close Register, next to + UPC, cancels the sale and puts the items back in stock.',
                'Stock cannot go below zero, so a quantity that is not on the shelf is refused.'
            ],
            'movie' => 'regular',
            'start' => null
        ],
        'leave_sale' => [
            'title' => 'Leave a Sale You Have Not Finished',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators, Managers, and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Choose what happens to a sale with items on it when you leave the register page.',
            'steps' => [
                'While a sale has items on it, press any link in the left menu.',
                'A box asks Leave Current Transaction? and offers Stay, Save, and Close.',
                'Stay closes the box and keeps you on the sale.',
                'Save leaves the page and keeps the sale. Choose Continue Sale in the menu to come back to it.',
                'Close cancels the sale, puts its items back in stock, and closes the register.'
            ],
            'movie' => 'regular',
            'start' => null
        ],
        'discounts' => [
            'title' => 'Apply Discounts to a Sale',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Take money off a sale by percent or by dollars, before or after tax, and record the reason.',
            'steps' => [
                'Add the items first. The Discount button appears once the sale has items.',
                'Press Discount, then choose Percent off or Dollar amount off.',
                'Choose Before tax or After tax and enter the amount.',
                'Pick a saved reason. Or choose Other, type a new one, and keep Save checked to reuse it.',
                'Press Apply Discount. A sale can have up to five. Use Remove to take one off.'
            ],
            'movie' => 'regular',
            'start' => null
        ],
        'coupons' => [
            'title' => 'Use Coupons',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Scan a coupon to take money off one item. Each coupon is used once per sale.',
            'steps' => [
                'Add the item the coupon is for. A coupon cannot be used before its item is on the sale.',
                'Scan the coupon barcode, or type its 12 digits, in the Barcode / Product Code box.',
                'The coupon appears under the items with its amount. It only discounts its own item.',
                'A coupon that needs 2 items takes 2 items. Items covered by one coupon cannot be used again.',
                'Press the x beside a coupon to take it off. The totals change right away.'
            ],
            'movie' => 'regular',
            'start' => null
        ],
        'checkout' => [
            'title' => 'Check Out and Take Payment',
            'assignment' => 'Assignment 2',
            'audience' => 'Administrators and Operators',
            'roles' => ['Administrator', 'Manager', 'Operator'],
            'summary' => 'Review the totals, then take cash and give change, or charge the customer.',
            'steps' => [
                'Review the items and any discounts, then press Checkout.',
                'Check the subtotal, the discounts, the tax, and the total due.',
                'Choose how the customer pays: Cash or Charge.',
                'For Cash, enter the cash tendered. For Charge, nothing more is entered and no change is given.',
                'Complete the sale. For Cash, hand the customer the change shown on the receipt.'
            ],
            'movie' => 'regular',
            'start' => null
        ],
        'express_orders' => [
            'title' => 'Take a Curbside Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Record a pickup order, pick the groceries, and check it out at an Express register.',
            'steps' => [
                'Open Express Orders. The board shows every order by stage, and how many are left today.',
                'Press Take New Order, pick or enter the customer, choose Curbside, and pick a free Express register.',
                'Add groceries with the touch buttons or a typed code. Type a quantity first to add several.',
                'Press + or - beside a quantity to add or remove one if something was picked by mistake.',
                'It moves to Picking when the first item is added. Open it from the board to add a forgotten item.',
                'Press Mark Ready when everything is picked, then press Checkout.',
                'The customer is charged (no cash, no change). Confirm the Express receipt.'
            ],
            'movie' => 'express',
            'start' => null
        ],
        'express_delivery' => [
            'title' => 'Take a Home Delivery Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Take an order for delivery, enter or reuse an address, and see the delivery fee at checkout.',
            'steps' => [
                'Press Take New Order for a customer. Delivery is open from 8:00 AM to 4:00 PM.',
                'Choose Delivery, then enter the address or load one the customer saved.',
                'To keep a new address, check Save this address for reuse and give it a name such as Home.',
                'Add the items, press Mark Ready, and confirm the $10.00 delivery fee is in the total charged.'
            ],
            'movie' => 'express',
            'start' => null
        ],
        'express_today' => [
            'title' => 'See Today\'s Express Orders and Stock',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Check how many Express orders are left, which are Curbside or Delivery, and the stock.',
            'steps' => [
                'Open Express Orders. The top shows how many of the 20 daily orders are still available.',
                'Read the Order Board to see each open order by stage: Received, Picking, and Ready.',
                'Press View Today\'s Orders to list every order with its method, Curbside or Delivery, and its status.',
                'Scroll down to Store Stock. It shows the same stock levels the register staff see.',
                'When all 20 orders are taken, a new order is refused until one is cancelled.'
            ],
            'movie' => 'express',
            'start' => null
        ],
        'express_cancel' => [
            'title' => 'Cancel an Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Cancel an Express order that will not be picked up, and put its items back in stock.',
            'steps' => [
                'Open the order from the Order Board or from Today\'s Express Orders.',
                'Press Cancel Order and confirm that the picked items go back into stock.',
                'The order moves to Finished Today and shows as Cancelled.',
                'A cancelled order frees its place in the daily limit of 20.'
            ],
            'movie' => 'express',
            'start' => null
        ],
        'express_rules' => [
            'title' => 'Express Rules at a Glance',
            'assignment' => 'Assignment 3',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Learn how Express registers, the 20-order daily limit, and delivery hours shape the work.',
            'steps' => [
                'Registers 1-3 are reserved for FnH Express. Register 1 must remain Express-only.',
                'Personal Shoppers do not ring regular sales. They take, pick, and check out Express orders.',
                'The Express page shows how many of the 20 daily orders are left. A cancelled order frees its place.',
                'Outside 8:00 AM to 4:00 PM, only curbside orders can be taken.'
            ],
            'movie' => 'express',
            'start' => null
        ],
        'inventory_management' => [
            'title' => 'Manage Products and Inventory',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators and Managers',
            'roles' => ['Administrator', 'Manager'],
            'summary' => 'Add and edit products, and add, remove, or set stock with a recorded reason.',
            'steps' => [
                'Open Manage Inventory and search or filter the product list.',
                'Press Add New Product and fill in the department, code, name, price, tax setting, and starting stock.',
                'Open Edit on a product to change its details. Stock is changed separately.',
                'Open Adjust to Add, Remove, or Set stock, type a reason, and save.',
                'Check the change in Recent Manual Inventory Adjustments.',
                'Administrators also have a Coupons button here. See Create and Manage Coupons.'
            ],
            'movie' => 'inventory',
            'start' => null
        ],
        'coupons_manage' => [
            'title' => 'Create and Manage Coupons',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Administrators',
            'roles' => ['Administrator'],
            'summary' => 'Make a coupon with its own barcode, turn it on or off, and see how often it is used.',
            'steps' => [
                'Open Manage Inventory and press Coupons.',
                'Choose the product, how many it needs, percent or dollar, and the amount.',
                'Type a description. The start and end dates are optional.',
                'Press Create Coupon. The coupon appears in the list with its barcode, ready to print.',
                'The Used column shows how many times each coupon has been used.',
                'Press Turn Off to stop a coupon from working, or Turn On to bring it back.'
            ],
            'movie' => 'additional',
            'start' => null
        ],
        'transaction_viewer' => [
            'title' => 'Look Up a Transaction',
            'assignment' => 'Assignment 4 extension',
            'audience' => 'Everyone',
            'roles' => ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            'summary' => 'Find a past sale and open its full receipt, including any discounts.',
            'steps' => [
                'Choose Transactions from the left menu. The page is called Transaction Viewer.',
                'Search, or filter by status and date. Supervisors can also switch between Regular and Express.',
                'Press Open to see the lines, discounts, tax, total, payment, and change.',
                'For an Express order, also read the status, delivery fee, and delivery address.'
            ],
            'movie' => 'additional',
            'start' => null
        ]
    ]
];