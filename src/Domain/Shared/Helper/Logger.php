<?php

namespace Domain\Shared\Helper;

interface Logger
{
    public function info(string $message, array $context);
    public function warning(string $message, array $context);
    public function error(string $message, array $context);
}