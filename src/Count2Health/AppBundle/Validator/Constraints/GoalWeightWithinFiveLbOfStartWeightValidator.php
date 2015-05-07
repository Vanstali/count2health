<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class GoalWeightWithinFiveLbOfStartWeightValidator extends ConstraintValidator
{
    public function validate($healthPlan, Constraint $constraint)
    {
        $startWeight = $healthPlan->getUser()->getSetting()->getStartWeight();
        $goalWeight = $healthPlan->getGoalWeight();
        $units = $healthPlan->getUser()->getSetting()->getWeightUnits();

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
