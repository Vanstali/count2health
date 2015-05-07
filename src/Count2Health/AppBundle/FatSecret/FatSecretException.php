<?php

namespace Count2Health\AppBundle\FatSecret;

class FatSecretException extends \Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message, $code);
    }
}
