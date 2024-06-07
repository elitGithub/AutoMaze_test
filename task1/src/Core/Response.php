<?php

namespace Core;

use Exception;

class Response
{
    public const SUCCESS_MESSAGE = 'Success';
    protected bool    $success      = true;
    protected string  $message      = '';
    protected array   $data         = [];
    protected bool    $silent       = true;
    protected int     $responseCode = 200;
    protected ?string $title        = null;


    final public function __construct($success = true, $message = self::SUCCESS_MESSAGE, $data = [], $title = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->title = $title;
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    final public function __toString()
    {
        $data['success'] = $this->success;
        $data['message'] = $this->message;
        $data['data'] = $this->data;
        if (!is_null($this->title)) {
            $data['title'] = $this->title;
        }
        $res = json_encode(self::convertEncoding($data));
        if (!$this->silent && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg());
        }
        return $res;
    }

    public static function convertEncoding($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::convertEncoding($value);
            }
        } elseif (is_string($input)) {
            return mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        }
        return $input;
    }

    public function setSilent(bool $value): Response
    {
        $this->silent = $value;
        return $this;
    }

    public function sendResponse(): void
    {
        http_response_code($this->responseCode);
        header('Content-Type: application/json');
        die((string)$this);
    }

    public function setSuccess(bool $value): Response
    {
        $this->success = $value;
        return $this;
    }

    public function setMessage(string $value): Response
    {
        $this->message = $value;
        return $this;
    }

    public function setData(array $value): Response
    {
        $this->data = $value;
        return $this;
    }

    public function setCode(int $value): Response
    {
        $this->responseCode = $value;
        return $this;
    }

    public function setTitle(string $value): Response
    {
        $this->title = $value;
        return $this;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getCode(): int
    {
        return $this->responseCode;
    }

    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function redirect(string $location)
    {
        header("Location:$location");
    }
}
