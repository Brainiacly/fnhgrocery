<?php // includes/access_control.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Escape output
function escapeOutput($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Check login
function operatorIsLoggedIn()
{
    return isset($_SESSION['operator_id']);
}

// Check assigned access
function operatorHasAssignedAccess()
{
    return operatorIsLoggedIn()
        && in_array(
            $_SESSION['role'] ?? '',
            ['Administrator', 'Operator'],
            true
        );
}

// Check administrator
function operatorIsAdministrator()
{
    return operatorIsLoggedIn()
        && ($_SESSION['role'] ?? '') === 'Administrator';
}

// Require login
function requireOperatorLogin()
{
    if (!operatorIsLoggedIn()) {
        header('Location: ' . APPLICATION_URL . '/index.php');
        exit;
    }
}

// Require assigned access
function requireAssignedAccess()
{
    requireOperatorLogin();

    if (!operatorHasAssignedAccess()) {
        header('Location: ' . APPLICATION_URL . '/index.php');
        exit;
    }
}

// Require administrator
function requireAdministrator()
{
    requireAssignedAccess();

    if (!operatorIsAdministrator()) {
        http_response_code(403);
        exit('Administrator access is required');
    }
}

// Create form security token
function getFormSecurityToken()
{
    if (empty($_SESSION['form_security_token'])) {
        $_SESSION['form_security_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['form_security_token'];
}

// Validate form security token
function formSecurityTokenIsValid($submittedToken)
{
    return isset($_SESSION['form_security_token'])
        && is_string($submittedToken)
        && hash_equals(
            $_SESSION['form_security_token'],
            $submittedToken
        );
}

// Check password requirements
function passwordMeetsRequirements($password)
{
    return preg_match(
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{8,}$/',
        $password
    ) === 1;
}

// Password requirement text
function passwordRequirementText()
{
    return 'Minimum 8 characters with at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 symbol';
}

// Check middle initial
function middleInitialIsValid($middleInitial)
{
    return $middleInitial === ''
        || preg_match('/^[A-Za-z]$/', $middleInitial) === 1;
}

// Get logged-in operator display name
function getLoggedInOperatorDisplayName()
{
    $firstName = trim((string)($_SESSION['first_name'] ?? ''));
    $middleInitial = trim((string)($_SESSION['middle_initial'] ?? ''));
    $lastName = trim((string)($_SESSION['last_name'] ?? ''));

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

    $username = trim((string)($_SESSION['username'] ?? ''));

    return $username !== '' ? $username : 'User';
}

// Refresh current session
function refreshCurrentOperatorSession()
{
    if (!operatorIsLoggedIn()) {
        return;
    }

    try {
        $databaseConnection = connectDatabase();

        $statement = $databaseConnection->prepare(
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
                Role,
                Active
            FROM vw_operatorlogin
            WHERE OperatorID = :operatorID
            LIMIT 1
            '
        );

        $statement->execute([
            ':operatorID' => $_SESSION['operator_id']
        ]);

        $operatorRecord = $statement->fetch();

        if (!$operatorRecord || (int)$operatorRecord['Active'] !== 1) {
            $_SESSION = [];
            session_destroy();
            return;
        }

        $_SESSION['operator_id'] =
            (int)$operatorRecord['OperatorID'];

        $_SESSION['store_id'] =
            $operatorRecord['StoreID'] === null
                ? null
                : (int)$operatorRecord['StoreID'];

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

    } catch (PDOException $exception) {
        error_log($exception->getMessage());
    }
}

refreshCurrentOperatorSession();