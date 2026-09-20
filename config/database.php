<?php // config/database.php

define('APPLICATION_NAME', 'FnH Groceries');
define('APPLICATION_URL', '/CSC_680');

define('DEVELOPER_NAME', 'Brian Phillips');
define('DEVELOPER_EMAIL', 'B.Phillips958@student.nu.edu');


// Connect to the FnH database
function connectDatabase()
{
    $databaseHost = getenv('FNH_DB_HOST') ?: '127.0.0.1';
    $databaseName = getenv('FNH_DB_NAME') ?: 'fnh_groceries';
    $databaseUsername = getenv('FNH_DB_USER') ?: 'root';
    $databasePassword = getenv('FNH_DB_PASSWORD') ?: '';

    $connectionString =
        "mysql:host=$databaseHost;dbname=$databaseName;charset=utf8mb4";

    try {
        $databaseConnection = new PDO(
            $connectionString,
            $databaseUsername,
            $databasePassword
        );

        $databaseConnection->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $databaseConnection->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        $databaseConnection->setAttribute(
            PDO::ATTR_EMULATE_PREPARES,
            false
        );

        return $databaseConnection;
    } catch (PDOException $exception) {
        error_log($exception->getMessage());

        exit(
            'The database connection could not be established.'
        );
    }
}


// Return safe stored procedure errors
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