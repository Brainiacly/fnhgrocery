<?php // includes/header.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/access_control.php';

$pageTitle = $pageTitle ?? 'Welcome';
$currentSection = $currentSection ?? '';
$currentPage = $currentPage ?? '';

if (!operatorIsLoggedIn()) {
    $siteLayoutClass = 'site-layout site-layout-public';
} elseif (!operatorHasAssignedAccess()) {
    $siteLayoutClass = 'site-layout site-layout-restricted';
} else {
    $siteLayoutClass = 'site-layout site-layout-authenticated';
}

$siteHeaderClass =
    operatorIsLoggedIn()
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
} elseif ($currentSection === 'inventory') {
    $pageStylesheet = 'inventory.css';
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
    $headerPageTitle = 'Operator List';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'create'
) {
    $headerPageTitle = 'Create Operator';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'update'
) {
    $headerPageTitle = 'Modify Operator';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'delete'
) {
    $headerPageTitle = 'Delete Operator';
}

if (
    $currentSection === 'operators'
    &&
    $currentPage === 'reactivate'
) {
    $headerPageTitle = 'Reactivate Operator';
}

if (
    $currentSection === 'sales'
    &&
    $currentPage === 'new'
) {
    $headerPageTitle = 'Ring Up New Sale';
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


// Get the logged-in username for the header
$headerUsername =
    (string)($_SESSION['username'] ?? '');

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= escapeOutput(APPLICATION_NAME) ?>
        -
        <?= escapeOutput($headerPageTitle) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= APPLICATION_URL ?>/assets/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="<?= APPLICATION_URL ?>/assets/css/layout.css"
    >

    <?php if ($pageStylesheet !== ''): ?>

        <link
            rel="stylesheet"
            href="<?= APPLICATION_URL ?>/assets/css/<?= escapeOutput($pageStylesheet) ?>"
        >

    <?php endif; ?>

</head>

<body>

<header class="<?= $siteHeaderClass ?>">

    <div
        class="header-image-group header-image-group-left"
        aria-hidden="true"
    >

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image1.png"
                alt=""
            >
        </div>

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image2.png"
                alt=""
            >
        </div>

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image3.png"
                alt=""
            >
        </div>

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image4.png"
                alt=""
            >
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


    <div
        class="header-image-group header-image-group-right"
        aria-hidden="true"
    >

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image5.png"
                alt=""
            >
        </div>

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image6.png"
                alt=""
            >
        </div>

        <div class="header-image-space">
            <img
                src="<?= APPLICATION_URL ?>/assets/images/image7.png"
                alt=""
            >
        </div>

    </div>


    <?php if (operatorIsLoggedIn()): ?>

        <div class="header-user-controls">

            <div class="header-logged-in-user">

                <span>
                    Logged in as:
                </span>

                <strong>
                    <?= escapeOutput($headerUsername) ?>
                </strong>

            </div>

            <a
                href="<?= APPLICATION_URL ?>/logout.php"
                class="header-logout-button"
            >
                Logout
            </a>

        </div>

    <?php endif; ?>

</header>


<div class="<?= $siteLayoutClass ?>">

    <?php require __DIR__ . '/nav.php'; ?>

    <main class="page-content">