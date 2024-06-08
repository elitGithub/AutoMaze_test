<?php

declare(strict_types = 1);

namespace Core;

use ArrayAccess;
use Filter;
use IteratorAggregate;
use SplStack;

class Request implements IteratorAggregate, ArrayAccess
{
    protected array $queryParams = [];
    protected array $postParams  = [];
    protected array $body        = [];

    public function __construct()
    {
        $body = [];

        if ($this->isGet() || count($_GET)) {
            foreach ($_GET as $key => $value) {
                $value = Filter::filterInput(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                $body[$key] = $value;
                $this->queryParams[$key] = $value;
            }
        }

        if ($this->isPost() || count($_POST)) {
            if (empty($_POST)) {
                $rawInput = $this->getRawBody();
                if ($rawInput !== false) {
                    $cleaned = html_entity_decode($rawInput, ENT_QUOTES);
                    $decoded = json_decode($cleaned, true);
                    if (isset($decoded['body'])) {
                        $decoded = $decoded['body'];
                    }
                    if (is_array($decoded)) {
                        $sanitized = $this->sanitizePostData($decoded);
                        foreach ($sanitized as $key => $value) {
                            // array_merge breaks this thing.
                            $_REQUEST[$key] = $value;
                            $_POST[$key] = $value;
                            $body[$key] = $value;
                            $this->postParams[$key] = $value;
                        }
                    }
                }

            } else {
                $sanitizedPost = $this->sanitizePostData($_POST);
                foreach ($sanitizedPost as $key => $value) {
                    $_REQUEST[$key] = $value;
                    $_POST[$key] = $value;
                    $body[$key] = $value;
                    $this->postParams[$key] = $value;
                }
            }
        }
        $this->body = $body;
        $this->initiateFiles();
    }

    private function initiateFiles(): void
    {
        $this->body['uploaded_files'] = [];

        foreach ($_FILES as $key => $file) {
            if (is_array($file) && $file['error'] === UPLOAD_ERR_OK) {
                $this->body['uploaded_files'][$key] = [
                    'name'     => $file['name'],
                    'type'     => $file['type'],
                    'tmp_name' => $file['tmp_name'],
                    'error'    => $file['error'],
                    'size'     => $file['size'],
                ];
            }
        }
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position); // Cut off query string
        }
        return $path;
    }


    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return string|false
     */
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return bool
     */
    public function isGet(): bool
    {
        return ($this->method() === 'get');
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return mb_strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return ($this->method() === 'post');
    }

    /**
     * @param $parameterArray
     * @param $paramName
     * @param $default
     *
     * @return array|mixed|string|null
     */
    public function getRequestParameter($parameterArray, $paramName, $default = null)
    {
        if (!empty($parameterArray[$paramName])) {
            if (is_array($parameterArray[$paramName])) {
                $param = array_map('addslashes', $parameterArray[$paramName]);
            } else {
                $param = addslashes($parameterArray[$paramName]);
            }
        }
        return $param ?? $default;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[$name] = $value;
        }
        return $headers;
    }

    /**
     * @return array
     */
    public function getAllRequestParams(): array
    {
        return [
            'post' => $this->getPostParams(),
            'get'  => $this->getQueryParams(),
        ];
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @return array
     */
    public function getPostParams(): array
    {
        return $this->postParams;
    }

    /**
     * @return array
     */
    public function getRequestSuperGlobal(): array
    {
        return $_REQUEST;
    }

    /**
     * @param $postData
     *
     * @return array|mixed
     */
    private function sanitizePostData($postData)
    {
        $sanitizedData = [];
        $stack = new SplStack();
        if (isset($postData['body'])) {
            $postData = $postData['body'];
        }
        // Initialize the stack with the POST data
        $stack->push(['data' => $postData, 'ref' => &$sanitizedData]);
        while (!$stack->isEmpty()) {
            $current = $stack->pop();
            $currentData = $current['data'];
            $currentSanitizedRef = &$current['ref'];

            foreach ($currentData as $key => $value) {
                if (is_array($value)) {
                    // Initialize an empty array to hold nested data
                    $currentSanitizedRef[$key] = [];
                    // Push the sub-array onto the stack to process it later, along with a reference to the newly created sub-array
                    $stack->push(['data' => $value, 'ref' => &$currentSanitizedRef[$key]]);
                } else {
                    // Sanitize the scalar value and add it to the current level of the sanitized array
                    $currentSanitizedRef[$key] = Filter::filterVar($value, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
        }

        return $sanitizedData;
    }


    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->getBody());
    }

    public function offsetExists($offset): bool
    {
        return isset($this->body[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->body[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->body[] = $value;
        } else {
            $this->body[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->body[$offset]);
    }
}
