<?php

namespace Libraries\Namshi\JOSE;

use Libraries\Namshi\JOSE\Base64\Base64UrlSafeEncoder;
use Libraries\Namshi\JOSE\Base64\Encoder;

/**
 * Class representing a JSON Web Token.
 */
class JWT
{
    /**
     * @var array
     */
    protected array $payload;

    /**
     * @var array
     */
    protected array $header;

    /**
     * @var \Libraries\Namshi\JOSE\Base64\Encoder
     */
    protected Encoder $encoder;

    /**
     * Constructor.
     *
     * @param  array  $payload
     * @param  array  $header
     */
    public function __construct(array $payload, array $header)
    {
        $this->setPayload($payload);
        $this->setHeader($header);
        $this->setEncoder(new Base64UrlSafeEncoder());
    }

    /**
     * @param  \Libraries\Namshi\JOSE\Base64\Encoder  $encoder
     */
    public function setEncoder(Encoder $encoder): JWT
    {
        $this->encoder = $encoder;

        return $this;
    }

    /**
     * Generates the signininput for the current JWT.
     *
     * @return string
     */
    public function generateSigninInput(): string
    {
        $base64payload = $this->encoder->encode(json_encode($this->getPayload(), JSON_UNESCAPED_SLASHES));
        $base64header = $this->encoder->encode(json_encode($this->getHeader(), JSON_UNESCAPED_SLASHES));

        return sprintf('%s.%s', $base64header, $base64payload);
    }

    /**
     * Returns the payload of the JWT.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Sets the payload of the current JWT.
     *
     * @param  array  $payload
     */
    public function setPayload(array $payload): JWT
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Returns the header of the JWT.
     *
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * Sets the header of this JWT.
     *
     * @param  array  $header
     */
    public function setHeader(array $header): JWT
    {
        $this->header = $header;

        return $this;
    }
}
