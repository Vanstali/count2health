<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\AbstractComparison;

/**
 * @Annotation
 */
class UnitGreaterThan extends AbstractComparison
{
    public $message = 'Unit must be greater than {{ compared_value }}.';
}
