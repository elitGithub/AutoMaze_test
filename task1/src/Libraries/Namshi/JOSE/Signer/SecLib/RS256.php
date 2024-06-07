<?php

namespace Libraries\Namshi\JOSE\Signer\SecLib;

class RS256 extends RSA
{
    public function __construct()
    {
        parent::__construct();
        $this->encryptionAlgorithm->setHash('sha256');
        $this->encryptionAlgorithm->setMGFHash('sha256');
    }
}
