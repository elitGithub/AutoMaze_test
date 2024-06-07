<?php

namespace Libraries\Namshi\JOSE\Signer\OpenSSL;

use Libraries\Namshi\JOSE\Signer\SignerInterface;

/**
 * None Signer.
 */
class None implements SignerInterface
{
    /**
     * {@inheritdoc}
     */
    public function sign($input, $key)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function verify($key, $signature, $input)
    {
        return $signature === '';
    }
}
