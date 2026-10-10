<?php // express/ex_new.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpress();

$db = connectDatabase();
$errorMessage = '';
$loadErrorMessage = '';
$addressSaveWarning = '';
$capacity = [
    'DailyCapacity' => EXPRESS_DAILY_CAPACITY,
    'OrdersUsed' => 0,
    'OrdersRemaining' => 0
];
$customers = [];
$registerRecords = [];
$selectedRegisterID = (int) ($_POST['register_id'] ?? 0);

// Remove a new Express customer when the order could not be created
function removeUnusedCustomer(PDO $db, int $customerID)
{
    if ($customerID <= 0) {
        return;
    }

    try {
        $cleanupStatement = $db->prepare('CALL sp_remove_unused_express_customer(:customerID)');
        $cleanupStatement->execute([':customerID' => $customerID]);
        $cleanupStatement->closeCursor();

    } catch (PDOException $cleanupException) {
        error_log($cleanupException->getMessage());
    }
}

try {
    $capacityStatement =
        $db->prepare(
            'CALL sp_get_express_capacity(?)'
        );

    $capacityStatement->execute([
        signedInStoreID()
    ]);

    $capacityRecord =
        $capacityStatement->fetch();

    $capacityStatement->closeCursor();

    if ($capacityRecord) {
        $capacity = $capacityRecord;
    }

    $registerStatement = $db->prepare(
        "SELECT r.RegisterID, r.RegisterNumber, r.RegisterName, r.RegisterType,
                sr.ReceiptID AS OpenReceiptID,
                sr.OperatorID AS OpenOperatorID,
                CONCAT_WS(' ', o.FirstName, o.LastName) AS OpenOperatorName
         FROM register r
         LEFT JOIN salesreceipt sr
             ON sr.RegisterID = r.RegisterID
            AND sr.StoreID = r.StoreID
            AND sr.Status = 'Open'
         LEFT JOIN operator o ON o.OperatorID = sr.OperatorID
         WHERE r.StoreID = :storeID AND r.Active = 1
         ORDER BY r.RegisterNumber"
    );
    $registerStatement->execute([
        ':storeID' => signedInStoreID()
    ]);
    $registerRecords = $registerStatement->fetchAll();

    $customerStatement =
        $db->query(
            'SELECT CustomerID, LoyaltyNumber, FirstName, LastName, Phone
             FROM customer
             WHERE Active = 1
             ORDER BY LastName, FirstName'
        );

    $customers =
        $customerStatement->fetchAll();

} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadErrorMessage =
        'Express customer and capacity information could not be loaded completely. Please try again.';
}

// Load active saved addresses for one customer
function loadAddresses(PDO $db, int $customerID): array
{
    if ($customerID <= 0) {
        return [];
    }

    $addressStatement = $db->prepare(
        'SELECT CustomerAddressID, AddressLabel, AddressLine1, AddressLine2, City, StateCode, PostalCode
         FROM customeraddress
         WHERE CustomerID = :customerID
           AND Active = 1
         ORDER BY AddressLabel'
    );

    $addressStatement->execute([
        ':customerID' => $customerID
    ]);

    return $addressStatement->fetchAll();
}

// Find a saved address in this customer's list
function findAddress(array $savedAddresses, int $addressID): ?array
{
    if ($addressID <= 0) {
        return null;
    }

    foreach ($savedAddresses as $savedAddress) {
        if ((int) $savedAddress['CustomerAddressID'] === $addressID) {
            return $savedAddress;
        }
    }

    return null;
}

$customerMode = 'existing';
$selectedCustomerID = 0;
$selectedAddressID = 0;

$newFirstName = '';
$newLastName = '';
$newPhone = '';
$newEmail = '';
$fulfillmentMethod = 'Curbside';

$deliveryAddressLine1 = '';
$deliveryAddressLine2 = '';
$deliveryCity = '';
$deliveryState = '';
$deliveryPostalCode = '';

// Keep the loaded address values so changes can be detected
$loadedAddressID = 0;
$loadedAddressLine1 = '';
$loadedAddressLine2 = '';
$loadedAddressCity = '';
$loadedAddressState = '';
$loadedPostalCode = '';

$updateAddressOnFile = false;
$saveNewAddress = false;
$newAddressLabel = '';
$savedAddresses = [];

$requestAction = '';
$submittedToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_order'])) {
        $requestAction = 'create_order';
    } elseif (isset($_POST['load_address'])) {
        $requestAction = 'load_address';
    } elseif (isset($_POST['load_customer'])) {
        $requestAction = 'load_customer';
    }

    $submittedToken =
        $_POST['form_security_token']
        ?? '';

    $customerMode =
        (
            $_POST['customer_mode']
            ?? 'existing'
        ) === 'new'
        ? 'new'
        : 'existing';

    $selectedCustomerID =
        (int) (
            $_POST['customer_id']
            ?? 0
        );

    $selectedAddressID =
        (int) (
            $_POST['address_id']
            ?? 0
        );

    $newFirstName =
        trim(
            $_POST['new_customer_first_name']
            ?? ''
        );
    $newLastName =
        trim(
            $_POST['new_customer_last_name']
            ?? ''
        );
    $newPhone =
        trim(
            $_POST['new_customer_phone']
            ?? ''
        );
    $newEmail =
        trim(
            $_POST['new_customer_email']
            ?? ''
        );

    $fulfillmentMethod =
        $_POST['fulfillment_method']
        ?? 'Curbside';

    $deliveryAddressLine1 =
        trim(
            $_POST['delivery_address_1']
            ?? ''
        );
    $deliveryAddressLine2 =
        trim(
            $_POST['delivery_address_2']
            ?? ''
        );
    $deliveryCity =
        trim(
            $_POST['delivery_city']
            ?? ''
        );
    $deliveryState =
        strtoupper(
            trim(
                $_POST['delivery_state']
                ?? ''
            )
        );
    $deliveryPostalCode =
        trim(
            $_POST['delivery_postal_code']
            ?? ''
        );

    $loadedAddressID =
        (int) (
            $_POST['loaded_address_id']
            ?? 0
        );
    $updateAddressOnFile =
        (
            $_POST['update_address_on_file']
            ?? ''
        ) === '1';
    $saveNewAddress =
        isset($_POST['save_new_address']);
    $newAddressLabel =
        trim(
            $_POST['new_address_label']
            ?? ''
        );

    if (!tokenIsValid($submittedToken)) {
        $errorMessage = 'The form expired. Please try again.';
    }
}

// Load the saved addresses for the selected customer
if (
    $customerMode === 'existing'
    &&
    $selectedCustomerID > 0
) {
    try {
        $savedAddresses =
            loadAddresses(
                $db,
                $selectedCustomerID
            );
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $loadErrorMessage =
            "The selected customer's saved addresses could not be loaded. Please try again.";
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    $errorMessage === ''
) {
    if ($requestAction === 'load_customer') {
        if (
            $customerMode !== 'existing'
            ||
            $selectedCustomerID <= 0
        ) {
            $errorMessage =
                'Select the customer you want to load.';
        } else {
            // Start over when the customer changes
            $selectedAddressID = 0;
            $loadedAddressID = 0;
            $loadedAddressLine1 = '';
            $loadedAddressLine2 = '';
            $loadedAddressCity = '';
            $loadedAddressState = '';
            $loadedPostalCode = '';
            $deliveryAddressLine1 = '';
            $deliveryAddressLine2 = '';
            $deliveryCity = '';
            $deliveryState = '';
            $deliveryPostalCode = '';
            $updateAddressOnFile = false;
            $saveNewAddress = false;
            $newAddressLabel = '';
        }
    } elseif ($requestAction === 'load_address') {
        if (
            $customerMode !== 'existing'
            ||
            $selectedCustomerID <= 0
        ) {
            $errorMessage =
                'Load an existing customer before selecting a saved address.';
        } elseif ($selectedAddressID <= 0) {
            $errorMessage =
                'Select the saved address you want to load.';
        } else {
            $loadedAddress =
                findAddress(
                    $savedAddresses,
                    $selectedAddressID
                );

            if (!$loadedAddress) {
                $errorMessage =
                    'The selected saved address does not belong to the selected customer.';
            } else {
                $loadedAddressID =
                    (int) $loadedAddress['CustomerAddressID'];
                $deliveryAddressLine1 =
                    $loadedAddress['AddressLine1'];
                $deliveryAddressLine2 =
                    (string) $loadedAddress['AddressLine2'];
                $deliveryCity =
                    $loadedAddress['City'];
                $deliveryState =
                    $loadedAddress['StateCode'];
                $deliveryPostalCode =
                    $loadedAddress['PostalCode'];

                $loadedAddressLine1 =
                    $deliveryAddressLine1;
                $loadedAddressLine2 =
                    $deliveryAddressLine2;
                $loadedAddressCity =
                    $deliveryCity;
                $loadedAddressState =
                    $deliveryState;
                $loadedPostalCode =
                    $deliveryPostalCode;

                $fulfillmentMethod = 'Delivery';
                $updateAddressOnFile = false;
                $saveNewAddress = false;
                $newAddressLabel = '';
            }
        }
    } elseif ($requestAction === 'create_order') {
        $chosenRegister = null;
        foreach ($registerRecords as $candidateRegister) {
            if ((int) $candidateRegister['RegisterID'] === $selectedRegisterID) {
                $chosenRegister = $candidateRegister;
                break;
            }
        }
        if (
            !$chosenRegister ||
            ($chosenRegister['RegisterType'] ?? '') !== 'Express' ||
            !empty($chosenRegister['OpenReceiptID'])
        ) {
            $errorMessage = 'Select an available Express register.';
        }

        // Curbside pickup does not accept, require, or save a delivery address
        if ($fulfillmentMethod === 'Curbside') {
            $selectedAddressID = 0;
            $loadedAddressID = 0;
            $deliveryAddressLine1 = '';
            $deliveryAddressLine2 = '';
            $deliveryCity = '';
            $deliveryState = '';
            $deliveryPostalCode = '';
            $updateAddressOnFile = false;
            $saveNewAddress = false;
            $newAddressLabel = '';
        }
        // Check that the loaded address belongs to this customer
        if ($fulfillmentMethod === 'Delivery' && $customerMode === 'new' && $loadedAddressID > 0) {
            $errorMessage =
                'A saved address from an existing customer cannot be used for a new customer. '
                . 'Enter the new customer address again.';

            $loadedAddressID = 0;
            $selectedAddressID = 0;
            $deliveryAddressLine1 = '';
            $deliveryAddressLine2 = '';
            $deliveryCity = '';
            $deliveryState = '';
            $deliveryPostalCode = '';
            $fulfillmentMethod = 'Curbside';
            $updateAddressOnFile = false;
            $saveNewAddress = false;
            $newAddressLabel = '';
        } elseif (
            $fulfillmentMethod === 'Delivery'
            &&
            $customerMode === 'existing'
            &&
            $loadedAddressID > 0
        ) {
            $loadedAddress =
                findAddress(
                    $savedAddresses,
                    $loadedAddressID
                );

            if (!$loadedAddress) {
                $errorMessage =
                    'The loaded saved address does not belong to the selected customer. '
                    . 'Load the customer and choose the address again.';

                $loadedAddressID = 0;
                $selectedAddressID = 0;
                $deliveryAddressLine1 = '';
                $deliveryAddressLine2 = '';
                $deliveryCity = '';
                $deliveryState = '';
                $deliveryPostalCode = '';
                $fulfillmentMethod = 'Curbside';
                $updateAddressOnFile = false;
                $saveNewAddress = false;
                $newAddressLabel = '';
            } else {
                $selectedAddressID =
                    $loadedAddressID;
                $loadedAddressLine1 =
                    $loadedAddress['AddressLine1'];
                $loadedAddressLine2 =
                    (string) $loadedAddress['AddressLine2'];
                $loadedAddressCity =
                    $loadedAddress['City'];
                $loadedAddressState =
                    $loadedAddress['StateCode'];
                $loadedPostalCode =
                    $loadedAddress['PostalCode'];
            }
        }

        if ($errorMessage === '') {
            if (
                $customerMode === 'existing'
                &&
                $selectedCustomerID <= 0
            ) {
                $errorMessage =
                    'Select the customer placing the Express order.';
            } elseif (
                $customerMode === 'new'
                &&
                (
                    $newFirstName === ''
                    ||
                    $newLastName === ''
                )
            ) {
                $errorMessage =
                    'Enter the new customer\'s first and last name.';
            } elseif (
                $customerMode === 'new'
                &&
                !phoneIsValid($newPhone)
            ) {
                $errorMessage =
                    'Phone number must contain exactly 10 digits or be left blank.';
            } elseif (
                $customerMode === 'new'
                &&
                $newEmail !== ''
                &&
                !filter_var($newEmail, FILTER_VALIDATE_EMAIL)
            ) {
                $errorMessage =
                    'Enter a valid email address or leave it blank.';
            } elseif (
                !in_array(
                    $fulfillmentMethod,
                    ['Curbside', 'Delivery'],
                    true
                )
            ) {
                $errorMessage =
                    'Select curbside pickup or home delivery.';
            } elseif (
                $fulfillmentMethod === 'Delivery'
                &&
                (
                    $deliveryAddressLine1 === ''
                    ||
                    $deliveryCity === ''
                    ||
                    strlen($deliveryState) !== 2
                    ||
                    $deliveryPostalCode === ''
                )
            ) {
                $errorMessage =
                    'Street address, city, two-letter state, and ZIP code are required for home delivery.';
            } elseif (
                $fulfillmentMethod === 'Delivery'
                &&
                $loadedAddressID <= 0
                &&
                $saveNewAddress
                &&
                $newAddressLabel === ''
            ) {
                $errorMessage =
                    'Enter a name for the address you want to save, such as Home or Work.';
            }
        }

        if ($errorMessage === '') {
            $newCustomerCreated = 0;

            try {
                if ($customerMode === 'new') {
                    $customerStatement =
                        $db->prepare(
                            'CALL sp_create_express_customer(?, ?, ?, ?)'
                        );

                    $customerStatement->execute([
                        $newFirstName,
                        $newLastName,
                        $newPhone,
                        $newEmail
                    ]);

                    $newCustomer =
                        $customerStatement->fetch();

                    $customerStatement->closeCursor();

                    if (!$newCustomer) {
                        throw new RuntimeException(
                            'The new Express customer could not be created.'
                        );
                    }

                    $customerID =
                        (int) $newCustomer['CustomerID'];

                    $newCustomerCreated =
                        $customerID;
                } else {
                    $customerID =
                        $selectedCustomerID;
                }

                $statement = $db->prepare(
                    'CALL sp_create_express_order(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                $statement->execute([
                    signedInStoreID(),
                    $selectedRegisterID,
                    signedInOperatorID(),
                    $customerID,
                    $fulfillmentMethod,
                    $deliveryAddressLine1,
                    $deliveryAddressLine2,
                    $deliveryCity,
                    $deliveryState,
                    $deliveryPostalCode
                ]);

                $created = $statement->fetch();
                $statement->closeCursor();

                if (
                    !$created
                    ||
                    !isset($created['ExpressOrderID'])
                ) {
                    throw new RuntimeException(
                        'The Express order was created without returning an order number.'
                    );
                }

                // Save address-book changes after the order succeeds
                if ($fulfillmentMethod === 'Delivery') {
                    try {
                        if (
                            $loadedAddressID > 0
                            &&
                            $updateAddressOnFile
                        ) {
                            $addressUpdateStatement = $db->prepare(
                                'CALL sp_update_customer_address(?, ?, ?, ?, ?, ?, ?)'
                            );

                            $addressUpdateStatement->execute([
                                $customerID,
                                $loadedAddressID,
                                $deliveryAddressLine1,
                                $deliveryAddressLine2,
                                $deliveryCity,
                                $deliveryState,
                                $deliveryPostalCode
                            ]);

                            $addressUpdateStatement->closeCursor();
                        } elseif (
                            $loadedAddressID <= 0
                            &&
                            $saveNewAddress
                            &&
                            $newAddressLabel !== ''
                        ) {
                            $addressCreateStatement = $db->prepare(
                                'CALL sp_create_customer_address(?, ?, ?, ?, ?, ?, ?)'
                            );

                            $addressCreateStatement->execute([
                                $customerID,
                                $newAddressLabel,
                                $deliveryAddressLine1,
                                $deliveryAddressLine2,
                                $deliveryCity,
                                $deliveryState,
                                $deliveryPostalCode
                            ]);

                            $addressCreateStatement->closeCursor();
                        }
                    } catch (PDOException $addressException) {
                        error_log(
                            $addressException->getMessage()
                        );

                        $addressSaveWarning =
                            '&address_warning=1';
                    }
                }

                header(
                    'Location: '
                    . APPLICATION_URL
                    . '/express/ex_order.php?id='
                    . (int) $created['ExpressOrderID']
                    . $addressSaveWarning
                );
                exit;
            } catch (PDOException $exception) {
                if ($newCustomerCreated > 0) {
                    removeUnusedCustomer(
                        $db,
                        $newCustomerCreated
                    );
                }

                $errorMessage =
                    databaseMessage(
                        $exception,
                        'The Express order could not be created.'
                    );
            } catch (RuntimeException $exception) {
                if ($newCustomerCreated > 0) {
                    removeUnusedCustomer(
                        $db,
                        $newCustomerCreated
                    );
                }

                $errorMessage =
                    $exception->getMessage();
            }
        }
    }
}

$pageTitle = 'Take New Order';
$currentSection = 'express';
$currentPage = 'express-new';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel form-panel express-panel">
    <div class="page-intro">
        <h1>Take New Order</h1>
        <p>
            <?= escapeOutput($capacity['OrdersRemaining'] ?? 0) ?>
            of <?= EXPRESS_DAILY_CAPACITY ?> Express orders remain available today.
        </p>
    </div>

    <?php if ($loadErrorMessage !== ''): ?>
        <div class="message message-error">
            <?= escapeOutput($loadErrorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" id="expressNewOrderForm">
        <input type="hidden" name="form_security_token" value="<?= escapeOutput(formToken()) ?>">

        <div class="form-field">
            <label for="register_id">Express Register</label>
            <select id="register_id" name="register_id" required>
                <option value="">Choose an available Express register</option>
                <?php
                foreach ($registerRecords as $registerRecord) {
                    $isExpress = ($registerRecord['RegisterType'] ?? '') === 'Express';
                    $busy = !empty($registerRecord['OpenReceiptID']);
                    $registerDisabledAttribute = (!$isExpress || $busy)
                        ? ' disabled="disabled"'
                        : '';
                    $registerSelectedAttribute =
                        $selectedRegisterID === (int) $registerRecord['RegisterID']
                        ? ' selected="selected"'
                        : '';

                    $registerOptionText = 'Register ' . (int) $registerRecord['RegisterNumber'];
                    if (!$isExpress) {
                        $registerOptionText .= ' - Reserved for Regular Sales';
                    } elseif ($busy) {
                        $registerOptionText .= ' - In Use By: '
                            . (string) ($registerRecord['OpenOperatorName'] ?? '');
                    } else {
                        $registerOptionText .= ' - Available';
                    }

                    echo '<option value="'
                        . (int) $registerRecord['RegisterID']
                        . '"'
                        . $registerDisabledAttribute
                        . $registerSelectedAttribute
                        . '>'
                        . escapeOutput($registerOptionText)
                        . '</option>';
                }
                ?>
            </select>
        </div>

        <input type="hidden" name="loaded_address_id" id="loadedAddressID" value="<?= (int) $loadedAddressID ?>">
        <input type="hidden" id="loadedAddressLine1" value="<?= escapeOutput($loadedAddressLine1) ?>">
        <input type="hidden" id="loadedAddressLine2" value="<?= escapeOutput($loadedAddressLine2) ?>">
        <input type="hidden" id="loadedAddressCity" value="<?= escapeOutput($loadedAddressCity) ?>">
        <input type="hidden" id="loadedAddressState" value="<?= escapeOutput($loadedAddressState) ?>">
        <input type="hidden" id="loadedAddressPostalCode" value="<?= escapeOutput($loadedPostalCode) ?>">
        <input type="hidden" name="update_address_on_file" id="updateAddressOnFile" value="0">

        <div class="form-grid">

            <fieldset class="express-customer-mode">
                <legend>Customer *</legend>

                <label>
                    <input type="radio" name="customer_mode" value="existing" id="customer_mode_existing"
                        <?= $customerMode === 'existing' ? 'checked' : '' ?>>
                    Existing Customer
                </label>

                <label>
                    <input type="radio" name="customer_mode" value="new" id="customer_mode_new" <?= $customerMode === 'new' ? 'checked' : '' ?>>
                    New Customer
                </label>
            </fieldset>

            <div class="form-field express-address" id="existingCustomerField">
                <label for="customer_id">Select Customer</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <?php
                        $customerSelectedAttribute =
                            $selectedCustomerID === (int) $customer['CustomerID']
                            ? ' selected="selected"'
                            : '';
                        $customerLabel =
                            $customer['LastName']
                            . ', '
                            . $customer['FirstName']
                            . ' - '
                            . $customer['LoyaltyNumber'];

                        echo '<option value="'
                            . (int) $customer['CustomerID']
                            . '"'
                            . $customerSelectedAttribute
                            . '>'
                            . escapeOutput($customerLabel)
                            . '</option>';
                        ?>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="load_customer" value="1" class="button button-secondary">
                    Load Customer
                </button>

                <p class="field-help">
                    Loading a customer shows their saved addresses below. This reloads the page.
                </p>
            </div>

            <div class="express-new-customer" id="newCustomerFields">

                <div class="form-field">
                    <label for="new_customer_first_name">First Name</label>
                    <input type="text" id="new_customer_first_name" name="new_customer_first_name" maxlength="60"
                        value="<?= escapeOutput($newFirstName) ?>">
                </div>

                <div class="form-field">
                    <label for="new_customer_last_name">Last Name</label>
                    <input type="text" id="new_customer_last_name" name="new_customer_last_name" maxlength="60"
                        value="<?= escapeOutput($newLastName) ?>">
                </div>

                <div class="form-field">
                    <label for="new_customer_phone">Phone</label>
                    <input type="tel" id="new_customer_phone" name="new_customer_phone" minlength="10" maxlength="10"
                        pattern="[0-9]{10}" inputmode="numeric"
                        title="Enter exactly 10 digits with no spaces or punctuation."
                        value="<?= escapeOutput($newPhone) ?>">
                </div>

                <div class="form-field">
                    <label for="new_customer_email">Email</label>
                    <input type="email" id="new_customer_email" name="new_customer_email" maxlength="120"
                        value="<?= escapeOutput($newEmail) ?>">
                </div>

                <p class="field-help">
                    A loyalty account is created automatically for a new Express customer.
                </p>

            </div>

            <fieldset class="express-fulfillment">
                <legend>Fulfillment *</legend>

                <label>
                    <input type="radio" name="fulfillment_method" value="Curbside" <?= $fulfillmentMethod === 'Curbside' ? 'checked' : '' ?>>
                    Curbside Pickup
                </label>

                <label>
                    <input type="radio" name="fulfillment_method" value="Delivery" <?= $fulfillmentMethod === 'Delivery' ? 'checked' : '' ?>>
                    Home Delivery (+$<?= number_format(EXPRESS_DELIVERY_FEE, 2) ?>)
                </label>

                <p class="field-help">
                    Delivery is accepted only for orders placed from
                    <?= EXPRESS_DELIVERY_OPENS ?> through <?= EXPRESS_DELIVERY_CLOSES ?>.
                    The database verifies the time when the order is submitted.
                </p>
            </fieldset>

            <div id="deliveryDetails" class="express-address" <?= $fulfillmentMethod === 'Delivery' ? '' : 'hidden' ?>>
                <div class="form-grid">
                    <?php if ($selectedCustomerID > 0 && $savedAddresses): ?>

                        <div class="form-field express-address" id="savedAddressField">
                            <label for="saved_address_select">Saved Address</label>
                            <select id="saved_address_select" name="address_id">
                                <option value="">Select a Saved Address</option>
                                <?php foreach ($savedAddresses as $savedAddress): ?>
                                    <?php
                                    $savedAddressSelectedAttribute =
                                        $loadedAddressID === (int) $savedAddress['CustomerAddressID']
                                        ? ' selected="selected"'
                                        : '';
                                    $savedAddressLabel =
                                        $savedAddress['AddressLabel']
                                        . ' - '
                                        . $savedAddress['AddressLine1']
                                        . ', '
                                        . $savedAddress['City'];

                                    echo '<option value="'
                                        . (int) $savedAddress['CustomerAddressID']
                                        . '"'
                                        . $savedAddressSelectedAttribute
                                        . '>'
                                        . escapeOutput($savedAddressLabel)
                                        . '</option>';
                                    ?>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" name="load_address" value="1" class="button button-secondary">
                                Load Address
                            </button>

                            <p class="field-help">
                                Loading a saved address fills in the delivery fields below. This reloads the page too.
                            </p>
                        </div>

                    <?php endif; ?>

                    <div class="form-field">
                        <label for="delivery_address_1">Delivery Address</label>
                        <input type="text" id="delivery_address_1" name="delivery_address_1" maxlength="120"
                            value="<?= escapeOutput($deliveryAddressLine1) ?>">
                    </div>

                    <div class="form-field">
                        <label for="delivery_address_2">Address Line 2</label>
                        <input type="text" id="delivery_address_2" name="delivery_address_2" maxlength="120"
                            value="<?= escapeOutput($deliveryAddressLine2) ?>">
                    </div>

                    <div class="form-field">
                        <label for="delivery_city">City</label>
                        <input type="text" id="delivery_city" name="delivery_city" maxlength="80"
                            value="<?= escapeOutput($deliveryCity) ?>">
                    </div>

                    <div class="form-field">
                        <label for="delivery_state">State</label>
                        <input type="text" id="delivery_state" name="delivery_state" maxlength="2"
                            value="<?= escapeOutput($deliveryState) ?>">
                    </div>

                    <div class="form-field">
                        <label for="delivery_postal_code">ZIP Code</label>
                        <input type="text" id="delivery_postal_code" name="delivery_postal_code" maxlength="10"
                            value="<?= escapeOutput($deliveryPostalCode) ?>">
                    </div>

                    <?php if ($loadedAddressID === 0): ?>

                        <div class="form-field express-save-address" id="saveNewAddressField">
                            <label>
                                <input type="checkbox" name="save_new_address" id="save_new_address" value="1"
                                    <?= $saveNewAddress ? 'checked' : '' ?>>
                                Save this address for reuse
                            </label>

                            <input type="text" name="new_address_label" id="new_address_label"
                                aria-label="Name for this saved address" maxlength="40"
                                placeholder="Label, such as Home or Work" value="<?= escapeOutput($newAddressLabel) ?>">
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>

        <div class="form-actions">
            <?php if ((int) ($capacity['OrdersRemaining'] ?? 0) > 0): ?>
                <button class="button button-primary" type="submit" name="create_order" value="1">
                    Create Order
                </button>
            <?php else: ?>
                <span class="button button-disabled" aria-disabled="true">
                    Daily Capacity Full
                </span>
            <?php endif; ?>
            <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/ex_home.php">
                Cancel
            </a>
        </div>
    </form>
</section>

<script>
    // Reset the address when the customer changes and confirm saved-address edits
    (function () {
        var form =
            document.getElementById('expressNewOrderForm');
        var existingRadio =
            document.getElementById('customer_mode_existing');
        var newRadio =
            document.getElementById('customer_mode_new');
        var existingField =
            document.getElementById('existingCustomerField');
        var newFields =
            document.getElementById('newCustomerFields');
        var customerSelect =
            document.getElementById('customer_id');
        var deliveryDetails = document.getElementById('deliveryDetails');
        var deliveryRadio = form.querySelector('[name="fulfillment_method"][value="Delivery"]');
        var curbsideRadio = form.querySelector('[name="fulfillment_method"][value="Curbside"]');
        var savedAddressField =
            document.getElementById('savedAddressField');
        var savedAddressSelect =
            document.getElementById('saved_address_select');
        var loadedAddressID =
            document.getElementById('loadedAddressID');
        var updateFlag =
            document.getElementById('updateAddressOnFile');

        var addressFieldIDs = [
            'delivery_address_1',
            'delivery_address_2',
            'delivery_city',
            'delivery_state',
            'delivery_postal_code'
        ];

        var baselineFieldIDs = [
            'loadedAddressLine1',
            'loadedAddressLine2',
            'loadedAddressCity',
            'loadedAddressState',
            'loadedAddressPostalCode'
        ];

        function clearAddressValues() {
            addressFieldIDs.forEach(function (fieldID) {
                document.getElementById(fieldID).value = '';
            });
        }

        function clearAddress(clearDeliveryValues) {
            loadedAddressID.value = '0';
            updateFlag.value = '0';

            baselineFieldIDs.forEach(function (fieldID) {
                document.getElementById(fieldID).value = '';
            });

            if (savedAddressSelect) {
                savedAddressSelect.value = '';
            }

            if (savedAddressField) {
                savedAddressField.hidden = true;
            }

            if (clearDeliveryValues) {
                clearAddressValues();
            }
        }

        function selectCurbside() {
            var curbsideRadio =
                form.querySelector(
                    '[name="fulfillment_method"][value="Curbside"]'
                );

            if (curbsideRadio) {
                curbsideRadio.checked = true;
            }
        }

        function updateFulfillmentFields() {
            var isDelivery = deliveryRadio && deliveryRadio.checked;
            deliveryDetails.hidden = !isDelivery;
            ['delivery_address_1', 'delivery_city', 'delivery_state', 'delivery_postal_code'].forEach(function (id) {
                document.getElementById(id).required = Boolean(isDelivery);
            });
            // A saved address only applies to an existing customer's delivery
            if (savedAddressField) {
                savedAddressField.hidden = !isDelivery || newRadio.checked;
            }
        }

        [deliveryRadio, curbsideRadio].forEach(function (radio) {
            if (radio) radio.addEventListener('change', updateFulfillmentFields);
        });

        function updateCustomerFields() {
            var useNew = newRadio.checked;
            existingField.hidden = useNew;
            newFields.hidden = !useNew;

            if (useNew && savedAddressField) {
                savedAddressField.hidden = true;
            }
        }

        customerSelect.addEventListener('change', function () {
            clearAddress(true);
            updateFulfillmentFields();
        });

        existingRadio.addEventListener('change', function () {
            updateCustomerFields();
        });

        newRadio.addEventListener('change', function () {
            if (newRadio.checked) {
                clearAddress(true);
                selectCurbside();
            }

            updateCustomerFields();
            updateFulfillmentFields();
        });

        updateCustomerFields();
        updateFulfillmentFields();

        form.addEventListener('submit', function (event) {
            var submitter = event.submitter;

            if (!submitter || submitter.name !== 'create_order') {
                return;
            }

            var deliveryRadio =
                form.querySelector(
                    '[name="fulfillment_method"][value="Delivery"]'
                );

            if (
                !deliveryRadio
                ||
                !deliveryRadio.checked
                ||
                !loadedAddressID.value
                ||
                loadedAddressID.value === '0'
            ) {
                return;
            }

            var line1 =
                document.getElementById(
                    'delivery_address_1'
                ).value;
            var line2 =
                document.getElementById(
                    'delivery_address_2'
                ).value;
            var city =
                document.getElementById(
                    'delivery_city'
                ).value;
            var state =
                document.getElementById(
                    'delivery_state'
                ).value;
            var zip =
                document.getElementById(
                    'delivery_postal_code'
                ).value;

            var changed =
                line1 !== document.getElementById('loadedAddressLine1').value
                || line2 !== document.getElementById('loadedAddressLine2').value
                || city !== document.getElementById('loadedAddressCity').value
                || state !== document.getElementById('loadedAddressState').value
                || zip !== document.getElementById('loadedAddressPostalCode').value;

            if (changed) {
                var shouldUpdate = window.confirm(
                    'You changed this saved address. Update it on file for next time?'
                );

                updateFlag.value = shouldUpdate ? '1' : '0';
            }
        });
    })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>