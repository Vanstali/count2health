<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

class UnitGreaterThanValidator extends AbstractComparisonValidator
{
    protected function compareValues($value1, $value2)
    {
        if (null === $value1) {
            return;
        }

        return $value1->toNativeUnit() > $value2;
    }
}
