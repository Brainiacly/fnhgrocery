<?php // config/database.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

define('APPLICATION_NAME', 'FnH Groceries');
define('APPLICATION_URL', '/CSC_680');

define('DEVELOPER_NAME', 'Brian Phillips');
define('DEVELOPER_EMAIL', 'B.Phillips958@student.nu.edu');

define('APPLICATION_TIME_ZONE', 'America/Los_Angeles');

// Hours an operator stays signed in
define('SIGN_IN_HOURS', 24);

// Business rules shown on the pages (the stored procedures enforce the same values)
define('SALES_TAX_RATE', 0.0775);
define('EXPRESS_DAILY_CAPACITY', 20);
define('EXPRESS_DELIVERY_FEE', 10.00);
define('EXPRESS_DELIVERY_OPENS', '8:00 AM');
define('EXPRESS_DELIVERY_CLOSES', '4:00 PM');

date_default_timezone_set(APPLICATION_TIME_ZONE);


// Connect to the FnH database
function connectDatabase()
{
    $databaseHost =
        getenv('FNH_DB_HOST')
        ?: '127.0.0.1';
    $databaseName =
        getenv('FNH_DB_NAME')
        ?: 'fnh_groceries';
    $databaseUsername =
        getenv('FNH_DB_USER')
        ?: 'root';
    $databasePassword =
        getenv('FNH_DB_PASSWORD')
        ?: '';

    $connectionString =
        "mysql:host=$databaseHost;dbname=$databaseName;charset=utf8mb4";

    try {
        $db = new PDO(
            $connectionString,
            $databaseUsername,
            $databasePassword
        );

        $db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $db->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        $db->setAttribute(
            PDO::ATTR_EMULATE_PREPARES,
            false
        );

        // Keep database date and time rules aligned with the California store time
        $databaseTimeZoneOffset =
            date('P');

        $db->exec(
            'SET time_zone = '
            . $db->quote(
                $databaseTimeZoneOffset
            )
        );

        return $db;
    } catch (PDOException $exception) {
        error_log($exception->getMessage());

        exit(
            'The database connection could not be established.'
        );
    }
}


// Return safe stored procedure errors
function databaseMessage(
    $exception,
    $defaultMessage
) {
    if (
        isset($exception->errorInfo[0])
        &&
        $exception->errorInfo[0] === '45000'
    ) {
        $databaseMessage =
            rtrim($exception->errorInfo[2]);

        if (!preg_match('/[.!?]$/', $databaseMessage)) {
            $databaseMessage .= '.';
        }

        return $databaseMessage;
    }

    error_log($exception->getMessage());

    return $defaultMessage;
}