<?php // express/new.php

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
$selectedCustomerID = 0;
$newFirstName = '';
$newLastName = '';
$newPhone = '';
$newEmail = '';
$fulfillmentMethod = 'Curbside';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedSecurityToken = $_POST['form_security_token'] ?? '';
    $customerMode = ($_POST['customer_mode'] ?? 'existing') === 'new' ? 'new' : 'existing';
    $selectedCustomerID = (int) ($_POST['customer_id'] ?? 0);
    $newFirstName = trim($_POST['new_customer_first_name'] ?? '');
    $newLastName = trim($_POST['new_customer_last_name'] ?? '');
    $newPhone = trim($_POST['new_customer_phone'] ?? '');
    $newEmail = trim($_POST['new_customer_email'] ?? '');
    $fulfillmentMethod = $_POST['fulfillment_method'] ?? 'Curbside';

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
                trim($_POST['delivery_address_1'] ?? ''),
                trim($_POST['delivery_address_2'] ?? ''),
                trim($_POST['delivery_city'] ?? ''),
                trim($_POST['delivery_state'] ?? ''),
                trim($_POST['delivery_postal_code'] ?? '')
            ]);

            $created = $statement->fetch();
            $statement->closeCursor();

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

    <form method="post">
        <input type="hidden" name="form_security_token" value="<?= escapeOutput(getFormSecurityToken()) ?>">

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
            </div>

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
                <input type="text" id="delivery_address_1" name="delivery_address_1" maxlength="120">
            </div>

            <div class="form-field">
                <label for="delivery_address_2">Address Line 2</label>
                <input type="text" id="delivery_address_2" name="delivery_address_2" maxlength="120">
            </div>

            <div class="form-field">
                <label for="delivery_city">City</label>
                <input type="text" id="delivery_city" name="delivery_city" maxlength="80">
            </div>

            <div class="form-field">
                <label for="delivery_state">State</label>
                <input type="text" id="delivery_state" name="delivery_state" maxlength="2">
            </div>

            <div class="form-field">
                <label for="delivery_postal_code">ZIP Code</label>
                <input type="text" id="delivery_postal_code" name="delivery_postal_code" maxlength="10">
            </div>
        </div>

        <div class="form-actions">
            <button class="button button-primary" type="submit">Create Express Order</button>
            <a class="button button-secondary" href="<?= APPLICATION_URL ?>/express/index.php">Cancel</a>
        </div>
    </form>
</section>

<script>
/*
   This only shows the customer section that applies to the
   selected mode. PHP validates whichever fields are actually
   submitted regardless of what the browser shows, so nothing
   here is required for the page to work correctly.
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
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>