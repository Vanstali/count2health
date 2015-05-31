<?php

namespace Count2Health\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class DeficitIsHealthyValidator extends ConstraintValidator
{
    public function validate($healthPlan, Constraint $constraint)
    {
        $gender = $healthPlan->getUser()->getPersonalDetails()->getGender();
        $tdee = $healthPlan->getUser()->getEstimatedTDEE();

        if ('male' == $gender) {
            $minimum = 1500;
        } elseif ('female' == $gender) {
            $minimum = 1200;
        }

        $consumed = $tdee - $healthPlan->getTargetCalorieDeficit();

        if ($consumed < $minimum) {
            $maximumDeficit = round($tdee - $minimum);

            $this->context->buildViolation($constraint->message)
            ->setParameter('{{ healthy_calories }}', $minimum)
            ->setParameter('{{ maximum_deficit }}', $maximumDeficit)
            ->atPath('targetCalorieDeficit')
            ->addViolation();
        }
    }
}
