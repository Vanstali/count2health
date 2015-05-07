<?php

namespace Count2Health\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Count2HealthUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
