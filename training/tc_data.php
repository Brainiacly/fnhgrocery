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

// Each use case names its movie and the second where it starts in that movie
return [
    'movies' => [
        'getting_started' => [
            'title' => 'Getting Started',
            'file' => '01_getting_started.mp4',
            'summary' => 'Sign in, find your way around the menus, manage your own account, and log out.'
        ],
        'operators' => [
            'title' => 'Employee Administration',
            'file' => '02_operator_administration.mp4',
            'summary' => 'How an Administrator creates, changes, deactivates, and reactivates employees.'
        ],
        'point_of_sale' => [
            'title' => 'Point of Sale',
            'file' => '03_point_of_sale.mp4',
            'summary' => 'Check stock, ring up a sale, apply discounts, and take payment at a register.'
        ],
        'express' => [
            'title' => 'FnH Express',
            'file' => '04_fnh_express.mp4',
            'summary' => 'Take curbside and home delivery orders and follow the Express rules.'
        ],
        'inventory_transactions' => [
            'title' => 'Inventory and Transactions',
            'file' => '05_inventory_and_transactions.mp4',
            'summary' => 'Add products, adjust stock, and look up past transactions and receipts.'
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
            'movie' => 'getting_started',
            'start' => 0
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
            'movie' => 'getting_started',
            'start' => 0
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
            'movie' => 'getting_started',
            'start' => 0
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
            'movie' => 'operators',
            'start' => 0
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
            'movie' => 'operators',
            'start' => 0
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
            'movie' => 'operators',
            'start' => 0
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
            'movie' => 'inventory_transactions',
            'start' => 0
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
            'movie' => 'operators',
            'start' => 0
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
                'Change the number, name, type (Express or Regular), or status in the form that opens below.',
                'Press Save Register. A register with an open transaction cannot be changed.',
                'Register 1 always stays an active Express register, and at least one Regular register stays active.'
            ],
            'movie' => 'operators',
            'start' => 0
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
            'movie' => 'point_of_sale',
            'start' => 0
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
            'movie' => 'point_of_sale',
            'start' => 0
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
            'movie' => 'point_of_sale',
            'start' => 0
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
            'movie' => 'point_of_sale',
            'start' => 0
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
            'movie' => 'point_of_sale',
            'start' => 0
        ],
        'express_orders' => [
            'title' => 'Take a Curbside Express Order',
            'assignment' => 'Assignment 3',
            'audience' => 'Administrators, Managers, and Personal Shoppers',
            'roles' => ['Administrator', 'Manager', 'Personal Shopper'],
            'summary' => 'Record a pickup order, pick the groceries, and check it out at an Express register.',
            'steps' => [
                'Open Express Orders. The board shows every order by stage, and how many are left today.',
                'Press Take New Order, choose the customer, choose Curbside, and choose a free Express register.',
                'Add groceries with the touch buttons or a typed code. Type a quantity first to add several.',
                'Press + or - beside a quantity to add or remove one if something was picked by mistake.',
                'It moves to Picking when the first item is added. Open it from the board to add a forgotten item.',
                'Press Mark Ready when everything is picked, then press Checkout.',
                'The customer is charged (no cash, no change). Confirm the Express receipt.'
            ],
            'movie' => 'express',
            'start' => 0
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
            'start' => 0
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
            'start' => 0
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
                'Administrators only: open Coupons to create a coupon. Its barcode is shown there to print.'
            ],
            'movie' => 'inventory_transactions',
            'start' => 0
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
            'movie' => 'inventory_transactions',
            'start' => 0
    ]
    ]
];