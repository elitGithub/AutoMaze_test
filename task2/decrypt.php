<?php

$protocol = 'aes-256-cbc';
$key = md5('automaze');
$encryptedString = 'OtSrzlB7n3MjD01XlzM4MfNeam1Z-oCnO3kEkxptuS4';
$encryptedData = base64_decode($encryptedString);
// IV is not known, so we have to guess it.

// Try using the first 16 letters as the IV
$iv = substr($key, 0, 16);

$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
    return;
}
echo "That didn't work" . PHP_EOL;
// Apparently something is wrong. Try using the last 16 letters as the IV
$iv = substr($key, 16, 16);

$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
    return;
}

echo "That didn't work" . PHP_EOL;

// So apparently the IV is not here nor there. Let's try to use automaze twice.
$iv = 'automazeautomaze';

$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
    return;
}

echo "That didn't work" . PHP_EOL;

// Ok, so neither is working. let's try 16 0?
$iv = str_repeat('0', 16);

$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
    return;
}

echo "That didn't work" . PHP_EOL;

// Ok, let's try to guess using a derived key:
$iv = substr(md5($key), 0, 16);
$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
    return;
}

echo "That didn't work" . PHP_EOL;

// Hmmm... why not use the words?
$iv = substr(md5('initialization vector'), 0, 16);

$decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted) {
    echo "Decrypted data: " . $decrypted;
}

echo "That didn't work" . PHP_EOL;

// OK, so apparently nothing we try works, so we're just going to brute force it!
for ($i = 0; $i < 256; $i++) {
    throw new Exception('This operation will ACTUALLY break your pc. Do not.');
    for ($j = 0; $j < 256; $j++) {
        for ($k = 0; $k < 256; $k++) {
            for ($l = 0; $l < 256; $l++) {
                $iv = chr($i) . chr($j) . chr($k) . chr($l) . str_repeat("\0", 12); // Last 12 bytes as nulls, just an example

                $decrypted = openssl_decrypt($encryptedData, $protocol, $key, OPENSSL_RAW_DATA, $iv);
                if ($decrypted !== false && strpos($decrypted, 'knownText') !== false) {
                    echo "Decrypted data: " . $decrypted . "\n";
                    echo "IV found: " . bin2hex($iv) . "\n";
                    exit;
                }
            }
        }
    }
}