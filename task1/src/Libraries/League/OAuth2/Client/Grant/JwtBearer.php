<?php

namespace Libraries\League\OAuth2\Client\Grant;

class JwtBearer extends \Libraries\League\OAuth2\Grant\AbstractGrant
{
    protected function getName()
    {
        return 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    }

    protected function getRequiredRequestParameters()
    {
        return [
            'requested_token_use',
            'assertion',
        ];
    }
}
