<?php declare(strict_types=1);

namespace App\Model;

class ApplicationResponseContent
{
    const STATUS_OK = 200;
    const SUCCESS = 'ok';
    const FAILURE = 'nok';

    private int $statusCode;
    private array $box;
    private array $errors;
    private bool $success;

    public function __construct
    (
        int $status = self::STATUS_OK, 
        array $box = [], 
        array $errors = [],
    )
    {
        $this->statusCode = $status;
        $this->box = $box;
        $this->errors = $errors;
        $this->success = $this->statusCode === self::STATUS_OK;
    }

    public function getJsonEncoded()
    {
        return json_encode([
            'status' => $this->success ? self::SUCCESS : self::FAILURE,
            'box' => $this->box,
            'errors' => $this->errors
        ]);
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function addErrorMessage(string $message)
    {
        $this->errors[] = $message;
    }

    public function setBoxData(array $box)
    {
        $this->box = $box;
    }

    public function setFailed()
    {
        $this->success = false;
    }
}