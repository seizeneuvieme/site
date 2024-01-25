<?php 

namespace Seat\Shared\Error;

class Notification
{
    private array $errors = [];

    public function addError(string $fieldName, string $error)
    {
        $this->errors[] = new Error($fieldName, $error);

        return $this;
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError()
    {
        return count($this->errors) > 0;
    }
}