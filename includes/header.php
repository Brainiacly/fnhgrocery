<?php // includes/header.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/access_control.php';

$pageTitle = $pageTitle ?? APPLICATION_NAME;
$currentSection = $currentSection ?? '';
$currentPage = $currentPage ?? '';

if (!operatorIsLoggedIn()) {
    $siteLayoutClass = 'site-layout site-layout-public';
} elseif (!operatorHasAssignedAccess()) {
    $siteLayoutClass = 'site-layout site-layout-restricted';
} else {
    $siteLayoutClass = 'site-layout site-layout-authenticated';
}

$siteHeaderClass = operatorIsLoggedIn()
    ? 'site-header site-header-logged-in'
    : 'site-header site-header-public';


// Select the stylesheet needed by the current page
$pageStylesheet = '';

if ($currentPage === 'home') {
    $pageStylesheet = 'index.css';
} elseif ($currentSection === 'operators') {
    $pageStylesheet = 'operators.css';
}

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
        <?= escapeOutput($pageTitle) ?>
        |
        <?= escapeOutput(APPLICATION_NAME) ?>
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
            FnH Groceries
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

        <a
            href="<?= APPLICATION_URL ?>/logout.php"
            class="header-logout-button"
        >
            Logout
        </a>

    <?php endif; ?>

</header>

<div class="<?= $siteLayoutClass ?>">

    <?php require __DIR__ . '/nav.php'; ?>

    <main class="page-content">