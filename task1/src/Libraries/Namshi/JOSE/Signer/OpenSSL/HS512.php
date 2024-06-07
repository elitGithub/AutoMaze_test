<?php

namespace Libraries\Namshi\JOSE\Signer\OpenSSL;

/**
 * HMAC Signer using SHA-512.
 */
class HS512 extends HMAC
{
    public function getHashingAlgorithm()
    {
        return 'sha512';
    }
}
