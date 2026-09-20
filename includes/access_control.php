<?php // includes/access_control.php

require_once __DIR__ . '/../config/database.php';


// This starts the session used to remember the logged-in user.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// This safely prepares text before displaying it in HTML.
function escapeOutput($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// This clears all information belonging to the logged-in user.
function clearOperatorLoginSession()
{
    unset(
        $_SESSION['operator_id'],
        $_SESSION['store_id'],
        $_SESSION['store_number'],
        $_SESSION['store_name'],
        $_SESSION['employee_number'],
        $_SESSION['username'],
        $_SESSION['first_name'],
        $_SESSION['middle_initial'],
        $_SESSION['last_name'],
        $_SESSION['role']
    );
}


// This checks whether a user is currently logged in.
function operatorIsLoggedIn()
{
    return isset(
        $_SESSION['operator_id']
    );
}


// This checks whether the logged-in user is an administrator.
function operatorIsAdministrator()
{
    return
        operatorIsLoggedIn()
        &&
        isset($_SESSION['role'])
        &&
        $_SESSION['role'] === 'Administrator';
}


// This checks whether the logged-in user has been granted system access.
function operatorHasAssignedAccess()
{
    if (!operatorIsLoggedIn()) {
        return false;
    }


    $currentRole =
        $_SESSION['role'] ?? '';


    return in_array(
        $currentRole,
        [
            'Administrator',
            'Operator'
        ],
        true
    );
}


// This checks whether the logged-in account is still waiting for access.
function operatorIsPending()
{
    return
        operatorIsLoggedIn()
        &&
        isset($_SESSION['role'])
        &&
        $_SESSION['role'] === 'Pending';
}


// This creates the name displayed beside Logged in as.
function getLoggedInOperatorDisplayName()
{
    $firstName =
        trim(
            (string)(
                $_SESSION['first_name']
                ??
                ''
            )
        );

    $middleInitial =
        trim(
            (string)(
                $_SESSION['middle_initial']
                ??
                ''
            )
        );

    $lastName =
        trim(
            (string)(
                $_SESSION['last_name']
                ??
                ''
            )
        );


    $displayName = '';


    if ($firstName !== '') {

        $displayName =
            $firstName;
    }


    if ($middleInitial !== '') {

        if ($displayName !== '') {
            $displayName .= ' ';
        }

        $displayName .=
            strtoupper($middleInitial)
            . '.';
    }


    if ($lastName !== '') {

        if ($displayName !== '') {
            $displayName .= ' ';
        }

        $displayName .=
            $lastName;
    }


    // The username is used if a complete name is not available.
    if ($displayName === '') {

        $displayName =
            trim(
                (string)(
                    $_SESSION['username']
                    ??
                    ''
                )
            );
    }


    if ($displayName === '') {

        $displayName =
            'User';
    }


    return $displayName;
}


// This refreshes the current user's session information from the database.
function refreshCurrentOperatorSession()
{
    if (!operatorIsLoggedIn()) {
        return;
    }


    try {

        $databaseConnection =
            connectDatabase();


        $operatorStatement =
            $databaseConnection->prepare(
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
                    Role
                FROM vw_operatorlogin
                WHERE OperatorID = :operatorID
                LIMIT 1
                '
            );


        $operatorStatement->execute([
            ':operatorID' =>
                $_SESSION['operator_id']
        ]);


        $operatorRecord =
            $operatorStatement->fetch();


        /*
           If the account or store is no longer active,
           the current login is removed.
        */
        if (!$operatorRecord) {

            clearOperatorLoginSession();

            return;
        }


        $_SESSION['operator_id'] =
            $operatorRecord['OperatorID'];

        $_SESSION['store_id'] =
            $operatorRecord['StoreID'];

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

        $_SESSION['role'] =
            $operatorRecord['Role'];

    } catch (PDOException $exception) {

        error_log(
            $exception->getMessage()
        );

        clearOperatorLoginSession();
    }
}


// This prevents logged-out visitors from accessing protected pages.
function requireOperatorLogin()
{
    if (!operatorIsLoggedIn()) {

        header(
            'Location: '
            . APPLICATION_URL
            . '/index.php'
        );

        exit;
    }
}


// This prevents Pending users from accessing normal operator pages.
function requireAssignedAccess()
{
    requireOperatorLogin();


    if (!operatorHasAssignedAccess()) {

        header(
            'Location: '
            . APPLICATION_URL
            . '/index.php'
        );

        exit;
    }
}


// This prevents Operators and Pending users from accessing administrator pages.
function requireAdministrator()
{
    requireOperatorLogin();


    if (!operatorIsAdministrator()) {

        header(
            'Location: '
            . APPLICATION_URL
            . '/index.php'
        );

        exit;
    }
}


// This creates a security token for forms that change information.
function getFormSecurityToken()
{
    if (
        empty(
            $_SESSION['form_security_token']
        )
    ) {

        $_SESSION['form_security_token'] =
            bin2hex(
                random_bytes(32)
            );
    }


    return
        $_SESSION['form_security_token'];
}


// This verifies that a submitted form belongs to the current session.
function formSecurityTokenIsValid($submittedToken)
{
    return
        isset(
            $_SESSION['form_security_token']
        )
        &&
        is_string(
            $submittedToken
        )
        &&
        hash_equals(
            $_SESSION['form_security_token'],
            $submittedToken
        );
}


// This checks the password requirements used by the website.
function passwordMeetsRequirements($password)
{
    $passwordPattern =
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9\s]).{8,}$/';


    return preg_match(
        $passwordPattern,
        $password
    ) === 1;
}


// This permits one alphabetic middle initial or a blank value.
function middleInitialIsValid($middleInitial)
{
    if ($middleInitial === '') {
        return true;
    }


    return preg_match(
        '/^[A-Za-z]$/',
        $middleInitial
    ) === 1;
}


// This keeps an existing login synchronized with the database.
refreshCurrentOperatorSession();