<?php 

namespace Seat\Shared\Error;

class Error
{
    private string $fieldName;
    private string $message;

    public function __construct(string $fieldName, string $message)
    {
        $this->fieldName = $fieldName;
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->fieldName.':'.$this->message;
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }

    public function message(): string
    {
        return $this->message;
    }
}