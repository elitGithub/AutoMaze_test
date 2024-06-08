<?php

$host = '0.0.0.0';
$port = 8080;
$internalPort = 8081;
$clients = [];

// Create main WebSocket server socket
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($sock, $host, $port);
socket_listen($sock);
socket_set_nonblock($sock);

// Create internal app communication socket
$internalSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($internalSock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($internalSock, $host, $internalPort);
socket_listen($internalSock);
socket_set_nonblock($internalSock);

echo "Server started on $host:$port and internal port $internalPort\n";

while (true) {
    $read = array_merge([$sock, $internalSock], $clients);
    $write = null;
    $except = null;

    socket_select($read, $write, $except, 0);

    // Accept new clients
    if (in_array($sock, $read)) {
        if ($newClient = @socket_accept($sock)) {
            socket_set_nonblock($newClient);
            $clients[] = $newClient;
            $headers = socket_read($newClient, 1024);
            performHandshake($headers, $newClient, $host, $port);
            echo "New client connected.\n";
        }
    }

    // Accept messages from internal app
    if (in_array($internalSock, $read)) {
        if ($appClient = @socket_accept($internalSock)) {
            $message = @socket_read($appClient, 1024);
            if ($message) {
                echo "Received message from internal app (raw): " . bin2hex($message) . "\n"; // Added detailed logging
                echo "Received message from internal app: $message\n";

                $encodedMessage = encode($message);
                foreach ($clients as $client) {
                    @socket_write($client, $encodedMessage, strlen($encodedMessage));
                }
            }
            socket_close($appClient);
        }
    }

    // Read messages from clients
    foreach ($clients as $key => $client) {
        $data = @socket_read($client, 1024, PHP_BINARY_READ);
        if ($data === false) {
            $errorcode = socket_last_error($client);
            if ($errorcode != SOCKET_EAGAIN && $errorcode != SOCKET_EWOULDBLOCK) {
                unset($clients[$key]);
                socket_close($client);
                echo "Client disconnected.\n";
            }
            continue;
        }

        if ($data) {
            $decodedData = decode($data);
            if ($decodedData) {
                echo "Received message: $decodedData\n";
                $message = "Hello, client! You said: $decodedData";
                $encodedMessage = encode($message);
                foreach ($clients as $sendClient) {
                    @socket_write($sendClient, $encodedMessage, strlen($encodedMessage));
                }
            }
        }
    }

    usleep(100000);
}

socket_close($sock);
socket_close($internalSock);

function performHandshake($headers, $clientConn, $host, $port)
{
    $lines = preg_split("/\r\n/", $headers);
    $secWebSocketKey = '';
    foreach ($lines as $line) {
        if (strpos($line, 'Sec-WebSocket-Key:') !== false) {
            $secWebSocketKey = trim(str_replace('Sec-WebSocket-Key:', '', $line));
            break;
        }
    }

    $secWebSocketAccept = base64_encode(pack('H*', sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Accept: $secWebSocketAccept\r\n\r\n";

    socket_write($clientConn, $upgrade, strlen($upgrade));
}
function encode($payload, $type = 'text', $masked = false)
{
    $frame = [];
    $payloadLength = strlen($payload);
    $frame[] = 0x81;  // 0x80 | 0x01 for final text frame

    if ($payloadLength <= 125) {
        $frame[] = $payloadLength;
    } elseif ($payloadLength <= 65535) {
        $frame[] = 126;
        $frame[] = ($payloadLength >> 8) & 0xFF;
        $frame[] = $payloadLength & 0xFF;
    } else {
        $frame[] = 127;
        $frame[] = ($payloadLength >> 56) & 0xFF;
        $frame[] = ($payloadLength >> 48) & 0xFF;
        $frame[] = ($payloadLength >> 40) & 0xFF;
        $frame[] = ($payloadLength >> 32) & 0xFF;
        $frame[] = ($payloadLength >> 24) & 0xFF;
        $frame[] = ($payloadLength >> 16) & 0xFF;
        $frame[] = ($payloadLength >> 8) & 0xFF;
        $frame[] = $payloadLength & 0xFF;
    }

    foreach (str_split($payload) as $char) {
        $frame[] = ord($char);
    }

    return implode(array_map("chr", $frame));
}

function decode($data) {
    $firstByte = ord($data[0]);
    $secondByte = ord($data[1]);
    $isMasked = ($secondByte & 0x80) >> 7;
    $payloadLength = $secondByte & 0x7F;
    $index = 2;

    if ($payloadLength === 126) {
        $payloadLength = unpack('n', substr($data, $index, 2))[1];
        $index += 2;
    } elseif ($payloadLength === 127) {
        $payloadLength = unpack('J', substr($data, $index, 8))[1];
        $index += 8;
    }

    if ($isMasked) {
        $mask = substr($data, $index, 4);
        $index += 4;
    }

    $payload = substr($data, $index, $payloadLength);
    if ($isMasked) {
        $unmaskedPayload = '';
        for ($i = 0; $i < $payloadLength; ++$i) {
            $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
        }
        $payload = $unmaskedPayload;
    }

    if (!mb_check_encoding($payload, 'UTF-8')) {
        echo "Decoding Error: Payload data is not valid UTF-8.\n";
        return false;
    }

    echo "Decoded Payload: $payload\n";
    return $payload;
}
