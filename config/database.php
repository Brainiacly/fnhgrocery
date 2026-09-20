<?php // config/database.php

// This stores the name displayed throughout the website.
define('APPLICATION_NAME', 'FnH Groceries');

// This stores the web address of this project inside XAMPP.
define('APPLICATION_URL', '/CSC_680');

// This stores the developer name displayed in the footer.
define('DEVELOPER_NAME', 'Brian Phillips');

// This stores the email address used by the feedback link.
define('DEVELOPER_EMAIL', 'B.Phillips958@student.nu.edu');


// This opens and returns the connection to the FnH database.
function connectDatabase()
{
    $databaseHost = '127.0.0.1';
    $databaseName = 'fnh_groceries';
    $databaseUsername = 'root';
    $databasePassword = '';

    $connectionString =
        "mysql:host=$databaseHost;dbname=$databaseName;charset=utf8mb4";

    try {

        $databaseConnection = new PDO(
            $connectionString,
            $databaseUsername,
            $databasePassword
        );

        // This makes PHP report database errors as exceptions.
        $databaseConnection->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        // This returns database rows using their column names.
        $databaseConnection->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        // This makes PDO use real prepared statements when supported.
        $databaseConnection->setAttribute(
            PDO::ATTR_EMULATE_PREPARES,
            false
        );

        return $databaseConnection;

    } catch (PDOException $exception) {

        // This records the technical error without displaying it to the operator.
        error_log($exception->getMessage());

        exit(
            'The database connection could not be established.'
        );
    }
}


// This returns safe procedure messages without exposing unexpected database details.
function getSafeDatabaseErrorMessage(
    $exception,
    $defaultMessage
) {
    if (
        isset($exception->errorInfo[0])
        &&
        $exception->errorInfo[0] === '45000'
    ) {
        return $exception->errorInfo[2];
    }

    error_log($exception->getMessage());

    return $defaultMessage;
}