<?php // registers/reg_setup.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAdministrator();

$db = connectDatabase();

$storeID = signedInStoreID();
$operatorID = signedInOperatorID();

$errorMessage = '';
$successMessage = '';

if (isset($_GET['saved'])) {
    $successMessage = 'The register was saved.';
}

// Save a register through the stored procedure, which enforces every register rule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!tokenIsValid((string) ($_POST['form_security_token'] ?? ''))) {
        $errorMessage = 'The form expired. Please try again.';
    } else {
        try {
            $saveStatement = $db->prepare(
                'CALL sp_save_register(
                    :operatorID,
                    :registerID,
                    :registerNumber,
                    :registerName,
                    :registerType,
                    :active
                )'
            );

            $saveStatement->execute([
                ':operatorID' => $operatorID,
                ':registerID' => (int) ($_POST['register_id'] ?? 0),
                ':registerNumber' => (int) ($_POST['register_number'] ?? 0),
                ':registerName' => trim((string) ($_POST['register_name'] ?? '')),
                ':registerType' => (string) ($_POST['register_type'] ?? ''),
                ':active' => (int) ($_POST['active'] ?? -1)
            ]);

            $savedRegister = $saveStatement->fetch();
            $saveStatement->closeCursor();

            header(
                'Location: ' . APPLICATION_URL
                . '/registers/reg_setup.php?saved=1&register=' . (int) $savedRegister['RegisterID']
            );
            exit;
        } catch (PDOException $exception) {
            $errorMessage = databaseMessage(
                $exception,
                'The register could not be saved.'
            );
        }
    }
}

$registerStatement = $db->prepare(
    "
    SELECT
        r.RegisterID,
        r.RegisterNumber,
        r.RegisterName,
        r.RegisterType,
        r.Active,
        sr.TransactionNumber,
        CONCAT_WS(' ', o.FirstName, o.LastName) AS OperatorName
    FROM register r
    LEFT JOIN salesreceipt sr
        ON sr.RegisterID = r.RegisterID
        AND sr.StoreID = r.StoreID
        AND sr.Status = 'Open'
    LEFT JOIN operator o
        ON o.OperatorID = sr.OperatorID
    WHERE r.StoreID = :storeID
    ORDER BY r.RegisterNumber
    "
);

$registerStatement->execute([':storeID' => $storeID]);
$registers = $registerStatement->fetchAll();

$registersByID = [];
$nextNumber = 2;

foreach ($registers as $register) {
    $registersByID[(int) $register['RegisterID']] = $register;
    $nextNumber = max($nextNumber, (int) $register['RegisterNumber'] + 1);
}

// The dropdown holds every register and Add New Register
$choice = (string) ($_GET['register'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '') {
    $choice = (int) ($_POST['register_id'] ?? 0) > 0 ? (string) (int) $_POST['register_id'] : 'new';
}

$adding = $choice === 'new';
$selected = ctype_digit($choice) ? ($registersByID[(int) $choice] ?? null) : null;

$form = [
    'id' => 0,
    'number' => $nextNumber,
    'name' => 'Register ' . $nextNumber,
    'type' => 'Regular',
    'active' => 1
];

if ($selected) {
    $form = [
        'id' => (int) $selected['RegisterID'],
        'number' => (int) $selected['RegisterNumber'],
        'name' => (string) $selected['RegisterName'],
        'type' => (string) $selected['RegisterType'],
        'active' => (int) $selected['Active']
    ];
}

// Show what was typed again when a save is refused
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '') {
    $form['number'] = (int) ($_POST['register_number'] ?? $form['number']);
    $form['name'] = trim((string) ($_POST['register_name'] ?? $form['name']));
    $form['type'] = (string) ($_POST['register_type'] ?? $form['type']);
    $form['active'] = (int) ($_POST['active'] ?? $form['active']);
}

$isRegisterOne = $selected && (int) $selected['RegisterNumber'] === 1;

$pageTitle = 'Registers';
$currentSection = 'registers';
$currentPage = 'setup';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel registers-panel">

    <div class="page-intro">

        <h1>Registers</h1>

        <p>
            Choose a register to change it, or choose Add New Register.
            Register 1 is always an active Express register.
        </p>

    </div>

    <?php if ($successMessage !== ''): ?>

        <div
            class="message message-success"
            role="status"
        >
            <?= escapeOutput($successMessage) ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div
            class="message message-error"
            role="alert"
        >
            <?= escapeOutput($errorMessage) ?>
        </div>

    <?php endif; ?>

    <form
        method="get"
        class="register-picker"
        action="<?= APPLICATION_URL ?>/registers/reg_setup.php"
    >

        <label for="registerPicker">
            Register
        </label>

        <select
            id="registerPicker"
            name="register"
            onchange="this.form.submit()"
        >

            <option value="">
                Choose a register
            </option>

            <?php foreach ($registers as $register): ?>

                <?php
                $optionSelected = $selected
                    && (int) $selected['RegisterID'] === (int) $register['RegisterID'];

                $optionText = 'Register ' . (int) $register['RegisterNumber']
                    . ': ' . $register['RegisterName']
                    . ' (' . $register['RegisterType']
                    . ((int) $register['Active'] === 1 ? '' : ', inactive') . ')'
                    . ($register['TransactionNumber'] ? ' - in use by ' . $register['OperatorName'] : '');
                ?>

                <option
                    value="<?= (int) $register['RegisterID'] ?>"
                    <?= $optionSelected ? 'selected' : '' ?>
                >
                    <?= escapeOutput($optionText) ?>
                </option>

            <?php endforeach; ?>

            <option
                value="new"
                <?= $adding ? 'selected' : '' ?>
            >
                Add New Register
            </option>

        </select>

        <noscript>
            <button
                type="submit"
                class="button button-secondary"
            >
                Open
            </button>
        </noscript>

    </form>

    <?php if ($selected || $adding): ?>

        <form
            method="post"
            class="register-form"
        >

            <h2>
                <?= $adding ? 'Add New Register' : 'Register ' . (int) $form['number'] ?>
            </h2>

            <input
                type="hidden"
                name="form_security_token"
                value="<?= escapeOutput(formToken()) ?>"
            >

            <input
                type="hidden"
                name="register_id"
                value="<?= (int) $form['id'] ?>"
            >

            <div class="register-fields">

                <div class="form-field">
                    <label for="register_number">Number</label>
                    <input
                        type="number"
                        id="register_number"
                        name="register_number"
                        min="1"
                        max="999"
                        value="<?= (int) $form['number'] ?>"
                        <?= $isRegisterOne ? 'readonly' : '' ?>
                        required
                    >
                </div>

                <div class="form-field">
                    <label for="register_name">Name</label>
                    <input
                        type="text"
                        id="register_name"
                        name="register_name"
                        maxlength="50"
                        value="<?= escapeOutput($form['name']) ?>"
                        required
                    >
                </div>

                <div class="form-field">
                    <label for="register_type">Type</label>
                    <select
                        id="register_type"
                        name="register_type"
                    >
                        <option
                            value="Express"
                            <?= $form['type'] === 'Express' ? 'selected' : '' ?>
                        >
                            Express
                        </option>
                        <option
                            value="Regular"
                            <?= $form['type'] === 'Regular' ? 'selected' : '' ?>
                        >
                            Regular
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="register_active">Status</label>
                    <select
                        id="register_active"
                        name="active"
                    >
                        <option
                            value="1"
                            <?= (int) $form['active'] === 1 ? 'selected' : '' ?>
                        >
                            Active
                        </option>
                        <option
                            value="0"
                            <?= (int) $form['active'] === 0 ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <?php if ($selected && $selected['TransactionNumber']): ?>

                <p class="field-help">
                    In use by <?= escapeOutput($selected['OperatorName']) ?>,
                    transaction <?= transactionNumberHtml($selected['TransactionNumber']) ?>.
                    A register in use cannot be changed.
                </p>

            <?php endif; ?>

            <div class="form-actions">

                <button
                    type="submit"
                    class="button button-primary"
                >
                    <?= $adding ? 'Add Register' : 'Save Register' ?>
                </button>

            </div>

        </form>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>