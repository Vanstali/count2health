<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsUnit extends Constraint
{
    public $message = "Value must be a unit.";
}
