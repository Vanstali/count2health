<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class GoalWeightWithinFiveLbOfStartWeightValidator extends ConstraintValidator
{
    public function validate($healthPlan, Constraint $constraint)
    {
        $startWeight = $healthPlan->getUser()->getPersonalDetails()->getStartWeight();
        $goalWeight = $healthPlan->getGoalWeight();
        $units = $healthPlan->getUser()->getPersonalDetails()->getWeightUnits();

        if (abs($startWeight->toUnit($units) - $goalWeight->toUnit($units)) > 5) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ start_weight }}',
                        $startWeight->toUnit($units) . ' ' . $units)
                ->setParameter('{{ units }}', $units)
                ->atPath('goalWeight')
                ->addViolation();
        }
    }
}
