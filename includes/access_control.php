<?php // includes/access_control.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Keep session files for the whole sign-in time
ini_set('session.gc_maxlifetime', (string) (SIGN_IN_HOURS * 3600));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// End the sign-in when the time limit has passed
if (
    isset($_SESSION['operator_id'])
    && time() - (int) ($_SESSION['signed_in_at'] ?? 0) > SIGN_IN_HOURS * 3600
) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: ' . APPLICATION_URL . '/index.php?expired=1');
    exit;
}

// Split a transaction number in two so tables can show it on two lines
function transactionNumberParts($number)
{
    if (preg_match('/^([A-Z]\d{3}-\d{8})(\d+-\d+)$/', (string) $number, $match)) {
        return [$match[1], $match[2]];
    }

    return [(string) $number, ''];
}


// Show a transaction number in two parts that break only between the parts
function transactionNumberHtml($number, $stacked = false)
{
    $parts = transactionNumberParts($number);

    $class = 'transaction-number';

    if ($stacked) {
        $class .= ' transaction-number-stacked';
    }

    $html =
        '<span class="' . $class . '" title="' . escapeOutput($number) . '">'
        . '<span class="transaction-number-line">'
        . escapeOutput($parts[0])
        . '</span>';

    if ($parts[1] !== '') {
        // Stacked lines are already apart, so only side-by-side parts need a break point
        if (!$stacked) {
            $html .= '<wbr>';
        }

        $html .=
            '<span class="transaction-number-line">'
            . escapeOutput($parts[1])
            . '</span>';
    }

    return $html . '</span>';
}


// Escape output
function escapeOutput($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// A stock quantity with its unit: whole numbers for each items, three decimals for pounds
function formatStock($quantity, $unitType)
{
    return $unitType === 'Each'
        ? number_format((float) $quantity, 0) . ' each'
        : number_format((float) $quantity, 3) . ' lb';
}

// Check login
function isLoggedIn()
{
    return isset($_SESSION['operator_id']);
}

// The signed-in operator's store, id, and role, read from the session in one place
function signedInStoreID()
{
    return (int) ($_SESSION['store_id'] ?? 0);
}

function signedInOperatorID()
{
    return (int) ($_SESSION['operator_id'] ?? 0);
}

function signedInRole()
{
    return (string) ($_SESSION['role'] ?? '');
}

function signedInStoreName()
{
    return (string) ($_SESSION['store_name'] ?? '');
}

// Check assigned access
function hasAccess()
{
    return isLoggedIn()
        && in_array(
            $_SESSION['role'] ?? '',
            ['Administrator', 'Manager', 'Operator', 'Personal Shopper'],
            true
        );
}

// Check administrator
function isManager()
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'Manager';
}

function isOperator()
{
    return isLoggedIn() && signedInRole() === 'Operator';
}

function isPersonalShopper()
{
    return isLoggedIn() && signedInRole() === 'Personal Shopper';
}

// A supervisor who chose Assist for this one transaction may work on it
function isAssisting($receiptID)
{
    return canSupervise()
        && (int) ($_SESSION['supervisor_assist_receipt'] ?? 0) === (int) $receiptID;
}

function canSupervise()
{
    return isAdministrator() || isManager();
}

function isAdministrator()
{
    return isLoggedIn()
        && ($_SESSION['role'] ?? '') === 'Administrator';
}

// Check regular point-of-sale access
function canUseRegister()
{
    return isLoggedIn()
        && in_array(
            $_SESSION['role'] ?? '',
            ['Administrator', 'Manager', 'Operator'],
            true
        );
}

// Check FnH Express access
function canUseExpress()
{
    return isLoggedIn()
        && in_array(
            $_SESSION['role'] ?? '',
            ['Administrator', 'Manager', 'Personal Shopper'],
            true
        );
}

// Display a full access-denied page while preserving the site navigation
function showAccessDeniedPage($message)
{
    http_response_code(403);

    $pageTitle = 'Access Denied';
    $currentSection = 'access';
    $currentPage = 'denied';
    $accessDeniedMessage = $message;

    require __DIR__ . '/header.php';
    require __DIR__ . '/access_denied.php';
    require __DIR__ . '/footer.php';
    exit;
}

// Require regular point-of-sale access
function requireRegister()
{
    requireLogin();

    if (!canUseRegister()) {
        showAccessDeniedPage(
            'Point of Sale access is required. Use the navigation menu to open the areas available to your account.'
        );
    }
}

// Require FnH Express access
function requireExpress()
{
    requireLogin();

    if (!canUseExpress()) {
        showAccessDeniedPage(
            'FnH Express access is required. Use the navigation menu to open the areas available to your account.'
        );
    }
}

// Require login and refresh the current operator before checking privileges
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . APPLICATION_URL . '/index.php');
        exit;
    }

    if (!refreshSession()) {
        header('Location: ' . APPLICATION_URL . '/index.php');
        exit;
    }
}

// Require assigned access
function requireAccess()
{
    requireLogin();

    if (!hasAccess()) {
        showAccessDeniedPage(
            'Your account does not currently have access to this area. '
            . 'Use the navigation menu to return Home or contact an Administrator for access.'
        );
    }
}

// Require administrator
function requireAdministrator()
{
    requireAccess();

    if (!isAdministrator()) {
        showAccessDeniedPage(
            'Administrator access is required. '
            . 'Use the navigation menu to continue to an area available to your account.'
        );
    }
}

// Require an Administrator or a Manager
function requireSupervisor()
{
    requireAccess();

    if (!canSupervise()) {
        showAccessDeniedPage(
            'Administrator or Manager access is required. '
            . 'Use the navigation menu to continue to an area available to your account.'
        );
    }
}

// Check inventory-management access
function canManageInventory()
{
    return canSupervise();
}

// Check employee-directory access
function canViewOperators()
{
    return canSupervise();
}

// Check transaction-viewer access
function canViewTransactions()
{
    return hasAccess();
}

// Create form security token
function formToken()
{
    if (empty($_SESSION['form_security_token'])) {
        $_SESSION['form_security_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['form_security_token'];
}

// Validate form security token
function tokenIsValid($submittedToken)
{
    return isset($_SESSION['form_security_token'])
        && is_string($submittedToken)
        && hash_equals(
            $_SESSION['form_security_token'],
            $submittedToken
        );
}

// Check password requirements
function passwordIsValid($password)
{
    return preg_match(
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{8,}$/',
        $password
    ) === 1;
}

// Password requirement text
function passwordRules()
{
    return 'Minimum 8 characters with at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 symbol';
}

// Check middle initial
function initialIsValid($middleInitial)
{
    return $middleInitial === ''
        || preg_match('/^[A-Za-z]$/', $middleInitial) === 1;
}


// Check optional phone number
function phoneIsValid($phone)
{
    return $phone === ''
        || preg_match('/^[0-9]{10}$/', $phone) === 1;
}


// Get logged-in operator display name
function signedInName()
{
    $firstName =
        trim(
            (string) (
                $_SESSION['first_name']
                ?? ''
            )
        );
    $middleInitial =
        trim(
            (string) (
                $_SESSION['middle_initial']
                ?? ''
            )
        );
    $lastName =
        trim(
            (string) (
                $_SESSION['last_name']
                ?? ''
            )
        );

    $nameParts = [];

    if ($firstName !== '') {
        $nameParts[] = $firstName;
    }

    if ($middleInitial !== '') {
        $nameParts[] = strtoupper($middleInitial) . '.';
    }

    if ($lastName !== '') {
        $nameParts[] = $lastName;
    }

    if ($nameParts) {
        return implode(' ', $nameParts);
    }

    $username =
        trim(
            (string) (
                $_SESSION['username']
                ?? ''
            )
        );

    return $username !== '' ? $username : 'User';
}

// Refresh current session from the database
function refreshSession()
{
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $db = connectDatabase();

        $statement = $db->prepare(
            '
            SELECT
                OperatorID,
                StoreID,
                StoreNumber,
                StoreName,
                EmployeeNumber,
                Username,
                FirstName,
                MiddleInitial,
                LastName,
                Email,
                Phone,
                Role
            FROM vw_operatorlogin
            WHERE OperatorID = :operatorID
            LIMIT 1
            '
        );

        $statement->execute([
            ':operatorID' => $_SESSION['operator_id']
        ]);

        $operatorRecord = $statement->fetch();

        if (!$operatorRecord) {
            $_SESSION = [];
            session_destroy();
            return false;
        }

        $_SESSION['operator_id'] =
            (int) $operatorRecord['OperatorID'];

        $_SESSION['store_id'] =
            $operatorRecord['StoreID'] === null
            ? null
            : (int) $operatorRecord['StoreID'];

        $_SESSION['store_number'] =
            $operatorRecord['StoreNumber'];

        $_SESSION['store_name'] =
            $operatorRecord['StoreName'];

        $_SESSION['employee_number'] =
            $operatorRecord['EmployeeNumber'];

        $_SESSION['username'] =
            $operatorRecord['Username'];

        $_SESSION['first_name'] =
            $operatorRecord['FirstName'];

        $_SESSION['middle_initial'] =
            $operatorRecord['MiddleInitial'];

        $_SESSION['last_name'] =
            $operatorRecord['LastName'];

        $_SESSION['email'] =
            $operatorRecord['Email'];

        $_SESSION['phone'] =
            $operatorRecord['Phone'];

        $_SESSION['role'] =
            $operatorRecord['Role'];

        return true;

    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $_SESSION = [];
        session_destroy();
        return false;
    }
}