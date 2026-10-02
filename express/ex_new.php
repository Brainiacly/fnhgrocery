<?php // express/ex_new.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireExpressAccess();

$databaseConnection = connectDatabase();
$errorMessage = '';

$capacityStatement = $databaseConnection->prepare('CALL sp_get_express_capacity(?)');
$capacityStatement->execute([(int) $_SESSION['store_id']]);
$capacity = $capacityStatement->fetch();
$capacityStatement->closeCursor();

$customerStatement = $databaseConnection->query(
    'SELECT CustomerID, LoyaltyNumber, FirstName, LastName, Phone
     FROM customer
     WHERE Active = 1
     ORDER BY LastName, FirstName'
);
$customers = $customerStatement->fetchAll();

$customerMode = 'existing';
$selectedCustomerID = (int) ($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
$selectedAddressID = (int) ($_GET['address_id'] ?? 0);

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

// These carry the originally-loaded address so the page can tell,
// at submit time, whether the shopper actually changed it.
$loadedAddressID = 0;
$loadedAddressLine1 = '';
$loadedAddressLine2 = '';
$loadedAddressCity = '';
$loadedAddressState = '';
$loadedAddressPostalCode = '';

// This is this customer's saved address book, if any.
$savedAddresses = [];

if ($selectedCustomerID > 0) {
    $addressStatement = $databaseConnection->prepare(
        'SELECT CustomerAddressID, AddressLabel, AddressLine1, AddressLine2, City, StateCode, PostalCode
         FROM customeraddress
         WHERE CustomerID = :customerID
           AND Active = 1
         ORDER BY AddressLabel'
    );
    $addressStatement->execute([':customerID' => $selectedCustomerID]);
    $savedAddresses = $addressStatement->fetchAll();
}

// A GET request with an address_id means the "Load Address" button
// was used. This fills the editable fields and separately records
// what was loaded, purely through hidden form fields - nothing here
// is stored by or read back from JavaScript.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $selectedAddressID > 0) {
    foreach ($savedAddresses as $savedAddress) {
        if ((int) $savedAddress['CustomerAddressID'] === $selectedAddressID) {
            $deliveryAddressLine1 = $savedAddress['AddressLine1'];
            $deliveryAddressLine2 = (string) $savedAddress['AddressLine2'];
            $deliveryCity = $savedAddress['City'];
            $deliveryState = $savedAddress['StateCode'];
            $deliveryPostalCode = $savedAddress['PostalCode'];

            $loadedAddressID = $selectedAddressID;
            $loadedAddressLine1 = $deliveryAddressLine1;
            $loadedAddressLine2 = $deliveryAddressLine2;
            $loadedAddressCity = $deliveryCity;
            $loadedAddressState = $deliveryState;
            $loadedAddressPostalCode = $deliveryPostalCode;

            break;
        }
    }

    $fulfillmentMethod = 'Delivery';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';
    $customerMode = ($_POST['customer_mode'] ?? 'existing') === 'new' ? 'new' : 'existing';
    $selectedCustomerID = (int) ($_POST['customer_id'] ?? 0);
    $newFirstName = trim($_POST['new_customer_first_name'] ?? '');
    $newLastName = trim($_POST['new_customer_last_name'] ?? '');
    $newPhone = trim($_POST['new_customer_phone'] ?? '');
    $newEmail = trim($_POST['new_customer_email'] ?? '');
    $fulfillmentMethod = $_POST['fulfillment_method'] ?? 'Curbside';

    $deliveryAddressLine1 = trim($_POST['delivery_address_1'] ?? '');
    $deliveryAddressLine2 = trim($_POST['delivery_address_2'] ?? '');
    $deliveryCity = trim($_POST['delivery_city'] ?? '');
    $deliveryState = trim($_POST['delivery_state'] ?? '');
    $deliveryPostalCode = trim($_POST['delivery_postal_code'] ?? '');

    $loadedAddressID = (int) ($_POST['loaded_address_id'] ?? 0);
    $updateAddressOnFile = ($_POST['update_address_on_file'] ?? '') === '1';
    $saveNewAddress = isset($_POST['save_new_address']);
    $newAddressLabel = trim($_POST['new_address_label'] ?? '');

    if (!formSecurityTokenIsValid($submittedSecurityToken)) {
        $errorMessage = 'The form expired. Please try again.';
    } elseif ($customerMode === 'existing' && $selectedCustomerID <= 0) {
        $errorMessage = 'Select the customer placing the Express order.';
    } elseif ($customerMode === 'new' && ($newFirstName === '' || $newLastName === '')) {
        $errorMessage = 'Enter the new customer\'s first and last name.';
    } else {
        try {
            if ($customerMode === 'new') {
                $customerStatement = $databaseConnection->prepare(
                    'CALL sp_create_express_customer(?, ?, ?, ?)'
                );

                $customerStatement->execute([
                    $newFirstName,
                    $newLastName,
                    $newPhone,
                    $newEmail
                ]);

                $newCustomer = $customerStatement->fetch();
                $customerStatement->closeCursor();

                $customerID = (int) $newCustomer['CustomerID'];
            } else {
                $customerID = $selectedCustomerID;
            }

            $statement = $databaseConnection->prepare(
                'CALL sp_create_express_order(?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $statement->execute([
                (int) $_SESSION['store_id'],
                (int) $_SESSION['operator_id'],
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

            // Address-book bookkeeping happens after the order is
            // safely created. A problem here should not undo an
            // order that already succeeded, so it is handled on
            // its own rather than inside the same transaction.
            if ($fulfillmentMethod === 'Delivery') {
                try {
                    if ($loadedAddressID > 0 && $updateAddressOnFile) {
                        $addressUpdateStatement = $databaseConnection->prepare(
                            'CALL sp_update_customer_address(?, ?, ?, ?, ?, ?)'
                        );

                        $addressUpdateStatement->execute([
                            $loadedAddressID,
                            $deliveryAddressLine1,
                            $deliveryAddressLine2,
                            $deliveryCity,
                            $deliveryState,
                            $deliveryPostalCode
                        ]);

                        $addressUpdateStatement->closeCursor();
                    } elseif ($loadedAddressID <= 0 && $saveNewAddress && $newAddressLabel !== '') {
                        $addressCreateStatement = $databaseConnection->prepare(
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
                    // The order already succeeded. The address book
                    // not updating is worth knowing about later, not
                    // worth losing the order over right now.
                    error_log($addressException->getMessage());
                }
            }

            header(
                'Location: '
                . APPLICATION_URL
                . '/express/order.php?id='
                . (int) $created['ExpressOrderID']
            );
            exit;
        } catch (PDOException $exception) {
            $errorMessage = getSafeDatabaseErrorMessage(
                $exception,
                'The Express order could not be created.'
            );
        }
    }
}

$pageTitle = 'New Express Order';
$currentSection = 'express';
$currentPage = 'express-new';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel form-panel express-panel">
    <div class="page-intro">
        <h1>New Express Order</h1>
        <p><?= escapeOutput($capacity['OrdersRemaining'] ?? 0) ?> of 20 Express orders remain available today.</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="message message-error"><?= escapeOutput($errorMessage) ?></div>
    <?php endif; ?>

    <form method="post" id="expressNewOrderForm">
        <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

        <input type="hidden" name="loaded_address_id" value="<?= (int) $loadedAddressID ?>">
        <input type="hidden" id="loadedAddressLine1" value="<?= escapeOutput($loadedAddressLine1) ?>">
        <input type="hidden" id="loadedAddressLine2" value="<?= escapeOutput($loadedAddressLine2) ?>">
        <input type="hidden" id="loadedAddressCity" value="<?= escapeOutput($loadedAddressCity) ?>">
        <input type="hidden" id="loadedAddressState" value="<?= escapeOutput($loadedAddressState) ?>">
        <input type="hidden" id="loadedAddressPostalCode" value="<?= escapeOutput($loadedAddressPostalCode) ?>">
        <input type="hidden" name="update_address_on_file" id="updateAddressOnFile" value="0">

        <div class="form-grid">

            <fieldset class="express-customer-mode">
                <legend>Customer *</legend>

                <label>
                    <input
                        type="radio"
                        name="customer_mode"
                        value="existing"
                        id="customer_mode_existing"
                        <?= $customerMode === 'existing' ? 'checked' : '' ?>
                    >
                    Existing Customer
                </label>

                <label>
                    <input
                        type="radio"
                        name="customer_mode"
                        value="new"
                        id="customer_mode_new"
                        <?= $customerMode === 'new' ? 'checked' : '' ?>
                    >
                    New Customer
                </label>
            </fieldset>

            <div class="form-field express-address" id="existingCustomerField">
                <label for="customer_id">Select Customer</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option
                            value="<?= (int) $customer['CustomerID'] ?>"
                            <?= $selectedCustomerID === (int) $customer['CustomerID'] ? 'selected' : '' ?>
                        >
                            <?= escapeOutput(
                                $customer['LastName']
                                . ', '
                                . $customer['FirstName']
                                . ' - '
                                . $customer['LoyaltyNumber']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" formmethod="get" name="load_customer" value="1" class="button button-secondary">
                    Load Customer
                </button>

                <p class="field-help">
                    Loading a customer shows their saved addresses below. This reloads the page.
                </p>
            </div>

            <?php if ($selectedCustomerID > 0 && $savedAddresses): ?>

                <div class="form-field express-address" id="savedAddressField">
                    <label for="saved_address_select">Saved Address</label>
                    <select id="saved_address_select" name="address_id">
                        <option value="">Select a Saved Address</option>
                        <?php foreach ($savedAddresses as $savedAddress): ?>
                            <option
                                value="<?= (int) $savedAddress['CustomerAddressID'] ?>"
                                <?= $loadedAddressID === (int) $savedAddress['CustomerAddressID'] ? 'selected' : '' ?>
                            >
                                <?= escapeOutput($savedAddress['AddressLabel']) ?>
                                -
                                <?= escapeOutput($savedAddress['AddressLine1']) ?>,
                                <?= escapeOutput($savedAddress['City']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="hidden" name="customer_id" value="<?= (int) $selectedCustomerID ?>">

                    <button type="submit" formmethod="get" name="load_address" value="1" class="button button-secondary">
                        Load Address
                    </button>

                    <p class="field-help">
                        Loading a saved address fills in the delivery fields below. This reloads the page too.
                    </p>
                </div>

            <?php endif; ?>

            <div class="express-new-customer" id="newCustomerFields">

                <div class="form-field">
                    <label for="new_customer_first_name">First Name</label>
                    <input
                        type="text"
                        id="new_customer_first_name"
                        name="new_customer_first_name"
                        maxlength="60"
                        value="<?= escapeOutput($newFirstName) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="new_customer_last_name">Last Name</label>
                    <input
                        type="text"
                        id="new_customer_last_name"
                        name="new_customer_last_name"
                        maxlength="60"
                        value="<?= escapeOutput($newLastName) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="new_customer_phone">Phone</label>
                    <input
                        type="tel"
                        id="new_customer_phone"
                        name="new_customer_phone"
                        maxlength="20"
                        value="<?= escapeOutput($newPhone) ?>"
                    >
                </div>

                <div class="form-field">
                    <label for="new_customer_email">Email</label>
                    <input
                        type="email"
                        id="new_customer_email"
                        name="new_customer_email"
                        maxlength="120"
                        value="<?= escapeOutput($newEmail) ?>"
                    >
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
                    Home Delivery (+$10.00)
                </label>

                <p class="field-help">
                    Delivery is accepted only for orders placed from 8:00 AM through 4:00 PM.
                    The database verifies the time when the order is submitted.
                </p>
            </fieldset>

            <div class="form-field">
                <label for="delivery_address_1">Delivery Address</label>
                <input type="text" id="delivery_address_1" name="delivery_address_1" maxlength="120" value="<?= escapeOutput($deliveryAddressLine1) ?>">
            </div>

            <div class="form-field">
                <label for="delivery_address_2">Address Line 2</label>
                <input type="text" id="delivery_address_2" name="delivery_address_2" maxlength="120" value="<?= escapeOutput($deliveryAddressLine2) ?>">
            </div>

            <div class="form-field">
                <label for="delivery_city">City</label>
                <input type="text" id="delivery_city" name="delivery_city" maxlength="80" value="<?= escapeOutput($deliveryCity) ?>">
            </div>

            <div class="form-field">
                <label for="delivery_state">State</label>
                <input type="text" id="delivery_state" name="delivery_state" maxlength="2" value="<?= escapeOutput($deliveryState) ?>">
            </div>

            <div class="form-field">
                <label for="delivery_postal_code">ZIP Code</label>
                <input type="text" id="delivery_postal_code" name="delivery_postal_code" maxlength="10" value="<?= escapeOutput($deliveryPostalCode) ?>">
            </div>

            <?php if ($loadedAddressID === 0): ?>

                <div class="form-field express-save-address" id="saveNewAddressField">
                    <label>
                        <input type="checkbox" name="save_new_address" id="save_new_address" value="1">
                        Save this address for reuse
                    </label>

                    <input
                        type="text"
                        name="new_address_label"
                        id="new_address_label"
                        maxlength="40"
                        placeholder="Label, such as Home or Work"
                    >
                </div>

            <?php endif; ?>

        </div>

        <div class="form-actions">
            <button class="button button-primary" type="submit" name="create_order" value="1">Create Express Order</button>
            <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/ex_home.php">Cancel</a>
        </div>
    </form>
</section>

<script>
/*
   Two things happen here, both light. First, only the customer
   section matching the selected mode is shown - purely a display
   choice, since PHP validates whichever fields are actually
   submitted no matter what the browser is showing. Second, if an
   address was loaded from the address book and the shopper then
   edits it, a confirm() asks whether to update that saved address.
   The comparison reads values already rendered by PHP into hidden
   fields on page load - nothing here stores or transports the
   address itself, that part is handled entirely through normal
   form submission and page reloads.
*/
(function () {
    var existingRadio = document.getElementById('customer_mode_existing');
    var newRadio = document.getElementById('customer_mode_new');
    var existingField = document.getElementById('existingCustomerField');
    var newFields = document.getElementById('newCustomerFields');

    function updateCustomerFields() {
        var useNew = newRadio.checked;
        existingField.hidden = useNew;
        newFields.hidden = !useNew;
    }

    existingRadio.addEventListener('change', updateCustomerFields);
    newRadio.addEventListener('change', updateCustomerFields);
    updateCustomerFields();

    var form = document.getElementById('expressNewOrderForm');
    var updateFlag = document.getElementById('updateAddressOnFile');

    form.addEventListener('submit', function (event) {
        var submitter = event.submitter;

        if (!submitter || submitter.name !== 'create_order') {
            return;
        }

        var loadedAddressID = form.querySelector('[name="loaded_address_id"]').value;

        if (!loadedAddressID || loadedAddressID === '0') {
            return;
        }

        var line1 = document.getElementById('delivery_address_1').value;
        var line2 = document.getElementById('delivery_address_2').value;
        var city = document.getElementById('delivery_city').value;
        var state = document.getElementById('delivery_state').value;
        var zip = document.getElementById('delivery_postal_code').value;

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