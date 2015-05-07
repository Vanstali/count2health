<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use PhpUnitsOfMeasure\PhysicalQuantityInterface;

class IsUnitValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
if (!($value instanceof PhysicalQuantityInterface)) {
    $this->context->buildViolation($constraint->message)
        ->addViolation();
}
    }
}
