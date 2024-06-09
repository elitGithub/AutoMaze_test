<?php

declare(strict_types = 1);

use Core\Storm;
use Core\System;

$csrfToken = Storm::getStorm()->security->generateCsrfToken();
Storm::getStorm()->session->addValue('csrf_token', $csrfToken);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <!-- META -->
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="application-name" content="Automaze <?php
    echo System::getVersion() ?>">
    <meta name="copyright" content="(c) 2024-<?php
    echo date('Y') ?>">
    <!-- /META -->

    <!-- PRIMARY IMPORTS -->
    <?php
    require_once 'primary_css_js_imports.php'; ?>
    <!-- PRIMARY IMPORTS -->
    <!-- COMPONENT STYLE IMPORTS -->
    {{styles}}
    <!-- /COMPONENT STYLE IMPORTS -->

    <!-- SECONDARY SCRIPT IMPORTS -->
    <?php
    require_once 'jquery_scripts.php' ?>
    <!-- /SECONDARY SCRIPT IMPORTS -->

    <!-- COMPONENT SCRIPT IMPORTS -->
    {{scripts}}
    <!-- /COMPONENT SCRIPT IMPORTS -->
    <title>$title</title>
</head>
<body class="bg-gray-900 flex items-center text-white justify-center h-screen" hx-headers='{"X-CSRF-TOKEN": "<?php echo $csrfToken; ?>"}'>
{{content}}
<footer></footer>
</body>



