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
<script>
  let conn;
  const reconnectInterval = 5000; // Reconnect every 5 seconds

  function connect() {
    conn = new WebSocket('ws://localhost:8080');

    conn.onopen = function(e) {
      console.log("Connection established!");
    };

    conn.onmessage = function(e) {
      console.log(e.data);
    };

    conn.onerror = function(e) {
      console.error("WebSocket error:", e);
      // Try to reconnect if there is an error
      reconnect();
    };

    conn.onclose = function(e) {
      console.log("Connection closed:", e);
      // Try to reconnect if the connection is closed
      reconnect();
    };
  }

  function reconnect() {
    console.log("Attempting to reconnect...");
    setTimeout(function() {
      connect();
    }, reconnectInterval);
  }

  // Initiate the first connection
  connect();
</script>

</body>



