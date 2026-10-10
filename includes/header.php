<?php // includes/header.php

/**
 * Brian Phillips
 * CSC 680
 */

// This file is included by pages and cannot be opened on its own
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/access_control.php';

$pageTitle = $pageTitle ?? 'Welcome';
$currentSection = $currentSection ?? '';
$currentPage = $currentPage ?? '';

if (!isLoggedIn()) {
    $siteLayoutClass = 'site-layout site-layout-public';
} elseif (!hasAccess()) {
    $siteLayoutClass = 'site-layout site-layout-restricted';
} else {
    $siteLayoutClass = 'site-layout site-layout-authenticated';
}

$siteHeaderClass =
    isLoggedIn()
    ? 'site-header site-header-logged-in'
    : 'site-header site-header-public';


// Select the stylesheet needed by the current page
$pageStylesheet = '';

if ($currentPage === 'home') {
    $pageStylesheet = 'index.css';
} elseif ($currentSection === 'operators') {
    $pageStylesheet = 'operators.css';
} elseif ($currentSection === 'sales') {
    $pageStylesheet = 'sales.css';
} elseif ($currentSection === 'registers') {
    $pageStylesheet = 'registers.css';
} elseif ($currentSection === 'inventory') {
    $pageStylesheet = 'inventory.css';
} elseif ($currentSection === 'express') {
    $pageStylesheet = 'express.css';
} elseif ($currentSection === 'transactions') {
    $pageStylesheet = 'transactions.css';
} elseif ($currentSection === 'training') {
    $pageStylesheet = 'training.css';
}


// Express orders use the same tiles and item list as the register
$sharedStylesheet = '';

if ($currentSection === 'express') {
    $sharedStylesheet = 'sales.css';
}

// Set the page name displayed in the header
$headerPageTitle = $pageTitle;

if ($currentPage === 'home') {
    $headerPageTitle = 'Welcome';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'list'
) {
    $headerPageTitle = 'Employee List';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'create'
) {
    $headerPageTitle = 'Create Employee';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'update'
) {
    $headerPageTitle = 'Modify Employee';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'delete'
) {
    $headerPageTitle = 'Delete Employee';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'reactivate'
) {
    $headerPageTitle = 'Reactivate Employee';
}

if (
    $currentSection === 'sales'
    &&
    $currentPage === 'new'
) {
    $headerPageTitle = $pageTitle;
}

if (
    $currentSection === 'sales'
    &&
    $currentPage === 'checkout'
) {
    $headerPageTitle = 'Checkout';
}

if (
    $currentSection === 'sales'
    &&
    $currentPage === 'complete'
) {
    $headerPageTitle = 'Sale Complete';
}

if (
    $currentSection === 'inventory'
    &&
    $currentPage === 'list'
) {
    $headerPageTitle = 'Store Stock Levels';
}

if ($currentSection === 'inventory' && $currentPage === 'manage') {
    $headerPageTitle = 'Manage Inventory';
}

if ($currentSection === 'inventory' && $currentPage === 'adjust') {
    $headerPageTitle = 'Adjust Inventory';
}

if ($currentSection === 'inventory' && $currentPage === 'product') {
    $headerPageTitle = $pageTitle;
}

if ($currentSection === 'transactions' && $currentPage === 'list') {
    $headerPageTitle = 'Transaction Viewer';
}

if ($currentSection === 'transactions' && $currentPage === 'detail') {
    $headerPageTitle = 'Transaction Details';
}

if ($currentSection === 'training') {
    $headerPageTitle = $pageTitle;
}

if ($currentSection === 'access') {
    $headerPageTitle = 'Access Denied';
}


// Get the logged-in username for the header
$headerUsername =
    (string) ($_SESSION['username'] ?? '');

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= escapeOutput(APPLICATION_NAME) ?>
        -
        <?= escapeOutput($headerPageTitle) ?>
    </title>

    <link rel="icon" type="image/png" href="<?= APPLICATION_URL ?>/assets/images/image1.png">

    <link rel="stylesheet" href="<?= APPLICATION_URL ?>/assets/css/styles.css">

    <link rel="stylesheet" href="<?= APPLICATION_URL ?>/assets/css/layout.css">

    <?php if ($sharedStylesheet !== ''): ?>

        <link rel="stylesheet" href="<?= APPLICATION_URL ?>/assets/css/<?= escapeOutput($sharedStylesheet) ?>">

    <?php endif; ?>

    <?php if ($pageStylesheet !== ''): ?>

        <link rel="stylesheet" href="<?= APPLICATION_URL ?>/assets/css/<?= escapeOutput($pageStylesheet) ?>">

    <?php endif; ?>

    <link rel="stylesheet" href="<?= APPLICATION_URL ?>/assets/css/print.css" media="print">

</head>

<body>

    <header class="<?= $siteHeaderClass ?>">

        <div class="header-image-group header-image-group-left" aria-hidden="true">

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image1.png" alt="">
            </div>

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image2.png" alt="">
            </div>

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image3.png" alt="">
            </div>

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image4.png" alt="">
            </div>

        </div>


        <div class="header-content">

            <div class="application-title">

                <div class="application-name">
                    FnH Groceries
                </div>

                <div class="application-page-name">
                    <?= escapeOutput($headerPageTitle) ?>
                </div>

            </div>

        </div>


        <div class="header-image-group header-image-group-right" aria-hidden="true">

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image5.png" alt="">
            </div>

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image6.png" alt="">
            </div>

            <div class="header-image-space">
                <img src="<?= APPLICATION_URL ?>/assets/images/image7.png" alt="">
            </div>

        </div>


        <?php if (isLoggedIn()): ?>

            <div class="header-user-controls">

                <div class="header-logged-in-user">

                    <span>
                        Logged in as:
                    </span>

                    <strong>
                        <?= escapeOutput($headerUsername) ?>
                    </strong>

                </div>

                <a href="<?= APPLICATION_URL ?>/logout.php" class="header-logout-button">
                    Logout
                </a>

            </div>

        <?php endif; ?>

    </header>


    <div class="<?= $siteLayoutClass ?>">

        <?php require __DIR__ . '/nav.php'; ?>

        <main class="page-content">